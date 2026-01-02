<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Api\Data;

interface ModulesListInterface
{
    /**
     * Get enabled modules
     *
     * @return string[]
     */
    public function getEnabled(): array;

    /**
     * Set enabled modules
     *
     * @param string[] $enabled
     * @return ModulesListInterface
     */
    public function setEnabled(array $enabled): ModulesListInterface;

    /**
     * Get disabled modules
     *
     * @return string[]
     */
    public function getDisabled(): array;

    /**
     * Set disabled modules
     *
     * @param string[] $disabled
     * @return ModulesListInterface
     */
    public function setDisabled(array $disabled): ModulesListInterface;

    /**
     * @return int
     */
    public function getTotalEnabled(): int;

    /**
     * @param int $count
     * @return ModulesListInterface
     */
    public function setTotalEnabled(int $count): ModulesListInterface;

    /**
     * @return int
     */
    public function getTotalDisabled(): int;

    /**
     * @param int $count
     * @return ModulesListInterface
     */
    public function setTotalDisabled(int $count): ModulesListInterface;

    /**
     * @return int
     */
    public function getTotalModules(): int;

    /**
     * @param int $count
     * @return ModulesListInterface
     */
    public function setTotalModules(int $count): ModulesListInterface;
}
