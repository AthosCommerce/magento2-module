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

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use AthosCommerce\Feed\Api\AppConfigInterface;

class AppConfig implements AppConfigInterface
{
    public const PREFIX = 'athoscommerce_feed';
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;
    /**
     * @var Http
     */
    private $http;
    /**
     * @var array
     */
    private $defaults = ['debug' => false, 'product_delete_file' => true];
    /**
     * @var string
     */
    private $prefix;

    /**
     * AppConfig constructor.
     * @param DeploymentConfig $deploymentConfig
     * @param Http $http
     * @param string $prefix
     * @param array $defaults
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        Http             $http,
        string           $prefix = self::PREFIX,
        array            $defaults = []
    )
    {
        $this->deploymentConfig = $deploymentConfig;
        $this->http = $http;
        $this->defaults = array_merge($this->defaults, $defaults);
        $this->prefix = $prefix;
    }

    /**
     * @param string $code
     * @return mixed
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function getValue(string $code)
    {
        $varPath = $this->buildVarPath($code);
        $envPath = $this->buildEnvPath($code);

        $result = $this->http->getServer($varPath);
        if ($result === null) {
            $result = $this->deploymentConfig->get($envPath);
        }
        if ($result === null) {
            $result = $this->defaults[$code] ?? null;
        }
        return $result;
    }

    /**
     * @return bool
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function isDebug(): bool
    {
        return $this->normalizeBool($this->getValue('debug'));
    }

    /**
     * @param string $code
     * @return string
     */
    private function buildVarPath(string $code): string
    {
        $path = $this->prefix . '_' . $code;
        return strtoupper($path);
    }

    /**
     * @param string $code
     * @return string
     */
    private function buildEnvPath(string $code): string
    {
        $path = $this->prefix . '_' . $code;
        return str_replace('_', '/', $path);
    }

    /**
     * @param $value
     * @return bool
     */
    private function normalizeBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool)$value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
