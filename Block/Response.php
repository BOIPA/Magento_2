<?php

namespace BOIPA\Payment\Block;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Response extends Template
{
    const REGISTRY_PARAMS_KEY = 'boipa_payment_params';

    /**
     * Registry
     *
     * @var Registry
     */
    protected $registry;

    /**
     * GatewayResponse constructor.
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return Template
     */
    public function _prepareLayout()
    {
        $params = $this->registry->registry(self::REGISTRY_PARAMS_KEY);
        $this->setParams($params);

        return parent::_prepareLayout();
    }
}
