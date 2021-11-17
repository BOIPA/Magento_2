<?php

namespace BOIPA\Payment\Model;

use BOIPA\Payment\Model\Config\Source\Brand;
use BOIPA\Payment\Helper\Helper;
use BOIPA\Payment\Model\Config\Source\NewOrderPaymentActions;
use BOIPA\Payment\Model\Config\Source\DisplayMode;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator\Exception;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class PaymentMethod extends AbstractMethod implements GatewayInterface
{

    const METHOD_CODE = 'boipa_payment';
    const NOT_AVAILABLE = 'N/A';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canAuthorize = false;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * @var bool
     */
    protected $_canCaptureOnce = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * @var bool
     */
    protected $_canVoid = true;


    protected $_canCancelInvoice = true;

    /**
     * @var bool
     */
    protected $_canReviewPayment = false;
    /**
     * @var ProductMetadataInterface
     */
    protected $_productMetadata;
    /**
     * @var ResourceInterface
     */
    protected $_resourceInterface;

    /**
     * @var $invoiceService
     */
    protected  $invoiceService;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var ResolverInterface
     */
    protected $_resolver;

    /**
     * @var LoggerInterface
     */
    protected $_ipgLogger;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param UrlInterface $urlBuilder
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $resolver
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param LoggerInterface $ipgLogger
     * @param ProductMetadataInterface $productMetadata
     * @param ResourceInterface $resourceInterface
     * @param Session $session
     * @param CustomerRepositoryInterface $customerRepository
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        UrlInterface $urlBuilder,
        Helper $helper,
        StoreManagerInterface $storeManager,
        ResolverInterface $resolver,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        LoggerInterface $ipgLogger,
        ProductMetadataInterface $productMetadata,
        ResourceInterface $resourceInterface,
        Session $session,
        CustomerRepositoryInterface $customerRepository,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_urlBuilder = $urlBuilder;
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
        $this->_resolver = $resolver;
        $this->_request = $request;
        $this->_ipgLogger = $ipgLogger;
        $this->_productMetadata = $productMetadata;
        $this->_resourceInterface = $resourceInterface;
        $this->_session = $session;
        $this->_customerRepository = $customerRepository;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        /*
         * do not send order confirmation mail after order creation wait for
         * result confirmation
         */
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $stateObject->setStatus($this->_helper->getConfigData('order_status'));
        $stateObject->setIsNotified(false);
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     */
    public function getTitle()
    {
        $title_code = $this->getConfigData('title');
        $brands = (new Brand())->toOptionArray();
        foreach ($brands as $brand) {
            if($brand['value'] == $title_code) {
                return $brand['label'];
            }
        }

        return $title_code;
    }

    /**
     * Checkout redirect URL.
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     *
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        $displayMode = $this->getConfigData('display_mode');
       if ($displayMode === DisplayMode::DISPLAY_MODE_IFRAME) {
           $redirectUrl = 'boipa/hosted/iframe';
        } else {
            $redirectUrl = 'boipa/hosted/redirect';
        }
        return $this->_urlBuilder->getUrl(
            $redirectUrl
        );
    }

    /**
     * Post request to gateway and return response.
     *
     * @param DataObject      $request
     * @param ConfigInterface $config
     */
    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        // Do nothing
        $this->_helper->logDebug('Gateway postRequest called');
    }

    /**
     * @desc Get form url
     *
     * @return string
     */
    public function getFormUrl()
    {
        return $this->_helper->getFormUrl();
    }

    /**
     * @desc Get form method
     *
     * @return string
     */
    public function getFormMethod()
    {
        return $this->_helper->getFormMethod();
    }

    /**
     * @desc Form fields that will be sent with the request
     *
     * @return array
     *
     * @throws LocalizedException
     */
    public function getFormFields()
    {
        $paymentAction = $this->_helper->getConfigData('payment_action');
        $formFields = $this->getAPIParametersForRedirect($this->toAPIOperation($paymentAction));

        return $formFields;
    }

    /**
     * @param $apiOperation
     * @throws LocalizedException
     * @throws Exception
     */
    private function getAPIParametersForRedirect($apiOperation)
    {
        $sessionTokenData = $this->getTokenHostedData($apiOperation);
        $token = $this->_helper->executeGatewayTransaction($sessionTokenData['action'],$sessionTokenData);
        if ($token->result != 'success' ) {
            throw new Exception(__(json_encode($token->errors)));
        }
        $merchantId = trim($this->_helper->getConfigData('merchant_id'));
        $formFields = [];
        $formFields['token'] = $token->token;
        $formFields['merchantId'] = $merchantId;

        $paymentMethodID = trim($this->_helper->getConfigData('payment_method'));
        if ($paymentMethodID != '') {
            $formFields['paymentSolutionId'] = $paymentMethodID;
        }
        $formFields['integrationMode'] = $this->_helper->getIntegrationMode();

        return $formFields;
    }

    private function getTokenHostedData($apiOperation)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        $url = $this->_urlBuilder->getBaseUrl();
        $parse_result = parse_url($url);
        if(isset($parse_result['port'])){
            $allowOriginUrl = $parse_result['scheme']."://".$parse_result['host'].":".$parse_result['port'];
        }else{
            $allowOriginUrl = $parse_result['scheme']."://".$parse_result['host'];
        }
        // currency code
        $orderCurrencyCode = $order->getBaseCurrencyCode();
        // amount
        $amount = $this->formatAmount($order->getBaseGrandTotal());
        // merchant transaction id or order id
        $orderId = $order->getRealOrderId();
        if(strlen($orderId) > 50) {
            $orderId = substr($orderId, -50);
        }
        // customer id
        $customerId = $order->getCustomerId();
        if ($customerId == '') {
            $customerId = 'guest_'.$orderId;
        }
        if(strlen($customerId) > 20) {
            $customerId = substr($customerId, -20);
        }

        // billing address
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            if (is_array($billingAddress->getStreet(1))) {
                $street_arr = $billingAddress->getStreet(1);
                $billingAddressStreet = substr($street_arr[0], 0, 50);
            } else {
                $billingAddressStreet = $billingAddress->getStreet(1);
            }
            $billingAddressCity = $billingAddress->getCity();
            $billingAddressCountry = $billingAddress->getcountryId();
            $billingAddressPostalCode = $billingAddress->getPostcode();
            $billingAddressPhone = $billingAddress->getTelephone();
        }

        // shipping address
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            if (is_array($shippingAddress->getStreet(1))) {
                $street_arr1 = $billingAddress->getStreet(1);
                $shippingAddressStreet = substr($street_arr1[0], 0, 40);
            } else {
                $shippingAddressStreet = $billingAddress->getStreet(1);
            }
            $shippingAddressCity = $shippingAddress->getCity();
            $shippingAddressCountry = $shippingAddress->getcountryId();
            $shippingAddressPostalCode = $shippingAddress->getPostcode();
            $shippingAddressPhone = $shippingAddress->getTelephone();
        }

        if ($billingAddress) {
            if ($billingAddressCountry == '') {
                if ($shippingAddress) {
                    $billingAddressCountry = $shippingAddressCountry;
                }
            }
        }

        // merchant notification URL: server-to-server, URL to which the Transaction Result Call will be sent
        $merchantNotificationUrl = $this->_urlBuilder->getUrl($this->_helper->getNotificationRoute($order->getRealOrderId()));
        // The URL to which the customer’s browser is redirected after the payment
        $merchantLandingPageUrl = $this->_urlBuilder->getUrl($this->_helper->getLandingPageOnReturnAfterRedirect($order->getRealOrderId()));
        // add to session in order to be retrieved on return
        if ($this->_session->getOrderId()) {
            $this->_session->unsOrderId();
        }
        $this->_session->setOrderId($order->getRealOrderId());

        // merchant reference
        $merchantReference = $order->getRealOrderId();

        // customer data
        $customerFirstName = $billingAddress->getFirstname();
        $customerLastName = $billingAddress->getLastname();
        $customerEmail = $billingAddress->getEmail();
        $customerPhone = $billingAddress->getTelephone();
        $customerAddressCountry = $billingAddressCountry;
        $customerAddressCity = $billingAddressCity;
        $customerAddressStreet = $billingAddressStreet;
        $customerAddressPostalCode = $billingAddress->getPostcode();

        $sessionTokenData = array(
            "action" => $apiOperation,
            "merchantTxId" => $orderId,
            "customerId" => $customerId,
            "channel" => "ECOM",
            "amount" => $amount,
            "currency" => $orderCurrencyCode,
            "country" => $billingAddressCountry,
            "allowOriginUrl" => $allowOriginUrl,
            "merchantNotificationUrl" => $merchantNotificationUrl,
            "merchantLandingPageUrl" => $merchantLandingPageUrl,
            "customerFirstName" => $customerFirstName,
            "customerLastName" => $customerLastName,
            "customerEmail" => $customerEmail,
            "customerPhone" => $customerPhone,
            "userDevice" => 'DESKTOP',
            "userAgent" => getenv('HTTP_USER_AGENT'),
            "customerIPAddress" => $order->getRemoteIp(),
            "customerAddressHouseName" => $customerAddressStreet,
            "customerAddressStreet" => $customerAddressStreet,
            "customerAddressCity" => $customerAddressCity,
            "customerAddressPostalCode" => $customerAddressPostalCode,
            "customerAddressCountry" => $customerAddressCountry,
            "merchantChallengeInd" => '01',
            "merchantDecReqInd" => 'N',
            "merchantLandingPageRedirectMethod" => 'GET'

        );

        $sessionTokenData["paymentSolutionId"] = '';
        // brand id
        $brandId = $this->_helper->getConfigData('merchant_brandid');
        if (trim($brandId) != '') {
            $sessionTokenData['brandId'] = trim($brandId);
        }

        // language
        $locale = $this->_resolver->getLocale();
        if ($locale != '') {
            $language = \Locale::getPrimaryLanguage($locale);
            //$country = \Locale::getRegion($locale);
            $sessionTokenData["language"] = $language;
        }

        return $sessionTokenData;
    }

    public function formatAmount($amount, $asFloat = false)
    {
        return number_format((float)$amount, 2, '.', '');
    }

    /**
     * Gets the session token
     *
     * @param array $tokenData
     * @return PaymentMethod $array
     * @throws Exception
     */
    private function getToken($tokenData = array())
    {
        try {
            $tokenRequest = RequestFactory::newTokenRequest($tokenData['action'], $tokenData);
            // this sets static fields of config
            $tokenRequest = $this->_helper->setCommonParams($tokenRequest);

            return $tokenRequest->execute();

        } catch (\Exception $e) {
            throw new Exception(__($e->getMessage()));
        }
    }

    /**
     * @param $paymentAction
     * @return string
     */
    protected function toAPIOperation($paymentAction) {
        switch ($paymentAction) {
            case NewOrderPaymentActions::PAYMENT_ACTION_AUTH: {
                return "AUTH";
            }
            case NewOrderPaymentActions::PAYMENT_ACTION_SALE: {
                return "PURCHASE";
            }
            default: {
                return strtoupper($paymentAction);
            }
        }
    }

    /**
     * @return string
     */
    public function getMerchantNotificationUrl(){
        // merchant notification URL: server-to-server, URL to which the Transaction Result Call will be sent
        $merchantNotificationUrl = $this->_urlBuilder->getUrl($this->_helper->getNotificationRoute($order->getRealOrderId()));
        return $merchantNotificationUrl;

    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getMerchantLandingPageUrl(){
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        // The URL to which the customer’s browser is redirected after the payment
        $merchantLandingPageUrl = $this->_urlBuilder->getUrl($this->_helper->getLandingPageOnReturnAfterRedirect($order->getRealOrderId()));
        return $merchantLandingPageUrl;
    }

    /**
     * Authorize payment method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        parent::authorize($payment, $amount);

        // TODO: tokenize card and add the token to the token request

        $data = array(
            "amount" => $amount
        );
        $sessionTokenData = $this->getTokenHostedData("AUTH", $data);
        // direct api supports only 500
        $sessionTokenData["paymentSolutionId"] = '500';
        $token = $this->getToken($sessionTokenData);
        if ($token->result != 'success' ) {
            throw new Exception(__(json_encode($token->errors)));
        }
        $params = $this->getAPIParametersForDirect("AUTH", $data);
        $params['token'] = $token->token;
        $result = $this->_helper->executeGatewayTransaction("AUTH", $params);
        if($result->result === 'success') {
            $payment->setTransactionId($result->merchantTxId)
                ->setIsTransactionClosed(false)
                ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $result);
            $order = $payment->getOrder();
            $transactionStatus = $result->status;
            if ($transactionStatus === 'NOT_SET_FOR_CAPTURE') { // auth was successful
                $order->setState("processing")
                    ->setStatus("processing")
                    ->addStatusHistoryComment('Payment authorised');
                $order->save();
            } else if($transactionStatus === 'DECLINED') {
                $order->setState("canceled")
                    ->setStatus("canceled")
                    ->addStatusHistoryComment('Payment declined');
                $order->save();
            } else { // error
                $order->setState("canceled")
                    ->setStatus("canceled")
                    ->addStatusHistoryComment('Payment auth error');
                $order->save();
            }
            return $this;
        } else if($result->result === 'redirection') {
            // redirect to the redirection URL
            $merchantId = $result->merchantId;
            $merchantTxId = $result->merchantTxId;
            $txId = $result->txId;
            $redirectionUrl = $result->redirectionUrl;
            Mage::app()->getFrontController()
                ->getResponse()
                ->setRedirect($redirectionUrl);
        } else {
            throw new Exception(__(json_encode($result->errors)));
        }

        return $this;
    }

    /**
     * @param $apiOperation
     * @param array $data
     * @return array|null
     */
    protected function getTokenDirectData($apiOperation, $data = array())
    {
        switch ($apiOperation) {

            case "CAPTURE":
            case "REFUND":
                {
                    return $this->getTokenDirectDataCaptureRefund($apiOperation, $data);
                }
            case "VOID":
                {
                    return $this->getTokenDirectDataVoid($apiOperation);
                }
        }
        return null;
    }

    /**
     * @param $apiOperation
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    protected function getTokenDirectDataCaptureRefund($apiOperation, $data = array()) {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        // allow origin url
        $allowOriginUrl = $this->_urlBuilder->getBaseUrl();

        // amount
        $amount = $data['amount'];
        // merchant transaction id or order id
        $orderId = $order->getRealOrderId();
        if(strlen($orderId) > 50) {
            $orderId = substr($orderId, -50);
        }

        $sessionTokenData = array(
            "action" => $apiOperation,
            "originalMerchantTxId" => $orderId,
            "amount" => $amount,
            "allowOriginUrl" => $allowOriginUrl

        );

        return $sessionTokenData;
    }

    /**
     * @param $apiOperation
     * @return array
     * @throws LocalizedException
     */
    protected function getTokenDirectDataVoid($apiOperation) {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        // allow origin url
        $allowOriginUrl = $this->_urlBuilder->getBaseUrl();

        // merchant transaction id or order id
        $orderId = $order->getRealOrderId();
        if(strlen($orderId) > 50) {
            $orderId = substr($orderId, -50);
        }

        $sessionTokenData = array(
            "action" => $apiOperation,
            "originalMerchantTxId" => $orderId,
            "allowOriginUrl" => $allowOriginUrl

        );

        return $sessionTokenData;
    }

    /**
     * @param $apiOperation
     * @param array $data
     * @return array|mixed|null
     */
    protected function getAPIParametersForDirect($apiOperation, $data = array())
    {
        switch ($apiOperation) {
            case "AUTH":
            case "PURCHASE":
            case "VERIFY":
                {
                    return $this->getAPIParametersForDirectAuthPurchaseVerify($apiOperation, $data);
                }
            case "CAPTURE":
            case "REFUND":
            case "VOID":
                {
                    return $this->getAPIParametersForDirectCaptureRefundVoid($apiOperation, $data);
                }
        }
        return null;
    }

    /**
     * @param $apiOperation
     * @param array $data
     * @return array|mixed
     */
    protected function getAPIParametersForDirectAuthPurchaseVerify($apiOperation, $data = array())
    {
        $merchantId = trim($this->_helper->getConfigData('merchant_id'));
        $data['merchantId'] = $merchantId;
        $paymentMethodID = trim($this->_helper->getConfigData('payment_method'));
        if ($paymentMethodID != '') {
            $data['paymentSolutionId'] = $paymentMethodID;
        }

        return $data;
    }

    /**
     * @param $apiOperation
     * @param array $data
     * @return array|mixed
     */
    protected function getAPIParametersForDirectCaptureRefundVoid($apiOperation, $data = array())
    {
        $merchantId = trim($this->_helper->getConfigData('merchant_id'));
        $data['merchantId'] = $merchantId;

        return $data;
    }

    /**
     * Capture payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function capture(InfoInterface $payment, $amount)
    {
        parent::capture($payment, $amount);
        $data = array(
            "amount" => $amount
        );
        $sessionTokenData = $this->getTokenDirectData("CAPTURE", $data);

        $params = $this->getAPIParametersForDirect("CAPTURE", $data);
		$params = array_merge($sessionTokenData, $params);
		$result = $this->_helper->executeGatewayTransaction("CAPTURE", $params);

        if($result->result === 'success') {
            $payment->setTransactionId($result->originalMerchantTxId)
                ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, json_encode($result));
            $order = $payment->getOrder();
            $transactionStatus = $result->status;
            if ($transactionStatus === 'SET_FOR_CAPTURE') { // capture was successful
                $order->setState("Paid")
                    ->setStatus("processing")
                    ->addStatusHistoryComment(__('Payment captured'));
                $order->save();
            } else { // error
                $order->setState("canceled")
                    ->setStatus("canceled")
                    ->addStatusHistoryComment('Payment capture error');
                $order->save();
            }
            return $this;
        }

        if($result->result === 'redirection') {
            // redirect to the redirection URL
            $merchantId = $result->merchantId;
            $merchantTxId = $result->merchantTxId;
            $txId = $result->txId;
            $redirectionUrl = $result->redirectionUrl;
            Mage::app()->getFrontController()
                ->getResponse()
                ->setRedirect($redirectionUrl);
        } else if (strpos($result->errors, 'current status: SUCCESS') !== false) {
            $order = $payment->getOrder();
            $payment->setTransactionId($order->getRealOrderId());
            $order->setState("Paid")
                ->setStatus("processing")
                ->addStatusHistoryComment(__('Payment captured'));
            $order->save();
            return $this;
        } else {
            throw new Exception(__(json_encode($result->errors)));
        }

        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function refund(InfoInterface $payment, $amount)
    {
        parent::refund($payment, $amount);

        $data = array(
            "amount" => $amount
        );
        $sessionTokenData = $this->getTokenDirectData("REFUND", $data);

        $params = $this->getAPIParametersForDirect("REFUND", $data);

        $params = array_merge($sessionTokenData, $params);
        $result = $this->_helper->executeGatewayTransaction("REFUND", $params);
        if($result->result === 'success') {
            $payment->setTransactionId($result->originalMerchantTxId)
                ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, json_encode($result));
            $order = $payment->getOrder();
            $transactionStatus = $result->status;
            if ($transactionStatus === 'SET_FOR_REFUND') { // refund was successful
                $order->setState("processing")
                    ->setStatus("processing")
                    ->addStatusHistoryComment('Payment refunded amount ' . $amount);

                $transaction = $payment->addTransaction(Transaction::TYPE_REFUND, null, true);
                $transaction->setIsClosed(0);
                $transaction->save();
                $order->save();

            } else { // error
                $order->setState("canceled")
                    ->setStatus("canceled")
                    ->addStatusHistoryComment('Payment refund error');
                $order->save();
            }
            return $this;
        }

        if (strpos($result->errors, 'Transaction not refundable: Original transaction not SUCCESS') !== false) {
            if($payment->getOrder()->getBaseGrandTotal() == $amount) {
                return $this->void($payment);
            } else {
                throw new Exception(__(json_encode($result->errors)));
            }
        }
        else {
            throw new Exception(__(json_encode($result->errors)));
        }

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @return $this
     * @throws LocalizedException
     * @deprecated 100.2.0
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function cancel(InfoInterface $payment)
    {
        return $this->void($payment);
    }

    /**
     * Void payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function void(InfoInterface $payment)
    {
        parent::void($payment);

        $sessionTokenData = $this->getTokenDirectData("VOID");

        $params = $this->getAPIParametersForDirect("VOID");
        $params = array_merge($sessionTokenData, $params);

        $result = $this->_helper->executeGatewayTransaction("VOID", $params);

        if($result->result === 'success') {
            $payment->setTransactionId($result->originalMerchantTxId)
                ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, json_encode($result));
            $order = $payment->getOrder();
            $transactionStatus = $result->status;
            if ($transactionStatus === 'VOID') { // void was successful
                $order->setState("processing")
                    ->setStatus("processing")
                    ->addStatusHistoryComment('Payment voided');
                $transaction = $payment->addTransaction(Transaction::TYPE_VOID, null, true);
                $transaction->setIsClosed(1);
                $transaction->save();
                $order->save();
            } else { // error
                $order->setState("canceled")
                    ->setStatus("canceled")
                    ->addStatusHistoryComment('Payment void error');
                $order->save();
            }
            return $this;
        } else {
            throw new Exception(__(json_encode($result->errors)));
        }

        return $this;
    }

    /**
     * Retrieve request object.
     *
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function _getRequest()
    {
        return $this->_request;
    }
}
