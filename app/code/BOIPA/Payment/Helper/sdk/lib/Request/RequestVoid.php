<?php

namespace BOIPA\Payment\Helper\sdk\lib\Request;

use BOIPA\Payment\Helper\sdk\lib\Request\Action\RequestActionVoid;
use BOIPA\Payment\Helper\sdk\lib\Request\Token\RequestTokenVoid;
use BOIPA\Payment\Helper\sdk\lib\Request\RequestRefund;

class RequestVoid extends RequestRefund {

    public function __construct($values = array()) {
        parent::__construct();
        $this->_token_request = new RequestTokenVoid($values);
        $this->_action_request = new RequestActionVoid($values);
    }

}
