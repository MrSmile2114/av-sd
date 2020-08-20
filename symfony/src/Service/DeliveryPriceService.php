<?php


namespace App\Service;


final class DeliveryPriceService implements DeliveryPriceServiceInterface
{

    /**
     * @var int[] key - distance, value - price for delivery to a distance not exceeding key.
     */
    private $prices = [10000 => 100, 20000 => 200, 30000 => 300];

    /**
     * @inheritDoc
     */
    public function getDeliveryRange(float $lat, float $lon): float
    {
        $earthRadius = 6371008;
        // convert from degrees to radians
        $latTo = deg2rad($lat);
        $lonTo = deg2rad($lon);
        $latFrom = deg2rad(self::START_POINT[0]);
        $lonFrom = deg2rad(self::START_POINT[1]);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(
                sqrt(
                    pow(sin($latDelta / 2), 2) +
                    cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
                )
            );

        return $angle * $earthRadius;
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryPrice(float $lat, float $lon): ?float
    {
        $range = $this->getDeliveryRange($lat, $lon);
        foreach ($this->prices as $maxRange => $price) {
            if($range < $maxRange){
                return $price;
            }
        }
        return null;
    }
}