<?php

namespace BOIPA\Payment\Controller\Hosted;

use BOIPA\Payment\Helper\Helper;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\App\CsrfAwareActionInterface;

class Callback extends Action
{

    /**
     * @var $resultPageFactory
     */
    protected $resultPageFactory;

    /**
     * @var InvoiceService
     */
    protected  $invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Helper $helper
     * @param InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param OrderSender $orderSender
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Helper $helper,
        InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        OrderSender $orderSender
    ) {
        parent::__construct($context);
        $this->_helper = $helper;
        $this->invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->orderSender = $orderSender;

        if (interface_exists(CsrfAwareActionInterface::class)) {
            $request = $this->getRequest();
            if ($request instanceof Http && $request->isPost() && empty($request->getParam('form_key'))) {
                $formKey = $this->_objectManager->get(FormKey::class);
                $request->setParam('form_key', $formKey->getFormKey());
            }
        }
    }

    /**
     * to handle the IPG Gateway callback when the payment finished
     *
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $objectManager  = ObjectManager::getInstance();
        $request        = $objectManager->get('\Magento\Framework\App\Request\Http');
        $requestPostPayload = $request->getPost();
        $urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');
        $raw_post = file_get_contents( 'php://input' );
        $parts = parse_url($raw_post);
        parse_str($parts['path'], $query);

        if(empty($query) || !isset($query['merchantTxId'])){
            //bad callback request
            return false;
        }

        if($query['action'] !== 'AUTH' && $query['action'] !== 'PURCHASE'){
            return false;
        }
        $orderId = $request->getParam('orderid');
        if(empty($orderId)){
            return false;
        }
        $orders = $objectManager->get('Magento\Sales\Model\Order');
        $order = $orders->loadByIncrementId($orderId);
        $params = array(
            "allowOriginUrl" => $urlInterface->getBaseUrl(),
            "merchantTxId" => $query['merchantTxId']
        );
        $gatewayTransaction = $this->_helper->executeGatewayTransaction("GET_STATUS", $params);
        if ($gatewayTransaction->result === 'success') {
            // notify customer with the email
            if (!$order->getEmailSent()) {
                $this->orderSender->send($order);
            }
            $realStatus = $gatewayTransaction->status;
            if ($realStatus === 'SET_FOR_CAPTURE' ||$realStatus === 'CAPTURED' ) { //PURCHASE was successful or transaction captured
                if($order->getStatus() != Order::STATE_PROCESSING && $order->getStatus() != Order::STATE_COMPLETE){
                    if($order->getState() == 'Paid'){
                        return false;
                    }
                    $order->setState("Paid")
                    ->setStatus("pending")
                    ->addStatusHistoryComment(__('Order status paid'))
                    ->setIsCustomerNotified(true);
                    $order->save();
                    try {
                        $this->_helper->generateInvoice($order, $this->invoiceService, $this->_transaction);
                    } catch (Exception $e) {
                        //log
                    }
                }
            } else if ($realStatus === 'NOT_SET_FOR_CAPTURE') { // AUTH was successful
                if($order->getState() === 'Authorized'){
                    return false;
                }
                $order->setState('Authorized')
                ->setStatus("pending")
                ->addStatusHistoryComment(__('Order payment authorized'))
                ->setIsCustomerNotified(true);

                $order->save();

                $payment = $order->getPayment();
                $payment->setIsTransactionClosed(false);
                $payment->resetTransactionAdditionalInfo()
                ->setTransactionId($query['merchantTxId']);

                $transaction = $payment->addTransaction(
                    Transaction::TYPE_AUTH,
                    null,
                    true
                );

                $transaction->setIsClosed(0);
                $transaction->save();

                $payment->save();
                // TODO: add auto-capture??
            }
        }
    }


}
