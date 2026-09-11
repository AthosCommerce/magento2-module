<?php

declare(strict_types=1);

namespace Magento\Cron\Model\ResourceModel\Schedule;

if (!class_exists(Collection::class, false)) {
    class Collection
    {
        public const SORT_ORDER_DESC = 'DESC';

        /**
         * @param string $field
         * @param mixed $condition
         *
         * @return $this
         */
        public function addFieldToFilter(string $field, $condition): self
        {
            return $this;
        }

        /**
         * @param string $field
         * @param string $direction
         *
         * @return $this
         */
        public function setOrder(string $field, string $direction): self
        {
            return $this;
        }

        /**
         * @param int $pageSize
         *
         * @return $this
         */
        public function setPageSize(int $pageSize): self
        {
            return $this;
        }

        /**
         * @param int $currentPage
         *
         * @return $this
         */
        public function setCurPage(int $currentPage): self
        {
            return $this;
        }

        /**
         * @return array
         */
        public function getItems(): array
        {
            return [];
        }

        /**
         * @return mixed
         */
        public function getFirstItem()
        {
            return null;
        }
    }
}
