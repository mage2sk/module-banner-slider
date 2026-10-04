<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Controller\Adminhtml\Upload;

use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Panth\BannerSlider\Controller\Adminhtml\Upload\Image;
use Panth\BannerSlider\Test\Unit\Controller\Adminhtml\AbstractControllerTestCase;
use Panth\Core\Security\UploadExtensionPolicy;

class ImageTest extends AbstractControllerTestCase
{
    private ?array $jsonData = null;

    private function upload(array $params, array $files, ImageUploader $uploader): void
    {
        $context = $this->buildContext($params, [], $files);

        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(
            function ($data) use (&$json) {
                $this->jsonData = $data;
                return $json;
            }
        );
        $this->resultFactory = $this->createStub(ResultFactory::class);
        $this->resultFactory->method('create')->willReturnCallback(
            static fn($type) => $type === ResultFactory::TYPE_JSON ? $json : null
        );

        (new Image($context, $uploader, new UploadExtensionPolicy()))->execute();
    }

    public function testValidImageIsSavedToTmpUsingParamName(): void
    {
        $uploader = $this->createMock(ImageUploader::class);
        $uploader->expects($this->once())->method('saveFileToTmpDir')->with('desktop_image')
            ->willReturn(['name' => 'a.jpg', 'url' => 'https://x/a.jpg']);

        $this->upload(['param_name' => 'desktop_image'], ['desktop_image' => ['name' => 'a.jpg']], $uploader);

        $this->assertSame(['name' => 'a.jpg', 'url' => 'https://x/a.jpg'], $this->jsonData);
    }

    public function testMissingFileReturnsError(): void
    {
        $uploader = $this->createMock(ImageUploader::class);
        $uploader->expects($this->never())->method('saveFileToTmpDir');

        $this->upload([], [], $uploader);

        $this->assertSame('No image file was uploaded.', $this->jsonData['error']);
        $this->assertArrayHasKey('errorcode', $this->jsonData);
    }

    public function testDangerousExtensionIsRejected(): void
    {
        $uploader = $this->createMock(ImageUploader::class);
        $uploader->expects($this->never())->method('saveFileToTmpDir');

        $this->upload([], ['image' => ['name' => 'shell.php']], $uploader);

        $this->assertSame('This file type is not allowed.', $this->jsonData['error']);
    }

    public function testUploaderExceptionIsReturnedAsJsonError(): void
    {
        $uploader = $this->createStub(ImageUploader::class);
        $uploader->method('saveFileToTmpDir')->willThrowException(new \Exception('Too big', 7));

        $this->upload([], ['image' => ['name' => 'big.png']], $uploader);

        $this->assertSame(['error' => 'Too big', 'errorcode' => 7], $this->jsonData);
    }
}
