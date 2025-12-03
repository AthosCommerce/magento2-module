<?php
namespace AthosCommerce\Feed\Api;

interface ConfigUpdateInterface
{
    /**
     * @param string $module
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     * @return array
     */
    public function update(string $module, string $path, string $value, string $scope = "default", int $scopeId = 0): array;

}
