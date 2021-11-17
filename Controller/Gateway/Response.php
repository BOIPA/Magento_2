<?php

namespace BOIPA\Payment\Controller\Gateway;

use BOIPA;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Validator\Exception;

class Response extends Action
{

    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Exception
     */
    public function execute()
    {
        $objectManager = ObjectManager::getInstance();
        $request = $objectManager->get('\Magento\Framework\App\Request\Http');
        $requestData = $request->getPost();
        $orderId = $request->getParam('orderid');

        $urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');

        $orders = $objectManager->get('Magento\Sales\Model\Order');
        $order = $orders->loadByIncrementId($orderId);

        $payment = $order->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        if (!isset($additionalInformation['originalMerchantTxId']) || $additionalInformation['originalMerchantTxId'] == '') {
            $this->_redirect($urlInterface->getUrl('boipa_payment/standard/cancel'));
            return;
        }

        try {
            $realStatus = $this->getRealTransactionStatus($order, $additionalInformation);
        } catch(Exception $e) {
            $this->_redirect($urlInterface->getUrl('boipa_payment/standard/cancel'));
            return;
        }

        $url = $urlInterface->getUrl('boipa_payment/standard/cancel');
        if($requestData['result'] === 'success') {
            if($realStatus === 'SET_FOR_CAPTURE' ||
                    $realStatus === 'NOT_SET_FOR_CAPTURE' ||
                    $realStatus === 'CAPTURED') {
                $order->setState("processing")
                    ->setStatus("processing")
                    ->addStatusHistoryComment('Order status processing')
                    ->setIsCustomerNotified(true);
                $order->save();

                $url = $urlInterface->getUrl('checkout/onepage/success/');
            }
        } else {
            $order->setState("canceled")
                ->setStatus("canceled")
                ->addStatusHistoryComment('Order cancelled due to failed transaction')->setIsCustomerNotified(true);
            $order->save();
        }

        $this->_redirect($url);
    }

    /**
     * @param $order
     * @param $additional
     * @return mixed
     * @throws Exception
     */
    protected function getRealTransactionStatus($order, $additional) {
        $objectManager = ObjectManager::getInstance();
        $urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');

        $ipgApi = $this->newIpgApi();
        $checkStatus = $ipgApi->get_status()
            ->allowOriginUrl($urlInterface->getBaseUrl())
            ->timestamp(time() * 1000)
            ->txId($order->getId())
            ->merchantTxId($additional['originalMerchantTxId']);

        $r = $checkStatus->execute();
        if($r->result != 'success')
            throw new Exception('Communication with payment gateway failed');

        return $r->status;
    }

    /**
     * @return mixed
     */
    protected function newIpgApi() {
        $payments = new Payments();

        if ($this->getConfigData('testmode') == 1) {
            $payments = $payments->testEnvironment(array(
                'sessionTestToken' => $this->getConfigData('testtoken'),
                'testAction' => $this->getConfigData('testpayment'),
                'baseTest' => $this->getConfigData('testbase'),
                'jsTestToken' => $this->getConfigData('testjs')
            ));
        } else {
            $payments = $payments->productionEnvironment(array(
                'sessionLiveToken' => $this->getConfigData('livetoken'),
                'liveAction' => $this->getConfigData('livepayment'),
                'baseLive' => $this->getConfigData('livebase'),
                'jsLiveToken' => $this->getConfigData('livejs')
            ));
        }

        return $payments->merchantId($this->getConfigData('merchant_key'))
            ->password($this->getConfigData('merchant_password'));
    }

}
