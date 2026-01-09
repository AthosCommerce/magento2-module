<?php

namespace AthosCommerce\Feed\Api;

interface CategoryInfoInterface
{
    /**
     * Get details of all categories in the store.
     *
     * @param bool $activeOnly
     * @param string $delimiter
     * @param int $currentPage
     * @param int $pageSize
     *
     * @return mixed
     */
    public function getAllCategories(
        bool $activeOnly = true,
        string $delimiter = '>',
        int $currentPage = 1,
        int $pageSize = 15
    );
}
