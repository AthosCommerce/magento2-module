<?php

namespace AthosCommerce\Feed\Api\Data;

interface ConfigUpdateResponseInterface
{
    /**
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * @param bool $success
     * @return self
     */
    public function setSuccess(bool $success): self;

    /**
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * @param string|null $message
     * @return self
     */
    public function setMessage(?string $message): self;

    /**
     * @return string
     */
    public function getStoreCode(): string;

    /**
     * @param string $storeCode
     * @return self
     */
    public function setStoreCode(string $storeCode): self;

    /**
     * @return int
     */
    public function getCount(): int;

    /**
     * @param int $count
     * @return self
     */
    public function setCount(int $count): self;

    /**
     * @return \AthosCommerce\Feed\Api\Data\ConfigUpdateResultInterface[]
     */
    public function getResults(): array;

    /**
     * @param array $results
     * @return self
     */
    public function setResults(array $results): self;
}
