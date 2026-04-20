<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Resolver;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

class RowResolverPool
{
    /**
     * @var RowResolverInterface[]
     */
    private array $resolvers;

    /**
     * @param RowResolverInterface[] $resolvers
     */
    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
    }

    /**
     * Apply all resolvers sequentially
     *
     * @param array $rows
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    public function process(
        array                      $rows,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        foreach ($this->resolvers as $resolver) {
            if (!$resolver instanceof RowResolverInterface) {
                continue;
            }

            $rows = $resolver->process($rows, $feedSpecification);
        }

        return $rows;
    }
}
