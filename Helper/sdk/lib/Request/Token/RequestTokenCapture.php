<?php

namespace BOIPA\Payment\Helper\sdk\lib\Request\Token;


use BOIPA\Payment\Helper\sdk\lib\Payments;

class RequestTokenCapture extends RequestTokenRefund {

    public function __construct() {
        parent::__construct();
        $this->_data["action"] = Payments::ACTION_CAPTURE;
    }

}
