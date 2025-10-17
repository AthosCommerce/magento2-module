<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Storage;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\Data\TaskInterfaceFactory;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Model\Aws\PreSignedUrl;
use AthosCommerce\Feed\Model\Feed\Storage\File\FileFactory;
use AthosCommerce\Feed\Model\Feed\Storage\File\NameGenerator;
use AthosCommerce\Feed\Model\Feed\StorageInterface;
use AthosCommerce\Feed\Model\TaskFactory;
use AthosCommerce\Feed\Model\TaskRepository;

class PreSignedUrlStorage implements StorageInterface
{
    /**
     * @var FormatterPool
     */
    private $formatterPool;
    /**
     * @var PreSignedUrl
     */
    private $preSignedUrl;
    /**
     * @var FileInterface
     */
    private $file;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $feedType;
    /**
     * @var NameGenerator
     */
    private $nameGenerator;

    /**
     * @var FeedSpecificationInterface
     */
    private $specification;
    /**
     * @var FileFactory
     */
    private $fileFactory;
    /**
     * @var AppConfigInterface
     */
    private $appConfig;

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * PreSignedUrlStorage constructor.
     * @param FormatterPool $formatterPool
     * @param PreSignedUrl $preSignedUrl
     * @param NameGenerator $nameGenerator
     * @param FileFactory $fileFactory
     * @param AppConfigInterface $appConfig
     * @param TaskInterface $task
     * @param TaskRepositoryInterface $taskRepository
     * @param string $type
     * @param string $feedType
     */
    public function __construct(
        FormatterPool $formatterPool,
        PreSignedUrl $preSignedUrl,
        NameGenerator $nameGenerator,
        FileFactory $fileFactory,
        AppConfigInterface $appConfig,
        TaskInterface $task,
        TaskRepositoryInterface $taskRepository,
        string $type = 'aws_presigned',
        string $feedType = 'product'
    ) {
        $this->formatterPool = $formatterPool;
        $this->preSignedUrl = $preSignedUrl;
        $this->type = $type;
        $this->feedType = $feedType;
        $this->nameGenerator = $nameGenerator;
        $this->fileFactory = $fileFactory;
        $this->appConfig = $appConfig;
        $this->taskFactory = $task;
        $this->taskRepository = $taskRepository;
    }

    /**
     * @param string $format
     * @return bool
     */
    public function isSupportedFormat(string $format): bool
    {
        return !is_null($this->formatterPool->get($format)) && $this->fileFactory->isSupportedFormat($format);
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @throws Exception
     */
    public function initiate(FeedSpecificationInterface $feedSpecification): void
    {
        $format = $feedSpecification->getFormat();
        if (!$format) {
            throw new Exception((string) __('format cannot be empty'));
        }

        if (!$this->isSupportedFormat($format)) {
            throw new Exception((string) __('%1 is not supported format', $format));
        }

        $this->initializeFile($feedSpecification);
        $this->specification = $feedSpecification;
    }

    /**
     * @param array $data
     * @param $id
     * @throws Exception
     */
    public function addData(array $data, $id): void
    {
        $file = $this->getFile();
        $specification = $this->getSpecification();
        $format = $specification->getFormat();
        if (!$format) {
            throw new Exception((string) __('format cannot be empty'));
        }

        if (!$this->isSupportedFormat($format)) {
            throw new Exception((string) __('%1 is not supported format', $format));
        }

        $formatter = $this->formatterPool->get($format);
        $data = $formatter->format($data, $specification);
        $file->appendData($data);
    }

    /**
     * @param int $id
     * @param bool $deleteFile
     * @throws FileSystemException
     * @throws RuntimeException
     * @throws Exception
     */
    public function commit(int $id, bool $deleteFile = true): void
    {
        $file = $this->getFile();
        $filePath = $file->getAbsolutePath();

        $urlPath = parse_url($this->specification->getPreSignedUrl(), PHP_URL_PATH);

        // For json.gz treat as JSON format for compression
        if (str_contains($urlPath, MetadataInterface::FORMAT_JSON_GZ)) {
            $gzFilePath = $filePath . '.gz';
            $this->compressFile($filePath, $gzFilePath);
            $filePath = $gzFilePath;  // Use the gzipped file for saving
        }

        $file->commit();

        // Get the file size (in bytes)
        $fileSize = filesize($filePath);

        $task = $this->taskRepository->get($id);
        $task->setFileSize($fileSize);
        $this->taskRepository->save($task);

        $data = [
            'type' => 'stream',
            'file' => $filePath,
        ];

        try {
            $this->preSignedUrl->save($this->specification, $data);
        } finally {
            if ((!$this->appConfig->isDebug() || $this->appConfig->getValue('product_delete_file'))
                && $deleteFile
            ) {
                $file->delete();
            }
        }
    }

    /**
     * Compress the file into GZ format
     *
     * @param string $sourceFile
     * @param string $targetFile
     * @return void
     * @throws RuntimeException
     */
    private function compressFile(string $sourceFile, string $targetFile): void
    {
        $source = fopen($sourceFile, 'rb');
        $destination = gzopen($targetFile, 'wb9'); // Open file for gz compression

        if ($source === false || $destination === false) {
            throw new RuntimeException(__('Unable to open file for compression.'));
        }

        // Compress the file in chunks to avoid memory overflow
        while (!feof($source)) {
            gzwrite($destination, fread($source, 1024 * 512)); // 512 KB chunk size
        }

        fclose($source);
        gzclose($destination);
    }

    /**
     * @throws Exception
     */
    public function rollback(): void
    {
        $this->getFile()->rollback();
    }

    /**
     * @throws Exception
     */
    public function getAdditionalData(): array
    {
        $additionalData = $this->getFile()->getFileInfo();
        $additionalData['name'] = $this->getFile()->getName();
        return $additionalData;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @throws Exception
     */
    private function initializeFile(FeedSpecificationInterface $feedSpecification) : void
    {
        $format = $feedSpecification->getFormat();
        $file = $this->fileFactory->create($format);
        $options = [$this->feedType, $this->type];
        $name = $this->nameGenerator->generate($options);
        $file->initialize($name, $feedSpecification);
        $this->file = $file;
    }

    /**
     * @return FileInterface
     * @throws Exception
     */
    public function getFile() : FileInterface
    {
        if (!$this->file) {
            throw new Exception('file is not initialized yet');
        }

        return $this->file;
    }

    /**
     * @return FeedSpecificationInterface
     * @throws Exception
     */
    private function getSpecification() : FeedSpecificationInterface
    {
        if (!$this->specification) {
            throw new Exception('specification is not initialized yet');
        }

        return $this->specification;
    }
}
