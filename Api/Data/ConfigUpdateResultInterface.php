<?php

namespace AthosCommerce\Feed\Api\Data;

interface ConfigUpdateResultInterface
{
    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @param string $key
     * @return ConfigUpdateResultInterface
     */
    public function setKey(string $key): self;

    /**
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * @param bool $success
     * @return ConfigUpdateResultInterface
     */
    public function setSuccess(bool $success): self;

    /**
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * @param string|null $message
     * @return ConfigUpdateResultInterface
     */
    public function setMessage(?string $message): self;
}
