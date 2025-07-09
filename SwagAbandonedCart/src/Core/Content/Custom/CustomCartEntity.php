<?php declare(strict_types=1);

namespace Swag\Abandoned\Core\Content\Custom;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class CustomCartEntity extends Entity
{
    use EntityIdTrait;

    public $timestamps = false;

    protected ?string $token;

    protected ?string $name;

    

    protected ?float $price;
    
    protected ?string $lineItemCount;

    protected ?string $customerId;
    protected ?string $currencyId;
    protected ?string $shippingMethodId;
    protected ?string $paymentMethodId;
    protected ?string $countryId;
    protected ?string $salesChannelId;
    
    

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getLineItemCount(): ?string
    {
        return $this->lineItemCount;
    }

    public function setLineItemCount(?string $lineItemCount): void
    {
        $this->lineItemCount = $lineItemCount;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCurrencyId(): ?string
    {
        return $this->currencyId;
    }

    public function setCurrencyId(?string $currencyId): void
    {
        $this->currencyId = $currencyId;
    }

    public function getShippingMethodId(): ?string
    {
        return $this->shippingMethodId;
    }

    public function setShippingMethodId(?string $shippingMethodId): void
    {
        $this->shippingMethodId = $shippingMethodId;
    }

    public function getPaymentMethodId(): ?string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(?string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    public function getCountryId(): ?string
    {
        return $this->countryId;
    }

    public function setCountryId(?string $countryId): void
    {
        $this->countryId = $countryId;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }
}