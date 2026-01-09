<?php

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\ConfigInfoResponseInterface;
use Magento\Framework\DataObject;

class ConfigInfoResponse extends DataObject implements ConfigInfoResponseInterface
{
    /**
     * @return bool
     */
    public function getSuccess(): bool
    {
        return (bool)$this->getData('success');
    }

    /**
     * @param bool $success
     * @return self
     */
    public function setSuccess(bool $success): self
    {
        return $this->setData('success', $success);
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->getData('message');
    }

    /**
     * @param string|null $message
     * @return self
     */
    public function setMessage(?string $message): self
    {
        return $this->setData('message', $message);
    }

    /**
     * @return array|\AthosCommerce\Feed\Api\Data\StoreConfigInterface[]
     */
    public function getStores(): array
    {
        return $this->getData('stores') ?? [];
    }

    /**
     * @param array|\AthosCommerce\Feed\Api\Data\StoreConfigInterface[] $stores
     * @return self
     */
    public function setStores(array $stores): self
    {
        return $this->setData('stores', $stores);
    }
}

