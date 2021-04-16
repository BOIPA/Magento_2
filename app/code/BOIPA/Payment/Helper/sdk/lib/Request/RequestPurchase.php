<?php

namespace BOIPA\Payment\Helper\sdk\lib\Request;

use BOIPA\Payment\Helper\sdk\lib\Request\Action\RequestActionPurchase;
use BOIPA\Payment\Helper\sdk\lib\Request\Token\RequestTokenPurchase;

class RequestPurchase extends RequestAuth {

    public function __construct($values = array()) {
        parent::__construct();
        $this->_token_request = new RequestTokenPurchase($values);
        $this->_action_request = new RequestActionPurchase($values);
    }

}
