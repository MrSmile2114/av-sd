<?php


namespace App\Service\EntityService;


use App\Entity\Order;

interface OrderServiceInterface
{
    /**
     * Return order data by id
     *
     * @param int $orderId              Order id in the system
     * @param string $optionalFields    String containing the names of the properties to be added to the return
     *
     * @return array|null               Returns a normalized entity, null if an entity with such orderId is not found
     */
    public function getOrderData(int $orderId, string $optionalFields): ?array;

    /**
     * Getting the count of Orders
     *
     * @param array|null $criteria
     * @return int                   The count of the entities that match the given criteria.
     */
    public function getOrdersCount(array $criteria = null): int;

    /**
     * Getting an array with data from several orders
     *
     * @param int|null $page            Entities page number.
     *                                  Parameter will have an effect only when used with not null $resOnPage
     * @param int|null $resOnPage       Number of entities on return.
     *                                  Parameter will have an effect only when used with not null $page
     * @param string $optionalFields    String containing the names of the properties to be added to the return
     * @param string|null $orderBy      String containing properties names with their sorting methods Ex: asc_id
     *
     * @return array                    Array of normalized entities
     */
    public function getOrdersPageData(
        int $page,
        int $resOnPage = null,
        string $optionalFields = '',
        string $orderBy = null
    ): array;

    /**
     * Create a delivery order
     *
     * @param array $data   Order data
     *
     * @return array
     */
    public function createOrder(array $data): array;
}