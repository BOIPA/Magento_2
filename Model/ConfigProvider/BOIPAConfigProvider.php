<?php

namespace BOIPA\Payment\Model\ConfigProvider;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

class BOIPAConfigProvider implements ConfigProviderInterface
{

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;
    /**
     * @var \BOIPA\Payment\Helper\Helper
     */

    protected $_helper;
    /**
     * @var string[]
     */
    protected $_methodCodes = [
        'boipa_payment',
    ];

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * @param PaymentHelper $paymentHelper
     * @param \BOIPA\Payment\Helper\Helper $helper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        \BOIPA\Payment\Helper\Helper $helper
    )
    {
        $this->_paymentHelper = $paymentHelper;
        $this->_helper = $helper;
        foreach ($this->_methodCodes as $code) {
            $this->methods[$code] = $this->_paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * @return array|\array[][]
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'boipa_payment' => [
                ],
            ],
        ];

        foreach ($this->_methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment'] [$code]['redirectUrl'] = $this->getMethodRedirectUrl($code);
                $config['payment'] [$code]['displayMode'] = $this->_helper->getConfigData('display_mode');
            }
        }

        return $config;
    }

    /**
     * @param $code
     * @return mixed
     */
    protected function getMethodRedirectUrl($code)
    {
        return $this->methods[$code]->getCheckoutRedirectUrl();
    }
}
