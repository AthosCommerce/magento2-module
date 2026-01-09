<?php

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\ConfigUpdateResponseInterface;

class ConfigUpdateResponse extends \Magento\Framework\DataObject implements ConfigUpdateResponseInterface
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
     * @return string
     */
    public function getStoreCode(): string
    {
        return (string)$this->getData('storeCode');
    }

    /**
     * @param string $storeCode
     * @return self
     */
    public function setStoreCode(string $storeCode): self
    {
        return $this->setData('storeCode', $storeCode);
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return (int)$this->getData('count');
    }

    /**
     * @param int $count
     * @return self
     */
    public function setCount(int $count): self
    {
        return $this->setData('count', $count);
    }

    /**
     * @return array|\AthosCommerce\Feed\Api\Data\ConfigUpdateResultInterface[]
     */
    public function getResults(): array
    {
        return $this->getData('results') ?? [];
    }

    /**
     * @param array $results
     * @return self
     */
    public function setResults(array $results): self
    {
        return $this->setData('results', $results);
    }
}
