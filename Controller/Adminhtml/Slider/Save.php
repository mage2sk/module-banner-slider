<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\AlreadyExistsException;
use Psr\Log\LoggerInterface;
use Panth\BannerSlider\Model\SliderFactory;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slider';

    public const PERSIST_KEY = 'panth_banner_slider';

    private SliderFactory $sliderFactory;
    private SliderResource $sliderResource;
    private LoggerInterface $logger;
    private ?DataPersistorInterface $dataPersistor;

    public function __construct(
        Action\Context $context,
        SliderFactory $sliderFactory,
        SliderResource $sliderResource,
        LoggerInterface $logger,
        ?DataPersistorInterface $dataPersistor = null
    ) {
        parent::__construct($context);
        $this->sliderFactory = $sliderFactory;
        $this->sliderResource = $sliderResource;
        $this->logger = $logger;
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

        $id = !empty($data['slider_id']) ? (int)$data['slider_id'] : 0;
        $slider = $this->sliderFactory->create();

        if ($id) {
            $this->sliderResource->load($slider, $id);
            if (!$slider->getId()) {
                $this->messageManager->addErrorMessage(__('This slider no longer exists.'));
                return $redirect->setPath('*/*/');
            }
        }

        unset($data['form_key'], $data['key']);

        if (empty($data['slider_id'])) {
            unset($data['slider_id']);
        }

        if (isset($data['identifier'])) {
            $data['identifier'] = trim((string)$data['identifier']);
            if (!preg_match('#^[A-Za-z][A-Za-z0-9_/-]*$#', $data['identifier'])) {
                return $this->fail(
                    $redirect,
                    $id,
                    $data,
                    (string)__('The identifier must start with a letter and use only letters, digits, underscore or hyphen.')
                );
            }
        }

        $slider->addData($data);

        try {
            $this->sliderResource->save($slider);
            $savedId = $slider->getId();
            $this->messageManager->addSuccessMessage(__('The slider has been saved.'));
            if ($this->dataPersistor !== null) {
                $this->dataPersistor->clear(self::PERSIST_KEY);
            }

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['slider_id' => $savedId]);
            }
            return $redirect->setPath('*/*/');
        } catch (AlreadyExistsException $e) {
            return $this->fail(
                $redirect,
                $id,
                $data,
                (string)__('A slider with this identifier already exists for the selected store view.')
            );
        } catch (\Exception $e) {
            $this->logger->error('BannerSlider save failed: ' . $e->getMessage());
            return $this->fail($redirect, $id, $data, $e->getMessage());
        }
    }

    private function fail(Redirect $redirect, int $id, array $formData, string $message): Redirect
    {
        $this->messageManager->addErrorMessage($message);
        if ($this->dataPersistor !== null) {
            $this->dataPersistor->set(self::PERSIST_KEY, $formData);
        }
        if ($id) {
            return $redirect->setPath('*/*/edit', ['slider_id' => $id]);
        }
        return $redirect->setPath('*/*/new');
    }
}
