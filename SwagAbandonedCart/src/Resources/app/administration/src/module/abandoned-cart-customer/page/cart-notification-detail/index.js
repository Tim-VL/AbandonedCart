import './cart-notification-detail.scss';
import template from './cart-notification-detail.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cart-notification-detail', {
    template,

    inject: ['repositoryFactory', 'AbandonedCartApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            salesChannelId: null,
            customer: null,
            cart: null,
            currency: null,
            isLoading: true,
            processSuccess: false
        };
    },

    computed: {
        sortedCalculatedTaxes() {
            return this.sortByTaxRate(this.cart.price.calculatedTaxes).filter(price => price.tax !== 0);
        },
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        }
    },

    methods: {
        getCustomer() {        
            this.isLoading = true;
            this.AbandonedCartApiService.getCustomer(this.$route.params.customerId).then((response) => {
                this.customer = response.customer;
                this.currency = response.currency;
                this.cart = response.cart[0];

                this.isLoading = false;
            }).catch((ex) => {
                this.isLoading = false;
            });
        },

        onClickNotify() {
            this.isLoading = true;
            this.AbandonedCartApiService.notify(this.$route.params.customerId).then((response) => {
                if (response) {
                    this.createNotificationSuccess({
                        title: this.$tc('abandoned-cart-customer.detail.successTitle'),
                        message: this.$tc('abandoned-cart-customer.detail.successMessage')
                    });
                } else {
                    this.createNotificationWarning({
                        title: this.$tc('abandoned-cart-customer.detail.warningTitle'),
                        message: this.$tc('abandoned-cart-customer.detail.warningMessage')
                    });
                }
                this.isLoading = false;
                this.getCustomer();
            }).catch((ex) => {
                this.isLoading = false;
                this.getCustomer();
            });
        },

        sortByTaxRate(price) {
            var res = price.sort((prev, current) => {
                return prev.taxRate - current.taxRate;
            });
            return res;
        },
    },

    created() {
        this.getCustomer();
    }
});
