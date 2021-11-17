<?php

namespace BOIPA\Payment\Controller\Gateway;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class Redirect extends Action
{
    /**
     * @var Quote
     */
    protected $_quote = false;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * Set redirect.
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }
}
