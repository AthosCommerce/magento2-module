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

namespace AthosCommerce\Feed\ViewModel;

use Magento\Framework\Escaper as FrameworkEscaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Escaper implements ArgumentInterface
{
    /**
     * @var FrameworkEscaper
     */
    private $escaper;

    /**
     * @param FrameworkEscaper $escaper
     */
    public function __construct(FrameworkEscaper $escaper)
    {
        $this->escaper = $escaper;
    }

    /**
     * @return FrameworkEscaper
     */
    public function getEscaper(): FrameworkEscaper
    {
        return $this->escaper;
    }
}
