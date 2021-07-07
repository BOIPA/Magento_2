<?php

namespace BOIPA\Payment\Helper\sdk\lib\Response\Error;

use BOIPA\Payment\Helper\sdk\lib\Response;

class ResponseErrorErrors extends Response {

<<<<<<< HEAD
    public function __construct($errors = array())
        {
            if (is_array($errors)) {
                foreach ($errors as $error) {
                    if (is_array($error)) {
                        $this->_data['errors'] = array_key_exists('messageCode', $error) ? $error['messageCode'] : print_r($error, true);
                    }else{
                        $this->_data['errors'] = $error;
                    }
                }
            } else {
                $this->_data['errors'] = $errors;
            }
        }
=======
    public function __construct($errors = array()) {
        if (is_array($errors)) {
            foreach ($errors as $error) {
                $this->_data[$error] = $error;
            }
        } else {
            $this->_data[$errors] = $errors;
        }
    }
>>>>>>> 32b2998bc5a466f484d7fc4e93e5fd4489bc3e30

}
