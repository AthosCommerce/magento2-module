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

use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var $mediaConfig Config */
$mediaConfig = $objectManager->get(Config::class);
/** @var $database Database */
$database = $objectManager->get(Database::class);

/** @var $mediaDirectory WriteInterface */
$mediaDirectory = $objectManager->get(Filesystem::class)
    ->getDirectoryWrite(DirectoryList::MEDIA);
$targetDirPath = $mediaConfig->getBaseMediaPath() . str_replace('/', DIRECTORY_SEPARATOR, '/m/a/');
$targetTmpDirPath = $mediaConfig->getBaseTmpMediaPath() . str_replace('/', DIRECTORY_SEPARATOR, '/m/a/');
$mediaDirectory->create($targetDirPath);
$mediaDirectory->create($targetTmpDirPath);

$images = ['magento_image.jpg', 'magento_small_image.jpg', 'magento_thumbnail.jpg', 'magento_image_additional.jpg', 'magento_image_additional_disabled.jpg'];
foreach ($images as $image) {
    $targetTmpFilePath = $mediaDirectory->getAbsolutePath() . $targetTmpDirPath . $image;

    $sourceFilePath = __DIR__ . DIRECTORY_SEPARATOR . $image;
    $mediaDirectory->getDriver()->filePutContents($targetTmpFilePath, file_get_contents($sourceFilePath));

    // Copying the image to target dir is not necessary because during product save, it will be moved there from tmp dir
    $database->saveFile($targetTmpFilePath);
}
