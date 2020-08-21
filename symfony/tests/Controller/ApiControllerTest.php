<?php


namespace App\Tests\Controller;


use App\DataFixtures\OrdersFixture;
use App\Entity\Order;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
    private $client;

    /**
     * @var EntityManager
     */
    private $entityManager;

    protected function setUp()
    {
        $this->client = static::createClient();
        $container = self::$container;
        $this->entityManager = $container
            ->get('doctrine')
            ->getManager();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
        parent::tearDown();
    }

    public function testRedirectToDoc()
    {
        $this->client->request('GET', '/api');

        $this->assertResponseRedirects();
    }


    /**
     * @dataProvider getDeliveryCostData
     * @param $latitude
     * @param $longitude
     * @param $expectedCode
     */
    public function testGetDeliveryPrice($latitude, $longitude, $expectedCode)
    {
        $this->client->request('GET', '/api/delivery_price?latitude='.$latitude.'&longitude='.$longitude);

        $this->assertResponseStatusCodeSame($expectedCode);
    }

    /**
     * @dataProvider getCreateOrderData
     * @param $data
     * @param $expectedCode
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testCreateOrder($data, $expectedCode)
    {
        $this->client->request(
            'POST',
            '/api/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
        $resp = $this->client->getResponse();
        $this->assertResponseStatusCodeSame($expectedCode);

        if ($expectedCode === 200) {
            $jsonData = json_decode($resp->getContent(), true);

            $this->assertEquals($data['composition'], $jsonData['order']['composition']);
            $this->assertEquals($data['address'], $jsonData['order']['address']);
            $this->assertEquals('processing', $jsonData['order']['status']);
            $this->assertNotNull($jsonData['order']['price']);

            /** @var Order $order */
            $order = $this->entityManager->getRepository(Order::class)->find($jsonData['order']['id']);
            $this->assertNotNull($order);
            $this->entityManager->remove($order);
            $this->entityManager->flush();
        }
    }


    public function testGetOrder()
    {
        $data = [
            'composition' => 'TEST COMP!',
            'address' => 'TEST ADDR TEST ADDR TEST ADDR TEST ADDR',
            'latitude' => '55.85968795',
            'longitude' => '37.713406',
        ];

        $this->client->request(
            'POST',
            '/api/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
        $resp = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(200);
        $jsonDataCreate = json_decode($resp->getContent(), true);

        $this->client->request('GET', '/api/order/'.$jsonDataCreate['order']['id']);
        $resp = $this->client->getResponse();
        $jsonDataGet = json_decode($resp->getContent(), true);

        $this->assertSame($jsonDataCreate['order'], $jsonDataGet['order']);

        /** @var Order $order */
        $order = $this->entityManager->getRepository(Order::class)->find($jsonDataGet['order']['id']);
        $this->assertNotNull($order);
        $this->entityManager->remove($order);
        $this->entityManager->flush();
    }

    /**
     * @dataProvider getGetOrderInvalidData
     * @param $id
     * @param $expectedCode
     */
    public function testGetOrderInvalid($id, $expectedCode)
    {
        $this->client->request('GET', '/api/order/'.$id);
        $this->assertResponseStatusCodeSame($expectedCode);
    }

    public function testGetOrdersPageData()
    {
        $fixture = new OrdersFixture();
        /** @var Order[] $orders */
        $orders = $fixture->load($this->entityManager);

        $this->client->request(
            'GET',
            '/api/orders?page=2&resOnPage=3&fields=additional&orderBy=asc_additional'
        );
        $resp = $this->client->getResponse();
        $ordersData = json_decode($resp->getContent(), true)['orders'];
        $this->assertCount(3, $ordersData);

        $i = 3;
        foreach ($ordersData as $orderData) {
            $this->assertEquals($orders[$i]->getAdditional(), $orderData['additional']);
            $this->assertEquals($orders[$i]->getAddress(), $orderData['address']);
            $this->assertEquals($orders[$i]->getPrice(), $orderData['price']);
            $i++;
        }
    }

    /**
     * @dataProvider getInvalidPage
     * @param $page
     * @param $resOnPage
     * @param $expectedCode
     */
    public function testGetOrdersPageInvalid($page, $resOnPage, $expectedCode)
    {
        $fixture = new OrdersFixture();
        /** @var Order[] $orders */
        $orders = $fixture->load($this->entityManager);

        $this->client->request(
            'GET',
            '/api/orders?page='.$page.'&resOnPage='.$resOnPage.'&fields=additional&orderBy=asc_additional'
        );
        $resp = $this->client->getResponse();
        $this->assertResponseStatusCodeSame($expectedCode);
        if($expectedCode === 200){
            $respData = json_decode($resp->getContent(), true);
            $this->assertEquals(1, $respData['page']);
        }


    }


    /**
     * Data Providers
     */

    public function getDeliveryCostData()
    {
        return [
            ['55.77868792', '37.713401', 200],
            ['50.77868792', '37.713401', 210],
            ['55.77868792', '30.713401', 210],
            ['-55.77868792', '-37.713401', 210],
            ['90.77868792', '37.713401', 400],
            ['55.77868792', '181.713401', 400],
            ['-90.77868792', '-181.713401', 400],
            ['55.77868792', '-181.713401', 400],
        ];
    }

    public function getCreateOrderData()
    {
        return [
            [
                [
                    'composition' => 'TEST COMP123',
                    'address' => 'TEST ADDR TEST ADDR TEST ADDR TEST ADDRR',
                    'latitude' => '55.85968792',
                    'longitude' => '37.713405',
                ],
                200,
            ],
            [
                [
                    'composition' => 'TEST COMP123',
                    'address' => 'TEST ADDR TEST ADDR TEST ADDR TEST ADDRR',
                    'latitude' => '57.85968792',
                    'longitude' => '37.713405',
                ],
                400,
            ],
            [
                [
                    'composition' => '',
                    'address' => 'TEST ADDR TEST ADDR TEST ADDR TEST ADDRR',
                    'latitude' => '55.85968792',
                    'longitude' => '37.713405',
                ],
                400,
            ],
            [
                [
                    'composition' => 'TEST COMP123',
                    'address' => '',
                    'latitude' => '55.85968792',
                    'longitude' => '37.713405',
                ],
                400,
            ],
            [
                [
                    'composition' => 'TEST COMP123',
                    'address' => 'TEST ADDR TEST ADDR TEST ADDR TEST ADDRR',
                    'latitude' => '',
                    'longitude' => '37.713405',
                ],
                400,
            ],
            [
                [
                    'composition' => 'TEST COMP123',
                    'address' => 'TEST ADDR TEST ADDR TEST ADDR TEST ADDRR',
                    'latitude' => '55.85968792',
                    'longitude' => '',
                ],
                400,
            ],
            [
                [
                    'composition' => 'TEST COMP123',
                    'address' => 'TEST ADDR TEST ADDR TEST ADDR TEST ADDRR',
                    'latitude' => '5585968792',
                    'longitude' => '37713405',
                ],
                400,
            ],
            [
                [
                    'composition' => 'TEST COMP123',
                    'address' => 'TEST ADDR TEST ADDR TEST ADDR TEST ADDRR',
                    'latitude' => '-55.85968792',
                    'longitude' => '37.713405',
                ],
                400,
            ],
        ];
    }

    public function getGetOrderInvalidData()
    {
        return [
            ['0', 404],
            ['-5', 404],
            ['sd', 404],
            [',', 404],
        ];
    }

    public function getInvalidPage()
    {
        return [
            ['0', '0', 200],
            ['-5', '5', 400],
            ['2', 'df', 400],
            ['6', '10', 200],
        ];
    }
}