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

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\ModulesListInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class ModulesList extends AbstractSimpleObject implements ModulesListInterface
{
    private const ENABLED = 'enabled';
    private const DISABLED = 'disabled';
    private const TOTAL_ENABLED = 'total_enabled';
    private const TOTAL_DISABLED = 'total_disabled';
    private const TOTAL_MODULES = 'total_modules';

    /**
     * Get enabled modules
     *
     * @return string[]
     */
    public function getEnabled(): array
    {
        return $this->_get(self::ENABLED) ?: [];
    }

    /**
     * Set enabled modules
     *
     * @param string[] $enabled
     * @return ModulesListInterface
     */
    public function setEnabled(array $enabled): ModulesListInterface
    {
        return $this->setData(self::ENABLED, $enabled);
    }

    /**
     * Get disabled modules
     *
     * @return string[]
     */
    public function getDisabled(): array
    {
        return $this->_get(self::DISABLED) ?: [];
    }

    /**
     * Set disabled modules
     *
     * @param string[] $disabled
     * @return ModulesListInterface
     */
    public function setDisabled(array $disabled): ModulesListInterface
    {
        return $this->setData(self::DISABLED, $disabled);
    }

    /**
     * @return int
     */
    public function getTotalEnabled(): int
    {
        return (int)$this->_get(self::TOTAL_ENABLED);
    }

    /**
     * @param int $count
     * @return ModulesListInterface
     */
    public function setTotalEnabled(int $count): ModulesListInterface
    {
        return $this->setData(self::TOTAL_ENABLED, $count);
    }

    /**
     * @return int
     */
    public function getTotalDisabled(): int
    {
        return (int)$this->_get(self::TOTAL_DISABLED);
    }

    /**
     * @param int $count
     * @return ModulesListInterface
     */
    public function setTotalDisabled(int $count): ModulesListInterface
    {
        return $this->setData(self::TOTAL_DISABLED, $count);
    }

    /**
     * @return int
     */
    public function getTotalModules(): int
    {
        return (int)$this->_get(self::TOTAL_MODULES);
    }

    /**
     * @param int $count
     * @return ModulesListInterface
     */
    public function setTotalModules(int $count): ModulesListInterface
    {
        return $this->setData(self::TOTAL_MODULES, $count);
    }
}
