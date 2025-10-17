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

use AthosCommerce\Feed\Api\GetApplicationLogInterface;
use AthosCommerce\Feed\Exception\ValidationException;
use AthosCommerce\Feed\Helper\LogInfo;

class GetApplicationLog implements GetApplicationLogInterface
{
    /** @var LogInfo */
    private $helper;

    /**
     * @param LogInfo $helper
     */
    public function __construct(LogInfo $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @return string
     */
    public function getExtensionLog(bool $compressOutput = false) : string
    {
        return $this->helper->getExtensionLogFile($compressOutput);
    }

    /**
     * @return bool
     */
    public function clearExtensionLog() : bool
    {
        return $this->helper->deleteExtensionLogFile();
    }

    /**
     * @return string
     */
    public function getExceptionLog(bool $compressOutput = false) : string
    {
        return $this->helper->getExceptionLogFile($compressOutput);
    }

    /**
     * @return bool
     */
    public function clearExceptionLog() : bool
    {
        return $this->helper->deleteExceptionLogFile();
    }
}
