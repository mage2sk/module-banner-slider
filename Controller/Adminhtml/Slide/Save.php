<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slide;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use Panth\BannerSlider\Helper\Data as BannerHelper;
use Panth\BannerSlider\Model\SlideFactory;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slide';

    private SlideFactory $slideFactory;
    private SlideResource $slideResource;
    private ImageUploader $imageUploader;
    private LoggerInterface $logger;
    private Filesystem $filesystem;
    private BannerHelper $bannerHelper;

    public function __construct(
        Action\Context $context,
        SlideFactory $slideFactory,
        SlideResource $slideResource,
        ImageUploader $imageUploader,
        LoggerInterface $logger,
        Filesystem $filesystem,
        BannerHelper $bannerHelper
    ) {
        parent::__construct($context);
        $this->slideFactory = $slideFactory;
        $this->slideResource = $slideResource;
        $this->imageUploader = $imageUploader;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->bannerHelper = $bannerHelper;
    }

    public function execute()
    {
        $rawData = $this->getRequest()->getPostValue();
        $redirect = $this->resultRedirectFactory->create();

        $this->logger->info('BannerSlide Save - Raw POST keys: ' . implode(', ', array_keys($rawData)));

        if (!$rawData) {
            return $redirect->setPath('*/*/');
        }

        $data = $rawData;
        if (isset($rawData['data']) && is_array($rawData['data'])) {
            $data = $rawData['data'];
        }

        $this->logger->info('BannerSlide Save - slide_id=' . ($data['slide_id'] ?? 'empty') . ', slider_id=' . ($data['slider_id'] ?? 'empty') . ', title=' . ($data['title'] ?? 'empty'));

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

        $linkUrl = trim((string)($data['link_url'] ?? ''));
        if ($linkUrl !== '' && !$this->bannerHelper->isAllowedLinkUrl($linkUrl)) {
            $this->messageManager->addErrorMessage(
                __('The link URL must be a relative path or use http, https, mailto or tel.')
            );
            return $redirect->setPath('*/*/edit', ['slide_id' => $id]);
        }
        $data['link_url'] = $linkUrl !== '' ? $linkUrl : null;

        if (isset($data['link_target']) && !in_array($data['link_target'], ['_self', '_blank'], true)) {
            $data['link_target'] = '_self';
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

        if (empty($data['date_from'])) {
            $data['date_from'] = null;
        }
        if (empty($data['date_to'])) {
            $data['date_to'] = null;
        }

        $this->logger->info('BannerSlide Save - desktop_image=' . ($data['desktop_image'] ?? 'null'));

        $slide->addData($data);

        try {
            $this->slideResource->save($slide);
            $savedId = $slide->getId();
            $this->logger->info('BannerSlide Save - SUCCESS, slide_id=' . $savedId);
            $this->messageManager->addSuccessMessage(__('The slide has been saved.'));

            $back = $this->getRequest()->getParam('back');
            if ($back) {
                return $redirect->setPath('*/*/edit', ['slide_id' => $savedId]);
            }
            return $redirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->logger->error('BannerSlide Save - ERROR: ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setPath('*/*/edit', ['slide_id' => $id]);
        }
    }
}
