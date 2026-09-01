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

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class PersistentCatalogProvider implements DataProviderInterface
{
    private const SUPPORTED_STANDALONE_TYPES = ['simple', 'virtual'];

    private const SUPPORTED_PARENT_TYPES = ['configurable', 'grouped'];

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;


    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array<string, array>
     */
    private $catalogRowsByKey = [];

    /**
     * @var array<string, bool>
     */
    private $emittedCatalogKeys = [];

    /**
     * @var string|null
     */
    private $mediaBaseUrl;

    /**
     * @param AthosCommerceLogger $logger
     * @param ParentVariantResolver $parentVariantResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AthosCommerceLogger $logger,
        ParentVariantResolver $parentVariantResolver,
        StoreManagerInterface $storeManager,
    ) {
        $this->logger = $logger;
        $this->parentVariantResolver = $parentVariantResolver;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws LocalizedException
     */
    public function getData(
        array $products,
        FeedSpecificationInterface $feedSpecification
    ): array {

        if (
            in_array('__catalog', $feedSpecification->getIgnoreFields(), true)
            || !$feedSpecification->getCatalogPreSignedUrl()
        ) {
            return $products;
        }

        foreach ($products as &$product) {
            $productModel = $product['product_model'] ?? null;

            if (!$productModel instanceof Product) {
                continue;
            }

            $catalogKey = $this->getCatalogKey($product, $productModel);
            if ($catalogKey === null) {
                $product['__catalog'] = [];
                continue;
            }

            if (isset($this->emittedCatalogKeys[$catalogKey])) {
                $product['__catalog'] = [];
                continue;
            }

            if (!isset($this->catalogRowsByKey[$catalogKey])) {
                $this->catalogRowsByKey[$catalogKey] = $this->buildCatalog($product, $productModel);
            }

            $product['__catalog'] = $this->catalogRowsByKey[$catalogKey];
            $this->emittedCatalogKeys[$catalogKey] = true;
        }

        unset($product);

        return $products;
    }

    /**
     * @param array $row
     * @param Product $product
     * @return array|array[]
     */
    private function buildCatalog(array $row, Product $product): array
    {
        $parent = $this->parentVariantResolver->resolveParentProductForRow($row, $product);

        // Child product
        if ($parent) {
            return $this->buildParentWithVariants($parent);
        }

        $type = $product->getTypeId();

        if (in_array($type, self::SUPPORTED_STANDALONE_TYPES, true)) {
            return [$this->buildProductRow($product)];
        }

        if (in_array($type, self::SUPPORTED_PARENT_TYPES, true)) {
            return $this->buildParentWithVariants($product);
        }

        return [];
    }

    /**
     * @param array $row
     * @param Product $product
     * @return string|null
     */
    private function getCatalogKey(array $row, Product $product): ?string
    {
        $parent = $this->parentVariantResolver->resolveParentProductForRow($row, $product);
        if ($parent) {
            return 'parent_' . (string)$parent->getId();
        }

        $type = $product->getTypeId();
        if (
            in_array($type, self::SUPPORTED_STANDALONE_TYPES, true)
            || in_array($type, self::SUPPORTED_PARENT_TYPES, true)
        ) {
            return 'product_' . (string)$product->getId();
        }

        return null;
    }

    /**
     * @param Product $parent
     * @return array
     */
    private function buildParentWithVariants(Product $parent): array
    {
        $rows = [];

        $rows[] = $this->buildProductRow($parent);

        foreach ($this->parentVariantResolver->getChildProducts($parent) as $child) {
            $rows[] = $this->buildVariantRow($parent, $child);
        }

        return $rows;
    }

    /**
     * @param Product $product
     * @return array
     */
    private function buildProductRow(Product $product): array
    {
        return $this->createRow(
            uid: (string)$product->getId(),
            parentUid: (string)$product->getId(),
            product: $product
        );
    }

    /**
     * @param Product $parent
     * @param Product $child
     * @return array
     */
    private function buildVariantRow(
        Product $parent,
        Product $child
    ): array {

        return $this->createRow(
            uid: $parent->getId() . '_' . $child->getId(),
            parentUid: (string)$parent->getId(),
            product: $child
        );
    }

    /**
     * @param string $uid
     * @param string $parentUid
     * @param Product $product
     * @return array
     */
    private function createRow(
        string $uid,
        string $parentUid,
        Product $product
    ): array {

        $row = [
            'uid' => $uid,
            'sku' => (string)$product->getSku(),
            'parent_uid' => $parentUid,
            'name' => (string)$product->getName(),
            'price' => $product->getPrice(),
            'url' => $product->getProductUrl(),
            'imageUrl' => $this->getImageUrl($product->getImage()),
            'thumbnailImageUrl' => $this->getImageUrl($product->getData('thumbnail')),
        ];

        $row['recordHash'] = $this->generateRecordHash($row);

        return $row;
    }

    /**
     * @param array $row
     * @return string
     */
    private function generateRecordHash(array $row): string
    {
        return hash('sha256',json_encode($row));
    }

    /**
     * @param string|null $image
     * @return string
     */
    private function getImageUrl(?string $image): string
    {
        if (!$image || $image === 'no_selection') {
            return '';
        }

        return $this->getMediaBaseUrl()
            . 'catalog/product'
            . $image;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getMediaBaseUrl(): string
    {
        if ($this->mediaBaseUrl === null) {
            $this->mediaBaseUrl = $this->storeManager
                ->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        }

        return $this->mediaBaseUrl;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->catalogRowsByKey = [];
        $this->emittedCatalogKeys = [];
        $this->mediaBaseUrl = null;
    }

    public function resetAfterFetchItems(): void
    {
        // No state
    }
}
