<?php


namespace App\Service\EntityService;


use App\Entity\Order;
use App\Repository\OrderRepositoryInterface;
use App\Service\DeliveryPriceServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Exception\InvalidParameterException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class OrderEntityService
 * @package App\Service\EntityService
 */
final class OrderEntityService extends AbstractEntityService implements OrderServiceInterface
{
    private $defaultOrder = '-id';

    private $defaultRespFields = ['id', 'composition', 'address', 'price', 'status'];

    private $allowedOptRespFields = ['additional', 'latitude', 'longitude'];

    private $orderlyFields = ['id', 'price', 'address', 'status'];

    /**
     * @var DeliveryPriceServiceInterface
     */
    private $deliveryPriceService;

    /**
     * @var OrderRepositoryInterface
     */
    protected $objectRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        OrderRepositoryInterface $repository,
        SerializerInterface $serializer,
        DeliveryPriceServiceInterface $deliveryPriceService
    ) {
        $this->deliveryPriceService = $deliveryPriceService;
        parent::__construct($entityManager, $repository, $serializer);
    }

    /**
     * @inheritDoc
     */
    public function getOrderData(int $orderId, string $optionalFields): ?array
    {
        $order = $this->objectRepository->find($orderId);

        return (is_null($order))
            ? null
            : $this->normalizeEntity(
                $order,
                $optionalFields,
                $this->allowedOptRespFields,
                $this->defaultRespFields
            );
    }

    /**
     * @inheritDoc
     */
    public function getOrdersCount(array $criteria = null): int
    {
        return $this->count($criteria);
    }

    /**
     * @inheritDoc
     */
    public function getOrdersPageData(
        int $page,
        int $resOnPage = null,
        string $optionalFields = '',
        string $orderBy = null
    ): array {
        return $this->getEntitiesPageData(
            $page,
            $resOnPage,
            $optionalFields,
            $this->allowedOptRespFields,
            $this->defaultRespFields,
            $orderBy,
            $this->orderlyFields
        );
    }

    /**
     * @inheritDoc
     */
    public function createOrder(array $data): array
    {
        $price = $this->deliveryPriceService->getDeliveryPrice(
            floatval($data['latitude']),
            floatval($data['longitude'])
        );
        if (is_null($price)) {
            throw new InvalidParameterException('Delivery to this point is not possible.');
        }
        /** @var Order $order */
        $order = $this->serializer->denormalize($data, Order::class);
        $order->setPrice($price);
        $order->setStatus('processing');

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $this->normalizeEntity($order, '', [], $this->defaultRespFields);
    }
}