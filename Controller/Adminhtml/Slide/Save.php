<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slide;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Catalog\Model\ImageUploader;
use Psr\Log\LoggerInterface;
use Panth\BannerSlider\Model\SlideFactory;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slide';

    private SlideFactory $slideFactory;
    private SlideResource $slideResource;
    private ImageUploader $imageUploader;
    private LoggerInterface $logger;

    public function __construct(
        Action\Context $context,
        SlideFactory $slideFactory,
        SlideResource $slideResource,
        ImageUploader $imageUploader,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->slideFactory = $slideFactory;
        $this->slideResource = $slideResource;
        $this->imageUploader = $imageUploader;
        $this->logger = $logger;
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

        // Process image fields — extract path from uploader array + move from tmp
        foreach (['desktop_image', 'tablet_image', 'mobile_image'] as $imageField) {
            if (isset($data[$imageField]) && is_array($data[$imageField])) {
                if (!empty($data[$imageField][0]['name']) || !empty($data[$imageField][0]['file'])) {
                    $imageName = $data[$imageField][0]['file'] ?? $data[$imageField][0]['name'];

                    // Try to move from tmp to permanent location
                    try {
                        $this->imageUploader->moveFileFromTmp($imageName);
                        $this->logger->info('BannerSlide Save - Moved image from tmp: ' . $imageName);
                    } catch (\Exception $e) {
                        // File may already be in permanent location (re-save without re-upload)
                        $this->logger->info('BannerSlide Save - Image already in place or move skipped: ' . $e->getMessage());
                    }

                    // Store the path relative to media dir: bannerslider/filename.jpg
                    $data[$imageField] = 'bannerslider/' . ltrim($imageName, '/');
                } else {
                    // Image was removed
                    $data[$imageField] = null;
                }
            } elseif (!isset($data[$imageField]) || $data[$imageField] === '') {
                $data[$imageField] = null;
            }
            // If it's a string already, keep as-is (existing path)
        }

        // Clean empty dates
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
