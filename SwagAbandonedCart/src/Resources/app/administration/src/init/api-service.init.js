const { Application } = Shopware;
import AbandonedCartApiService from '../../src/core/service/api/abandoned-cart-api.service';

Application.addServiceProvider('AbandonedCartApiService', (container) => {
    const initContainer = Application.getContainer('init');
    return new AbandonedCartApiService(initContainer.httpClient, container.loginService);
});
