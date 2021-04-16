<?php

namespace BOIPA\Payment\Controller\Hosted;

use Magento\Framework\App\Action\Action;

class Redirect extends Action
{
    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $_quote = false;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

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
