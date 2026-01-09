<?php

namespace AthosCommerce\Feed\Service\Entity;

use AthosCommerce\Feed\Service\FilterEntitiesToAddServiceInterface;
use AthosCommerce\Feed\Service\Action\AddIndexingEntitiesActionInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class EnsureEntityExistsService
{
    /**
     * @var FilterEntitiesToAddServiceInterface
     */
    private $filterEntitiesToAddService;
    /**
     * @var AddIndexingEntitiesActionInterface
     */
    private $addIndexingEntitiesAction;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param FilterEntitiesToAddServiceInterface $filterEntitiesToAddService
     * @param AddIndexingEntitiesActionInterface $addIndexingEntitiesAction
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        FilterEntitiesToAddServiceInterface $filterEntitiesToAddService,
        AddIndexingEntitiesActionInterface  $addIndexingEntitiesAction,
        AthosCommerceLogger                 $logger
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
