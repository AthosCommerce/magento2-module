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
    function getSales(string $dateRangeStr, string $rowRangeStr): array
    {
        $result = [];
        $collection = $this->saleFactory->create();
        $select = $collection->getSelect();

        // Build date range query.
        $dateRange = Utils::getDateRange($dateRangeStr);
        if ($dateRange) {
            $collection->addBindParam(':from', $dateRange[0]);

            $condition = "(main_table.created_at >= :from OR main_table.updated_at >= :from)";
            if (isset($dateRange[1])) {
                $plusOneDay = Utils::plusOneDay($dateRange[1], $format = 'Y-m-d');
                $collection->addBindParam(':to', $plusOneDay);
                $condition = "(main_table.created_at >= :from AND main_table.created_at <= :to) OR (main_table.updated_at >= :from AND main_table.updated_at <= :to) ";
            }
            $select->where($condition);
        }

        // Chunk sales with row range.
        $rowRange = Utils::getRowRange($rowRangeStr);
        if (isset($rowRange[0]) && isset($rowRange[1])) {
            $select->limit((int)$rowRange[1], (int)$rowRange[0]);
        }

        foreach($collection as $item){
            $orderID = $item->getOrderID();

            $order = $item->getOrder();
            $customerID = $order->getData('customer_id');
            if (empty($customerID)) {
                $customerID = $order->getData('customer_email');
            }

            $productID = $item->getData('product_id');
            $quantity = $item->getData('qty_ordered') - ($item->getData('qty_canceled') + $item->getData('qty_refunded'));
            $price = $item->getData('price');

            // This has returned "" in the wild
            $storeId = $item->getData('store_id');

            // Normal storeIds start at "1", but the sneaky admin store is "0".
            if($storeId == "0" || !empty($storeId)){
                $zones = $this->getTimeZones();
                $zone = $zones[$storeId];
                $dt = new DateTime($item->getData('created_at'), new DateTimeZone($zone));
            } else {
                $dt = new DateTime($item->getData('created_at'));
            }
            $createdAt = $dt->format('Y-m-d H:i:sP');

            $salesData = $this->salesDataFactory->create();

            $salesData->setOrderId($orderID);
            $salesData->setCustomerId($customerID);
            $salesData->setProductId($productID);
            $salesData->setQuantity((string)$quantity);
            $salesData->setPrice($price);
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
}
