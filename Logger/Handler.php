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
    )
    {
        $this->configModel = $configModel;

        $this->loggerType = $this->isDebug()
            ? Logger::DEBUG
            : Logger::INFO;

        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * @return bool
     */
    private function isDebug(): bool
    {
        return (bool)$this->configModel->isDebugLogEnabled();
    }
}
