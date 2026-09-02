<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
use AthosCommerce\Feed\Model\Config as ConfigModel;

class Handler extends Base
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/athoscommerce_feed.log';

    /**
     * @var ConfigModel
     */
    private $configModel;

    /**
     * @param ConfigModel $configModel
     * @param DriverInterface $filesystem
     * @param string|null $filePath
     * @param string|null $fileName
     */
    public function __construct(
        ConfigModel     $configModel,
        DriverInterface $filesystem,
        ?string         $filePath = null,
        ?string         $fileName = null
    ) {
        $this->configModel = $configModel;

        // Default to INFO level during instantiation to avoid store calls in __construct
        $this->loggerType = Logger::INFO;

        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * Check if the record should be handled, evaluating debug mode lazily
     *
     * @param array|\Monolog\LogRecord $record
     * @return bool
     */
    public function isHandling($record): bool
    {
        if (!$this->isDebug()) {
            return false;
        }

        return parent::isHandling($record);
    }

    /**
     * @return bool
     */
    private function isDebug(): bool
    {
        return (bool)$this->configModel->isDebugLogEnabled();
    }

    /**
     * @param array|\Monolog\LogRecord $record
     * @return bool
     */
    public function handle($record): bool
    {
        if (is_array($record)) {
            $levelValue = $record['level'] ?? '';
        } else {
            $levelValue = $record->level->value ?? $record->level;
        }

        if ($levelValue !== Logger::INFO) {
            return false;
        }

        return parent::handle($record);
    }
}