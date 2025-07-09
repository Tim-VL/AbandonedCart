<?php declare(strict_types=1);

namespace Swag\Abandoned\Subscriber;

use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Checkout\Cart\Event\CartChangedEvent;
use Shopware\Core\Checkout\Cart\Event\AfterLineItemAddedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\Event\AfterLineItemRemovedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;


class AbandonedCartEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityRepository
     */
    protected $cartNotificationRepository;

    /**
     * @var EntityRepository
     */
    protected $orderCustomerRepository;

    /**
     * @var EntityRepository
     */
    protected $wkCustomCartRepository;

    public function __construct(
        EntityRepository $cartNotificationRepository,
        EntityRepository $orderCustomerRepository,
        EntityRepository $wkCustomCartRepository
    ) {
        $this->cartNotificationRepository = $cartNotificationRepository;
        $this->orderCustomerRepository = $orderCustomerRepository;
        $this->wkCustomCartRepository = $wkCustomCartRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_WRITTEN_EVENT => 'updateAbandonedCart',
            CartChangedEvent::class => 'onCartChanged',
            AfterLineItemAddedEvent::class => 'onLineItemAdded',
            AfterLineItemRemovedEvent::class => 'onLineItemRemoved',
        ];
    }

    public function updateAbandonedCart(EntityWrittenEvent $event)
    {
        $results = $event->getWriteResults();
        if ($results) {
            foreach ($results as $result) {
                $payload = $result->getPayload();

                if (!empty($payload['id']) && isset($payload['id'])) {
                    $this->removeLastNotified($payload['id'], $event->getContext());
                }
            }
        }
    }

    private function removeLastNotified($orderId, $context): void
    {
        $orderCustomerEntitites = $this->orderCustomerRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('orderId', $orderId)),
            $context
        );

        if ($orderCustomerEntitites->getTotal()) {
            $customerId = $orderCustomerEntitites->first()->getCustomerId();

            $notificationEntities = $this->cartNotificationRepository->search(
                (new Criteria())->addFilter(new EqualsFilter('customerId', $customerId)),
                $context
            );

            if ($notificationEntities->getTotal() > 0) {
                $id = $notificationEntities->first()->getId();

                $this->cartNotificationRepository->delete([['id' => $id]], $context);
            }
        }
    }

    public function onCartChanged(CartChangedEvent $event): void
    {
        $this->updateOrCreateCustomCart($event->getCart(), $event->getContext());
    }

    public function onLineItemAdded(AfterLineItemAddedEvent $event): void
    {
        $this->updateOrCreateCustomCart($event->getCart(), $event->getSalesChannelContext());
    }

    public function onLineItemRemoved(AfterLineItemRemovedEvent $event): void
    {
        $this->updateOrCreateCustomCart($event->getCart(), $event->getSalesChannelContext(), true);
    }

    private function updateOrCreateCustomCart($cart, $context, $remove = false): void
    {
        $token = $cart->getToken();
        $customerId = $context->getCustomer() ? $context->getCustomer()->getId() : null;

        if ($customerId) {
            $lineItemCount = $cart->getLineItems()->count();
        
            $criteria = new Criteria();
            $criteria->addFilter(
                        new EqualsFilter('customerId', $customerId)
                    )->addFilter(new EqualsFilter('wildcard', $context->getToken()));
    
            $existingEntry = $this->wkCustomCartRepository->search($criteria, $context->getContext())->first();
            if ($lineItemCount === 0) {
                if ($existingEntry) {
                    $this->wkCustomCartRepository->delete([['id' => $existingEntry->getId()]], $context->getContext());
                }
                return;
            }
        
            $data = [
                'token' => $token,
                'cart' => [$cart],
                'price' => $cart->getPrice()->getTotalPrice(),
                'lineItemCount' => (string) $lineItemCount,
                'currencyId' => $context->getCurrency()->getId(),
                'shippingMethodId' => $context->getShippingMethod()->getId(),
                'paymentMethodId' => $context->getPaymentMethod()->getId(),
                'countryId' => $context->getShippingLocation()->getCountry()->getId(),
                'customerId' => $customerId,
                'salesChannelId' => $context->getSalesChannel()->getId(),
                'wildcard' => $context->getToken(),
            ];
        
            if ($existingEntry) {
                $data['id'] = $existingEntry->getId();
                $this->wkCustomCartRepository->update([$data], $context->getContext());
            } else {
                
                $this->wkCustomCartRepository->create([$data], $context->getContext());
            }
        }
    }
}