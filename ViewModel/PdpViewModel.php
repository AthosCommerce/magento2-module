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

namespace AthosCommerce\Feed\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use AthosCommerce\Feed\Service\Config;

/**
 * Class PdpViewModel
 *
 * This is view model for Product Detail Page
 *
 * @package AthosCommerce\Feed\ViewModel
 */
class PdpViewModel implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * PdpViewModel constructor.
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }

    /**
     * @return string|null
     */
    public function getAthoscommerceSiteId(): ?string
    {
        return (string)$this->config->getSiteId();
    }
}
