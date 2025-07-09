
const { Component } = Shopware;

import './abandoned-cart-item-grid.scss';
import template from './abandoned-cart-item-grid.html.twig';

Component.register('abandoned-cart-item-grid', {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            isLoading: false,
            selectedItems: {}
        };
    },
    props: {
        cart: {
            type: Object,
            required: true
        },
        currency: {
            type: Object,
            required: true
        }
    },
    computed: {
        cartLineItems() {
            return this.cart.lineItems;
        },

        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        getLineItemColumns() {
            return [{
                property: 'label',
                dataIndex: 'label',
                label: this.$tc('abandoned-cart-customer.detail.columnItem'),
                allowResize: false,
                primary: true,
                inlineEdit: true,
                width: '200px'
            }, {
                property: 'price.unitPrice',
                dataIndex: 'price.unitPrice',
                label: this.$tc('abandoned-cart-customer.detail.columnNetPrice'),
                allowResize: false,
                align: 'right',
                inlineEdit: true,
                width: '120px'
            }, {
                property: 'quantity',
                dataIndex: 'quantity',
                label: this.$tc('abandoned-cart-customer.detail.columnQuantity'),
                allowResize: false,
                align: 'right',
                inlineEdit: true,
                width: '80px'
            }, {
                property: 'price.totalPrice',
                dataIndex: 'price.totalPrice',
                label: this.$tc('abandoned-cart-customer.detail.columnSubTotal'),
                allowResize: false,
                align: 'right',
                width: '80px'
            }, {
                property: 'price.taxRules[0]',
                label: this.$tc('sw-order.detailBase.columnTax'),
                allowResize: false,
                align: 'right',
                inlineEdit: true,
                width: '100px'
            }];
        }
    }
});
