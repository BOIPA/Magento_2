<?php

namespace BOIPA\Payment\Helper\sdk\lib\Request\Token;


use BOIPA\Payment\Helper\sdk\lib\Payments;

class RequestTokenPurchase extends RequestTokenAuth {

    public function __construct() {
        parent::__construct();
        $this->_data["action"] = Payments::ACTION_PURCHASE;
    }

}
