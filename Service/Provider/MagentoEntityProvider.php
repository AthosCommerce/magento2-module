<?php

namespace AthosCommerce\Feed\Service\Provider;

use AthosCommerce\Feed\Model\CollectionProcessor;

class MagentoEntityProvider
{
    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;

    /**
     * @param CollectionProcessor $collectionProcessor
     */
    public function __construct(
        CollectionProcessor $collectionProcessor
    )
    {
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @param mixed $feedSpecification
     * @param int $batchSize
     * @return \Generator|int[]
     */
    public function getMagentoEntityIds(
        $feedSpecification,
        int $batchSize = 1000
    ): \Generator
    {
        $lastEntityId = 0;

        while (true) {
            $collection = $this->collectionProcessor->getCollection($feedSpecification);
            $collection->addFieldToFilter('entity_id', ['gt' => $lastEntityId]);
            $collection->getSelect()->order('e.entity_id ASC');

            $entityIds = $collection->getAllIds($batchSize);
            if (!$entityIds) {
                break;
            }

            $lastEntityId = (int) end($entityIds);

            yield $entityIds;

            unset($collection, $entityIds);
            gc_collect_cycles();
        }
    }
}
