<?php

namespace BOIPA\Payment\Controller\Hosted;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;

class Cancel extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Order
     */
    protected $orderModel;

    /**
     * @param Context $context
     * @param Order $orderModel
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Order $orderModel,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->orderModel = $orderModel;
        $this->resultPageFactory =$resultPageFactory;
    }

    /**
     * Customer canceled payment on gateway side.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $this->orderModel->cancel('');
        return $this->resultPageFactory->create();
    }
}
