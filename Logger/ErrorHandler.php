<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
use AthosCommerce\Feed\Model\Config as ConfigModel;

class ErrorHandler extends Base
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/athoscommerce_feed_error.log';

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
    )
    {
        $this->configModel = $configModel;
        // Set to NOTICE (250) to allow NOTICE, WARNING, ERROR and above
        $this->loggerType = Logger::NOTICE;

        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * @param $record
     * @return bool
     */
    public function handle($record): bool
    {
        if (is_array($record)) {
            $levelValue = $record['level'] ?? '';
        } else {
            $levelValue = $record->level->value ?? $record->level;
        }

        // Accept NOTICE (250) and above: NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY
        if ($levelValue < Logger::NOTICE) {
            return false;
        }

        return parent::handle($record);
    }
}
