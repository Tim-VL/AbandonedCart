import './page/cart-notification-detail';
import './page/cart-notification-list';
import './component/abandone-cart-item-grid';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('abandoned-cart-customer', {
    type: 'plugin',
    name: 'abandoned-cart-customer',
    title: 'abandoned-cart-customer.general.mainMenuItemGeneral',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#57D9A3',
    icon: 'regular-cog',
    
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'cart-notification-list',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index.plugins'
            }
        },

        detail: {
            component: 'cart-notification-detail',
            path: 'detail/:customerId',
            meta: {
                parentPath: 'abandoned.cart.customer.index'
            }
        }
    },

    settingsItem: [{
        name: 'abandoned-cart-customer',
        label: 'abandoned-cart-customer.general.mainMenuItemGeneral',
        to: 'abandoned.cart.customer.index',
        icon: 'regular-shopping-cart',
        group: 'plugins'
    }],

    navigation: [{
        id: 'abandoned-cart-customer',
        label: 'abandoned-cart-customer.general.mainMenuItemGeneral',
        parent: 'sw-order',
        path: 'abandoned.cart.customer.index',
        position: 100,
        icon: 'regular-shopping-cart'
    }]
})