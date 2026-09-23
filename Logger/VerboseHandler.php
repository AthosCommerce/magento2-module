<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Logger;

use AthosCommerce\Feed\Model\Config as ConfigModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Magento\Store\Model\StoreManagerInterface;
use Monolog\Logger;

class VerboseHandler extends Base
{
    /**
     * Target log file path.
     * @var string
     */
    protected $fileName = '/var/log/athoscommerce_feed_debug.log';

    /**
     * @var ConfigModel
     */
    private $configModel;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array<string, bool>
     */
    private $debugStateByStoreId = [];

    /**
     * @param ConfigModel $configModel
     * @param StoreManagerInterface $storeManager
     * @param DriverInterface $filesystem
     * @param string|null $filePath
     * @param string|null $fileName
     */
    public function __construct(
        ConfigModel           $configModel,
        StoreManagerInterface $storeManager,
        DriverInterface       $filesystem,
        ?string               $filePath = null,
        ?string               $fileName = null
    )
    {
        $this->configModel = $configModel;
        $this->storeManager = $storeManager;
        $this->loggerType = Logger::DEBUG;

        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * Handle DEBUG-level records only.
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

        if ($levelValue !== Logger::DEBUG) {
            return false;
        }

        return parent::handle($record);
    }

    /**
     * Check if the record should be handled, evaluating debug mode lazily
     *
     * @param array|\Monolog\LogRecord $record
     * @return bool
     */
    public function isHandling($record): bool
    {
        if (!$this->isDebug($this->getStoreId($record))) {
            return false;
        }

        return parent::isHandling($record);
    }

    /**
     * Check whether debug logging is enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    private function isDebug(?int $storeId): bool
    {
        $cacheKey = $storeId !== null ? (string)$storeId : 'default';
        if (!array_key_exists($cacheKey, $this->debugStateByStoreId)) {
            $this->debugStateByStoreId[$cacheKey] = (bool)$this->configModel->isDebugLogEnabled($storeId);
        }

        return $this->debugStateByStoreId[$cacheKey];
    }

    /**
     * @param array|\Monolog\LogRecord $record
     * @return int|null
     */
    private function getStoreId($record): ?int
    {
        if (is_array($record)) {
            $storeId = $this->normalizeStoreId($record['context']['store_id'] ?? null);
        } else {
            $storeId = $this->normalizeStoreId($record->context['store_id'] ?? null);
        }

        if ($storeId !== null) {
            return $storeId;
        }

        try {
            return $this->normalizeStoreId((int)$this->storeManager->getStore()->getId());
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    /**
     * @param mixed $storeId
     * @return int|null
     */
    private function normalizeStoreId($storeId): ?int
    {
        if ($storeId === null || $storeId === '' || !is_numeric($storeId)) {
            return null;
        }

        $storeId = (int)$storeId;

        return $storeId > 0 ? $storeId : null;
    }
}
