<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\SampleData;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Magento\Framework\Serialize\Serializer\Json;
use Panth\BannerSlider\Model\SliderFactory;
use Panth\BannerSlider\Model\SlideFactory;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;
use Psr\Log\LoggerInterface;

class Install extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slider';

    private SliderFactory $sliderFactory;
    private SlideFactory $slideFactory;
    private SliderResource $sliderResource;
    private SlideResource $slideResource;
    private Filesystem $filesystem;
    private ModuleDirReader $moduleDirReader;
    private Json $json;
    private LoggerInterface $logger;

    public function __construct(
        Action\Context $context,
        SliderFactory $sliderFactory,
        SlideFactory $slideFactory,
        SliderResource $sliderResource,
        SlideResource $slideResource,
        Filesystem $filesystem,
        ModuleDirReader $moduleDirReader,
        Json $json,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->sliderFactory = $sliderFactory;
        $this->slideFactory = $slideFactory;
        $this->sliderResource = $sliderResource;
        $this->slideResource = $slideResource;
        $this->filesystem = $filesystem;
        $this->moduleDirReader = $moduleDirReader;
        $this->json = $json;
        $this->logger = $logger;
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();

        try {
            $moduleDir = $this->moduleDirReader->getModuleDir('', 'Panth_BannerSlider');
            $sampleDataDir = $moduleDir . '/Setup/SampleData';
            $imagesSourceDir = $sampleDataDir . '/images';

            // Get the media directory for copying images
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $targetImageDir = 'bannerslider';

            // Ensure target directory exists
            $mediaDirectory->create($targetImageDir);

            // Copy all images from sample data to pub/media/bannerslider/
            if (is_dir($imagesSourceDir)) {
                $imageFiles = glob($imagesSourceDir . '/*');
                foreach ($imageFiles as $imageFile) {
                    if (is_file($imageFile)) {
                        $filename = basename($imageFile);
                        $destination = $mediaDirectory->getAbsolutePath($targetImageDir . '/' . $filename);
                        if (!file_exists($destination)) {
                            copy($imageFile, $destination);
                        }
                    }
                }
            }

            // Find all JSON files in the SampleData directory
            $jsonFiles = glob($sampleDataDir . '/*.json');

            if (empty($jsonFiles)) {
                $this->messageManager->addWarningMessage(
                    __('No sample data JSON files found in %1.', $sampleDataDir)
                );
                return $redirect->setPath('*/slider/');
            }

            $sliderCount = 0;
            $slideCount = 0;
            $skippedSliders = [];

            foreach ($jsonFiles as $jsonFile) {
                $jsonContent = file_get_contents($jsonFile);
                $data = $this->json->unserialize($jsonContent);

                if (!isset($data['slider'])) {
                    continue;
                }

                $identifier = $data['slider']['identifier'] ?? '';

                // Check if slider with this identifier already exists
                if ($identifier) {
                    $existingSlider = $this->sliderFactory->create();
                    $this->sliderResource->load($existingSlider, $identifier, 'identifier');
                    if ($existingSlider->getId()) {
                        $skippedSliders[] = $identifier;
                        continue;
                    }
                }

                // Create the slider
                $slider = $this->sliderFactory->create();
                $slider->addData($data['slider']);
                $this->sliderResource->save($slider);
                $sliderId = (int)$slider->getId();
                $sliderCount++;

                // Create the slides
                if (!empty($data['slides']) && is_array($data['slides'])) {
                    foreach ($data['slides'] as $slideData) {
                        $slideData['slider_id'] = $sliderId;

                        if (!empty($slideData['desktop_image'])) {
                            $slideData['desktop_image'] = $targetImageDir . '/' . $slideData['desktop_image'];
                        }
                        if (!empty($slideData['mobile_image'])) {
                            $slideData['mobile_image'] = $targetImageDir . '/' . $slideData['mobile_image'];
                        }

                        $slide = $this->slideFactory->create();
                        $slide->addData($slideData);
                        $this->slideResource->save($slide);
                        $slideCount++;
                    }
                }
            }

            if ($sliderCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('Sample data installed: %1 slider(s) and %2 banner(s) created.', $sliderCount, $slideCount)
                );
            }

            if (!empty($skippedSliders)) {
                $this->messageManager->addNoticeMessage(
                    __('Skipped %1 slider(s) that already exist: %2', count($skippedSliders), implode(', ', $skippedSliders))
                );
            }

            if ($sliderCount === 0 && empty($skippedSliders)) {
                $this->messageManager->addWarningMessage(__('No sample data JSON files found.'));
            }
        } catch (\Exception $e) {
            $this->logger->error('BannerSlider SampleData Install - ERROR: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(
                __('Error installing sample data: %1', $e->getMessage())
            );
        }

        return $redirect->setPath('*/slider/');
    }
}
