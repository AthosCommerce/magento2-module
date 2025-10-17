<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Model\Task\Validator;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Task\Validator\StoreValidator;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StoreValidatorTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var StoreValidator
     */
    private $storeValidator;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeValidator = $this->objectManager->get(StoreValidator::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/store.php
     */
    public function testValidate() : void
    {
        $payload = ['store' => 'test'];
        $result = $this->storeValidator->validate($payload);
        $this->assertEmpty($result->getErrors());
        $this->assertTrue($result->isValid());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testValidateWithEmptyStore() : void
    {
        $payload = [];
        $result = $this->storeValidator->validate($payload);
        $this->assertEmpty($result->getErrors());
        $this->assertTrue($result->isValid());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testValidateWithInvalidStoreCode() : void
    {
        $storeCode = 'test_invalid';
        $payload = ['store' => $storeCode];
        $result = $this->storeValidator->validate($payload);
        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
        $errors = $result->getErrors();
        $error = array_pop($errors);
        $this->assertEquals((string) __('Store "%1" doesn\'t exist', $storeCode), $error);
    }
    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/inactive_store.php
     */
    public function testValidateWithDeactivatedStoreCode() : void
    {
        $storeCode = 'inactive_store';
        $payload = ['store' => $storeCode];
        $result = $this->storeValidator->validate($payload);
        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
        $errors = $result->getErrors();
        $error = array_pop($errors);
        $this->assertEquals((string) __('Store "%1" is not active', $storeCode), $error);
    }
}
