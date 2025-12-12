<?php

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\ConfigUpdateInterface;
use AthosCommerce\Feed\Helper\Constants;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;

class ConfigUpdate implements ConfigUpdateInterface
{
    const MODULE_NAME = 'AthosCommerce_Feed';

    const MODULE_PREFIX = 'athoscommerce/';

    const ALLOWED_INDEXING_VALUES = ['0', '1'];

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        WriterInterface $configWriter,
        EncryptorInterface $encryptor
    ) {
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
    }

    /**
     * @param string $module
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     *
     * @return array
     */
    public function update(
        string $module,
        string $path,
        string $value,
        string $scope = "default",
        int $scopeId = 0
    ): array {
        try {
            if ($module !== self::MODULE_NAME) {
                $message = sprintf(
                    "Saving config is only allowed for module %s. Provided: %s",
                    self::MODULE_NAME,
                    $module
                );
                throw new LocalizedException(__($message));
            }

            switch ($path) {
                case Constants::XML_PATH_LIVE_INDEXING_ENABLED:
                    if (!in_array($value, self::ALLOWED_INDEXING_VALUES, true)) {
                        throw new LocalizedException(
                            __("Invalid value '%1' for %2. Allowed: 0 or 1.", $value, $path)
                        );
                    }
                    break;

                case Constants::XML_PATH_LIVE_INDEXING_SYNC_CRON_EXPR:
                    $this->validateCronExpression($value);
                    break;

                case Constants::XML_PATH_CONFIG_ENDPOINT:
                    $this->validateEndpoint($value);
                    break;

                case Constants::XML_PATH_LIVE_INDEXING_PER_MINUTE:
                case Constants::XML_PATH_LIVE_INDEXING_CHUNK_PER_SIZE:
                    $this->validateNumber($value);
                    break;

                case Constants::XML_PATH_CONFIG_SITE_ID:
                case Constants::XML_PATH_CONFIG_SHOP_DOMAIN:
                case Constants::XML_PATH_LIVE_INDEXING_TASK_PAYLOAD:
                case Constants::XML_PATH_CONFIG_FEED_ID:
                    $this->validateString($value);
                    break;

                case Constants::XML_PATH_CONFIG_SECRET_KEY:
                    $this->validateSecretKey($value);
                    $value = $this->encryptor->encrypt($value);
                    break;

                default:
                    $this->validateAthosCommercePath($path, $value);
                    throw new LocalizedException(
                        __(
                            "Invalid path '%1' found. Only athos module configuration allowed.",
                            $path
                        )
                    );
                    break;
            }

            // Save to core_config_data
            $this->configWriter->save(
                $path,
                $value,
                $scope,
                $scopeId
            );

            return [
                'data' => [
                    'success' => true,
                    'path' => $path,
                    'value' => $value,
                    'scope' => $scope,
                    'scope_id' => $scopeId,
                    'message' => 'Config saved successfully',
                ],
            ];
        } catch (LocalizedException $e) {
            // Only return the message
            return [
                'data' => [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * @param string $cronExpression
     *
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
            '/^(\*|([0-5]?\d)(\/\d+)?|\*\/\d+|([0-5]?\d-[0-5]?\d)|(,?[0-5]?\d)+)$/',
            // minute (0-59)
            '/^(\*|([01]?\d|2[0-3])(\/\d+)?|\*\/\d+|([01]?\d|2[0-3]-([01]?\d|2[0-3]))(,(?:[01]?\d|2[0-3]))*)$/',
            // hour (0-23)
            '/^(\*|([1-9]|[12]\d|3[01])(\/\d+)?|\*\/\d+|([1-9]|[12]\d|3[01]-([1-9]|[12]\d|3[01]))(,(?:[1-9]|[12]\d|3[01]))*)$/',
            // day of month (1-31)
            '/^(\*|(0?[1-9]|1[0-2])(\/\d+)?|\*\/\d+|(0?[1-9]|1[0-2]-(0?[1-9]|1[0-2]))(,(?:0?[1-9]|1[0-2]))*)$/',
            // month (1-12)
            '/^(\*|[0-6](\/\d+)?|\*\/\d+|([0-6]-[0-6])(,(?:[0-6]))*)$/',
            // day of week (0–6)
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

    /**
     * @param string|null $endpoint
     *
     * @return void
     */
    private function validateEndpoint(?string $endpoint): void
    {
        if (null === $endpoint) {
            return;
        }
        $urlToValidate = 'https://' . $endpoint;
        if (filter_var($urlToValidate, FILTER_VALIDATE_URL)) {
            return;
        }
        throw new LocalizedException(
            __(
                'Supplied Endpoint URl is invalid. Received %1, Valid format: live-indexing.com/api/custom/webhook/  : `https://` and `feedId` should not be included.',
                $endpoint,
            ),
        );
    }

    /**
     * @param string|null $endpoint
     *
     * @return void
     */
    private function validateNumber(?string $value): void
    {
        if (null === $value) {
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_INT)) {
            return;
        }
        throw new LocalizedException(
            __(
                'Supplied Value is invalid. Received %1',
                $value,
            ),
        );
    }

    /**
     * @param string|null $value
     *
     * @return void
     * @throws LocalizedException
     */
    private function validateBool(?string $value): void
    {
        if (null === $value) {
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_BOOL)) {
            return;
        }
        throw new LocalizedException(
            __(
                'Supplied value is invalid. Received %1',
                $value,
            ),
        );
    }

    /**
     * @param string|null $value
     *
     * @return void
     * @throws LocalizedException
     */
    private function validateSecretKey(?string $value): void
    {
        if (null === $value || trim($value) === '') {
            throw new LocalizedException(
                __('Secret Key cannot be empty.')
            );
        }

        //10 characters minimum, to avoid overly simple keys
        if (strlen($value) < 10) {
            throw new LocalizedException(
                __('Secret Key must be at least 10 characters long.')
            );
        }
    }

    /**
     * @param string|null $value
     *
     * @return void
     * @throws LocalizedException
     */
    private function validateString(?string $value): void
    {
        if (null === $value) {
            return;
        }

        if (is_string($value)) {
            return;
        }
        throw new LocalizedException(
            __(
                'Supplied Value is invalid. Received %1',
                $value,
            ),
        );
    }

    /**
     * @param string $path
     * @param string|null $value
     *
     * @return void
     * @throws LocalizedException
     */
    private function validateAthosCommercePath(string $path, ?string $value): void
    {
        $isConfigPath = $this->validateStringPrefix(
            $path,
            static::MODULE_PREFIX . 'configuration/'
        );
        $isIndexingPath = $this->validateStringPrefix(
            $path,
            static::MODULE_PREFIX . 'indexing/'
        );

        if (!$isConfigPath && !$isIndexingPath) {
            throw new LocalizedException(
                __(
                    "Invalid path '%1' found. Only '%s' module configuration allowed.",
                    $path,
                    static::MODULE_PREFIX
                )
            );
        }
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    private function validateStringPrefix(string $haystack, string $needle): bool
    {
        return $needle !== '' && substr($haystack, 0, strlen($needle)) === $needle;
    }
}
