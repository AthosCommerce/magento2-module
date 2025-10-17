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

namespace AthosCommerce\Feed\Model\Aws\Client;

use AthosCommerce\Feed\Exception\ClientException;

interface ClientInterface
{
    /**
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param array|null $content
     * @return ResponseInterface
     * @throws ClientException
     */
    public function execute(string $method, string $url, ?array $content = null, array $headers = []) : ResponseInterface;
}
