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

namespace AthosCommerce\Feed\Test\Unit\Model;

use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;

    /**
     * @var EncryptorInterface
     */
    private $encryptorMock;

    /**
     * @var Config
     */
    private $configModel;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->encryptorMock = $this->createMock(EncryptorInterface::class);
        $this->configModel = new Config($this->scopeConfigMock, $this->encryptorMock);
    }

    public function testGetEndpointByStoreIdAddsHttpsAndAllowsAthosDomain(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Constants::XML_PATH_CONFIG_ENDPOINT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('api.athoscommerce.net');

        $this->assertSame('https://api.athoscommerce.net', $this->configModel->getEndpointByStoreId(1));
    }

    public function testGetEndpointByStoreIdRejectsMalformedEndpointContainingEmbeddedScheme(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Constants::XML_PATH_CONFIG_ENDPOINT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('foohttps://evil.com');

        $this->assertSame('', $this->configModel->getEndpointByStoreId(1));
    }

    public function testGetEndpointByStoreIdRejectsHttpScheme(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Constants::XML_PATH_CONFIG_ENDPOINT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('http://api.athoscommerce.net');

        $this->assertSame('', $this->configModel->getEndpointByStoreId(1));
    }

    public function testGetEndpointByStoreIdRejectsNonAthosDomain(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Constants::XML_PATH_CONFIG_ENDPOINT, ScopeInterface::SCOPE_STORE, 1)
            ->willReturn('https://example.com');

        $this->assertSame('', $this->configModel->getEndpointByStoreId(1));
    }
}

