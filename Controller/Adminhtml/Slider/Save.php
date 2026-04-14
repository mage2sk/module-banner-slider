<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Psr\Log\LoggerInterface;
use Panth\BannerSlider\Model\SliderFactory;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slider';

    private SliderFactory $sliderFactory;
    private SliderResource $sliderResource;
    private LoggerInterface $logger;

    public function __construct(
        Action\Context $context,
        SliderFactory $sliderFactory,
        SliderResource $sliderResource,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->sliderFactory = $sliderFactory;
        $this->sliderResource = $sliderResource;
        $this->logger = $logger;
    }

    public function execute()
    {
        $rawData = $this->getRequest()->getPostValue();
        $redirect = $this->resultRedirectFactory->create();

        $this->logger->info('BannerSlider Save - Raw POST keys: ' . implode(', ', array_keys($rawData)));
        $this->logger->info('BannerSlider Save - back param: ' . ($this->getRequest()->getParam('back') ?? 'null'));

        if (!$rawData) {
            $this->logger->error('BannerSlider Save - No POST data');
            return $redirect->setPath('*/*/');
        }

        // UI Component forms may nest data under the dataScope key
        $data = $rawData;
        if (isset($rawData['data']) && is_array($rawData['data'])) {
            $this->logger->info('BannerSlider Save - Found nested data scope');
            $data = $rawData['data'];
        }

        $this->logger->info('BannerSlider Save - Data keys: ' . implode(', ', array_keys($data)));
        $this->logger->info('BannerSlider Save - slider_id=' . ($data['slider_id'] ?? 'empty') . ', title=' . ($data['title'] ?? 'empty') . ', identifier=' . ($data['identifier'] ?? 'empty'));

        $id = !empty($data['slider_id']) ? (int)$data['slider_id'] : 0;
        $slider = $this->sliderFactory->create();

        if ($id) {
            $this->sliderResource->load($slider, $id);
            if (!$slider->getId()) {
                $this->messageManager->addErrorMessage(__('This slider no longer exists.'));
                return $redirect->setPath('*/*/');
            }
        }

        // Remove form_key and other non-entity data
        unset($data['form_key'], $data['key']);

        // If slider_id is empty (new), remove it so auto-increment works
        if (empty($data['slider_id'])) {
            unset($data['slider_id']);
        }

        $slider->addData($data);

        try {
            $this->sliderResource->save($slider);
            $savedId = $slider->getId();
            $this->logger->info('BannerSlider Save - SUCCESS, slider_id=' . $savedId);
            $this->messageManager->addSuccessMessage(__('The slider has been saved.'));

            $back = $this->getRequest()->getParam('back');
            $this->logger->info('BannerSlider Save - Redirect back param: ' . ($back ?? 'null'));

            if ($back) {
                return $redirect->setPath('*/*/edit', ['slider_id' => $savedId]);
            }
            return $redirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->logger->error('BannerSlider Save - ERROR: ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setPath('*/*/edit', ['slider_id' => $id]);
        }
    }
}
