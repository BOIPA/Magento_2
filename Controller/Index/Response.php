<?php
namespace BOIPA\Payment\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Response extends Action
{
    /**
     * @var ResultFactory
     */
    protected $resultRedirect;

    /**
     * @param ResultFactory $result
     */
    public function __construct(
        ResultFactory $result
    ){
        $this->resultRedirect = $result;
    }

    /**
     * @return Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);

        if ($this->getConfigData('ipg_mode') === 'standard') {
            $resultRedirect->setPath('BOIPA_Payment/Controller/Standard');
        } else {
            $resultRedirect->setPath('BOIPA_Payment/Controller/Hosted');
        }

        return $resultRedirect;
    }

}
