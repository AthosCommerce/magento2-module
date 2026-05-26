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

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\ConfigurableAttributeOptionsProviderInterface;
use AthosCommerce\Feed\Api\Data\ProductOptionsResponseInterface;
use AthosCommerce\Feed\Api\GetOptionsInterface;
use AthosCommerce\Feed\Api\Data\ProductOptionsResponseInterfaceFactory;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Provider\StoreProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Serialize\Serializer\Json;

class GetOptions implements GetOptionsInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Json
     */
    private $jsonSerializer;
    /**
     * @var ConfigurableAttributeOptionsProviderInterface
     */
    private $configurableAttributeOptionsProvider;
    /**
     * @var ProductOptionsResponseInterfaceFactory
     */
    private $responseFactory;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var StoreProvider
     */
    private $storeProvider;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $jsonSerializer
     * @param ConfigurableAttributeOptionsProviderInterface $configurableAttributeOptionsProvider
     * @param ProductOptionsResponseInterfaceFactory $responseFactory
     * @param AthosCommerceLogger $logger
     * @param StoreProvider $storeProvider
     */
    public function __construct(
        ScopeConfigInterface                          $scopeConfig,
        Json                                          $jsonSerializer,
        ConfigurableAttributeOptionsProviderInterface $configurableAttributeOptionsProvider,
        ProductOptionsResponseInterfaceFactory        $responseFactory,
        AthosCommerceLogger                           $logger,
        StoreProvider                                 $storeProvider
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->jsonSerializer = $jsonSerializer;
        $this->configurableAttributeOptionsProvider = $configurableAttributeOptionsProvider;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->storeProvider = $storeProvider;
    }

    /**
     * @param string|null $storeCode
     * @return ProductOptionsResponseInterface
     */
    public function get(?string $storeCode = null): ProductOptionsResponseInterface
    {
        $storeId = $this->storeProvider->getStoreId($storeCode);
        if (null === $storeId) {
            $message = "We couldn't find a store with that code. Please provide a valid store code, or leave it blank to use the default store.";

            $this->logger->warning(
                $message,
                ['storeCode' => $storeCode]
            );

            $response = $this->responseFactory->create();
            $response->setMessage($message);
            $response->setOptions([]);
            $response->setCatalogOptions([]);
            return $response;
        }
        $scopeId = 0;
        if (null !== $storeId) {
            $scopeId = $storeId;
        }

        $value = (string)$this->scopeConfig->getValue(
            Constants::XML_PATH_ATTRIBUTE_OPTIONS_LIST,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        $options = [];

        if ($value !== '') {
            try {
                $decoded = $this->jsonSerializer->unserialize($value);

                if (is_array($decoded)) {
                    $options = array_values($decoded);
                }
            } catch (\InvalidArgumentException $e) {
                $this->logger->error($e->getMessage());
                $options = [];
            }
        }
        $catalogOptions = $this->configurableAttributeOptionsProvider->getOptions($storeCode);

        $response = $this->responseFactory->create();
        $response->setMessage('Data generated successfully.');
        $response->setOptions($options);
        $response->setCatalogOptions($catalogOptions);

        return $response;
    }
}
