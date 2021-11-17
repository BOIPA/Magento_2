<?php
namespace BOIPA\Payment\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Currency implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray() {
        return [
            ['value' => 'AED', 'label' =>  __('United Arab Emirates dirham (AED)')],
            ['value' => 'EUR', 'label' =>  __('Euro(EUR)')],
            ['value' => 'USD', 'label' =>  __('United States dollar(USD)')]
        ];
    }
}
