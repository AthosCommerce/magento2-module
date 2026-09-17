<?php

declare(strict_types=1);

namespace Magento\Cron\Model;

if (!class_exists(Schedule::class, false)) {
    class Schedule
    {
        /**
         * @return int|null
         */
        public function getScheduleId(): ?int
        {
            return null;
        }

        /**
         * @return string|null
         */
        public function getJobCode(): ?string
        {
            return null;
        }

        /**
         * @return string|null
         */
        public function getStatus(): ?string
        {
            return null;
        }

        /**
         * @return string|null
         */
        public function getMessages(): ?string
        {
            return null;
        }

        /**
         * @return string|null
         */
        public function getCreatedAt(): ?string
        {
            return null;
        }

        /**
         * @return string|null
         */
        public function getScheduledAt(): ?string
        {
            return null;
        }

        /**
         * @return string|null
         */
        public function getExecutedAt(): ?string
        {
            return null;
        }

        /**
         * @return string|null
         */
        public function getFinishedAt(): ?string
        {
            return null;
        }

        /**
         * @return int|null
         */
        public function getId(): ?int
        {
            return null;
        }
    }
}
