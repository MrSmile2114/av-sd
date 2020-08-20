<?php


namespace App\Service;

/**
 * Interface DeliveryPriceServiceInterface
 *
 * @package App\Service
 */
interface DeliveryPriceServiceInterface
{
    /**
     * Starting point of delivery
     */
    const START_POINT = [55.77868792, 37.58800507];

    /**
     * Getting the distance between warehouse and point
     *
     * @param float $lat latitude
     * @param float $lon longitude
     *
     * @return float distance between warehouse and point
     */
    public function getDeliveryRange(float $lat, float $lon): float;

    /**
     * Shipping cost to the point. If delivery is not possible, return NULL.
     *
     * @param float $lat latitude
     * @param float $lon longitude
     *
     * @return float|null Shipping price to the point. If delivery is not possible, return NULL.
     */
    public function getDeliveryPrice(float $lat, float $lon): ?float;
}