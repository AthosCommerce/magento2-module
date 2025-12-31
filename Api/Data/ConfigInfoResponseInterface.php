<?php

namespace AthosCommerce\Feed\Api\Data;

interface ConfigInfoResponseInterface
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
     * @return \AthosCommerce\Feed\Api\Data\StoreConfigInterface[]
     */
    public function getStores(): array;

    /**
     * @param \AthosCommerce\Feed\Api\Data\StoreConfigInterface[] $stores
     * @return self
     */
    public function setStores(array $stores): self;
}
