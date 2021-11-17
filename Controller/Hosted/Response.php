<?php

namespace BOIPA\Payment\Controller\Hosted;

use BOIPA\Payment\Helper\Helper;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Service\InvoiceService;

class Response extends Action implements CsrfAwareActionInterface
{

    /**
     * @var $resultPageFactory
     */
    protected $resultPageFactory;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     * @var Helper
     */
    private $_helper;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param Helper $helper
     * @param InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $transaction
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        Helper $helper,
        InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_helper = $helper;
    }

    /**
     * @param  RequestInterface  $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param  RequestInterface  $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|ResultInterface|void
     * @throws Exception
     */
    public function execute()
    {
        $objectManager = ObjectManager::getInstance();
        $request = $objectManager->get('\Magento\Framework\App\Request\Http');
        $requestPostPayload = $request->getParams();
        $urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');

        $checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');

        $redirectUrl = $this->_url->getUrl('checkout/onepage/failure/');
        if (isset($checkoutSession)) {
            $orderId = $checkoutSession->getOrderId();
            if (!isset($orderId)) {
                $orderId = $request->getParam('orderid');
            }
        } else {
            $orderId = $request->getParam('orderid');
        }
        if (!isset($orderId)) {
            $redirectUrl = $urlInterface->getUrl('checkout/onepage/failure/');
            $this->_redirect($redirectUrl);
            return;
        }
        $orders = $objectManager->get('Magento\Sales\Model\Order');
        $order = $orders->loadByIncrementId($orderId);
        if (isset($requestPostPayload) && isset($requestPostPayload['result'])) {
            if ($requestPostPayload['result'] === 'success') {
                $redirectUrl = $urlInterface->getUrl('checkout/onepage/success/');
            } else {
                $redirectUrl = $urlInterface->getUrl('checkout/onepage/failure/');
            }
        }
        $payment = $order->getPayment();
        try {
			$params = array(
				"allowOriginUrl" => $urlInterface->getBaseUrl(),
				"merchantTxId" => $order->getRealOrderId()
			);
            $gatewayTransaction = $this->_helper->executeGatewayTransaction("GET_STATUS", $params);
        } catch (Exception $e) {
            $this->_redirect($urlInterface->getUrl('checkout/onepage/failure/'));
            return;
        }

        if ($gatewayTransaction->result === 'success') {
            $realStatus = $gatewayTransaction->status;
            if ($realStatus === 'SET_FOR_CAPTURE') { //PURCHASE was successful
                $redirectUrl = $urlInterface->getUrl('checkout/onepage/success/');
            } else if ($realStatus === 'NOT_SET_FOR_CAPTURE') { // AUTH was successful
                $redirectUrl = $urlInterface->getUrl('checkout/onepage/success/');
            } else if ($realStatus === 'CAPTURED') { // transaction captured
                $redirectUrl = $urlInterface->getUrl('checkout/onepage/success/');
            } else if ($realStatus === 'SUCCESS') {
                $redirectUrl = $urlInterface->getUrl('checkout/onepage/success/');
            } else if ($realStatus === 'DECLINED' ||
                $realStatus === 'ERROR') {
                $redirectUrl = $urlInterface->getUrl('checkout/onepage/failure/');
            } else if ($realStatus === 'VERIFIED') {
                $redirectUrl = $urlInterface->getUrl('checkout/onepage/success/');
            } else {
                $redirectUrl = $urlInterface->getUrl('checkout/onepage/failure/');
            }
        } else if ($gatewayTransaction->result === 'failure') {
            $order->cancel();
            $order->addCommentToStatusHistory(__('Order cancelled due to failed transaction'))->setIsCustomerNotified(true);
            $order->save();
            $redirectUrl = $urlInterface->getUrl('checkout/onepage/failure/');
        } else {
            $order->cancel();
            $order->addCommentToStatusHistory('Order cancelled due to failed transaction: ' . $gatewayTransaction->merchantTxId . '(' . $gatewayTransaction->txId . ') failed: ' . implode("|", $gatewayTransaction->errors))->setIsCustomerNotified(true);
            $order->save();
            $redirectUrl = $urlInterface->getUrl('checkout/onepage/failure/');
        }
        $params['redirectUrl'] = $redirectUrl;

        $this->registry->register(\BOIPA\Payment\Block\Response::REGISTRY_PARAMS_KEY, $params);

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }


}
