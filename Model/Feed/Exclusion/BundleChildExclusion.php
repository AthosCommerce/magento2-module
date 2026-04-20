<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Exclusion;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\Filter\FeedItemFilterInterface;
use Magento\Catalog\Model\Product;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface as FeedSpecification;

class BundleChildExclusion implements FeedItemFilterInterface
{
    private $logger;
    /**
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        AthosCommerceLogger $logger,
    )
    {
        $this->logger = $logger;
    }

    /**
     * Cached bundle child ids
     *
     * @var array
     */
    private array $bundleChildIds = [];

    /**
     * @param array $products
     * @param FeedSpecification $feedSpecification
     * @return array
     */
    public function filterEntities(
        array             $products,
        FeedSpecification $feedSpecification
    ): array
    {

        $childIds = [];

        foreach ($products as $product) {

            if (!$product instanceof Product) {
                continue;
            }

            if (in_array(!$product->getTypeId(), $this->getBundleTypeIds(), true)) {
                continue;
            }

            try {
                $bundleChildIds = $this->getBundleInvisibleChildIds($product);
            } catch (\RuntimeException $e) {
                $this->logger->error(
                    sprintf(
                        'Error getting bundle child ids for product ID %s: %s',
                        $product->getId(),
                        $e->getMessage()
                    )
                );
                continue;
            }


            foreach ($bundleChildIds as $id) {
                $childIds[$id] = true;
            }
        }

        $this->bundleChildIds = $childIds;

        return $products;
    }

    /**
     * To skip dependency we have used hard coded-string
     *
     * @return string[]
     */
    public function getBundleTypeIds(): array
    {
        return ['bundle'];
    }

    /**
     * @param Product $product
     * @param FeedSpecification $feedSpecification
     * @return bool
     */
    public function shouldExcludeEntity(
        Product           $product,
        FeedSpecification $feedSpecification
    ): bool
    {
        return false;
    }

    /**
     * Remove bundle children from rows
     *
     * @param array $rows
     * @param FeedSpecification $feedSpecification
     * @return array
     */
    public function filterRows(
        array             $rows,
        FeedSpecification $feedSpecification
    ): array
    {

        if (empty($this->bundleChildIds)) {
            return $rows;
        }

        foreach ($rows as $index => $row) {

            $entityId = $row['entity_id'] ?? null;

            if ($entityId && isset($this->bundleChildIds[$entityId])) {
                unset($rows[$index]);
            }
        }

        return $rows;
    }

    /**
     * @param array $row
     * @param FeedSpecification $feedSpecification
     * @return bool
     */
    public function shouldExcludeRow(
        array             $row,
        FeedSpecification $feedSpecification
    ): bool
    {
        return false;
    }

    /**
     * Get bundle child ids
     *
     * @param Product $product
     * @return array
     */
    private function getBundleInvisibleChildIds(Product $product): array
    {
        $typeInstance = $product->getTypeInstance();

        if (!method_exists($typeInstance, 'getOptionsIds')) {
            return [];
        }
        $optionIds = $typeInstance->getOptionsIds($product);

        if (!$optionIds) {
            return [];
        }

        $selectionsCollection = $typeInstance->getSelectionsCollection(
            $optionIds,
            $product
        );

        if ($selectionsCollection->getSize() < 1) {
            return [];
        }

        $items = $selectionsCollection->getItems();

        $invislbleChildIds = array_map(static function ($item) {
            /**
             * @var Product $item
             */
            if ($item && $item instanceof Product && $item->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE) {
                return $item->getId();
            }
            return null;
        }, $items);
        return array_filter($invislbleChildIds, static function ($childId) {
            return $childId !== null;
        });
    }
}
