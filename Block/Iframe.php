<?php

namespace BOIPA\Payment\Block;

use BOIPA\Payment\Helper\Helper;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Symfony\Component\Config\Definition\Exception\Exception;

class Iframe extends Form
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Checkout\Model\Order
     */
    protected $_order;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var
     */
    protected $_urlInterface;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * Process constructor.
     *
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        Helper $helper,
        array $data = []
    ) {
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        parent::__construct($context, $data);
        $this->_getOrder();
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getFormUrl()
    {
        $result = '';
        try {
            $order = $this->_order;
            if ($order->getPayment()) {
                $result = $this->_order->getPayment()->getMethodInstance()->getFormUrl();
            }
        } catch (Exception $e) {
            $this->_helper->logDebug('Could not get redirect form url: '.$e);
            throw($e);
        }

        return $result;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getMerchantLandingPageUrl(){
        $result = '';
        try {
            $order = $this->_order;
            if ($order->getPayment()) {
                $result = $this->_order->getPayment()->getMethodInstance()->getMerchantLandingPageUrl();
            }
        } catch (Exception $e) {
            $this->_helper->logDebug('Could not get MerchantLandingPageUrl: '.$e);
            throw($e);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getAllowOriginUrl(){

        $url = $this->_urlBuilder->getBaseUrl();
        $parse_result = parse_url($url);
        if(isset($parse_result['port'])){
            $allowOriginUrl = $parse_result['scheme']."://".$parse_result['host'].":".$parse_result['port'];
        }else{
            $allowOriginUrl = $parse_result['scheme']."://".$parse_result['host'];
        }

        return $allowOriginUrl;
    }

    /**
     * @return bool|string
     */
    public function getJsUrl(){
        return $this->_helper->getJsUrl();
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getFormMethod() {
        $result = '';
        try {
            $order = $this->_order;
            if ($order->getPayment()) {
                $result = $this->_order->getPayment()->getMethodInstance()->getFormMethod();
            }
        } catch (Exception $e) {
            $this->_helper->logDebug('Could not get redirect form method: '.$e);
            throw($e);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getFormFields()
    {
        $result = [];
        try {
            if ($this->_order->getPayment()) {
                $result = $this->_order->getPayment()->getMethodInstance()->getFormFields();
            }
        } catch (Exception $e) {
            $this->_helper->logDebug('Could not get redirect form fields: '.$e);
        }

        return $result;
    }

    /**
     * Get order object.
     *
     * @return Order
     */
    private function _getOrder()
    {
        if (!$this->_order) {
            $incrementId = $this->_getCheckout()->getLastRealOrderId();
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }

        return $this->_order;
    }

    /**
     * Get frontend checkout session object.
     *
     * @return Session
     */
    private function _getCheckout()
    {
        return $this->_checkoutSession;
    }
}
