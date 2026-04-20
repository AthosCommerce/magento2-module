<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Resolver;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

class ParentOverrideResolver implements RowResolverInterface
{
    /**
     * @param array $rows
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    public function process(array $rows, FeedSpecificationInterface $feedSpecification): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();
        if (in_array('ignore_parent_override', $ignoredFields, true)) {
            return $rows;
        }

        foreach ($rows as &$row) {

            if (empty($row['__is_belong_to_parent'])) {
                continue;
            }

            if (!in_array('ignore_parent_name_override', $ignoredFields, true) && !empty($row['__parent_title'])) {
                $row['name'] = $row['__parent_title'];
            }

            if (!in_array('ignore_parent_type_id_override', $ignoredFields, true) && !empty($row['parent_type_id'])) {
                $row['type_id'] = $row['parent_type_id'];
            }

            if (
                isset($row['ignore_parent_product_type_override']) &&
                !in_array('product_type', $ignoredFields, true) &&
                !empty($row['parent_type_id'])
            ) {
                $row['product_type'] = $row['parent_type_id'];
            }

            if (!in_array('ignore_parent_visibility_override', $ignoredFields, true) && !empty($row['parent_visibility'])) {
                $row['visibility'] = $row['parent_visibility'];
            }

            if (!in_array('ignore_parent_url_override', $ignoredFields, true) && !empty($row['parent_url'])) {
                $row['url'] = $row['parent_url'];
            }
        }

        return $rows;
    }
}
