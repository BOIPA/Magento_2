<?php

namespace BOIPA\Payment\Model\Config\Source;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

/**
 * Order Statuses source model.
 */
class Cancelled extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_CANCELED,
    ];
}
