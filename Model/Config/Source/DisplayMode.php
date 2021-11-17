<?php

namespace BOIPA\Payment\Model\Config\Source;

class DisplayMode implements \Magento\Framework\Data\OptionSourceInterface
{
    const DISPLAY_MODE_EMBEDDED = 'embedded';
    const DISPLAY_MODE_REDIRECT = 'redirect';
    const DISPLAY_MODE_STANDALONE = 'standalone';
    const DISPLAY_MODE_IFRAME = 'iframe';
    const DISPLAY_MODE_HOSTEDPAY = 'hostedPayPage';

    /**
     * Possible display modes.
     *
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => self::DISPLAY_MODE_HOSTEDPAY, 'label' => 'hostedPayPage'],
            ['value' => self::DISPLAY_MODE_IFRAME, 'label' => 'Iframe'],
            ['value' => self::DISPLAY_MODE_REDIRECT, 'label' => 'Redirect']
        ];
    }
}
