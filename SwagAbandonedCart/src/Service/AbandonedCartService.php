<?php declare(strict_types=1);

namespace Swag\Abandoned\Service;

use Exception;
use Shopware\Core\Defaults;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Content\Mail\Service\MailService as ServiceMailService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class AbandonedCartService
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var EntityRepository
     */
    protected $customerRepository;
    /**
     * @var EntityRepository
     */
    protected $currencyRepository;

    /**
     * @var ServiceMailService
     */
    protected $mailService;

    /**
     * @var EntityRepository
     */
    protected $notificationRepository;

    /**
     * @var EntityRepository
     */
    protected $customCartRepository;

    /**
     * @var EntityRepository
     */
    protected $userRepository;
    
    /**
     * @var SystemConfigService
     */
    protected $systemConfigService;

    protected $salesChannelApiContextRepository;

    static $staticConnection;

    public function __construct(
        Connection $connection,
        EntityRepository $customerRepository,
        EntityRepository $currencyRepository,
        ServiceMailService $mailService,
        EntityRepository $notificationRepository,
        EntityRepository $customCartRepository,
        EntityRepository $userRepository,
        SystemConfigService $systemConfigService,
    ) {
        $this->connection = $connection;
        self::$staticConnection = $connection;
        $this->customerRepository = $customerRepository;
        $this->currencyRepository = $currencyRepository;
        $this->mailService = $mailService;
        $this->notificationRepository = $notificationRepository;
        $this->customCartRepository = $customCartRepository;
        $this->userRepository = $userRepository;
        $this->systemConfigService = $systemConfigService;
    }

    public function getCartCriteria($customerIds, $salesChannel = null, $page = null, $limit = null){
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('customerId', $customerIds));

        if($salesChannel){
            $criteria->getAssociation('sales_channel_id')->addFilter(new EqualsFilter('id', $salesChannel));
        }

        if($page && $limit){
            $offset = ($page -1) * $limit;
            $criteria->setOffset($offset);
            $criteria->setLimit($limit);
        }
        return $criteria;
    }

    protected static function forDefinitionCheck(): bool
    {
        return \count(array_filter(
            self::$staticConnection->getSchemaManager()->listTableColumns('cart'),
            static function (Column $column): bool {
                return $column->getName() === 'payload';
            }
        )) > 0;
    }

    protected function checkPayloadExist(): bool
    {
        return \count(array_filter(
            $this->connection->getSchemaManager()->listTableColumns('cart'),
            static function (Column $column): bool {
                return $column->getName() === 'payload';
            }
        )) > 0;
    }

    public function getCartCriteria_($customerIds, $salesChannel = null, $page = null, $limit = null, $sortBy = null, $sortDirection = null){
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('customerId', $customerIds));
    
        if($salesChannel){
            $criteria->getAssociation('sales_channel_id')->addFilter(new EqualsFilter('id', $salesChannel));
        }
    
        if($sortBy){
            $sortDirection = $sortDirection ?: 'DESC';
            if($sortBy === 'dateAdded'){
                $criteria->addSorting(new FieldSorting('createdAt', $sortDirection));
            }
        } else {
            // Default sorting - newest first
            $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        }
    
        if($page && $limit){
            $offset = ($page -1) * $limit;
            $criteria->setOffset($offset);
            $criteria->setLimit($limit);
        }
        
        return $criteria;
    }
    
    //getting the date added column
    public function loadCart(RequestDataBag $data, Context $context)
    {
        $customerArray = [];
        $customers = $this->getCustomers($data, $context);

        $customerIds = $this->getCustomerUnhexIds($customers);
        
        $salesChaneelId = null;
        $page = null;
        $limit = null;
        $sortBy = 'dateAdded';  
        $sortDirection = $data->get('sortDirection');  
        
        if ($data->get('salesChannelId')) {
            $salesChaneelId = $data->get('salesChannelId');
        }
        
        if ($data->get('page') && $data->get('limit')) {
            $page = $data->get('page');
            $limit = $data->get('limit');
        }

        if ($data->get('sortBy')) {
            $sortBy = $data->get('sortBy');
        }

        $cartCriteria = $this->getCartCriteria($customerIds, $salesChaneelId);
        $cartData = $this->customCartRepository->search($cartCriteria, $context);

        $formatCartData = [];

        foreach($cartData->getEntities() as $data){
            if($this->checkPayloadExist()){
                $formatCartData[strtoupper($data->getCustomerId())] = [
                    'cart' => $data->cart,
                    'createdAt' => $data->getCreatedAt()
                ];
            }else{
                $formatCartData[strtoupper($data->getCustomerId())] = [
                    'cart' => $data->cart,
                    'createdAt' => $data->getCreatedAt()
                ];
            }
        }
        
        if ($formatCartData) {
            foreach ($customers as $customerId => $customerEntity) {
                
                if (isset($formatCartData[strtoupper((string)$customerId)])) {
                    $cartInfo = $formatCartData[strtoupper((string)$customerId)];

                    
                    $customerArray[] = [
                        'customerId' => $customerId,
                        'salesChannelId' => $customerEntity->getSalesChannelId(),
                        'salesChannel' => $customerEntity->getSalesChannel()->getName(),
                        'name' => $customerEntity->getFirstName() . ' ' . $customerEntity->getLastName(),
                        'email' => $customerEntity->getEmail(),
                        'dateAdded' => $cartInfo['createdAt'] ? $cartInfo['createdAt']->format('d-m-Y H:i:s') : null,
                        'dateAddedSort' => $cartInfo['createdAt'],
                        'lastNotified' => $this->getLastNotified(strtoupper((string)$customerId), $cartInfo['cart'], $context)
                    ];

                }
            }
        }

        if (!empty($customerArray)) {
            usort($customerArray, function($a, $b) use ($sortBy, $sortDirection) {
                if ($sortBy === 'dateAdded') {
                    $dateA = $a['dateAddedSort'];
                    $dateB = $b['dateAddedSort'];
                    
                    if ($dateA === null && $dateB === null) return 0;
                    if ($dateA === null) return ($sortDirection === 'ASC') ? -1 : 1;
                    if ($dateB === null) return ($sortDirection === 'ASC') ? 1 : -1;
                    
                    $comparison = $dateA <=> $dateB;
                    return $sortDirection === 'ASC' ? $comparison : -$comparison;
                }
                
                return 0;
        });
            
        
        }

        foreach ($customerArray as &$customer) {
            unset($customer['dateAddedSort']);
        }

        $total = count($customerArray);

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            $customerArray = array_slice($customerArray, $offset, $limit);
        }

        return [$total, $customerArray];
    }


    /**
     * criterial for notification
     */
    private function getCriteriaForNotification(string $customerId){
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        return $criteria;
    }

    /**
     * get cartItems from notification table
     * 
     */
    private function getCartItemsFromNotification(string $customerId, $context)
    {
        $criteria = $this->getCriteriaForNotification($customerId);
        
        $item = $this->notificationRepository->search($criteria, $context)->first();
        
        if($item){
            return unserialize($item->getcartItems());
        }
        return null;
    }

    private function getLastNotified(string $customerId, $cartItems, $context)
    {
        //get cartItems for which previous notification is sent
        $getPastNotifiedCartItems = $this->getCartItemsFromNotification($customerId, $context);
        
        
        if(!$getPastNotifiedCartItems){
            return null;
        }
       //check if items in previous and latest cart is equal
        if(count($getPastNotifiedCartItems) != count($cartItems[0]['lineItems'])){
            return null;
        }

        $result = $this->compareCartArrays($getPastNotifiedCartItems, $cartItems);
        if ($result === null) {
            return null;
        }

        $items = $this->notificationRepository->search($this->getCriteriaForNotification($customerId), $context)->first();
        return $items->getLastNotified()->format('d-m-Y h:i:s');
        
    }

    private function compareCartArrays($getPastNotifiedCartItems, $cartItems)
    {
        foreach ($getPastNotifiedCartItems as $previousItem) {
            $previousItemId = $previousItem['lineItems'][0]['id'] ?? null;
            
            if ($previousItemId === null) {
                continue; 
            }
    
            $found = false;
            foreach ($cartItems[0]['lineItems'] as $currentItem) {
                if ($currentItem['id'] === $previousItemId) {
                    $found = true;
                    break;
                }
            }
    
            if (!$found) {
                return null;
            }
        }
    
        return true;
    }

    public function getCustomers(RequestDataBag $data, $context)
    {
        $critera = $this->getCriteria($data);

        return $this->customerRepository->search($critera, $context);
    }

    private function getCriteria(RequestDataBag $data)
    {
        $criteria = new Criteria();
        $criteria->addAssociation('salesChannel');

        if ($data->get('salesChannelId')) {
            $criteria->addFilter(new EqualsFilter('salesChannelId', $data->get('salesChannelId')));
        }

        if ($data->get('term')) {
            $criteria->setTerm($data->get('term'));
        }

        return $criteria;
    }

    public function loadCustomerCart($customerId, $context)
    {
        $customerEntity = $this->customerRepository->search(
            (new Criteria([$customerId]))->addAssociation('salesChannel.currency')->addAssociation('salesChannel.domains'),
            $context
        )->first();
        
       
        $cartFilter = $this->getCartCriteria([strtoupper($customerId)]);
        $cartData = $this->customCartRepository->search($cartFilter, $context)->first();
        
        if($cartData){
            $currencyEntity = $this->currencyRepository->search((new Criteria())->addFilter(new EqualsFilter('id',$cartData->getCurrencyId())), $context)->first();
        }
        if($this->checkPayloadExist()){
            $cart = $cartData->cart;
        }else{
            $cart = $cartData->cart;
        }
        return [$customerEntity, $cart, $currencyEntity];
    }

    public function updateCartCriteria(string $token){
        $criteria = new Criteria();
        return $criteria->addFilter(new EqualsFilter('token', $token));
    }

    public function updateCart(string $token, string $customerId, CartService $cartService, $context, Cart $cartCurrent)
    {
        $cartCriterial = $this->getCartCriteria([strtoupper($customerId)]);
        $cart = $this->customCartRepository->search($cartCriterial, $context->getContext())->first();
       //checking saleschannel is different

        if ($cart && $cart->getWildcard() != $token) {
            $currentCartCriteria = $this->updateCartCriteria($token);
            $currentCart = $this->customCartRepository->search($currentCartCriteria, $context->getContext())->first();
            //any cart of current sales channel
            if ($currentCart) {
                if($this->checkPayloadExist()){

                    $currentCart = $currentCart->cart;
                    $currentCart = $currentCart;
                    $previousCart =  $cart->cart;
                }else{
                    $currentCart = $currentCart->cart;
                    $currentCart = $currentCart;
                    $previousCart = $cart->cart;
                }
                $lineItems = $previousCart[0]['lineItems'];

                $priceDifference = false;
                $netPrice = $currentCart[0]['price']['netPrice'];
                $totalPrice = $currentCart[0]['price']['totalPrice'];
                $positionPrice = $currentCart[0]['price']['positionPrice'];
                $taxStatus = $currentCart[0]['price']['taxStatus'];


                $previousNetPrice = $previousTotalPrice = $previousPositionPrice = 0;

                foreach($currentCart[0]['lineItems'] as $key => $lineItem) {
                    if (!isset($lineItems[$key])) {
                        $lineItems[$key] = $lineItem;
                        $priceDifference = true;
                        $previousNetPrice += $lineItem['price']['unitPrice'];
                        $previousTotalPrice += $lineItem['price']['totalPrice'];
                    }
                }

                $netPrice += $previousNetPrice;
                $totalPrice += $previousTotalPrice;
                $positionPrice = $totalPrice;

                $currentCartPrice = $currentCart[0]['price'];
                $previousCartPrice = $previousCart[0]['price'];
                $calculatedTax = $this->getCartCalculatedTax($currentCartPrice['calculatedTaxes'][0], $previousCartPrice['calculatedTaxes'][0]);

                $taxRule = $this->getCartTaxRule($currentCartPrice['taxRules'][0], $previousCartPrice['taxRules'][0]);


                $currentCart = new Cart($currentCart[0]['token']);
                if ($priceDifference) {
                    //empty currentCart
                    $cartService->reset();
                    //add all items
                    foreach($lineItems[0] as $item){
                        $cartService->add($cartCurrent, $item, $context);
                    }
                    
                    // Delete previous cart
                    $this->connection->executeQuery("DELETE  FROM `cart` WHERE `token` = :token", ['token' => $cart->getWildcard()]);

                } else {
                    //delete oldCart
                    $this->connection->executeQuery("DELETE  FROM `cart` WHERE `token` = :token", ['token' => $cart->getWildcard()]);
                    if($this->checkPayloadExist()){
                        $lineItems = $cart->cart[0]['lineItems'];
                    }else{
                        $lineItems = $cart->cart[0]['lineItems'];
                    }

                    foreach($lineItems as $item){
                        $lineItem = new LineItem($item['id'],LineItem::CONTAINER_LINE_ITEM, $item['id']);
                            
                            $cartService->add($cartCurrent, $lineItem, $context);
                        }
                    }
                    
            } else {
                if (!empty($currentCart) && isset($currentCart[0])) {

                    if (isset($currentCart[0]['token']) && !empty($currentCart[0]['token'])) {
                        $currentCart = new Cart($currentCart[0]['token']);
                        try{
                            if($this->checkPayloadExist()){
                                $lineItems = $cart->cart[0]['lineItems'];
                            }else{
                                $lineItems = $cart->cart[0]['lineItems'];
                            }
                            foreach($lineItems as $item){
                                $lineItem = new LineItem($item['id'],LineItem::CONTAINER_LINE_ITEM, $item['id']);
                                $cartService->add($cartCurrent, $lineItem, $context);
                            }
                            //delete previous cart
                            $this->connection->executeQuery("DELETE FROM `cart` WHERE `token` = :token", ['token' => $cart->getWildcard()]);
                        }
                        catch(Exception $ex){
                            throw $ex;
                        }
                    }
                }
        
            }
        }
    }

    public function getCartCalculatedTax($currentTaxCollection, $previousTaxCollection)
    {
        foreach ($previousTaxCollection as $key => $calculatedTax) {
            if (!isset($currentTaxCollection[$key])) {
                $currentTaxCollection[$key] = $calculatedTax;
            }
        }

        return $currentTaxCollection;
    }

    public function getCartTaxRule($currentTaxRuleCollection, $previousTaxRuleCollection)
    {
        foreach ($previousTaxRuleCollection as $key => $taxRule) {
            if (!isset($currentTaxRuleCollection[$key])) {
                $currentTaxRuleCollection[$key] = $taxRule;
            }
        }

        return $currentTaxRuleCollection;
    }

    private function getCustomerUnhexIds($customerEntities)
    {
        $customerIds = [];
        foreach ($customerEntities->getElements() as $customerId => $customerEntity) {
            array_push($customerIds, strtoupper($customerId));
        }
        return $customerIds;
    }

    public function handleTask($context)
    {
        $daysInCart =  $this->systemConfigService->get('SwagAbandonedCart.config.daysInCart');
        $isMaxTimesEnabled = $this->systemConfigService->get('SwagAbandonedCart.config.enableMaxTimesNotification');
        $maxTimesNotification =  $this->systemConfigService->get('SwagAbandonedCart.config.maxTimesNotification');
        $configInterval = 5;
        $maxNotify = 2;
        if(isset($daysInCart) && $daysInCart != 0){
            $configInterval = $daysInCart;
        }

        if(isset($maxTimesNotification)){
            $maxNotify = $maxTimesNotification;
        }    

        if ($configInterval && $isMaxTimesEnabled) {
            $modified = mktime((int)date("h"), (int)date("i"), (int)date("s"), (int)date("m")  , date("d")-$configInterval, (int)date("Y"));
            //duration to notify
            $abandonedCartDuration = date(Defaults::STORAGE_DATE_TIME_FORMAT,$modified);
            
            $criteria = new Criteria();
            $criteria->addFilter(new RangeFilter('createdAt',[
                RangeFilter::GTE => $abandonedCartDuration,
            ]));
            $cartData = $this->customCartRepository->search($criteria, $context)->getEntities()->getElements();

            if($cartData){
                foreach($cartData as $cart){

                    if($cart->getCustomerId()){
                        $customerEntities = $this->notificationRepository->search(
                            (new Criteria())->addFilter(
                                new EqualsFilter('customerId', $cart->getCustomerId())
                                ),
                            $context
                        )->first();


                        if($customerEntities){

                            if((new \DateTimeImmutable('now')) > $customerEntities->getNextNotified())
                             {   
                               if($customerEntities->timesHandlerNotified == $maxNotify){
                                    continue;
                               }
                            }else{
                                continue;
                            }
                        }
                        $this->notifyCustomer($cart->getCustomerId(), true, $context);
                    }
                }
            }
        }
    }


    public function notifyCustomer(string $customerId, bool $isHandler, $context): bool
    {   
        [$customer, $cart] = $this->loadCustomerCart($customerId, $context);
        
        
        if ($customer) {
            if ($customer->getSalesChannel()->getCurrencyId()) {
                $customerCurrentCurrrencyId = $customer->getSalesChannel()->getCurrencyId();
                $currencyEntity = $this->currencyRepository->search((new Criteria())->addFilter(new EqualsFilter('id',$customerCurrentCurrrencyId)),$context)->first();
            } else {
                $currencyEntity = $this->currencyRepository->search((new Criteria())->addFilter(new EqualsFilter('factor',1)),$context)->first();
            }
        } else {
            $currencyEntity = $this->currencyRepository->search((new Criteria())->addFilter(new EqualsFilter('factor',1)),$context)->first();
        }

        $mailSubject = $this->systemConfigService->get('SwagAbandonedCart.config.mailSubject', $customer->getSalesChannelId());
        $mailMessage = $this->systemConfigService->get('SwagAbandonedCart.config.mailMessage', $customer->getSalesChannelId());
               
        $find = ['[firstname]', '[lastname]', '[sales_channel]', '[products]', '[redirect]', '[admin]'];
    
        $salesChannelUrl = $this->getUrl($customer->getSalesChannel()->getDomains());
        
        // Update 09072025
        $products = '';
        $products .= '<!DOCTYPE htmlPUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        $products .= '<style type="text/css">';
        $products .= '   .mail-template table td, table th {';
        $products .= '      padding: 6px;';
        $products .= '      line-height: 1.42857143;';
        $products .= '      vertical-align: top;';
        $products .= '      border-top: 1px solid #ddd;';
        $products .= '   }';
        $products .= '   .mail-template a {';
        $products .= '      text-decoration: none;';
        $products .= '      color: #078e8e;';
        $products .= '      font-weight: 600;';
        $products .= '      font-family: sans-serif;';
        $products .= '  }';
        $products .= ' :root {';
        $products .= '      color-scheme: light dark;';
        $products .= '      supported-color-schemes: light dark;';
        $products .= ' }';
        $products .= '</style>';

        $products .= '<html xmlns="http://www.w3.org/1999/xhtml" lang="en">';
        $products .= '<head>';
        $products .= '<meta name="color-scheme" content="light dark">';
        $products .= '<meta name="supported-color-schemes" content="light dark">';
        $products .= '</head>';

        $products .= '<table border="0px" style="border-spacing: 0; border-collapse: collapse;" width="90%">';
        $products .= '  <thead>';
        $products .= '      <tr>';
        $products .= '          <th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>';
        $products .= '          <th style="width:60%">Productname</th>';
        $products .= '          <th>Price</th>';
        $products .= '          <th>Qty</th>';
        $products .= '          <th>Total</th>';
        $products .= '      </tr>';
        $products .= '  </thead>';
        // end update
        $products .= '  <tbody>';
        foreach ($cart[0]["lineItems"] as $lineItem) {
            $products .= '<tr>';    
            
            $productName = str_replace(' ', '-', $lineItem['label']);
            $productName = preg_replace('/[^A-Za-z0-9\-]/', '', $productName); 

            if ($lineItem['cover']) {
                $imageUrl = $lineItem['cover']['url'];
                $productUrl = $salesChannelUrl . '/detail/' . $lineItem['id'];
                $altText = htmlspecialchars($lineItem['label'], ENT_QUOTES, 'UTF-8');
                
                $products .= '
                    <td>
                        <a href="' . $productUrl . '">
                            <img src="' . $imageUrl . '" 
                                 data-src="' . $imageUrl . '" 
                                 title="' . $altText . '" 
                                 alt="' . $altText . '" 
                                 height="30" 
                                 width="100%" 
                                 style="display: block; max-width: 100%;">
                        </a>
                    </td>';
            } else {
                $products .= '<td></td>';
            }
            $payload = ' ';
            $options = false;
           
            if(isset($lineItem['payload']["options"])){
                $options = $lineItem['payload']["options"];
            }
            if ($options) {
                $payload .= '(';
                foreach ($options as $option) { 
                    $payload .= $option['group'].':'. $option['option'];
                    if (end($options) != $option) {
                        $payload .= '|';
                    }
                }
                $payload .= ')';
            }
            $unitPrice =  $lineItem['price']['unitPrice'];
            $totalPrice =  $lineItem['price']['totalPrice'];
            $products .= ' <td><a href="' . $salesChannelUrl .'/detail/'. $lineItem['id'] . '">' . $lineItem['label'] .$payload . '</a></td>';
            $products .= ' <td>' . $currencyEntity->getSymbol() . number_format((float)$unitPrice, 2, '.', '') . ' </td>';
            $products .= ' <td>' . $lineItem['price']['quantity'] . 'x</td>';
            $products .= '    <td>' . $currencyEntity->getSymbol() . number_format((float)$totalPrice, 2, '.', '') . '</td>';
            $products .= '      </tr>';
            
        }

        $products .= '  </tbody>';

        $products .= '</table>';

        $redirect = "<a href='" . $salesChannelUrl . "/abandoned/cart/check-customer'>here</a>";

        $admin = $this->getAdmin($context) ?? '';
        
        $replace = [
            $customer->getFirstName(),
            $customer->getLastName(),
            $customer->getSalesChannel()->getName(),
            $products,
            $redirect,
            $admin
        ];

        $message = isset($mailSubject) && $mailMessage ? $mailMessage : '';

        if (! $message) {
            $message = "Hello [firstname],<br/><br/>We noticed you left a few items in your basket. Not to worry — they’re still there, waiting for you. Complete your purchase now before they sell out!<br/><br/> [products] <br/><br/> Click [redirect] to checkout your cart products. <br/><br/> Thanks & Regards, <br/>Team [sales_channel]";
        }
        if (! $mailSubject){
            $mailSubject = "You left items in your cart at [sales_channel]";   
        }

        $subject = str_replace($find, $replace, $mailSubject);

        $replace = [
            $customer->getFirstName(),
            $customer->getLastName(),
            '<a href="' . $salesChannelUrl . '">' . $customer->getSalesChannel()->getName() . '</a>',
            $products,
            $redirect,
            $admin
        ];
       
        $message = str_replace($find, $replace, $message);
        
        $data = new DataBag();
        $data->set('recipients', [
            $customer->getEmail() => $customer->getFirstName() . ' ' . $customer->getLastName()
        ]);

        $data->set('senderName', $this->systemConfigService->get('core.basicInformation.email'));
        $data->set('salesChannelId', $customer->getSalesChannelId());
        $data->set('subject', $subject);
        $data->set('contentHtml',  $message);
        $data->set('contentPlain', strip_tags($message));
        try {
          $this->mailService->send($data->all(), $context, ['salesChannel' => $customer->getSalesChannel()]);
        } catch(Exception $ex ){
            throw $ex;
        }

        
        // Update Last Notified

        /**
         * update notification table based on cart token and customer ID
         */
        $this->updateLastNotified($customer->getId(), serialize($cart[0]['lineItems']), $isHandler, $context);

        return true;
    }

    public function updateLastNotified(string $customerId, string $cartItems, bool $isHandler, $context): void
    {
        $customerEntities = $this->notificationRepository->search(
            (new Criteria())->addFilter(
                new EqualsFilter('customerId', $customerId)
                ),
                $context
        )->first();

        $id = Uuid::randomHex();

        $modified = mktime((int)date("h")+12, (int)date("i"), (int)date("s"), (int)date("m")  , (int)date("d"), (int)date("Y"));
        $nextNotified = date(Defaults::STORAGE_DATE_TIME_FORMAT, $modified);
            
        $lastNotified = date(Defaults::STORAGE_DATE_TIME_FORMAT);
        

        if ($customerEntities) {
            $id = $customerEntities->getId();
            $notificationData = [
                'id' => $id,
                'customerId' => $customerId,
                'cartItems' => $cartItems,
                'lastNotified' => $lastNotified,
                'timesNotified' => $customerEntities->getTimesNotified() + 1,
                'nextNotified' => $nextNotified
            ];
            
            if($isHandler){
                $notificationData['timesHandlerNotified']  = $customerEntities->timesHandlerNotified +1;
            }

            $this->notificationRepository->update([$notificationData], $context);
        } else {
          
            $notificationData = [
                'id' => $id,
                'customerId' => $customerId,
                'cartItems' => $cartItems,
                'lastNotified' => $lastNotified,
                'nextNotified' => $nextNotified,
                'timesHandlerNotified' => 0
            ];
    
            $this->notificationRepository->create([$notificationData], $context);
        }
    }

    private function getAdmin($context)
    {
        $criteria=new Criteria();
        $criteria->addFilter(new EqualsFilter('active',1));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        $adminData = $this->userRepository->search($criteria, $context)->first();
        if($adminData){
            return $adminData->getFirstName()." ".$adminData->getLastName();
        }
        return null;
    }

    private function getUrl($domains)
    {
        $url = '';

        if ($domains->getElements()) {
            $url = $domains->first()->getUrl();
        } else {
            $url = getenv('APP_URL');
        }

        return $url;
    }

    //deleting the abandoned cart
    public function removeFromNotificationList(string $customerId, Context $context): bool
    {
        try {
            
            $notificationCriteria = new Criteria();
            $notificationCriteria->addFilter(new EqualsFilter('customerId', $customerId));
            $notificationEntities = $this->customCartRepository->search($this->getCartCriteria([strtoupper($customerId)]), $context);
            // dd($notificationEntities);
            if ($notificationEntities->getTotal() > 0) {
                $notificationIds = [];
                foreach ($notificationEntities as $entity) {
                    $notificationIds[] = ['id' => $entity->getId()];
                }
                $this->customCartRepository->delete($notificationIds, $context);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    //deleting the abandoned cart in bulk
    public function bulkRemoveFromNotificationList(array $customerIds, Context $context): bool
    {
        try {
            $allNotificationIds = [];
            
            foreach ($customerIds as $customerId) {
                $notificationEntities = $this->customCartRepository->search(
                    $this->getCartCriteria([strtoupper($customerId)]), 
                    $context
                );
                
                foreach ($notificationEntities as $entity) {
                    $allNotificationIds[] = ['id' => $entity->getId()];
                }
            }
            
            if (!empty($allNotificationIds)) {
                $this->customCartRepository->delete($allNotificationIds, $context);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
