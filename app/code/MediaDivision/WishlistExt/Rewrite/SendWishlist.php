<?php

namespace MediaDivision\WishlistExt\Rewrite;

use Magento\Framework\App\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Session\Generic as WishlistSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Layout as ResultLayout;
use Magento\Captcha\Helper\Data as CaptchaHelper;
use Magento\Captcha\Observer\CaptchaStringResolver;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Captcha\Model\DefaultModel as CaptchaModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Customer;

class SendWishlist extends \Magento\Wishlist\Controller\Index\Send
{
    private $emailCodeFactory;

    protected $_customerHelperView;

    protected $inlineTranslation;

    protected $_transportBuilder;

    protected $_wishlistConfig;

    protected $wishlistProvider;

    protected $_customerSession;

    protected $_formKeyValidator;

    protected $wishlistSession;

    protected $scopeConfig;

    protected $storeManager;

    private $captchaHelper;

    private $captchaStringResolver;

    public function __construct(
        \MediaDivision\WishlistExt\Model\CodeFactory $emailCodeFactory,
        Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Wishlist\Controller\WishlistProviderInterface $wishlistProvider,
        \Magento\Wishlist\Model\Config $wishlistConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Customer\Helper\View $customerHelperView,
        WishlistSession $wishlistSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ?CaptchaHelper $captchaHelper = null,
        ?CaptchaStringResolver $captchaStringResolver = null
    ) {
        parent::__construct(
            $context,
            $formKeyValidator,
            $customerSession,
            $wishlistProvider,
            $wishlistConfig,
            $transportBuilder,
            $inlineTranslation,
            $customerHelperView,
            $wishlistSession,
            $scopeConfig,
            $storeManager,
            $captchaHelper,
            $captchaStringResolver
        );
        $this->emailCodeFactory = $emailCodeFactory;

        $this->_formKeyValidator = $formKeyValidator;
        $this->_customerSession = $customerSession;
        $this->wishlistProvider = $wishlistProvider;
        $this->_wishlistConfig = $wishlistConfig;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_customerHelperView = $customerHelperView;
        $this->wishlistSession = $wishlistSession;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->captchaHelper = $captchaHelper ?: ObjectManager::getInstance()->get(CaptchaHelper::class);
        $this->captchaStringResolver = $captchaStringResolver ?: ObjectManager::getInstance()->get(CaptchaStringResolver::class);
    }


    public function execute()
    {

        //        d($_POST);
        /**
*
         *
 * @var \Magento\Framework\Controller\Result\Redirect $resultRedirect
*/
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $captchaForName = 'share_wishlist_form';
        /**
*
         *
 * @var CaptchaModel $captchaModel
*/
        $captchaModel = $this->captchaHelper->getCaptcha($captchaForName);

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        $isCorrectCaptcha = $this->validateCaptcha($captchaModel, $captchaForName);

        $this->logCaptchaAttempt($captchaModel);

        if (!$isCorrectCaptcha) {
            $this->messageManager->addErrorMessage(__('Incorrect CAPTCHA'));
            $resultRedirect->setPath('*/*/share');
            return $resultRedirect;
        }

        $wishlist = $this->wishlistProvider->getWishlist();

        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }

        $sharingLimit = $this->_wishlistConfig->getSharingEmailLimit();
        $textLimit = $this->_wishlistConfig->getSharingTextLimit();
        $emailsLeft = $sharingLimit - $wishlist->getShared();
        $emailsLeft = 9999;

        $emails = $this->getRequest()->getPost('emails');
        $emails = empty($emails) ? $emails : explode(',', $emails);

        $error = false;
        $message = (string)$this->getRequest()->getPost('message');
        if (strlen($message) > $textLimit) {
            $error = __('Message length must not exceed %1 symbols', $textLimit);
        } else {
            $message = nl2br(htmlspecialchars($message));
            if (empty($emails)) {
                $error = __('Please enter an email address.');
            } else {
                if (count($emails) > $emailsLeft) {
                    $error = __('This wish list can be shared %1 more times.', $emailsLeft);
                } else {
                    foreach ($emails as $index => $email) {
                        $email = trim($email);
                        if (!\Zend_Validate::is($email, \Magento\Framework\Validator\EmailAddress::class)) {
                            $error = __('Please enter a valid email address.');
                            break;
                        }
                        $emails[$index] = $email;
                    }
                }
            }
        }

