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

use Magento\Framework\Api\AbstractExtensibleObject;
use AthosCommerce\Feed\Api\Data\ModulesListInterface;

class ModulesList extends AbstractExtensibleObject implements ModulesListInterface
{
    const ENABLED = 'enabled';
    const DISABLED = 'disabled';

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
     * @return $this
     */
    public function setEnabled(array $enabled): self
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
     * @return $this
     */
    public function setDisabled(array $disabled): self
    {
        return $this->setData(self::DISABLED, $disabled);
    }
}
