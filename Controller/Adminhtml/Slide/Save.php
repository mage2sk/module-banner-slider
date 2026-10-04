<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slide;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use Panth\BannerSlider\Helper\Data as BannerHelper;
use Panth\BannerSlider\Model\SlideFactory;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slide';

    public const PERSIST_KEY = 'panth_banner_slide';

    private SlideFactory $slideFactory;
    private SlideResource $slideResource;
    private ImageUploader $imageUploader;
    private LoggerInterface $logger;
    private Filesystem $filesystem;
    private BannerHelper $bannerHelper;
    private ?DataPersistorInterface $dataPersistor;

    public function __construct(
        Action\Context $context,
        SlideFactory $slideFactory,
        SlideResource $slideResource,
        ImageUploader $imageUploader,
        LoggerInterface $logger,
        Filesystem $filesystem,
        BannerHelper $bannerHelper,
        ?DataPersistorInterface $dataPersistor = null
    ) {
        parent::__construct($context);
        $this->slideFactory = $slideFactory;
        $this->slideResource = $slideResource;
        $this->imageUploader = $imageUploader;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->bannerHelper = $bannerHelper;
        $this->dataPersistor = $dataPersistor;
    }

    public function execute()
    {
        $rawData = $this->getRequest()->getPostValue();
        $redirect = $this->resultRedirectFactory->create();

        if (empty($rawData) || !is_array($rawData)) {
            return $redirect->setPath('*/*/');
        }

        $data = $rawData;
        if (isset($rawData['data']) && is_array($rawData['data'])) {
            $data = $rawData['data'];
        }

        $id = !empty($data['slide_id']) ? (int)$data['slide_id'] : 0;
        $slide = $this->slideFactory->create();

        if ($id) {
            $this->slideResource->load($slide, $id);
            if (!$slide->getId()) {
                $this->messageManager->addErrorMessage(__('This slide no longer exists.'));
                return $redirect->setPath('*/*/');
            }
        }

        unset($data['form_key'], $data['key']);

        if (empty($data['slide_id'])) {
            unset($data['slide_id']);
        }

        $formData = $data;

        $linkUrl = trim((string)($data['link_url'] ?? ''));
        if ($linkUrl !== '' && !$this->bannerHelper->isAllowedLinkUrl($linkUrl)) {
            return $this->fail(
                $redirect,
                $id,
                $formData,
                (string)__('The link URL must be a relative path or use http, https, mailto or tel.')
            );
        }
        $data['link_url'] = $linkUrl !== '' ? $linkUrl : null;

        if (isset($data['link_target']) && !in_array($data['link_target'], ['_self', '_blank'], true)) {
            $data['link_target'] = '_self';
        }

        if (empty($data['date_from'])) {
            $data['date_from'] = null;
        }
        if (empty($data['date_to'])) {
            $data['date_to'] = null;
        }

        if ($data['date_from'] !== null && $data['date_to'] !== null) {
            $from = strtotime((string)$data['date_from']);
            $to = strtotime((string)$data['date_to']);
            if ($from !== false && $to !== false && $to < $from) {
                return $this->fail(
                    $redirect,
                    $id,
                    $formData,
                    (string)__('The Active To date cannot be earlier than the Active From date.')
                );
            }
        }

        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        foreach (['desktop_image', 'tablet_image', 'mobile_image'] as $imageField) {
            if (isset($data[$imageField]) && is_array($data[$imageField])) {
                if (!empty($data[$imageField][0]['name']) || !empty($data[$imageField][0]['file'])) {
                    $rawName = str_replace(
                        '\\',
                        '/',
                        (string)($data[$imageField][0]['file'] ?? $data[$imageField][0]['name'])
                    );
                    $isNewUpload = strpos($rawName, '/') === false;
                    $imageName = basename($rawName);

                    if ($imageName === '' || $imageName === '.' || $imageName === '..') {
                        $data[$imageField] = null;
                        continue;
                    }

                    $imagePath = 'bannerslider/' . $imageName;
                    if ($isNewUpload && $mediaDirectory->isFile('bannerslider/tmp/' . $imageName)) {
                        try {
                            $imagePath = (string)$this->imageUploader->moveFileFromTmp($imageName, true);
                        } catch (\Exception $e) {
                            $this->logger->error('BannerSlide Save - Image move failed: ' . $e->getMessage());
                        }
                    }

                    $data[$imageField] = $imagePath;
                } else {
                    $data[$imageField] = null;
                }
            } elseif (!isset($data[$imageField]) || $data[$imageField] === '') {
                $data[$imageField] = null;
            }
        }

        $slide->addData($data);

        try {
            $this->slideResource->save($slide);
            $savedId = $slide->getId();
            $this->messageManager->addSuccessMessage(__('The slide has been saved.'));
            if ($this->dataPersistor !== null) {
                $this->dataPersistor->clear(self::PERSIST_KEY);
            }

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['slide_id' => $savedId]);
            }
            return $redirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->logger->error('BannerSlide Save - ERROR: ' . $e->getMessage());
            return $this->fail($redirect, $id, $formData, $e->getMessage());
        }
    }

    private function fail(Redirect $redirect, int $id, array $formData, string $message): Redirect
    {
        $this->messageManager->addErrorMessage($message);
        if ($this->dataPersistor !== null) {
            $this->dataPersistor->set(self::PERSIST_KEY, $formData);
        }
        if ($id) {
            return $redirect->setPath('*/*/edit', ['slide_id' => $id]);
        }
        return $redirect->setPath('*/*/new');
    }
}
