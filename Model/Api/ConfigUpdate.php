<?php

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\ConfigUpdateInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Exception\LocalizedException;

class ConfigUpdate implements ConfigUpdateInterface
{
    const MODULE_NAME = 'AthosCommerce_Feed';

    const MODULE_PREFIX = 'athoscommerce/';

    const ALLOWED_INDEXING_VALUES = ['0', '1'];

    const array Allowed_PATHS = [
        self::MODULE_PREFIX . 'indexing/enable_live_indexing',
        self::MODULE_PREFIX . 'indexing/entity_cron_expr'
    ];

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    public function __construct(
        WriterInterface   $configWriter,
        TypeListInterface $cacheTypeList
    )
    {
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @param string $module
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     * @return array
     */
    public function update(string $module, string $path, string $value, string $scope = "default", int $scopeId = 0): array
    {
        try {
            if ($module !== self::MODULE_NAME) {
                $message = sprintf(
                    "Saving config is only allowed for module %s. Provided: %s",
                    self::MODULE_NAME,
                    $module
                );
                throw new LocalizedException(__($message));
            }

            $isConfigPath = str_starts_with($path, self::MODULE_PREFIX . 'configuration/');

            if (in_array($path, self::Allowed_PATHS, true)) {
                if ($path === self::Allowed_PATHS[0]) {
                    if (!in_array($value, self::ALLOWED_INDEXING_VALUES, true)) {
                        throw new LocalizedException(
                            __("Invalid value '%1' for %2. Allowed: 0 or 1.", $value, $path)
                        );
                    }
                } elseif ($path === self::Allowed_PATHS[1]) {
                    $this->validateCronExpression($value);
                }
            } elseif ($isConfigPath) {
                if (trim($value) === '') {
                    throw new LocalizedException(
                        __("Value for '%1' cannot be empty.", $path)
                    );
                }
            } else {
                throw new LocalizedException(
                    __("Invalid config path '%1'. Allowed paths must start with '%2' and match module structure.", $path, json_encode(self::Allowed_PATHS))
                );
            }

            // Save to core_config_data
            $this->configWriter->save(
                $path,
                $value,
                $scope,
                $scopeId
            );

            // Flush config cache
            $this->cacheTypeList->cleanType('config');

            return [
                'data' => [
                    'success' => true,
                    'path' => $path,
                    'value' => $value,
                    'scope' => $scope,
                    'scope_id' => $scopeId,
                    'message' => 'Config saved successfully'
                ]
            ];
        } catch (LocalizedException $e) {
            // Only return the message
            return [
                'data' => [
                    'success' => false,
                    'message' => $e->getMessage()
                ],
            ];
        }
    }

    /**
     * @param string $cronExpression
     * @return void
     * @throws LocalizedException
     */
    private function validateCronExpression(string $cronExpression): void
    {
        $fields = preg_split('/\s+/', trim($cronExpression));

        if (count($fields) !== 5) {
            $message = sprintf(
                "Cron expression must contain exactly 5 fields. Provided: %d",
                count($fields)
            );
            throw new LocalizedException(__($message));
        }

        // Field definitions
        $fieldNames = ['minute', 'hour', 'day of month', 'month', 'day of week'];

        // Validation patterns for each field
        $patterns = [
            '/^(\*|([0-5]?\d)(\/\d+)?|\*\/\d+|([0-5]?\d-[0-5]?\d)|(,?[0-5]?\d)+)$/',        // minute (0-59)
            '/^(\*|([01]?\d|2[0-3])(\/\d+)?|\*\/\d+|([01]?\d|2[0-3]-([01]?\d|2[0-3]))(,(?:[01]?\d|2[0-3]))*)$/', // hour (0-23)
            '/^(\*|([1-9]|[12]\d|3[01])(\/\d+)?|\*\/\d+|([1-9]|[12]\d|3[01]-([1-9]|[12]\d|3[01]))(,(?:[1-9]|[12]\d|3[01]))*)$/', // day of month (1-31)
            '/^(\*|(0?[1-9]|1[0-2])(\/\d+)?|\*\/\d+|(0?[1-9]|1[0-2]-(0?[1-9]|1[0-2]))(,(?:0?[1-9]|1[0-2]))*)$/', // month (1-12)
            '/^(\*|[0-6](\/\d+)?|\*\/\d+|([0-6]-[0-6])(,(?:[0-6]))*)$/',  // day of week (0–6)
        ];

        // Message for each field when invalid
        $errorMessages = [
            "Minutes must be 0–59, ranges (1-5), lists (1,2,3), or steps (*/5).",
            "Hours must be 0–23, ranges (1-5), lists (1,2,3), or steps (*/2).",
            "Day of month must be 1–31, ranges (1-10), lists (1,5,10), or steps (*/3).",
            "Month must be 1–12, ranges (1-6), lists (1,5,7), or steps (*/2).",
            "Day of week must be 0–6, ranges (1-3), lists (1,5), or steps (*/2).",
        ];

        // Validate each individual field
        foreach ($fields as $index => $field) {
            if (!preg_match($patterns[$index], $field)) {
                $message = sprintf(
                    'Invalid %s field: "%s". %s',
                    $fieldNames[$index],
                    $field,
                    $errorMessages[$index]
                );
                throw new LocalizedException(__($message));
            }
        }
    }
}
