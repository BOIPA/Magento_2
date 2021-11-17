<?php

namespace BOIPA\Payment\Helper\sdk\lib\Request\Action;

use BOIPA\Payment\Helper\sdk\lib\Payments;
use BOIPA\Payment\Helper\sdk\lib\Request\RequestAction;

class RequestActionRefund extends RequestAction {

    protected $_params = array(
        "merchantId" => array("type" => "mandatory"),
        "token" => array("type" => "mandatory"),
    );

    public function __construct() {
        parent::__construct();
        $this->_data["action"] = Payments::ACTION_REFUND;
    }

}
