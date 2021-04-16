<?php

namespace BOIPA\Payment\Helper\sdk\lib\Request\Action;

use BOIPA\Payment\Helper\sdk\lib\Payments;
use BOIPA\Payment\Helper\sdk\lib\Request\RequestAction;

class RequestActionAvailable extends RequestAction {

    protected $_params = array(
        "merchantId" => array("type" => "mandatory"),
        "token" => array("type" => "mandatory"),
        "action" => array(
            "type" => "mandatory",
            "values" => array(Payments::ACTION_AVAILABLE_PAYMENT_SOLUTION),
        ),
    );

    public function __construct() {
        parent::__construct();
        $this->_data["action"] = Payments::ACTION_AVAILABLE_PAYMENT_SOLUTION;
    }

}
