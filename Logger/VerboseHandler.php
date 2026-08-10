<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
use AthosCommerce\Feed\Model\Config as ConfigModel;

class VerboseHandler extends Base
{
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/athoscommerce_feed_debug.log';

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
        $this->loggerType = Logger::DEBUG;

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

        if ($levelValue !== Logger::DEBUG) {
            return false;
        }

        return parent::handle($record);
    }
}

