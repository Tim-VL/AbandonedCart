<?php declare(strict_types=1);

namespace Swag\Abandoned\Controller\Storefront;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Swag\Abandoned\Service\AbandonedCartService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Cart;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class AbandonedCartStorefrontController extends StorefrontController
{
    /**
     * @var AbandonedCartService
     */
    protected $abandonedCartService;


    /**
     * @var CartService
     */
    protected $CartService;

    public function __construct(
        AbandonedCartService $abandonedCartService,
        CartService $CartService
        )
    {
        $this->abandonedCartService = $abandonedCartService;
        $this->CartService = $CartService;
    }

    #[Route(path: 'abandoned/cart/check-customer', name: 'abandoned.cart.check-customer', methods: ['POST', 'GET'])]
    public function checkCustomerCart(SalesChannelContext $context, Request $request, Cart $cart)
    {   
        if ($context->getCustomer()) {
            $this->abandonedCartService->updateCart($context->getToken(), $context->getCustomer()->getId(), $this->CartService, $context, $cart);
            return $this->redirectToRoute('frontend.checkout.confirm.page');
        } else {
            return $this->redirectToRoute('frontend.account.login.page', ['redirectTo' => 'abandoned.cart.check-customer']);
        }
    }
}