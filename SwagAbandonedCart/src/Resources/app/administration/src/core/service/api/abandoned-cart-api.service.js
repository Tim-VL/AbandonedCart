const { ApiService } = Shopware.Classes;

class AbandonedCartApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'abandoned-cart') {
        super(httpClient, loginService, apiEndpoint);
    }

    saveConfig(config, salesChannelId = null) {
        let apiRoute = `${this.getApiBasePath()}/config`;

        if (salesChannelId) {
            apiRoute = `${apiRoute}/${salesChannelId}`;
        }

        return this.httpClient.post(
            apiRoute,
            {
                config
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then(ApiService.handleResponse);
    }

    getConfig(salesChannelId = null) {
        let apiRoute = `${this.getApiBasePath()}/config`;

        if (salesChannelId) {
            apiRoute = `${apiRoute}/${salesChannelId}`;
        }

        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then(ApiService.handleResponse);
    }

    removeFromNotificationList(customerId) {
        return this.httpClient.post(
            `${this.getApiBasePath()}/remove-notification`,
            {
                customerId
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then(ApiService.handleResponse);
    }

    bulkRemoveFromNotificationList(customerIds) {
        return this.httpClient.post(
            `${this.getApiBasePath()}/bulk-remove-notification`,
            {
                customerIds
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then(ApiService.handleResponse);
    }

    search(term, page, limit, sortBy = null, sortDirection = null) {
        return this.httpClient.post(
            `${this.getApiBasePath()}/customer`,
            {
                term,
                page,
                limit,
                sortBy,
                sortDirection
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then(ApiService.handleResponse);
    }

    getCustomer(customerId) {
        return this.httpClient.get(
            `${this.getApiBasePath()}/customer/${customerId}`,
            {
                headers: this.getBasicHeaders()
            }
        ).then(ApiService.handleResponse);
    }

    notify(customerId) {
        return this.httpClient.post(
            `${this.getApiBasePath()}/notify`,
            {
                customerId
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then(ApiService.handleResponse);
    }
}

export default AbandonedCartApiService;