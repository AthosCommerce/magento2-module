<?php

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\GetConfigInfoInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;

class GetConfigInfo implements GetConfigInfoInterface
{
    const MODULE_PREFIX = 'athoscommerce';

    /** @var ResourceConnection */
    private $resource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ResourceConnection $resource
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ResourceConnection $resource,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resource = $resource;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array[]
     */
    public function getConfigDetails(): array
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('core_config_data');

            $select = $connection->select()
                ->from($table, ['config_id', 'scope', 'scope_id', 'path', 'value'])
                ->where('path LIKE ?', self::MODULE_PREFIX . '%')
                ->order(['scope ASC', 'scope_id ASC']);

            $results = $connection->fetchAll($select);

            if (empty($results)) {
                return [
                    'data' => [
                        'success' => true,
                        'message' => "No config values found for path '{$path}'",
                        'results' => [],
                    ],
                ];
            }

            return [
                'data' => [
                    'success' => true,
                    'count' => count($results),
                    'message' => "Secret key will be shown in encrypted form.",
                    'results' => $results,
                ],
            ];
        } catch (LocalizedException $e) {
            return [
                'data' => [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'data' => [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }
}
