<?php

namespace MediaDivision\Campaigns\Controller\Adminhtml\Campaign;

class Save extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Campaign';

    protected $resultPageFactory;
    protected $itemFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \MediaDivision\Campaigns\Model\CampaignFactory $itemFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->itemFactory = $itemFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue('campaign');

        if ($data) {
            try {
                $item = $this->itemFactory->create();
                if (isset($data["id"])) {
                    $item->load($data["id"]);
                }

                $data = array_filter(
                    $data,
                    function ($value) {
                        return $value !== '';
                    }
                );

                $item->setData($data);
                $item->save();
                $this->messageManager->addSuccess(__('Successfully saved the item.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                return $resultRedirect->setPath('*/*/grid');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData($data);
                if (isset($item) && $item->getId()) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $item->getId()]);
                } else {
                    return $resultRedirect->setPath('*/*/grid');
                }
            }
        }

        return $resultRedirect->setPath('*/*/grid');
    }
}
