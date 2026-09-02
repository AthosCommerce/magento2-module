<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    /**
     * Target log file path.
     * @var string
     */
    protected $fileName = '/var/log/athoscommerce_feed.log';

    /**
     * @param DriverInterface $filesystem
     * @param string|null $filePath
     * @param string|null $fileName
     */
    public function __construct(
        DriverInterface $filesystem,
        ?string         $filePath = null,
        ?string         $fileName = null
    ) {
        // Default to INFO level during instantiation to avoid store calls in __construct
        $this->loggerType = Logger::INFO;

        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * Handle INFO-level records only.
     *
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
