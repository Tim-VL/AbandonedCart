<?php declare(strict_types=1);

namespace Swag\Abandoned\Core\Notification;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Checkout\Customer\CustomerEntity;

class CartNotificationEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $customerId;

    /**
     * @var CustomerEntity
     */
    protected $customer;

    /**
     * @var string
     */
    protected $cartItems;

    /**
     * @var int
     */
    protected $timesNotified;
    
    /**
     * @var \DateTimeInterface
     */
    protected $lastNotified;

    /**
     * @var \DateTimeInterface
     */
    protected $nextNotified;

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getcartItems(): string
    {
        return $this->cartItems;
    }

    public function setcartItems(string $cartItems): void
    {
        $this->cartItems = $cartItems;
    }

    public function getTimesNotified(): int
    {
        return $this->timesNotified;
    }

    public function setTimesNotified(int $timesNotified): void
    {
        $this->timesNotified = $timesNotified;
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }
    
    public function setLastNotified(\DateTimeInterface $lastNotified): void
    {
        $this->lastNotified = $lastNotified;
    }

    public function getLastNotified(): \DateTimeInterface
    {
        return $this->lastNotified;
    }

    public function setNextNotified(\DateTimeInterface $nextNotified): void
    {
        $this->nextNotified = $nextNotified;
    }

    public function getNextNotified(): \DateTimeInterface
    {
        return $this->nextNotified;
    }
}