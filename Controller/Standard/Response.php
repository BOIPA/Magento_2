<?php
namespace BOIPA\Payment\Controller\Standard;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Response extends Action
{

    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param Context  $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);

    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws Exception
     */
    public function execute()
    {
        $objectManager = ObjectManager::getInstance();
        $request = $objectManager->get('\Magento\Framework\App\Request\Http');
        $requestData =$request->getPost();
        $orderId = $request->getParam('orderid');

        $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId);
        $urlInterface   = $objectManager->get('\Magento\Framework\UrlInterface');

        if($requestData['result'] === 'success')
        {
            $orders = $objectManager->get('Magento\Sales\Model\Order');
            $order = $orders->loadByIncrementId($orderId);

            $order->setState("processing")
                ->setStatus("processing")
                ->addCommentToStatusHistory('Order status processing')
                ->setIsCustomerNotified(true);

            $order->save();
            $this->_redirect($urlInterface->getUrl('checkout/onepage/success/'));

        } else if($requestData['result'] === 'error') {

            $orders = $objectManager->get('Magento\Sales\Model\Order');
            $order = $orders->loadByIncrementId($orderId);
            $order->setState("canceled")
                ->setStatus("canceled")
                ->addCommentToStatusHistory('Order cancelled due to failed transaction')
                ->setIsCustomerNotified(true);

            $order->save();

            $this->_redirect($urlInterface->getUrl('boipa_payment/standard/cancel'));
        }

    }

}
