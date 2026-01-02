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

namespace AthosCommerce\Feed\Model\Api;

use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Module\Manager as ModuleManager;
use AthosCommerce\Feed\Api\GetModulesListInterface;
use AthosCommerce\Feed\Api\Data\ModulesListInterface;
use AthosCommerce\Feed\Api\Data\ModulesListInterfaceFactory;

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
     * @var ModulesListInterface
     */
    private $modulesListFactory;

    /**
     * Constructor
     *
     * @param FullModuleList $fullModuleList
     * @param ModuleManager $moduleManager
     * @param ModulesListInterfaceFactory $modulesListFactory
     */
    public function __construct(
        FullModuleList              $fullModuleList,
        ModuleManager               $moduleManager,
        ModulesListInterfaceFactory $modulesListFactory
    )
    {
        $this->fullModuleList = $fullModuleList;
        $this->moduleManager = $moduleManager;
        $this->modulesListFactory = $modulesListFactory;
    }

    /**
     * Get list of enabled and disabled modules
     *
     * @return \AthosCommerce\Feed\Api\Data\ModulesListInterface
     */
    public function getModulesList(): \AthosCommerce\Feed\Api\Data\ModulesListInterface
    {
        /** @var \AthosCommerce\Feed\Api\Data\ModulesListInterface $response */
        $response = $this->modulesListFactory->create();

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

        $response->setEnabled($enabled);
        $response->setDisabled($disabled);
        $response->setTotalEnabled(count($enabled));
        $response->setTotalDisabled(count($disabled));
        $response->setTotalModules(count($enabled) + count($disabled));

        return $response;
    }
}
