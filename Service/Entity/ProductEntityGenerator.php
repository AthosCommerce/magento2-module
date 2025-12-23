<?php

namespace AthosCommerce\Feed\Service\Entity;

use AthosCommerce\Feed\Model\Api\MagentoEntityInterfaceFactory;

class ProductEntityGenerator
{
    /**
     * @var MagentoEntityInterfaceFactory
     */
    private $magentoEntityInterfaceFactory;

    public function __construct(
        MagentoEntityInterfaceFactory $magentoEntityInterfaceFactory
    )
    {
        $this->magentoEntityInterfaceFactory = $magentoEntityInterfaceFactory;
    }

    /**
     * @param array $productIds
     * @param string $siteId
     * @return \Generator
     */
    public function generate(
        array  $productIds,
        string $siteId
    ): \Generator
    {
        foreach ($productIds as $productId) {
            yield $this->magentoEntityInterfaceFactory->create([
                'entityId' => (int)$productId,
                'entitySubtype' => 'simple',
                'entityParentId' => 0,
                'siteId' => $siteId,
                'isIndexable' => true,
            ]);
        }
    }
}
