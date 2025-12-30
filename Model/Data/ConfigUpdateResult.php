<?php

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\ConfigUpdateResultInterface;

class ConfigUpdateResult extends \Magento\Framework\DataObject implements ConfigUpdateResultInterface
{
    public const KEY = 'key';
    public const SUCCESS = 'success';
    public const MESSAGE = 'message';

    /**
     * @return string
     */
    public function getKey(): string
    {
        return (string)$this->getData(self::KEY);
    }

    /**
     * @param string $key
     * @return self
     */
    public function setKey(string $key): self
    {
        return $this->setData(self::KEY, $key);
    }

    /**
     * @return bool
     */
    public function getSuccess(): bool
    {
        return (bool)$this->getData(self::SUCCESS);
    }

    /**
     * @param bool $success
     * @return self
     */
    public function setSuccess(bool $success): self
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * @param string|null $message
     * @return self
     */
    public function setMessage(?string $message): self
    {
        return $this->setData(self::MESSAGE, $message);
    }
}

