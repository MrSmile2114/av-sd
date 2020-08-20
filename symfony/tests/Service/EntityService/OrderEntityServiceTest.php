<?php


namespace App\Tests\Service\EntityService;


use App\DataFixtures\OrdersFixture;
use App\Entity\Order;
use App\Service\EntityService\OrderEntityService;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Exception\InvalidParameterException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderEntityServiceTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var OrderEntityService
     */
    private $orderService;


    protected function setUp()
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->orderService = self::$container->get(OrderEntityService::class);
    }

    protected function tearDown(): void
    {
        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
        parent::tearDown();
    }

    public function testGetOrdersPageData()
    {
        $fixture = new OrdersFixture();
        /** @var Order[] $orders */
        $orders = $fixture->load($this->entityManager);

        $ordersData = $this->orderService->getOrdersPageData(2, 3, "additional", "asc_additional");

        $this->assertCount(3, $ordersData);

        $i = 3;
        foreach ($ordersData as $orderData) {
            $this->assertEquals($orders[$i]->getAdditional(), $orderData['additional']);
            $this->assertEquals($orders[$i]->getAddress(), $orderData['address']);
            $this->assertEquals($orders[$i]->getPrice(), $orderData['price']);
            $i++;
        }
    }

    public function testGetOrdersCount()
    {
        $fixture = new OrdersFixture();
        /** @var Order[] $orders */
        $orders = $fixture->load($this->entityManager);

        $this->assertCount($this->orderService->getOrdersCount(), $orders);
    }

    public function testGetEntityData()
    {
        $order = new Order();
        $order->setAdditional("Test additional");
        $order->setAddress("Test address");
        $order->setComposition("Test composition");
        $order->setLatitude("55.768922");
        $order->setLongitude("37.736982");
        $order->setPrice(mt_rand(10, 1000));
        $order->setStatus("delivered");

        $this->entityManager->persist($order);
        $this->entityManager->flush();
        $id = $order->getId();

        $data = $this->orderService->getEntityData($id, 'status', [], ['address', 'price']);
        $this->assertCount(2, $data);
        $this->assertEquals($order->getPrice(), $data['price']);
        $this->assertEquals($order->getAddress(), $data['address']);

        $this->entityManager->remove($order);
        $this->entityManager->flush();

        $data = $this->orderService->getEntityData($id);
        $this->assertNull($data);
    }

    public function testGetOrderData()
    {
        $order = new Order();
        $order->setAdditional("Test additional");
        $order->setAddress("Test address");
        $order->setComposition("Test composition");
        $order->setLatitude("55.768922");
        $order->setLongitude("37.736982");
        $order->setPrice(mt_rand(10, 1000));
        $order->setStatus("delivered");

        $this->entityManager->persist($order);
        $this->entityManager->flush();
        $id = $order->getId();

        $data = $this->orderService->getOrderData($id, 'additional, latitude, longitude');

        $this->assertEquals($order->getPrice(), $data['price']);
        $this->assertEquals($order->getAdditional(), $data['additional']);
        $this->assertEquals($order->getLatitude(), $data['latitude']);
        $this->assertEquals($order->getLongitude(), $data['longitude']);

        $this->entityManager->remove($order);
        $this->entityManager->flush();

        $data = $this->orderService->getOrderData($id,'');
        $this->assertNull($data);
    }

    /**
     * @dataProvider getInvalidData
     * @param $data
     */
    public function testCreateOrderInvalid($data)
    {
        $this->expectException(InvalidParameterException::class);
        $this->orderService->createOrder($data);
    }

    /**
     * @dataProvider getValidData
     * @param $data
     */
    public function testCreateOrderValid($data)
    {
        $orderData = $this->orderService->createOrder($data);

        $this->assertEquals($data['composition'], $orderData['composition']);
        $this->assertEquals($data['address'], $orderData['address']);
        $this->assertNotNull($orderData['price']);
    }

    /**
     * @dataProvider getOrderCriteriaData
     * @param $initData
     * @param $procData
     * @param $orderlyFields
     */
    public function testGetOrderCriteriaData($initData, $procData, $orderlyFields)
    {
        $this->assertEquals(
            $this->orderService->getOrderCriteria($initData, $orderlyFields, ['created' => 'desc']),
            $procData
        );
    }

    /**
     * Data providers
     */

    public function getInvalidData()
    {
        return [
            [
                [
                    "composition" => "TEST COMP",
                    "address" => "TEST ADDR TEST ADDR TEST ADDR TEST ADDR",
                    "latitude" => "58.99968792",
                    "longitude" => "37.913405",
                ],
            ],
            [
                [
                    "composition" => "TEST COMP",
                    "address" => "TEST ADDR TEST ADDR TEST ADDR TEST ADDR",
                    "latitude" => "56.99968792",
                    "longitude" => "35.913405",
                ],
            ],
        ];
    }

    public function getValidData()
    {
        return [
            [
                [
                    "composition" => "TEST COMP1",
                    "address" => "TEST ADDR TEST ADDR TEST ADDR TEST ADDR",
                    "latitude" => "55.85968792",
                    "longitude" => "37.713405",
                ],
            ],
            [
                [
                    "composition" => "TEST COMP2",
                    "address" => "TEST ADDR TEST ADDR TEST ADDR TEST ADDR",
                    "latitude" => "55.85968792",
                    "longitude" => "37.723405",
                ],
            ],
        ];
    }

    public function getOrderCriteriaData()
    {
        return [
            [
                'asc_name, dec_price, ASC(id), ASC_created',
                [
                    'name' => 'asc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'id', 'created', 'price'],
            ],
            [
                '',
                ['created' => 'desc'],
                [],
            ],
            [
                'asc_name, desc_price, ASC(id), ASC_created',
                ['created' => 'desc'],
                [],
            ],
            [
                'asc_name, desc_price, ASC(id), ASC_created',
                [
                    'name' => 'asc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'id', 'created'],
            ],
            [
                'asc_name, desc_price, ASC(id), ASC_created',
                [
                    'name' => 'asc',
                    'price' => 'desc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_name,desc_price,DESC(id),asc_created',
                [
                    'name' => 'asc',
                    'price' => 'desc',
                    'id' => 'desc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_name, desc_prrrice, ASC(id), asc_created',
                [
                    'name' => 'asc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_namedesc_priceASC(id)asc_created',
                [
                    'name' => 'asc',
                    'price' => 'desc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_name,asc(price),asc_id asc_created',
                [
                    'name' => 'asc',
                    'price' => 'asc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'DESC(name)DESC(price)ASC(id), asc_created',
                [
                    'name' => 'desc',
                    'price' => 'desc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_nafme, desc_price, ASC(id), asc__created',
                [
                    'price' => 'desc',
                    'id' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'dfgdfDdsasc_nameghdesc_fggfdesc_price, ASC(id), asc_created',
                [
                    'name' => 'asc',
                    'price' => 'desc',
                    'id' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_id, asc_price, asc_created',
                [
                    'id' => 'asc',
                    'price' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_fid, asc_ddprice, asc_jkcreated',
                [
                    'created' => 'desc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                '',
                [
                    'created' => 'desc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
            [
                'asc_id, asc_price, asc_created, asc_imgLinks, desc_imgLinksArr',
                [
                    'id' => 'asc',
                    'price' => 'asc',
                    'created' => 'asc',
                ],
                ['name', 'price', 'id', 'created'],
            ],
        ];
    }
}