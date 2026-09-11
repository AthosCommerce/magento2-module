<?php
/**
 * Helper to fetch sale data.
 *
 * This file is part of AthosCommerce/Feed.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace AthosCommerce\Feed\Helper;

use DateTimeZone;
use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Store\Model\StoresConfig;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use AthosCommerce\Feed\Api\Data\SalesDataInterface;
use AthosCommerce\Feed\Api\Data\SalesDataInterfaceFactory;
use DateTime;

class Sale extends AbstractHelper
{
    protected $storesConfig;
    protected $saleFactory;
    protected $salesDataFactory;

    public function __construct(StoresConfig $storesConfig, CollectionFactory $saleFactory, SalesDataInterfaceFactory $salesDataFactory)
    {
        $this->storesConfig = $storesConfig;
        $this->saleFactory = $saleFactory;
        $this->salesDataFactory = $salesDataFactory;
    }

    /**
     * @param string $dateRangeStr
     * @param string $rowRangeStr
     *
     * @return SalesDataInterface[]
     */
    public function getSales(string $dateRangeStr, string $rowRangeStr): array
    {
        $result = [];
        $collection = $this->saleFactory->create();
        $rowRange = Utils::getRowRange($rowRangeStr);
        $this->applyDateRangeFilter($collection, $dateRangeStr);
        $this->applyOrderJoin($collection);
        $collection->getSelect()->order('main_table.item_id ASC');
        if (isset($rowRange[0], $rowRange[1])) {
            $collection->getSelect()->limit((int)$rowRange[1], (int)$rowRange[0]);
        }

        foreach ($collection->getItems() as $item) {
            $orderID = $item->getData('order_id');

            $customerID = $item->getData('order_customer_id');
            if (empty($customerID)) {
                $customerID = $item->getData('order_customer_email');
            }

            $productID = $item->getData('product_id');
            $quantity = $item->getData('qty_ordered') - ($item->getData('qty_canceled') + $item->getData('qty_refunded'));
            $price = $item->getData('price');
            $createdAt = $this->formatCreatedAt(
                (string)$item->getData('created_at'),
                (string)$item->getData('store_id')
            );

            $salesData = $this->salesDataFactory->create();

            $salesData->setOrderId((string)$orderID);
            $salesData->setCustomerId((string)$customerID);
            $salesData->setProductId((string)$productID);
            $salesData->setQuantity((string)$quantity);
            $salesData->setPrice((string)$price);
            $salesData->setCreatedAt($createdAt);

            $result[] = $salesData;
        }
        return $result;
    }

    /**
     * Get timezones used by the stores in this Magento setup.
     *
     * @return array
     */
    private function getTimeZones()
    {
        return $this->storesConfig->getStoresConfigByPath(Custom::XML_PATH_GENERAL_LOCALE_TIMEZONE);
    }

    /**
     * @param string $dateRangeStr
     * @return int
     */
    public function getSalesTotalCount(string $dateRangeStr): int
    {
        $collection = $this->saleFactory->create();
        $this->applyDateRangeFilter($collection, $dateRangeStr);

        return (int)$collection->getSize();
    }

    /**
     * @param Collection $collection
     * @param string $dateRangeStr
     * @return void
     */
    private function applyDateRangeFilter(Collection $collection, string $dateRangeStr): void
    {
        $dateRange = Utils::getDateRange($dateRangeStr);
        if (!$dateRange) {
            return;
        }

        $select = $collection->getSelect();
        $collection->addBindParam(':from', $dateRange[0]);
        $condition = '(main_table.created_at >= :from OR main_table.updated_at >= :from)';
        if (isset($dateRange[1])) {
            $plusOneDay = Utils::plusOneDay($dateRange[1], 'Y-m-d');
            $collection->addBindParam(':to', $plusOneDay);
            $condition = '((main_table.created_at >= :from AND main_table.created_at <= :to)'
                . ' OR '
                . '(main_table.updated_at >= :from AND main_table.updated_at <= :to))';
        }
        $select->where($condition);
    }

    /**
     * @param Collection $collection
     * @return void
     */
    private function applyOrderJoin(Collection $collection): void
    {
        $collection->getSelect()->joinLeft(
            ['order_table' => $collection->getTable('sales_order')],
            'order_table.entity_id = main_table.order_id',
            [
                'order_customer_id' => 'customer_id',
                'order_customer_email' => 'customer_email',
            ]
        );
    }

    /**
     * @param string $createdAt
     * @param string $storeId
     * @return string
     */
    private function formatCreatedAt(string $createdAt, string $storeId): string
    {
        $zones = $this->getTimeZones();
        if ($storeId !== '' && isset($zones[$storeId]) && $zones[$storeId] !== '') {
            $dateTime = new DateTime($createdAt, new DateTimeZone($zones[$storeId]));
            return $dateTime->format('Y-m-d H:i:sP');
        }

        return (new DateTime($createdAt))->format('Y-m-d H:i:sP');
    }
}
