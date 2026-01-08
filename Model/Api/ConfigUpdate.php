<?php

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\ConfigUpdateInterface;
use AthosCommerce\Feed\Api\Data\ConfigItemInterface;
use AthosCommerce\Feed\Api\Data\ConfigUpdateResponseInterface;
use AthosCommerce\Feed\Api\Data\ConfigUpdateResultInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Config\ConfigMap;
use AthosCommerce\Feed\Api\Data\ConfigUpdateResponseInterfaceFactory;
use AthosCommerce\Feed\Api\Data\ConfigUpdateResultInterfaceFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreRepository;

class ConfigUpdate implements ConfigUpdateInterface
{
    public const MODULE_PREFIX = 'athoscommerce/';

    public const ALLOWED_INDEXING_VALUES = ['0', '1'];

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
     * @var ConfigUpdateResponseInterfaceFactory
     */
    private $responseFactory;
    /**
     * @var ConfigUpdateResultInterfaceFactory
     */
    private $configUpdateResultFactory;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     * @param StoreRepositoryInterface $storeRepository
     * @param ConfigUpdateResponseInterfaceFactory $responseFactory
     * @param ConfigUpdateResultInterfaceFactory $configUpdateResultFactory
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        WriterInterface                      $configWriter,
        EncryptorInterface                   $encryptor,
        StoreRepositoryInterface             $storeRepository,
        ConfigUpdateResponseInterfaceFactory $responseFactory,
        ConfigUpdateResultInterfaceFactory   $configUpdateResultFactory,
        AthosCommerceLogger                  $logger,
    )
    {
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
        $this->storeRepository = $storeRepository;
        $this->responseFactory = $responseFactory;
        $this->configUpdateResultFactory = $configUpdateResultFactory;
        $this->logger = $logger;
    }

    /**
     * @param ConfigItemInterface $payload
     * @return ConfigUpdateResponseInterface
     * @throws LocalizedException
     */
    public function update(
        \AthosCommerce\Feed\Api\Data\ConfigItemInterface $payload
    ): ConfigUpdateResponseInterface
    {
        /** @var ConfigUpdateResponseInterface $response */
        $response = $this->responseFactory->create();

        $storeCode = $payload->getStoreCode();
        if (empty($storeCode)) {
            throw new LocalizedException(__('storeCode is required'));
        }

        try {
            $store = $this->storeRepository->get($storeCode);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            throw new LocalizedException(__('Invalid storeCode provided'));
        }

        $scope = 'stores';
        $scopeId = (int)$store->getId();

        $results = [];
        $taskPayload = [];

        $data = $payload->toArray();
        foreach (ConfigMap::MAP as $requestKey => $config) {

            if (!array_key_exists($requestKey, $data)) {
                $this->logger->debug(
                    '[ConfigUpdateAPI]: Missing Key',
                    [
                        'storeCode' => $storeCode,
                        'requestKey' => $requestKey,
                        'data' => $data
                    ]
                );
                continue;
            }

            $value = $data[$requestKey];
            if ($value === null) {
                continue;
            }
            if (is_array($value) && $value === []) {
                continue;
            }
            if (is_string($value) && trim($value) === '') {
                continue;
            }
            $path = $config['path'] ?? '';

            /** @var ConfigUpdateResultInterface $configUpdateResultRow */
            $configUpdateResultRow = $this->configUpdateResultFactory->create();

            try {
                $this->validateAthosCommercePath($path, $value);

                switch ($config['validator']) {
                    case 'validateBoolean':
                        if (!in_array($value, [false, true, 0, 1, "0", '1'], true)) {
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

                    case 'validatePositiveInt':
                        $this->validatePositiveInt($value);
                        break;

                    case 'validateNullableInt':
                        $this->validateNullableInt($value);
                        break;

                    case 'validateStringArray':
                        $this->validateStringArray($value);
                        break;

                    case 'validateIntegerArray':
                        $this->validateIntegerArray($value);
                        break;

                    default:
                        throw new LocalizedException(
                            __(
                                sprintf(
                                    'Unknown validator type (%s)',
                                    $config['validator']
                                )
                            )
                        );
                        break;
                }


                if (!empty($config['group']) && $config['group'] === 'taskPayload') {
                    $taskPayload[$requestKey] = $value;
                } elseif (!empty($config['path'])) {

                    if (!empty($config['encrypt'])) {
                        $value = $this->encryptor->encrypt($value);
                    }

                    $this->configWriter->save(
                        $config['path'],
                        $value,
                        $scope,
                        $scopeId
                    );
                }

                $configUpdateResultRow->setKey($requestKey);
                $configUpdateResultRow->setSuccess(true);
                $configUpdateResultRow->setMessage(__('Config updated successfully.')->render());

            } catch (LocalizedException $e) {
                $configUpdateResultRow->setKey($requestKey);
                $configUpdateResultRow->setSuccess(false);
                $configUpdateResultRow->setMessage($e->getMessage());
            }
            $results[] = $configUpdateResultRow;
            $this->logger->debug(
                sprintf('[ConfigUpdateAPI] Processed config key: %s', $requestKey),
                [
                    'storeCode' => $storeCode,
                    'config' => $config,
                    'result' => $configUpdateResultRow->toArray(),
                    'data' => $data
                ]
            );
        }

        $taskPayload = array_filter(
            $taskPayload,
            static function ($value) {
                if ($value === null) {
                    return false;
                }
                if (is_array($value) && $value === []) {
                    return false;
                }
                if (is_string($value) && trim($value) === '') {
                    return false;
                }
                return true;
            }
        );

        if ($taskPayload !== []) {
            $this->logger->debug(
                '[ConfigUpdateAPI] Task Payload: ',
                [
                    'storeCode' => $storeCode,
                    'taskPayload' => $taskPayload,
                    'data' => $data
                ]
            );
            $this->configWriter->save(
                Constants::XML_PATH_LIVE_INDEXING_TASK_PAYLOAD,
                json_encode($taskPayload, JSON_THROW_ON_ERROR),
                $scope,
                $scopeId
            );
        }

        $response->setSuccess(true);
        $response->setStoreCode($storeCode);
        $response->setCount(count($results));
        $response->setMessage(
            __('Config updated successfully. Flush the Magento config relevant cache if required.')->render()
        );
        $response->setResults($results);
        $this->logger->info(
            '[ConfigUpdateAPI] Config updated successfully',
            [
                'storeCode' => $storeCode,
                'results' => $results,
                'data' => $data
            ],
        );
        return $response;
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
                'Supplied Endpoint URl is invalid. Received %1',
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
     * @param $value
     *
     * @return void
     * @throws LocalizedException
     */
    private function validateAthosCommercePath(string $path, $value): void
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
                __("Invalid path " . $path . " found. Only " . static::MODULE_PREFIX . " module configuration allowed.")
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

    /**
     * @param $value
     * @return void
     * @throws LocalizedException
     */
    private function validatePositiveInt($value): void
    {
        if (!is_numeric($value) || (int)$value <= 0) {
            throw new LocalizedException(__('Value must be a positive integer'));
        }
    }

    /**
     * @param $value
     * @return void
     * @throws LocalizedException
     */
    private function validateNullableInt($value): void
    {
        if ($value === null) {
            return;
        }
        if (!is_numeric($value)) {
            throw new LocalizedException(__('Value must be an integer or null'));
        }
    }

    /**
     * @param $value
     * @return void
     * @throws LocalizedException
     */
    private function validateStringArray($value): void
    {
        if (!is_array($value)) {
            throw new LocalizedException(__('Value must be an array'));
        }

        foreach ($value as $item) {
            if (!is_string($item)) {
                throw new LocalizedException(__('Array values must be strings'));
            }
        }
    }

    /**
     * Validate integer array
     *
     * @param mixed $value
     * @throws LocalizedException
     */
    private function validateIntegerArray($value): void
    {
        if (!is_array($value)) {
            throw new LocalizedException(__('Value must be an array'));
        }

        foreach ($value as $item) {
            if (is_array($item) || is_object($item)) {
                throw new LocalizedException(__('Array must contain only integers'));
            }

            if (!is_numeric($item) || (int)$item != $item) {
                throw new LocalizedException(
                    __('Array must contain only integer values')
                );
            }

            if ((int)$item <= 0) {
                throw new LocalizedException(
                    __('Product IDs must be greater than zero')
                );
            }
        }
    }
}
