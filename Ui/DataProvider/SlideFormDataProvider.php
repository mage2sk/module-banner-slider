<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Ui\DataProvider;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\BannerSlider\Model\ResourceModel\Slide\CollectionFactory;

class SlideFormDataProvider extends AbstractDataProvider
{
    private DataPersistorInterface $dataPersistor;
    private StoreManagerInterface $storeManager;
    private Filesystem $filesystem;
    private ?array $loadedData = null;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        foreach ($this->collection->getItems() as $slide) {
            $data = $slide->getData();

            // Format image fields for the UI image uploader component
            foreach (['desktop_image', 'tablet_image', 'mobile_image'] as $imageField) {
                if (!empty($data[$imageField])) {
                    $imagePath = ltrim($data[$imageField], '/');

                    // Check if file exists at the stored path
                    $fileExists = $mediaDir->isFile($imagePath);
                    $fullUrl = $mediaUrl . $imagePath;

                    // If not found, check tmp location
                    if (!$fileExists) {
                        $tmpPath = str_replace('bannerslider/', 'bannerslider/tmp/', $imagePath);
                        if ($mediaDir->isFile($tmpPath)) {
                            $fullUrl = $mediaUrl . $tmpPath;
                            $fileExists = true;
                        }
                    }

                    // Get file size if exists
                    $fileSize = 0;
                    if ($fileExists) {
                        try {
                            $stat = $mediaDir->stat($imagePath);
                            $fileSize = $stat['size'] ?? 0;
                        } catch (\Exception $e) {
                            // Try tmp path
                            try {
                                $tmpPath = str_replace('bannerslider/', 'bannerslider/tmp/', $imagePath);
                                $stat = $mediaDir->stat($tmpPath);
                                $fileSize = $stat['size'] ?? 0;
                            } catch (\Exception $e2) {
                                $fileSize = 0;
                            }
                        }
                    }

                    $data[$imageField] = [
                        [
                            'name' => basename($imagePath),
                            'url' => $fullUrl,
                            'file' => $imagePath,
                            'size' => $fileSize,
                            'type' => 'image/' . pathinfo($imagePath, PATHINFO_EXTENSION),
                        ],
                    ];
                }
            }

            $this->loadedData[$slide->getId()] = $data;
        }

        // Restore from dataPersistor (failed save)
        $data = $this->dataPersistor->get('panth_banner_slide');
        if (!empty($data)) {
            $id = $data['slide_id'] ?? null;
            $this->loadedData[$id] = $data;
            $this->dataPersistor->clear('panth_banner_slide');
        }

        return $this->loadedData;
    }
}
