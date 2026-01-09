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
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

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
     * @var AthosCommerceLogger
     */
    protected  $logger;

    public const LOG = [
        'athoscommerce' => 'athoscommerce_feed.log',
        'exception' => 'exception.log',
        'group_cron' => 'magento.cron.athoscommerce_task.log',
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
        'getCronLogFileInfo' => 'File group cron log file will be retrieved from the path',
        'getCronLogFileError' => 'File group cron log file not present at the location',
    ];

    /**
     * Constructor.
     *
     * @param DirectoryList $directoryList
     * @param File $fileDriver
     * @param AthosCommerceLogger $logger
     */
    public function __construct( DirectoryList $directoryList, File $fileDriver, AthosCommerceLogger $logger)
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

    /**
     * @param bool $compressOutput
     *
     * @return string
     * @throws FileSystemException
     */
    public function getCronLogFile(bool $compressOutput = false): string
    {
        $result = '';

        $logPath = $this->directoryList->getPath(DirectoryList::LOG);
        $logFile = $logPath . '/' . self::LOG['group_cron'];

        try {
            if (!$this->fileDriver->isExists($logFile)) {
                $this->logger->info(
                    'File is not exists.',
                    [
                        'logFile' => $logFile,
                    ]
                );

                return $result;
            }
            $this->logger->info(self::LOG['getCronLogFileInfo'] . $logPath);
            $result = $this->fileDriver->fileGetContents($logFile);

            if ($result && strlen($result) > 0 && $compressOutput) {
                $result = $this->compressString($result);
            }
        } catch (FileSystemException $e) {
            $this->logger->error(self::LOG['getCronLogFileError'] . $logPath);
        }

        return $result;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function compressString(string $content): string
    {
        $compressed = gzdeflate($content, 9);
        $encoded = base64_encode($compressed);

        return rtrim(strtr($encoded, '+/', '-_'), '=');
    }
}
