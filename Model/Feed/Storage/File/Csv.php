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

namespace AthosCommerce\Feed\Model\Feed\Storage\File;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

class Csv extends FileAbstract
{
    /**
     * Json constructor.
     * @param Filesystem $filesystem
     * @param Random $random
     * @param string $fileExtension
     * @param string $subDirectory
     */
    public function __construct(
        Filesystem $filesystem,
        Random $random,
        string $fileExtension = 'txt',
        string $subDirectory = 'athoscommerce'
    ) {
        parent::__construct($filesystem, $random, $fileExtension, $subDirectory);
    }

    /**
     * @param string $fileName
     * @param FeedSpecificationInterface $feedSpecification
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function initialize(string $fileName, FeedSpecificationInterface $feedSpecification): void
    {
        $this->initializeFile($fileName);
    }

    /**
     * @param array $data
     * @throws FileSystemException
     * @throws \Exception
     */
    public function appendData(array $data): void
    {
        if (!$this->isInitialized()) {
            throw new \Exception('file is not initialized yet');
        }

        $this->checkFile();
        $this->openFile();
        $file = $this->getFile();

        $catalogRows = [];

        // Header
        $file->writeCsv([
            'uid',
            'sku',
            'parent_uid',
            'name',
            'price',
            'url',
            'imageUrl',
            'thumbnailImageUrl',
            'recordHash'
        ], "\t");

        // Collect unique catalog rows
        foreach ($data as $item) {
            if (empty($item['__catalog']) || !is_array($item['__catalog'])) {
                continue;
            }

            foreach ($item['__catalog'] as $row) {
                $recordHash = $row['recordHash'] ?? '';

                if (isset($catalogRows[$recordHash])) {
                    continue;
                }

                $catalogRows[$recordHash] = [
                    'uid' => $row['uid'] ?? '',
                    'sku' => $row['sku'] ?? '',
                    'parent_uid' => $row['parent_uid'] ?? '',
                    'name' => $row['name'] ?? '',
                    'price' => $row['price'] ?? '',
                    'url' => $row['url'] ?? '',
                    'imageUrl' => $row['imageUrl'] ?? '',
                    'thumbnailImageUrl' => $row['thumbnailImageUrl'] ?? '',
                    'recordHash' => $recordHash,
                ];
            }
        }

        // Write unique rows
        foreach ($catalogRows as $row) {
            $file->writeCsv([
                $row['uid'],
                $row['sku'],
                $row['parent_uid'],
                $row['name'],
                $row['price'],
                $row['url'],
                $row['imageUrl'],
                $row['thumbnailImageUrl'],
                $row['recordHash']
            ], "\t");
        }
    }
}
