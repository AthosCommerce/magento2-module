<?php

namespace AthosCommerce\Feed\Service\Entity;

use AthosCommerce\Feed\Service\FilterEntitiesToAddServiceInterface;
use AthosCommerce\Feed\Service\Action\AddIndexingEntitiesActionInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class EnsureEntityExistsService
{
    private $filterEntitiesToAddService;
    private $addIndexingEntitiesAction;
    private $logger;

    public function __construct(
        FilterEntitiesToAddServiceInterface $filterEntitiesToAddService,
        AddIndexingEntitiesActionInterface  $addAction,
        AthosCommerceLogger                     $logger
    )
    {
        $this->filterEntitiesToAddService = $filterEntitiesToAddService;
        $this->addIndexingEntitiesAction = $addIndexingEntitiesAction;
        $this->logger = $logger;
    }

    /**
     * @param iterable $entities (array OR generator)
     */
    public function execute(
        iterable $entities,
        string   $type,
        string   $siteId
    ): void
    {
        $entitiesArray = iterator_to_array($entities, false);
        if (!$entitiesArray) {
            return;
        }

        $missing = $this->filterEntitiesToAddService->execute(
            $entitiesArray,
            $type,
            $siteId,
            []
        );

        if (!$missing) {
            return;
        }

        try {
            $this->addIndexingEntitiesAction->execute($type, $missing);
        } catch (\Throwable $e) {
            $this->logger->error(
                '[EnsureEntityExists] Failed',
                ['message' => $e->getMessage()]
            );
        }
    }
}
