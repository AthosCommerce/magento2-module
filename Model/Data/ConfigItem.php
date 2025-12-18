<?php

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\ConfigItemInterface;
use Magento\Framework\DataObject;

class ConfigItem extends DataObject implements ConfigItemInterface
{
    const PATH = 'path';
    const VALUE = 'value';

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->_getData('path');
    }

    /**
     * Set schedule ID
     *
     * @param string $value
     *
     * @return $this
     */
    public function setPath(string $value): self
    {
        return $this->setData(self::PATH, $value);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->_getData('value');
    }

    /**
     * Set schedule ID
     *
     * @param string $value
     * @return $this
     */
    public function setValue(string $value): self
    {
        return $this->setData(self::VALUE, $value);
    }
}
