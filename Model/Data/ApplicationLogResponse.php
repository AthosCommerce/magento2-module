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

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\ApplicationLogResponseInterface;
use Magento\Framework\DataObject;

class ApplicationLogResponse extends DataObject implements ApplicationLogResponseInterface
{
    private const LINES = 'lines';
    private const CONTENT = 'content';
    private const COMPRESSED = 'compressed';

    /**
     * Get uncompressed log lines.
     *
     * @return string[]
     */
    public function getLines(): array
    {
        return $this->getData(self::LINES) ?? [];
    }

    /**
     * Set uncompressed log lines.
     *
     * @param string[] $lines
     * @return ApplicationLogResponseInterface
     */
    public function setLines(array $lines): ApplicationLogResponseInterface
    {
        return $this->setData(self::LINES, $lines);
    }

    /**
     * Get compressed log content.
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * Set compressed log content.
     *
     * @param string|null $content
     * @return ApplicationLogResponseInterface
     */
    public function setContent(?string $content): ApplicationLogResponseInterface
    {
        return $this->setData(self::CONTENT, $content);
    }

    /**
     * Check whether the payload is compressed.
     *
     * @return bool
     */
    public function getCompressed(): bool
    {
        return (bool)$this->getData(self::COMPRESSED);
    }

    /**
     * Set whether the payload is compressed.
     *
     * @param bool $compressed
     * @return ApplicationLogResponseInterface
     */
    public function setCompressed(bool $compressed): ApplicationLogResponseInterface
    {
        return $this->setData(self::COMPRESSED, $compressed);
    }
}
