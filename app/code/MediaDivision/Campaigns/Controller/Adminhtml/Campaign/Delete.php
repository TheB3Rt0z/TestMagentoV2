<?php

namespace MediaDivision\Campaigns\Controller\Adminhtml\Campaign;

use MediaDivision\Campaigns\Model\Campaign;
use Magento\Backend\App\Action;

class Delete extends \Magento\Backend\App\Action
{

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        if (!($item = $this->_objectManager->create(Campaign::class)->load($id))) {
            $this->messageManager->addError(__('Unable to proceed. Please, try again.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/grid', ['_current' => true]);
        }
        try {
            $item->delete();
            $this->messageManager->addSuccess(__('Your item has been deleted !'));
        } catch (Exception $e) {
            $this->messageManager->addError(__('Error while trying to delete item: '));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/grid', ['_current' => true]);
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/grid', ['_current' => true]);
    }
}
