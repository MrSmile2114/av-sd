<?php

namespace App\Tests\Service;

use App\Service\DeliveryPriceService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeliveryPriceServiceTest extends WebTestCase
{
    /**
     * @var DeliveryPriceService
     */
    private $deliveryCostService;

    public function setUp()
    {
        parent::setUp();

        self::bootKernel();
        $this->deliveryCostService = self::$container->get(DeliveryPriceService::class);
    }

    /**
     * @dataProvider getDeliveryRangeData
     *
     * @param $lat
     * @param $lon
     * @param $range
     */
    public function testGetDeliveryRange($lat, $lon, $range)
    {
        $calculatedRange = $this->deliveryCostService->getDeliveryRange($lat, $lon);
        $this->assertEqualsWithDelta($range, $calculatedRange, $range * 0.005); //permissible error 0.5%
    }

    /**
     * @dataProvider getDeliveryPriceData
     * @param $lat
     * @param $lon
     * @param $price
     */
    public function testGetDeliveryCost($lat, $lon, $price)
    {
        $calculatedPrice = $this->deliveryCostService->getDeliveryPrice($lat, $lon);
        $this->assertEquals($price, $calculatedPrice);
    }

    /**
     * Data Providers
     */

    public function getDeliveryRangeData()
    {
        return [
            [55.762922, 37.739982, 9666],
            [55.928350, 37.713401, 18400],
            [-5.811153, 31.669824, 6870000],
            [-5.154546, -79.266990, 12132020],
            [54.016658, -113.752391, 7530060],
            [DeliveryPriceService::START_POINT[0], DeliveryPriceService::START_POINT[1], 0],
        ];
    }


    public function getDeliveryPriceData()
    {
        return [
            [55.762922, 37.739982, 100],
            [55.928350, 37.713401, 200],
            [-5.811153, 31.669824, null],
            [-5.154546, -79.266990, null],
            [54.016658, -113.752391, null],

        ];
    }
}