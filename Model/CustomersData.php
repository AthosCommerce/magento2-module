<?php

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Api\Data\CustomersDataInterface;

class CustomersData implements CustomersDataInterface
{
    private $id;
    private $email;
    private $phoneNumber;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * @param $value string
     */
    public function setId(string $value)
    {
        $this->id = $value;
    }

    /**
     * @param $value string
     */
    public function setEmail(string $value)
    {
        $this->email = $value;
    }

    /**
     * @param $value string
     */
    public function setPhoneNumber(string $value)
    {
        $this->phoneNumber = $value;
    }
}
