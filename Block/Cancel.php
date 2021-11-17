<?php

namespace BOIPA\Payment\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Cancel extends Template
{
    /**
     * @var $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var UrlInterface
     */
    protected $_urlInterface;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        UrlInterface $urlInterface
    ) {
	$this->_checkoutSession = $checkoutSession;
    $this->_urlInterface = $urlInterface;
        parent::__construct($context);

    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function cancel()
    {
        return __('IPG redirect');
    }

    /**
     * @return UrlInterface
     */
    public function getPageURL()
    {
        return $this->_urlInterface;
    }
}
