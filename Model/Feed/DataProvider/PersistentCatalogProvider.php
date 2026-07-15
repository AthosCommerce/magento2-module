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

//    /**
//     * @param array $products
//     * @param FeedSpecificationInterface $feedSpecification
//     * @return array
//     * @throws LocalizedException
//     */
//    public function getData(
//        array $products,
//        FeedSpecificationInterface $feedSpecification
//    ): array {
//        $ignoredFields = $feedSpecification->getIgnoreFields();
//
//        if (
//            in_array('__catalog', $ignoredFields, true)
//            || !$feedSpecification->getCatalogPreSignedUrl()
//        ) {
//            return $products;
//        }
//
//        foreach ($products as &$product) {
//            /** @var Product|null $productModel */
//            $productModel = $product['product_model'] ?? null;
//
//            if (!$productModel instanceof Product) {
//                continue;
//            }
//
//            $catalog = [];
//
//            // Find parent if this is a child product
//            $parentProduct = $this->parentVariantResolver->getParentProduct($productModel);
//
//
//            if ($parentProduct) {
//                // Parent row
//                $catalog[] = $this->buildParentRow($parentProduct);
//
//                // Variant rows
//                foreach ($this->parentVariantResolver->getChildProducts($parentProduct) as $child) {
//                    $catalog[] = $this->buildVariantRow($parentProduct, $child);
//                }
//            } else {
//                // Standalone simple
//                if (in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
//                    $catalog[] = $this->buildStandaloneRow($productModel);
//                } // Parent product
//                elseif (in_array($productModel->getTypeId(), ['configurable', 'grouped'], true)) {
//                    $catalog[] = $this->buildParentRow($productModel);
//
//                    foreach ($this->parentVariantResolver->getChildProducts($productModel) as $child) {
//                        $catalog[] = $this->buildVariantRow($productModel, $child);
//                    }
//                }
//            }
//
//            $product['__catalog'] = $catalog;
//        }
//
//        return $products;
//    }
//
//    private function buildParentRow(Product $parent): array
//    {
//        $row = [
//            'uid' => (string)$parent->getId(),
//            'sku' => $parent->getSku(),
//            'parent_uid' => (string)$parent->getId(),
//            'name' => $parent->getName(),
//            'price' => $parent->getPrice(),
//            'url' => $parent->getProductUrl(),
//            'imageUrl' => $this->getParentImage($parent),
//            'thumbnailImageUrl' => $this->getParentImage($parent),
//        ];
//        $row['recordHash'] = md5(json_encode($row));
//        return $row;
//    }
//
//    private function getParentImage(Product $parent): string
//    {
//        $image = $parent->getImage()
//            ?: $parent->getSmallImage()
//                ?: $parent->getThumbnail();
//
//        if ($image && $image !== 'no_selection') {
//            return $parent->getMediaConfig()->getMediaUrl($image);
//        }
//
//        return '';
//    }
//
//    private function buildVariantRow(
//        Product $parent,
//        Product $child
//    ): array {
//        $row = [
//            'uid' => $parent->getId() . '_' . $child->getId(),
//            'sku' => $child->getSku(),
//            'parent_uid' => (string)$parent->getId(),
//            'name' => $child->getName(),
//            'price' => $child->getPrice(),
//            'url' => $child->getProductUrl(),
//            'imageUrl' => $this->getParentImage($child),
//            'thumbnailImageUrl' => $this->getParentImage($child),
//        ];
//
//        $row['recordHash'] = md5(json_encode($row));
//        return $row;
//    }
//
//    private function buildStandaloneRow(Product $product): array
//    {
//        $row = [
//            'uid' => (string)$product->getId(),
//            'sku' => $product->getSku(),
//            'parent_uid' => (string)$product->getId(),
//            'name' => $product->getName(),
//            'price' => $product->getPrice(),
//            'url' => $product->getProductUrl(),
//            'imageUrl' => $this->getParentImage($product),
//            'thumbnailImageUrl' => $this->getParentImage($product),
//        ];
//        $row['recordHash'] = md5(json_encode($row));
//        return $row;
//    }
//
//    public function reset(): void
//    {
//        // No state to reset in this provider
//    }
//
//    public function resetAfterFetchItems(): void
//    {
//        // No state to reset after fetching items in this provider
//    }

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

            $product['__catalog'] = $this->buildCatalog($productModel);
        }

        unset($product);

        return $products;
    }

    /**
     * Build catalog rows for a product.
     */
    private function buildCatalog(Product $product): array
    {
        $parent = $this->parentVariantResolver->getParentProduct($product);

        // Child product
        if ($parent) {
            return $this->buildParentWithVariants($parent);
        }

        $type = $product->getTypeId();

        if (in_array($type, ['simple', 'virtual'], true)) {
            return [$this->buildProductRow($product)];
        }

        if (in_array($type, ['configurable', 'grouped'], true)) {
            return $this->buildParentWithVariants($product);
        }

        return [];
    }

    /**
     * Parent + all variants.
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
     * Parent / Standalone row.
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
     * Variant row.
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
     * Creates one persistent catalog row.
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
     * Generate record hash.
     */
    private function generateRecordHash(array $row): string
    {
        return md5(json_encode($row));
    }

    /**
     * Build full media URL.
     */
    private function getImageUrl(?string $image): string
    {
        if (!$image || $image === 'no_selection') {
            return '';
        }

        return $this->storeManager
                ->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            . 'catalog/product'
            . $image;
    }

    public function reset(): void
    {
        // No state
    }

    public function resetAfterFetchItems(): void
    {
        // No state
    }
}
