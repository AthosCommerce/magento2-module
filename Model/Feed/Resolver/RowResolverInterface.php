<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Resolver;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

interface RowResolverInterface
{
    /**
     * @param array $rows
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    public function process(array $rows, FeedSpecificationInterface $feedSpecification): array;
}
