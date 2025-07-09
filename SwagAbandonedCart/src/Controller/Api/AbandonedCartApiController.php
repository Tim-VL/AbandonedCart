<?php declare(strict_types=1);

namespace Swag\Abandoned\Controller\Api;

use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Swag\Abandoned\Service\AbandonedCartService;

#[Route(defaults: ['_routeScope' => ['api']])]
class AbandonedCartApiController extends AbstractController
{
    /**
     * @var SystemConfigService
     */
    protected $systemConfigService;

    /**
     * @var AbandonedCartService
     */
    protected $abandonedCartService;

    public function __construct(
        SystemConfigService $systemConfigService,
        AbandonedCartService $abandonedCartService
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->abandonedCartService = $abandonedCartService;
    }

    #[Route(path: '/api/abandoned-cart/config/{salesChannelId}', name: 'swag.abandoned-cart.config', methods: ['GET','POST'])]
    public function config(Request $request, RequestDataBag $data, $salesChannelId = null): JsonResponse
    {
        $config = null;
        
        if ($request->server->get('REQUEST_METHOD') == 'GET') {
            $config['autoMailNotification'] = $this->systemConfigService->get('SwagAbandonedCart.config.autoMailNotification', $salesChannelId);
            $config['mailMessage'] = $this->systemConfigService->get('SwagAbandonedCart.config.mailMessage', $salesChannelId);
            $config['mailSubject'] = $this->systemConfigService->get('SwagAbandonedCart.config.mailSubject', $salesChannelId);
            $config['notificationInterval'] = $this->systemConfigService->get('SwagAbandonedCart.config.notificationInterval', $salesChannelId);
            $config['enableMaxTimesNotification'] = $this->systemConfigService->get('SwagAbandonedCart.config.enableMaxTimesNotification', $salesChannelId) ?? true;
            $config['maxTimesNotification'] = $this->systemConfigService->get('SwagAbandonedCart.config.maxTimesNotification', $salesChannelId) ?? 3;
            $config['daysInCart'] = $this->systemConfigService->get('SwagAbandonedCart.config.daysInCart', $salesChannelId) ?? 5;
            $config['salesChannelId'] = $salesChannelId;
        } else {
            if ($data->get('config')) {
                foreach($data->get('config') as $key => $value) {
                    $this->systemConfigService->set('SwagAbandonedCart.config.' . $key, $value, $salesChannelId);
                }
            }
            $config = true;
        }
    
        return new JsonResponse($config);
    }


    #[Route(
        path: '/api/abandoned-cart/customer/{customerId}',
        name: 'swag.abandoned-cart.customer',
        methods: ['GET', 'POST'],
        defaults: ['auth_required' => false, 'customerId' => null]
    )]
    public function customer(Request $request, RequestDataBag $data, Context $context, $customerId): JsonResponse
    {

        if ($customerId && ($request->server->get('REQUEST_METHOD') == 'GET')) {
            [$customer, $cart, $currency] = $this->abandonedCartService->loadCustomerCart((string)$customerId, $context);

            $response = ['customer' => $customer, 'cart' => $cart, 'currency' => $currency];
        } else {
            [$total, $customers] = $this->abandonedCartService->loadCart($data, $context);

            $response = ['total' => $total, 'customers' => $customers];
        }
        return new JsonResponse($response);
    }

    #[Route(path: '/api/abandoned-cart/notify', name: 'swag.abandoned-cart.notify', methods: ['POST'])]
    public function notify(RequestDataBag $data, Context $context): JsonResponse
    {
        $response = $this->abandonedCartService->notifyCustomer($data->get('customerId'), false, $context);
        return new JsonResponse($response);
    }

    // Single Delete
    #[Route(path: '/api/abandoned-cart/remove-notification', name: 'swag.abandoned-cart.remove-notification', methods: ['POST'])]
    public function removeFromNotificationList(RequestDataBag $data, Context $context): JsonResponse
    {
        //delete the abandoned cart
        $response = $this->abandonedCartService->removeFromNotificationList($data->get('customerId'), $context);
        return new JsonResponse($response);
    }

    // Bulk Delete
    #[Route(path: '/api/abandoned-cart/bulk-remove-notification', name: 'swag.abandoned-cart.bulk-remove-notification', methods: ['POST'])]
    public function bulkRemoveFromNotificationList(RequestDataBag $data, Context $context): JsonResponse
    {
        $customerIds = $data->all('customerIds', []);
        
        $response = $this->abandonedCartService->bulkRemoveFromNotificationList($customerIds, $context);
        return new JsonResponse($response);
    }


}