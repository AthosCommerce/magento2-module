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

namespace AthosCommerce\Feed\Test\Integration\Traits;

use Magento\Framework\ObjectManagerInterface;

/**
 * @property ObjectManagerInterface $objectManager
 */
trait ObjectInstantiationTrait
{
    /**
     * @var string|null
     */
    private ?string $implementationFqcn = null;
    /**
     * @var string|null
     */
    private ?string $interfaceFqcn = null;
    /**
     * @var mixed[]|null
     */
    private ?array $constructorArgumentDefaults = null;

    private function instantiateTestObject(
        ?array $arguments = null
    ): object {
        if (!$this->implementationFqcn) {
            throw new \LogicException('Cannot instantiate test object: no implementationFqcn defined');
        }
        if (!(($this->objectManager ?? null) instanceof ObjectManagerInterface)) {
            throw new \LogicException('Cannot instantiate test object: objectManager property not defined');
        }
        if (null === $arguments) {
            $arguments = $this->constructorArgumentDefaults;
        }

        return (null === $arguments)
            ? $this->objectManager->get($this->implementationFqcn)
            : $this->objectManager->create($this->implementationFqcn, $arguments);
    }
}
