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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Api\Data;

interface ApplicationLogResponseInterface
{
    /**
     * Get uncompressed log lines.
     *
     * @return string[]
     */
    public function getLines(): array;

    /**
     * Set uncompressed log lines.
     *
     * @param string[] $lines
     * @return $this
     */
    public function setLines(array $lines);

    /**
     * Get compressed log content.
     *
     * @return string|null
     */
    public function getContent(): ?string;

    /**
     * Set compressed log content.
     *
     * @param string|null $content
     * @return $this
     */
    public function setContent(?string $content);

    /**
     * Check whether the payload is compressed.
     *
     * @return bool
     */
    public function getCompressed(): bool;

    /**
     * Set whether the payload is compressed.
     *
     * @param bool $compressed
     * @return $this
     */
    public function setCompressed(bool $compressed);
}
