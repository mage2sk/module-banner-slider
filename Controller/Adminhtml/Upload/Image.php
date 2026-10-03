<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Upload;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Panth\Core\Security\UploadExtensionPolicy;

class Image extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slide';

    private ImageUploader $imageUploader;
    private UploadExtensionPolicy $uploadExtensionPolicy;

    public function __construct(
        Context $context,
        ImageUploader $imageUploader,
        UploadExtensionPolicy $uploadExtensionPolicy
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
        $this->uploadExtensionPolicy = $uploadExtensionPolicy;
    }

    public function execute(): ResultInterface
    {
        $imageId = (string)$this->getRequest()->getParam('param_name', 'image');

        try {
            $file = $this->getRequest()->getFiles($imageId);
            if (!is_array($file) || !isset($file['name']) || !is_string($file['name'])) {
                throw new LocalizedException(__('No image file was uploaded.'));
            }
            $this->uploadExtensionPolicy->assertSafeExtension($file['name']);

            $result = $this->imageUploader->saveFileToTmpDir($imageId);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
