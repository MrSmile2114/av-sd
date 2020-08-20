<?php

namespace App\Controller;

use App\Service\DeliveryPriceServiceInterface;
use App\Service\EntityService\OrderServiceInterface;
use App\Validator\Constraints\GeoCoordinate;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints;

/**
 * Class ApiController
 *
 * @RateLimit(limit=10, period=60)
 *
 * @package App\Controller
 */
final class ApiController extends AbstractFOSRestController
{
    /**
     * @Route("/api", name="api")
     */
    public function index()
    {
        return $this->redirect("/api/doc");
    }

    /**
     * Getting the shipping cost
     *
     * @Rest\Get("/api/delivery_price")
     *
     * @Rest\QueryParam(
     *     name="latitude",
     *     requirements={@Constraints\NotNull(), @Constraints\NotBlank(), @GeoCoordinate(type="latitude")},
     *     allowBlank=false,
     *     strict=true,
     *     description="")
     * @Rest\QueryParam(
     *     name="longitude",
     *     requirements={@Constraints\NotNull(), @Constraints\NotBlank(), @GeoCoordinate()},
     *     allowBlank=false,
     *     strict=true,
     *     description="")
     *
     *
     *
     * @param float $latitude
     * @param float $longitude
     * @param DeliveryPriceServiceInterface $deliveryPriceService
     *
     * @return View
     */
    public function getDeliveryCost(
        float $latitude,
        float $longitude,
        DeliveryPriceServiceInterface $deliveryPriceService
    ) {
        return $this->view(['code' => 200, 'price' => $deliveryPriceService->getDeliveryPrice($latitude, $longitude)]);
    }

    /**
     * @Rest\Post("/api/order", name="create_order")
     *
     * @Rest\RequestParam(
     *     name="composition",
     *     requirements={@Constraints\Length(min=1, max=600)},
     *     allowBlank=false,
     *     strict=true,
     *     nullable=false,
     *     description="")
     * @Rest\RequestParam(
     *     name="address",
     *     requirements={@Constraints\Length(min=20, max=600)},
     *     allowBlank=false,
     *     strict=true,
     *     nullable=false,
     *     description="")
     * @Rest\RequestParam(
     *     name="latitude",
     *     requirements={@GeoCoordinate(type="latitude")},
     *     allowBlank=false,
     *     strict=true,
     *     description="")
     * @Rest\RequestParam(
     *     name="longitude",
     *     requirements={@GeoCoordinate(type="longitude")},
     *     allowBlank=false,
     *     strict=true,
     *     description="")
     * @Rest\RequestParam(
     *     name="additional",
     *     requirements={@Constraints\Length(min=0, max=600)},
     *     allowBlank=true,
     *     strict=true,
     *     nullable=true,
     *     description="")
     *
     *
     * @param ParamFetcherInterface $paramFetcher
     * @param OrderServiceInterface $orderService
     *
     * @return View
     */
    public function createOrder(ParamFetcherInterface $paramFetcher, OrderServiceInterface $orderService)
    {
        $orderData = $orderService->createOrder($paramFetcher->all());

        return $this->view(['code' => 200, 'order' => $orderData]);
    }

    /**
     * @Rest\Get("/api/order/{id}", name="get_order", requirements={"id"="\d+"})
     *
     * @Rest\QueryParam(
     *     name="fields",
     *     requirements={@Constraints\Length(min=0, max=255)},
     *     allowBlank=true,
     *     strict=true,
     *     nullable=true,
     *     description="",
     *     default="")
     *
     * @param int $id
     * @param string $fields
     * @param OrderServiceInterface $orderService
     * @return View
     *
     */
    public function getOrder(int $id, string $fields, OrderServiceInterface $orderService)
    {
        $orderData = $orderService->getOrderData($id, $fields);
        if (is_null($orderData)) {
            return $this->view(['code' => 404, 'message' => 'Order with this ID not found'], 404);
        }

        return $this->view(['code' => 200, 'order' => $orderData]);
    }

    /**
     * @Rest\Get("/api/orders", name="get_orders_page")
     *
     *
     * @Rest\QueryParam(
     *     name="page",
     *     requirements="\d+",
     *     allowBlank=true,
     *     strict=true,
     *     nullable=false,
     *     description="",
     *     default="1")
     * @Rest\QueryParam(
     *     name="resOnPage",
     *     requirements="\d+",
     *     allowBlank=true,
     *     strict=true,
     *     nullable=false,
     *     description="",
     *     default="10")
     * @Rest\QueryParam(
     *     name="fields",
     *     requirements={@Constraints\Length(min=0, max=255)},
     *     allowBlank=true,
     *     strict=true,
     *     nullable=true,
     *     description="",
     *     default="")
     * @Rest\QueryParam(
     *     name="orderBy",
     *     requirements={@Constraints\Length(min=0, max=255)},
     *     allowBlank=true,
     *     strict=true,
     *     nullable=true,
     *     description="",
     *     default="desc_id")
     *
     * @param int $page
     * @param int $resOnPage
     * @param string $fields
     * @param string $orderBy
     * @param OrderServiceInterface $orderService
     *
     * @return View
     */
    public function getOrdersPage(
        int $page,
        int $resOnPage,
        string $fields,
        string $orderBy,
        OrderServiceInterface $orderService
    ) {
        $ordersCount = $orderService->getOrdersCount();
        if (($page === 0) or (($page - 1) * $resOnPage >= $ordersCount)) {
            $page = 1;
        }
        if ($resOnPage === 0) {
            $resOnPage = 10;
        }
        $ordersData = $orderService->getOrdersPageData($page, $resOnPage, $fields, $orderBy);

        return $this->view(
            [
                'code' => 200,
                'page' => $page,
                'nextPageExists' => ($page * $resOnPage < $ordersCount),
                'orders' => $ordersData,
            ]
        );
    }
}