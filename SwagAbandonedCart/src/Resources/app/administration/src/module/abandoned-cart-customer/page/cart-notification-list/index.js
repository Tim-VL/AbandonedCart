const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import './cart-notification-list.scss';
import template from './cart-notification-list.html.twig';

Component.register('cart-notification-list', {
    template,

    inject: ['repositoryFactory', 'AbandonedCartApiService'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('salutation'),
        Mixin.getByName('listing')
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            customers: [],
            total: 0,
            page: 1,
            limit: 25,
            isLoading: false,
            processSuccess: false,
            showKeywords: false,
            term: null,
            selectedItems: [],
            selection: {}, 
            showBulkDeleteModal: false,
            sortBy: 'dateAdded',
            sortDirection: 'DESC',
            isBulkDeleting: false
        }
    },

    computed: {
        columns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('abandoned-cart-customer.list.columnCustomer'),
                routerLink: 'abandoned.cart.customer.detail',
                allowResize: true,
                primary: true
            }, {
                property: 'cartDetails',
                dataIndex: 'cartDetails',
                label: this.$tc('abandoned-cart-customer.list.columnCartDetails'),
                allowResize: true,
                sortable: false
            }, {
                property: 'email',
                dataIndex: 'email',
                label: this.$tc('abandoned-cart-customer.list.columnEmail'),
                allowResize: true,
                primary: true
            }, {
                property: 'salesChannel',
                dataIndex: 'salesChannel',
                label: this.$tc('abandoned-cart-customer.list.columnSalesChannel'),
                allowResize: true,
                sortable: false
            }, {
                property: 'dateAdded',
                dataIndex: 'dateAdded',
                label: this.$tc('abandoned-cart-customer.list.columnDateAdded'),
                allowResize: true,
                sortable: true
            }, {
                property: 'lastNotified',
                dataIndex: 'lastNotified',
                label: this.$tc('abandoned-cart-customer.list.columnLastNotified'),
                allowResize: true,
                sortable: false
            }];
        },
        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
        selectedCount() {
            
            return Object.keys(this.selection).length;
        }
    },

    methods: {
        getList() {
            this.isLoading = true;
            this.AbandonedCartApiService.search(
                this.term,
                this.page,
                this.limit,
                this.sortBy,
                this.sortDirection
            ).then((response) => {
                
                this.customers = response.customers.map(customer => ({
                    ...customer,
                    id: customer.customerId 
                }));
                this.total = response.total;
                this.isLoading = false;
            }).catch((ex) => {
                this.isLoading = false;
            });
        },

        onClickDelete(customerId) {
            this.isLoading = true;
            this.AbandonedCartApiService.removeFromNotificationList(customerId).then((response) => {
                if (response) {
                    this.createNotificationSuccess({
                        title: this.$tc('abandoned-cart-customer.list.removeSuccessTitle'),
                        message: this.$tc('abandoned-cart-customer.list.removeSuccessMessage')
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('abandoned-cart-customer.list.removeErrorTitle'),
                        message: this.$tc('abandoned-cart-customer.list.removeErrorMessage')
                    });
                }
                this.getList();
            }).catch((ex) => {
                this.createNotificationError({
                    title: this.$tc('abandoned-cart-customer.list.removeErrorTitle'),
                    message: this.$tc('abandoned-cart-customer.list.removeErrorMessage')
                });
                this.getList();
            });
        },

        onSelectionChange(selection, selectionCount) {
            this.selection = selection;
            this.selectedItems = Object.values(selection);
        },

        onBulkDelete() {
            this.showBulkDeleteModal = true;
        },

        onCloseBulkDeleteModal() {
            this.showBulkDeleteModal = false;
        },

        onConfirmBulkDelete() {
            if (Object.keys(this.selection).length === 0) {
                this.onCloseBulkDeleteModal();
                return;
            }
        
            const customerIds = Object.values(this.selection).map(item => item.customerId);
            this.isBulkDeleting = true;
            this.showBulkDeleteModal = false;
        
            this.AbandonedCartApiService.bulkRemoveFromNotificationList(customerIds).then((response) => {
                if (response) {
                    this.createNotificationSuccess({
                        title: this.$tc('abandoned-cart-customer.list.bulkRemoveSuccessTitle'),
                        message: this.$tc('abandoned-cart-customer.list.bulkRemoveSuccessMessage', customerIds.length)
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('abandoned-cart-customer.list.bulkRemoveErrorTitle'),
                        message: this.$tc('abandoned-cart-customer.list.bulkRemoveErrorMessage')
                    });
                }
        
                // Clear selection and refresh list
                this.clearSelection();
                this.getList();
                
            }).catch((ex) => {
                this.createNotificationError({
                    title: this.$tc('abandoned-cart-customer.list.bulkRemoveErrorTitle'),
                    message: this.$tc('abandoned-cart-customer.list.bulkRemoveErrorMessage')
                });
                
                // Clear selection and refresh list even on error
                this.clearSelection();
                this.getList();
            }).finally(() => {
                this.isBulkDeleting = false;
                this.isLoading = false;
            });
        },
        
        clearSelection() {
            this.selection = {};
            this.selectedItems = [];
            
            // Clear the data grid's internal selection state
            if (this.$refs.dataGrid && this.$refs.dataGrid.resetSelection) {
                this.$refs.dataGrid.resetSelection();
            }
        },

        onClickNotify(customerId) {
            this.isLoading = true;
            this.AbandonedCartApiService.notify(customerId).then((response) => {
                if (response) {
                    this.createNotificationSuccess({
                        title: this.$tc('abandoned-cart-customer.list.successTitle'),
                        message: this.$tc('abandoned-cart-customer.list.successMessage')
                    });
                } else {
                    this.createNotificationWarning({
                        title: this.$tc('abandoned-cart-customer.detail.warningTitle'),
                        message: this.$tc('abandoned-cart-customer.detail.warningMessage')
                    });
                }
                this.getList();
            }).catch((ex) => {
                this.getList();
            });
        },

        onRefresh() {
            this.page = 1;
            this.limit = 25;
            this.selection = {};
            this.selectedItems = [];
            this.getList();
        },

        onSearch(term) {
            this.term = term;
            this.selection = {};
            this.selectedItems = [];
            this.getList();
        },

        onPageChange({ page, limit }) {
            this.page = page;
            this.limit = limit;
            this.selection = {};
            this.selectedItems = [];
            this.getList();
        },

        onSortColumn(column) {
            if (this.sortBy === column.dataIndex) {
                this.sortDirection = this.sortDirection === 'DESC' ? 'ASC' : 'DESC';
            } else {
               
                this.sortBy = column.dataIndex;
                this.sortDirection = 'DESC';
            }
            
            this.page = 1;
            this.getList();
        }
    },

    created() {
        this.getList();
    }
});
