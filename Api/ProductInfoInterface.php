<?php

namespace AthosCommerce\Feed\Api;

interface ProductInfoInterface
{

    /**
     * @param int $productId
     * @param int $storeId
     *
     * @return array
     */
    public function getInfo(
        int $productId,
        int $storeId = 1,
    ): array;
}
