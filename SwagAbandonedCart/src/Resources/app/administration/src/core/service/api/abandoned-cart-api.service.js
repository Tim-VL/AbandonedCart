const { ApiService}  = Shopware.Classes;

class AbandonedCartApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'abandoned-cart') {
        super(httpClient, loginService, apiEndpoint);
    }

    saveConfig(config, salesChannelId = null) {
        let apiRoute = `${this.getApiBasePath()}/config`;

        if (salesChannelId) {
            apiRoute = `${this.getApiBasePath()}/config/${salesChannelId}`;
        }
        return this.httpClient.post(
            apiRoute, {
                config: config
            }, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getConfig(salesChannelId = null) {
        let apiRoute = `${this.getApiBasePath()}/config`;

        if (salesChannelId) {
            apiRoute = `${this.getApiBasePath()}/config/${salesChannelId}`;
        }

        return this.httpClient.get(
            apiRoute, {}, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    //Removed the abandoned cart
    removeFromNotificationList(customerId) {
        let apiRoute = `${this.getApiBasePath()}/remove-notification`;
        return this.httpClient.post(
            apiRoute, {customerId: customerId}, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    // Bulk Delete the abandoned carts
    bulkRemoveFromNotificationList(customerIds) {
        let apiRoute = `${this.getApiBasePath()}/bulk-remove-notification`;
        return this.httpClient.post(
            apiRoute, {customerIds: customerIds}, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }


    search(term, page, limit, sortBy = null, sortDirection = null) {
        let apiRoute = `${this.getApiBasePath()}/customer`;
        return this.httpClient.post(
            apiRoute, {
                term: term, 
                page: page, 
                limit: limit,
                sortBy: sortBy,
                sortDirection: sortDirection
            }, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
    

    getCustomer(customerId) {
        let apiRoute = `${this.getApiBasePath()}/customer/` + customerId;
        return this.httpClient.get(
            apiRoute, {}, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    notify(customerId) {
        let apiRoute = `${this.getApiBasePath()}/notify`;
        return this.httpClient.post(
            apiRoute, {customerId: customerId}, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default AbandonedCartApiService;
