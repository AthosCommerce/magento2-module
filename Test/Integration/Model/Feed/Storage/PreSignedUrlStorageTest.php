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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\Storage;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\MetadataInterface;
use AthosCommerce\Feed\Model\Feed\Specification\Feed;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\Feed\Storage\PreSignedUrlStorage;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PreSignedUrlStorageTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var PreSignedUrlStorage
     */
    private $preSignedUrlStorage;
    /**
     * @var SpecificationBuilderInterface
     */
    private $feedSpecificationBuilder;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->preSignedUrlStorage = $this->objectManager->get(PreSignedUrlStorage::class);
        $this->feedSpecificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws \Exception
     */
    public function testInitiate() : void
    {
        $feed = $this->getFeedSpecification();
        $this->preSignedUrlStorage->initiate($feed);
        // check that we achieve this place and dont have any exceptions
        $this->assertEquals(1,1);
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws \Exception
     */
    public function testInitiateWithEmptyFormat() : void
    {
        /** @var Feed $feed */
        $feed = $this->getFeedSpecification();
        $feed->setData(FeedSpecificationInterface::FORMAT, null);
        $this->expectExceptionObject(new Exception((string) __('format cannot be empty')));
        $this->preSignedUrlStorage->initiate($feed);
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws \Exception
     */
    public function testInitiateWithInvalidFormat() : void
    {
        /** @var Feed $feed */
        $feed = $this->getFeedSpecification();
        $format = '___test___';
        $feed->setData(FeedSpecificationInterface::FORMAT, $format);
        $this->expectExceptionObject(new Exception((string) __('%1 is not supported format', $format)));
        $this->preSignedUrlStorage->initiate($feed);
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws \Exception
     */
    public function testAddData() : void
    {
        $feed = $this->getFeedSpecification();
        $this->preSignedUrlStorage->initiate($feed);
        $data = $this->getData();
        $this->preSignedUrlStorage->addData($data);
        // check that we achieve this place and dont have any exceptions
        $this->assertEquals(1,1);
        $file = $this->preSignedUrlStorage->getFile();
        $file->commit();
        $path = $file->getAbsolutePath();
        $content = file_get_contents($path);
        $this->assertEquals(2, count(json_decode($content)));
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws \Exception
     */
    public function testAddDataToNotInitiatedStorage() : void
    {
        $data = $this->getData();
        $this->expectExceptionObject(new Exception('file is not initialized yet'));
        $this->preSignedUrlStorage->addData($data);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configure_aws_client_mock.php
     *
     * @return void
     * @throws \Exception
     */
    public function testCommit() : void
    {
        $feed = $this->getFeedSpecification();
        $this->preSignedUrlStorage->initiate($feed);
        $data = $this->getData();
        $this->preSignedUrlStorage->addData($data);
        $this->preSignedUrlStorage->commit();
        // check that file was deleted
        $this->assertEquals(1, count($this->preSignedUrlStorage->getAdditionalData()));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configure_aws_client_mock.php
     *
     * @return void
     * @throws \Exception
     */
    public function testCommitNotInitiatedStorage() : void
    {
        $this->expectExceptionObject(new Exception('file is not initialized yet'));
        $this->preSignedUrlStorage->commit();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configure_aws_client_mock.php
     *
     * @return void
     * @throws \Exception
     */
    public function testRollback() : void
    {
        $feed = $this->getFeedSpecification();
        $this->preSignedUrlStorage->initiate($feed);
        $data = $this->getData();
        $this->preSignedUrlStorage->addData($data);
        $this->preSignedUrlStorage->rollback();
        // check that file was deleted
        $this->assertEquals(1, count($this->preSignedUrlStorage->getAdditionalData()));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configure_aws_client_mock.php
     *
     * @return void
     * @throws \Exception
     */
    public function testRollbackNotInitiatedStorage() : void
    {
        $this->expectExceptionObject(new Exception('file is not initialized yet'));
        $this->preSignedUrlStorage->rollback();
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws \Exception
     */
    public function testGetAdditionalData() : void
    {
        $feed = $this->getFeedSpecification();
        $this->preSignedUrlStorage->initiate($feed);
        $data = $this->getData();
        $this->preSignedUrlStorage->addData($data);
        $this->assertGreaterThan(1, count($this->preSignedUrlStorage->getAdditionalData()));
    }

    /**
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws \Exception
     */
    public function testGetAdditionalDataNotInitiatedStorage() : void
    {
        $this->expectExceptionObject(new Exception('file is not initialized yet'));
        $this->preSignedUrlStorage->getAdditionalData();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configure_aws_client_mock.php
     *
     * @return void
     * @throws \Exception
     */
    public function testIsSupportedFormat() : void
    {
        $this->assertTrue($this->preSignedUrlStorage->isSupportedFormat(MetadataInterface::FORMAT_JSON));
        $this->assertFalse($this->preSignedUrlStorage->isSupportedFormat('___test____'));
    }

    /**
     * @return FeedSpecificationInterface
     */
    private function getFeedSpecification() : FeedSpecificationInterface
    {
        return $this->feedSpecificationBuilder->build(['preSignedUrl' => 'https://testurl.com', 'format' => MetadataInterface::FORMAT_JSON]);
    }

    /**
     * @return array
     */
    private function getData() : array
    {
        return [
            [
                'test_key' => 'Test Value'
            ],
            [
                'test_key' => 'Different Test Value'
            ]
        ];
    }
}
