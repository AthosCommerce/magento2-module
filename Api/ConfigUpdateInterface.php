<?php

namespace AthosCommerce\Feed\Api;

use AthosCommerce\Feed\Api\Data\ConfigItemInterface;

interface ConfigUpdateInterface
{
    /**
     * @param string $module
     * @param \AthosCommerce\Feed\Api\Data\ConfigItemInterface[] $configs
     * @param string $scope
     * @param int $scopeId
     *
     * @return array
     */
    public function update(
        string $module,
        array  $configs,
        string $scope,
        int    $scopeId
    ): array;

}
