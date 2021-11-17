<?php
namespace BOIPA\Payment\Model\Source;

class Cctype extends \Magento\Payment\Model\Source\Cctype
{

    /**
     * @return string[]
     */
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DI', 'JCB', 'OT');
    }
}
