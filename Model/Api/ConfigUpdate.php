<?php

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\ConfigUpdateInterface;
use AthosCommerce\Feed\Api\Data\ConfigItemInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\Config\ConfigMap;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreRepository;

class ConfigUpdate implements ConfigUpdateInterface
{
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
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        WriterInterface          $configWriter,
        EncryptorInterface       $encryptor,
        StoreRepositoryInterface $storeRepository
    )
    {
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
        $this->storeRepository = $storeRepository;
    }

    /**
     * @param \AthosCommerce\Feed\Api\Data\ConfigItemInterface[] $payload
     * @return array
     * @throws LocalizedException
     */
    public function update(
        \AthosCommerce\Feed\Api\Data\ConfigItemInterface $payload
    ): array
    {
        if (empty($payload->getStoreCode())) {
            throw new LocalizedException(__('storeCode is required'));
        }

        try {
            $store = $this->storeRepository->get($payload->getStoreCode());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            throw new LocalizedException(__('Invalid storeCode provided'));
        }

        $scope = 'stores';
        $scopeId = (int)$store->getId();

        $results = [];

        $data = $payload->toArray();
        foreach (ConfigMap::MAP as $requestKey => $config) {
            //Only allowed
            if (!array_key_exists($requestKey, $data)) {
                continue;
            }

            $value = $data[$requestKey];
            if ($value === null) {
                continue;
            }
            $path = $config['path'];

            try {
                $this->validateAthosCommercePath($path, $value);

                switch ($config['validator']) {
                    case 'validateBoolean':
                        if (!in_array($value, [0, 1, "0", '1'], true)) {
                            throw new LocalizedException(__('Invalid boolean value'));
                        }
                        break;

                    case 'validateCron':
                        $this->validateCronExpression($value);
                        break;

                    case 'validateEndpoint':
                        $this->validateEndpoint($value);
                        break;

                    case 'validateNumber':
                        $this->validateNumber($value);
                        break;

                    case 'validateString':
                        $this->validateString($value);
                        break;

                    case 'validateSecretKey':
                        $this->validateSecretKey($value);
                        break;
                }

                if (!empty($config['encrypt'])) {
                    $value = $this->encryptor->encrypt($value);
                }

                $this->configWriter->save(
                    $path,
                    $value,
                    $scope,
                    $scopeId
                );

                $results[] = [
                    'key' => $requestKey,
                    'success' => true
                ];

            } catch (LocalizedException $e) {
                $results[] = [
                    'key' => $requestKey,
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return [
            'data' => [
                'success' => true,
                'message' => __('Config updated successfully. Flush the Magento cache if required.'),
                'storeCode' => $payload['storeCode'],
                'count' => count($results),
                'results' => $results,
            ]
        ];
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
     * @throws LocalizedException
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
                'Supplied Endpoint URl is invalid. Received %1, `https://` and `feedId` should not be included.',
                $endpoint,
            ),
        );
    }

    /**
     * @param string|null $value
     * @return void
     * @throws LocalizedException
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
        $isDeveloperPath = $this->validateStringPrefix(
            $path,
            static::MODULE_PREFIX . 'developer/'
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
