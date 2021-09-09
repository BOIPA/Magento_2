<?php

namespace BOIPA\Payment\Helper\sdk\lib\Request\Token;


use BOIPA\Payment\Helper\sdk\lib\Payments;

class RequestTokenVoid extends RequestTokenRefund
{
    protected $_params = array(
        "merchantId" => array("type" => "mandatory"),
        "originalMerchantTxId" => array("type" => "mandatory"),
        "password" => array("type" => "mandatory"),
        "action" => array(
            "type" => "mandatory",
            "values" => array(Payments::ACTION_VOID),
        ),
        "timestamp" => array("type" => "mandatory"),
        "allowOriginUrl" => array("type" => "mandatory"),
        "originalTxId" => array("type" => "optional"),
        "agentId" => array("type" => "optional"),
    );

    public function __construct()
    {
        parent::__construct();
        $this->_data["action"] = Payments::ACTION_VOID;
    }

}
