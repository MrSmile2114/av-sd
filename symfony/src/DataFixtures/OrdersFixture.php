<?php

namespace App\DataFixtures;

use App\Entity\Order;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OrdersFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $orders = $manager->getRepository(Order::class)->findAll();
        foreach ($orders as $order){
            $manager->remove($order);
        }
        $manager->flush();

        $orders = [];
        for ($i = 0; $i < 50; $i++) {
            $order = new Order();
            $order->setAdditional("Test additional".$i);
            $order->setAddress("Test address".$i);
            $order->setComposition("Test composition");
            $order->setLatitude("55.76".$i."922");
            $order->setLongitude("37.73".$i."982");
            $order->setPrice(mt_rand(10,1000));
            $order->setStatus("processing");
            $manager->persist($order);
            $orders[$i] = $order;
        }

        $manager->flush();

        return $orders;
    }
}
