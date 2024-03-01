<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Catalog\Api;

use Magento\Catalog\Model\ProductRepository;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\Framework\Webapi\Exception as HTTPExceptionCodes;
use Magento\Catalog\Model\ProductRepositoryFactory;

/**
 * BasePriceStorage test.
 */
class BasePriceStorageTest extends WebapiAbstract
{
    const SERVICE_NAME = 'catalogBasePriceStorageV1';
    const SERVICE_VERSION = 'V1';
    const SIMPLE_PRODUCT_SKU = 'simple';

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * @var ProductRepositoryFactory
     */
    private $repositoryFactory;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->repositoryFactory = Bootstrap::getObjectManager()->get(ProductRepositoryFactory::class);
    }

    /**
     * Test get method.
     *
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testGet()
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/products/base-prices-information',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Get',
            ],
        ];
        $response = $this->_webApiCall($serviceInfo, ['skus' => [self::SIMPLE_PRODUCT_SKU]]);
        $productRepository = $this->objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $productRepository->get(self::SIMPLE_PRODUCT_SKU);

        $this->assertNotEmpty($response);
        $this->assertEquals($product->getPrice(), $response[0]['price']);
        $this->assertEquals($product->getSku(), $response[0]['sku']);
    }

    /**
     * Test update method.
     *
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testUpdate()
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/products/base-prices',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Update',
            ],
        ];
        $newPrice = 9999;
        $storeId = 0;
        $response = $this->_webApiCall(
            $serviceInfo,
            [
                'prices' => [
                    [
                        'price' => $newPrice,
                        'store_id' => $storeId,
                        'sku' => self::SIMPLE_PRODUCT_SKU,
                    ]
                ]
            ]
        );
        $productRepository = $this->objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $productRepository->get(self::SIMPLE_PRODUCT_SKU);

        $this->assertEmpty($response);
        $this->assertEquals($product->getPrice(), $newPrice);
    }

    /**
     * Test update method call with invalid parameters.
     */
    public function testUpdateWithInvalidParameters()
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/products/base-prices',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Update',
            ],
        ];
        $newPrice = -9999;
        $storeId = 9999;
        $response = $this->_webApiCall(
            $serviceInfo,
            [
                'prices' => [
                    [
                        'sku' => 'not_existing_sku',
                        'price' => $newPrice,
                        'store_id' => $storeId,
                    ]
                ]
            ]
        );

        $expectedResponse = [
            0 => [
                'message' => 'Invalid attribute %fieldName = %fieldValue.',
                'parameters' => [
                    'SKU',
                    'not_existing_sku',
                ]
            ],
            1 => [
                'message' => 'Invalid attribute %fieldName = %fieldValue.',
                'parameters' => [
                    'Price',
                    '-9999',
                ]
            ],
            2 => [
                'message' =>
                    'Requested store is not found. Row ID: SKU = not_existing_sku, Store ID: 9999.',
                'parameters' => [
                    'not_existing_sku',
                    '9999'
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * Test update last updated at method.
     *
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testUpdateLastUpdatedAt()
    {
        /** @var ProductRepository $beforeProductRepository */
        $beforeProductRepository = $this->repositoryFactory->create();
        $beforeProductUpdatedAt = $beforeProductRepository->get(self::SIMPLE_PRODUCT_SKU)->getUpdatedAt();
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/products/base-prices?XDEBUG_SESSION_START=PHPSTORM',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Update',
            ],
        ];
        $newPrice = 9999;
        $storeId = 0;
        $response = $this->_webApiCall(
            $serviceInfo,
            [
                'prices' => [
                    [
                        'price' => $newPrice,
                        'store_id' => $storeId,
                        'sku' => self::SIMPLE_PRODUCT_SKU,
                    ]
                ]
            ]
        );
        $this->assertEmpty($response);

        /** @var ProductRepository $afterProductRepository */
        $afterProductRepository = $this->repositoryFactory->create();
        $afterProductUpdatedAt = $afterProductRepository->get(self::SIMPLE_PRODUCT_SKU)->getUpdatedAt();
        $this->assertTrue(strtotime($afterProductUpdatedAt) >= strtotime($beforeProductUpdatedAt));
    }
}