        if ($error) {
            $this->messageManager->addError($error);
            $this->wishlistSession->setSharingForm($this->getRequest()->getPostValue());
            $resultRedirect->setPath('*/*/share');
            return $resultRedirect;
        }
        /**
*
         *
 * @var \Magento\Framework\View\Result\Layout $resultLayout
*/
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $this->addLayoutHandles($resultLayout);
        $this->inlineTranslation->suspend();

        $sent = 0;

        try {
            $customer = $this->_customerSession->getCustomerDataObject();
            $customerName = $this->_customerHelperView->getCustomerName($customer);

            $message .= $this->getRssLink($wishlist->getId(), $resultLayout);
            $emails = array_unique($emails);
            $sharingCode = $wishlist->getSharingCode();

            try {
                foreach ($emails as $email) {
                    $collection = $this->emailCodeFactory->create()->getResourceCollection()
                        ->addFieldToFilter('email', $email)->addFieldToFilter('wishlist_id', $wishlist->getId());

                    if (count($collection)) {
                        foreach ($collection as $item) {
                            $item->delete();
                        }
                    }

                    $sharingEmailCode = sha1(uniqid() . time());
                    $this->emailCodeFactory->create()
                        ->setData(['wishlist_id' => $wishlist->getId(), 'code' => $sharingEmailCode, 'email' => $email])->save();
 
                    $transport = $this->_transportBuilder->setTemplateIdentifier(
                        $this->scopeConfig->getValue(
                            'wishlist/email/email_template',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        )
                    )->setTemplateOptions(
                        [
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => $this->storeManager->getStore()->getStoreId(),
                        ]
                    )->setTemplateVars(
                        [
                            'customer' => $customer,
                            'customerName' => $customerName,
                            'salable' => $wishlist->isSalable() ? 'yes' : '',
                            'items' => $this->getWishlistItems($resultLayout),
                            'viewOnSiteLink' => $this->_url->getUrl('*/shared/index', ['code' => $sharingCode, 'email_code' => $sharingEmailCode]),
                            'message' => $message,
                            'store' => $this->storeManager->getStore(),
                        ]
                    )->setFrom(
                        $this->scopeConfig->getValue(
                            'wishlist/email/email_identity',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        )
                    )->addTo(
                        $email
                    )->getTransport();

                    $transport->sendMessage();

                    $sent++;
                }
            } catch (\Exception $e) {
                $wishlist->setShared($wishlist->getShared() + $sent);
                $wishlist->save();
                throw $e;
            }
            $wishlist->setShared($wishlist->getShared() + $sent);
            $wishlist->save();

            $this->inlineTranslation->resume();

            $this->_eventManager->dispatch('wishlist_share', ['wishlist' => $wishlist]);
            $this->messageManager->addSuccess(__('Your wish list has been shared.'));
            $resultRedirect->setPath('*/*', ['wishlist_id' => $wishlist->getId()]);
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->messageManager->addError($e->getMessage());
            $this->wishlistSession->setSharingForm($this->getRequest()->getPostValue());
            $resultRedirect->setPath('*/*/share');
            return $resultRedirect;
        }
    }

    private function logCaptchaAttempt(CaptchaModel $captchaModel): void
    {
        /**
*
         *
 * @var Customer $customer
*/
        $customer = $this->_customerSession->getCustomer();
        $email = '';

        if ($customer->getId()) {
            $email = $customer->getEmail();
        }

        $captchaModel->logAttempt($email);
    }

    private function validateCaptcha(CaptchaModel $captchaModel, string $captchaFormName): bool
    {
        if ($captchaModel->isRequired()) {
            $word = $this->captchaStringResolver->resolve(
                $this->getRequest(),
                $captchaFormName
            );

            if (!$captchaModel->isCorrect($word)) {
                return false;
            }
        }

        return true;
    }
}
