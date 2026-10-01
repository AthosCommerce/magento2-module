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

namespace AthosCommerce\Feed\Setup\Patch\Data;

use AthosCommerce\Feed\Helper\Constants;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Live indexing cron schedules are global: keep the schedule that is in effect and drop store rows.
 *
 * Magento cron reads a job's config_path schedule for the default store view only, so a value
 * saved for that store view (by earlier versions of the config API) is the one that runs today.
 * It is moved to default scope, unless a default-scope value exists; values saved for other store
 * views or websites were never used and are removed.
 */
class MoveLiveIndexingCronSchedulesToDefaultScope implements DataPatchInterface
{
    private const PATHS = [
        Constants::XML_PATH_LIVE_INDEXING_SYNC_CRON_EXPR,
        Constants::XML_PATH_LIVE_INDEXING_DISCOVERY_CRON_EXPR,
    ];

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');
        $defaultStoreId = (int)$this->storeManager->getDefaultStoreView()->getId();

        foreach (self::PATHS as $path) {
            $hasDefault = (bool)$connection->fetchOne(
                $connection->select()->from($table, ['config_id'])
                    ->where('path = ?', $path)
                    ->where('scope = ?', ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
            );
            $effective = $connection->fetchOne(
                $connection->select()->from($table, ['value'])
                    ->where('path = ?', $path)
                    ->where('scope = ?', 'stores')
                    ->where('scope_id = ?', $defaultStoreId)
            );
            if (!$hasDefault && $effective !== false && trim((string)$effective) !== '') {
                $connection->insert($table, [
                    'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    'scope_id' => 0,
                    'path' => $path,
                    'value' => trim((string)$effective),
                ]);
            }
            $connection->delete($table, ['path = ?' => $path, 'scope IN (?)' => ['stores', 'websites']]);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
