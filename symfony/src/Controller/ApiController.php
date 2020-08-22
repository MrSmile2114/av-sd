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
use Swagger\Annotations as SWG;
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
     * Redirect to documentation
     *
     * @Route("/api", name="api")
     */
    public function index()
    {
        return $this->redirect("/api/doc");
    }

    /**
     * Getting the shipping price
     *
     * @Rest\Get("/api/delivery_price")
     *
     * @Rest\QueryParam(
     *     name="latitude",
     *     requirements={@Constraints\NotNull(), @Constraints\NotBlank(), @GeoCoordinate(type="latitude")},
     *     allowBlank=false,
     *     strict=true)
     * @Rest\QueryParam(
     *     name="longitude",
     *     requirements={@Constraints\NotNull(), @Constraints\NotBlank(), @GeoCoordinate()},
     *     allowBlank=false,
     *     strict=true)
     *
     * @SWG\Parameter(
     *     name="latitude", in="query",
     *     description="delivery point latitude",
     *     required=true,
     *     type="number",
     *     @SWG\Schema(ref="#/definitions/Latitude"))
     * @SWG\Parameter(
     *     name="longitude", in="query",
     *     description="delivery point longitude",
     *     required=true,
     *     type="number",
     *     @SWG\Schema(ref="#/definitions/Longitude"))
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns delivery price to the point",
     *     @SWG\Schema(ref="#/definitions/GetPriceSucc"))
     * @SWG\Response(
     *     response=210,
     *     description="Delivery to this point is not possible",
     *     @SWG\Schema(ref="#/definitions/GetPriceNull"))
     * @SWG\Response(
     *     response=400,
     *     description="Error validating query parameters",
     *     @SWG\Schema(ref="#/definitions/ValidationError"))
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
        $price = $deliveryPriceService->getDeliveryPrice($latitude, $longitude);
        $code = (!is_null($price)) ? 200 : 210;

        return $this->view(['code' => $code, 'price' => $price], $code);
    }

    /**
     * Create a new delivery order
     *
     * @Rest\Post("/api/order", name="create_order")
     *
     * @Rest\RequestParam(
     *     name="composition",
     *     requirements={@Constraints\Length(min=1, max=600)},
     *     allowBlank=false,
     *     strict=true,
     *     nullable=false)
     * @Rest\RequestParam(
     *     name="address",
     *     requirements={@Constraints\Length(min=20, max=600)},
     *     allowBlank=false,
     *     strict=true,
     *     nullable=false,
     *     description="delivery address")
     * @Rest\RequestParam(
     *     name="latitude",
     *     requirements={@GeoCoordinate(type="latitude")},
     *     allowBlank=false,
     *     strict=true,
     *     description="delivery point latitude")
     * @Rest\RequestParam(
     *     name="longitude",
     *     requirements={@GeoCoordinate(type="longitude")},
     *     allowBlank=false,
     *     strict=true,
     *     description="delivery point longitude")
     * @Rest\RequestParam(
     *     name="additional",
     *     requirements={@Constraints\Length(min=0, max=600)},
     *     allowBlank=true,
     *     strict=true,
     *     nullable=true,
     *     description="additional information")
     *
     *
     * @SWG\Response(
     *     response=400,
     *     description="Error validating query parameters",
     *     @SWG\Schema(ref="#/definitions/ValidationError"))
     * @SWG\Response(
     *     response=200,
     *     description="Order successfully created",
     *     @SWG\Schema(ref="#/definitions/GetOrder"))
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
     * Get order information
     *
     * @Rest\Get("/api/order/{id}", name="get_order", requirements={"id"="\d+"})
     *
     * @Rest\QueryParam(
     *     name="fields",
     *     requirements={@Constraints\Length(min=0, max=255)},
     *     allowBlank=true,
     *     strict=true,
     *     nullable=true,
     *     description="Optional fields to display in the response",
     *     default="")
     *
     * @SWG\Parameter(
     *     name="id", in="path",
     *     description="order id",
     *     required=true,
     *     type="integer",
     *     @SWG\Schema(ref="#/definitions/Id"))
     * @SWG\Parameter(
     *     name="fields", in="query",
     *     description="order id",
     *     required=true,
     *     type="string",
     *     @SWG\Schema(ref="#/definitions/Fields"))
     *
     * @SWG\Response(
     *     response=200,
     *     description="Recieve order information",
     *     @SWG\Schema(ref="#/definitions/GetOrder"))
     * @SWG\Response(
     *     response=404,
     *     description="Order not found",
     *     @SWG\Schema(ref="#/definitions/GetOrderNotFound"))
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
     * Get a list of orders
     *
     * @Rest\Get("/api/orders", name="get_orders_page")
     *
     * @Rest\QueryParam(
     *     name="page",
     *     requirements="\d+",
     *     allowBlank=true,
     *     strict=true,
     *     nullable=false,
     *     description="page number",
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
     *     description="Optional fields to display in the response",
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
     * @SWG\Parameter(
     *     name="page", in="query",
     *     description="page number",
     *     required=false,
     *     type="integer")
     * @SWG\Parameter(
     *     name="resOnPage", in="query",
     *     description="number orders in one page",
     *     required=false,
     *     type="integer")
     * @SWG\Parameter(
     *     name="fields", in="query",
     *     description="order id",
     *     required=true,
     *     type="string",
     *     @SWG\Schema(ref="#/definitions/Fields"))
     *
     * @SWG\Response(
     *     response=200,
     *     description="Recieve orders information",
     *     @SWG\Schema(ref="#/definitions/GetOrderList"))
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

    /**
     * Update order status as delivered
     *
     * @Rest\Patch("/api/order/{id}/delivered", name="order_delivered", requirements={"id"="\d+"})
     *
     *
     * @SWG\Parameter(
     *     name="id", in="path",
     *     description="order id",
     *     required=true,
     *     type="integer",
     *     @SWG\Schema(ref="#/definitions/Id"))
     *
     * @SWG\Response(
     *     response=200,
     *     description="Status updated successfully",
     *     @SWG\Schema(ref="#/definitions/GetOrder"))
     * @SWG\Response(
     *     response=204,
     *     description="Order status is already delivered"
     *     )
     * @SWG\Response(
     *     response=404,
     *     description="Order not found",
     *     @SWG\Schema(ref="#/definitions/GetOrderNotFound"))
     *
     * @param int $id
     * @param OrderServiceInterface $orderService
     *
     * @return View
     */
    public function orderDelivered(int $id, OrderServiceInterface $orderService)
    {
        $orderData = $orderService->getOrderData($id, '');
        if (is_null($orderData)) {
            return $this->view(['code' => 404, 'message' => 'Order with this ID not found'], 404);
        }

        if ($orderData['status'] === 'delivered') {
            return $this->view([], 204);
        }

        $orderData = $orderService->updateOrder($id, ['status' => 'delivered']);

        return $this->view(['code' => 200, 'order' => $orderData]);
    }
}