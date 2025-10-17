<?php
/**
 * Helper to fetch version data.
 *
 * This file is part of AthosCommerce/Feed.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace AthosCommerce\Feed\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;
class LogInfo extends AbstractHelper
{

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var File
     */
    protected $fileDriver;
    /**
     * @var LoggerInterface
     */
    protected  $logger;

    public const LOG = [
        'athoscommerce' => 'athoscommerce_feed.log',
        'exception' => 'exception.log',
        'deleteExtensionLogFileInfo' => 'File athoscommerce feed log will be removed from the path',
        'deleteExtensionLogFileRemove' => 'File athoscommerce feed log removed successfully',
        'deleteExtensionLogFileError' => 'File athoscommerce feed log not present at the location',
        'getExtensionLogFileInfo' => 'File athoscommerce feed log will be retrieved from the path',
        'getExtensionLogFileError' => 'File athoscommerce feed log  not present at the location:',
        'deleteExceptionLogFileInfo' => 'File exception log will be removed from the path',
        'deleteExceptionLogFileRemove' => 'File exception log removed successfully from the path',
        'deleteExceptionLogFileError' => 'File exception log not present at the location',
        'getExceptionLogFileInfo' => 'File exception log will be removed from the path',
        'getExceptionLogFileError' => 'File exception log not present at the location',
    ];

    /**
     * Constructor.
     *
     * @param DirectoryList $directoryList
     * @param File $fileDriver
     * @param LoggerInterface $logger
     */
    public function __construct( DirectoryList $directoryList, File $fileDriver, LoggerInterface $logger)
    {
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->logger = $logger;
    }

    public function deleteExtensionLogFile() : bool
    {
        $logPath = $this->directoryList->getPath(DirectoryList::LOG);
        $logFile = $logPath . '/'. self::LOG['athoscommerce'];

        if ($this->fileDriver->isExists($logFile)) {
            $this->logger->info(self::LOG['deleteExtensionLogFileInfo']. $logPath);
            unlink($logFile);
            $this->logger->info(self::LOG['deleteExtensionLogFileRemove'] . $logPath . '/'. self::LOG['athoscommerce']);
        }
        $this->logger->error(self::LOG['deleteExtensionLogFileError'] . $logFile);

        return true;
    }

    public function getExtensionLogFile(bool $compressOutput = false) : string
    {
        $result = '';

        $logPath = $this->directoryList->getPath(DirectoryList::LOG);
        $logFile = $logPath . '/'. self::LOG['athoscommerce'];

        if ($this->fileDriver->isExists($logFile)) {
            $this->logger->info(self::LOG['getExtensionLogFileInfo']. $logPath);
            $result = $this->fileDriver->fileGetContents($logFile);

            if (strlen($result) > 0 and $compressOutput){
                $result = rtrim(strtr(base64_encode(gzdeflate($result, 9)), '+/', '-_'), '=');
            }
        }
        $this->logger->error(self::LOG['getExtensionLogFileError'] . $logPath);

        return $result;
    }

    public function deleteExceptionLogFile() : bool
    {
        $logPath = $this->directoryList->getPath(DirectoryList::LOG);
        $logFile = $logPath . '/'. self::LOG['exception'];

        if ($this->fileDriver->isExists($logFile)) {
            $this->logger->info(self::LOG['deleteExceptionLogFileInfo'] . $logPath);
            unlink($logFile);
            $this->logger->info(self::LOG['deleteExceptionLogFileRemove'] . $logPath);
        }
        $this->logger->error(self::LOG['deleteExceptionLogFileError'] . $logPath);

        return true;
    }

    public function getExceptionLogFile(bool $compressOutput = false) : string
    {
        $result = '';

        $logPath = $this->directoryList->getPath(DirectoryList::LOG);
        $logFile = $logPath . '/'. self::LOG['exception'];

        if ($this->fileDriver->isExists($logFile)) {
            $this->logger->info(self::LOG['getExceptionLogFileInfo'] . $logPath);
            $result = $this->fileDriver->fileGetContents($logFile);

            if (strlen($result) > 0 and $compressOutput){
                $result = rtrim(strtr(base64_encode(gzdeflate($result, 9)), '+/', '-_'), '=');
            }
        }
        $this->logger->error(self::LOG['getExceptionLogFileError'] . $logPath);

        return $result;
    }
}
