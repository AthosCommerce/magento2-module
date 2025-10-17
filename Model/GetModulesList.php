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

namespace AthosCommerce\Feed\Model;

use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Module\Manager as ModuleManager;
use AthosCommerce\Feed\Api\GetModulesListInterface;

class GetModulesList implements GetModulesListInterface
{
    /**
     * @var FullModuleList
     */
    private $fullModuleList;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @param FullModuleList $fullModuleList
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        FullModuleList $fullModuleList,
        ModuleManager $moduleManager
    ) {
        $this->fullModuleList = $fullModuleList;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Get list of enabled and disabled modules
     *
     * @return array
     */
    public function getModulesList(): array
    {
        $enabled = [];
        $disabled = [];

        // Get all modules (both enabled and disabled) from FullModuleList
        $allModules = $this->fullModuleList->getAll();
        foreach ($allModules as $moduleName => $moduleInfo) {
            if ($this->moduleManager->isEnabled($moduleName)) {
                $enabled[] = $moduleName;
            } else {
                $disabled[] = $moduleName;
            }
        }

        return [
            'data' => [
                'enabled' => $enabled,
                'disabled' => $disabled,
                'total_enabled' => count($enabled),
                'total_disabled' => count($disabled),
                'total_modules' => count($enabled) + count($disabled)
            ]
        ];
    }
}
