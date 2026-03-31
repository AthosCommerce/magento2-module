<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Service;

use Magento\Framework\App\ProductMetadataInterface;

class LinkFieldResolver
{
    const EDITION_ENTERPRISE = 'Enterprise';
    const EDITION_COMMUNITY = 'Community';
    const EDITION_B2B = 'B2B';

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(ProductMetadataInterface $productMetadata)
    {
        $this->productMetadata = $productMetadata;

    }

    /**
     * @return string
     */
    public function getLinkField(): string
    {
        $idColumnName = 'entity_id';

        if (in_array(
            $this->getEdition(),
            [
                self::EDITION_ENTERPRISE, self::EDITION_B2B
            ],
            true
        )) {
            $idColumnName = 'row_id';
        }
        return $idColumnName;
    }

    /**
     * @return string
     */
    public function getEdition(): string
    {
        return $this->productMetadata->getEdition();
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->productMetadata->getVersion();
    }
}
