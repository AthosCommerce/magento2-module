<?php

namespace AthosCommerce\Feed\Api;

interface GetConfigInfoInterface
{
    /**
     * @param string $path
     * @return array
     */
    public function GetConfigDetails(string $path): array;

}

