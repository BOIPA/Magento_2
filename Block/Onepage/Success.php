<?php

namespace BOIPA\Payment\Block\Onepage;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template\Context;

class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Session
     */
    private $_checkoutSession;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    protected function _toHtml()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        if (!$order) {
            return '';
        }
        if ($order->getId()) {
            if ($order->getPayment()->getMethodInstance()->getCode() === 'boipa_payment') {
                $this->addData(
                    [
                    'is_boipa' => true
                    ]
                );

                return parent::_toHtml();
            }
        }

        return '';
    }
}
