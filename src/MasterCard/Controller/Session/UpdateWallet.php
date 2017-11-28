<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
namespace OnTap\MasterCard\Controller\Session;

use Magento\Framework\App\Action\Context;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use OnTap\MasterCard\Gateway\Request\Masterpass\OAuth;
use Magento\Checkout\Api\PaymentInformationManagementInterface;

abstract class UpdateWallet extends \Magento\Framework\App\Action\Action
{
    const UPDATE_WALLET_COMMAND = 'update_wallet';

    const STATUS = 'mpstatus';

    const OAUTH_TOKEN = 'oauth_token';
    const OAUTH_VERIFIER = 'oauth_verifier';
    const CHECKOUT_URL = 'checkout_resource_url';

    /**
     * @var CommandPoolInterface
     */
    protected $commandPool;

    /**
     * @var PaymentDataObjectFactory
     */
    protected $paymentDataObjectFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var PaymentInformationManagementInterface
     */
    protected $paymentInformationManagement;

    /**
     * UpdateWallet constructor.
     * @param Context $context
     * @param CommandPoolInterface $commandPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     */
    public function __construct(
        Context $context,
        CommandPoolInterface $commandPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        PaymentInformationManagementInterface $paymentInformationManagement
    ) {
        parent::__construct($context);
        $this->commandPool = $commandPool;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->checkoutSession = $checkoutSession;
        $this->paymentInformationManagement = $paymentInformationManagement;
    }

    /**
     * @return string
     */
    abstract protected function getMethod();

    /**
     * @throws \Exception
     */
    public function execute()
    {
        /**
        On Success, Masterpass returns the following parameters:
        mpstatus: String that indicates whether the Masterpass flow resulted in success, failure, or cancel.
        checkout_resource_url: The API URL that will be used to retrieve checkout information in Step 7.
        oauth_verifier: The verifier token that is used If the successCallback parameter to retrieve the access token in Step 6.
        oauth_token: The request token that is used to retrieve the access token in Step 6. This token has the same value as the request token that is generated in Step 1.
         *
        Perform an Update Session From Wallet operation to get the payer's payment and shipping details from Masterpass. You need to provide the following parameters in this operation.

        Session ID: The identifier for the payment session as returned by the Create Session operation.
        order.walletProvider: Set this to MASTERPASS_ONLINE.
        wallet.masterpass.oauthToken: The oauth_token retrieved from the callback.
        wallet.masterpass.oauthVerifier: The oauth_verifier retrieved from the callback.
        wallet.masterpass.checkoutUrl: The checkout_resource_url retrieved from the callback.
         */

        if ($this->getRequest()->getParam(self::STATUS) !== 'success') {
            throw new \Exception('Invalid Status');
        }

        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $paymentDO = $this->paymentDataObjectFactory->create($payment);

        $this->commandPool
            ->get(self::UPDATE_WALLET_COMMAND)
            ->execute([
                'payment' => $paymentDO,
                OAuth::OAUTH_TOKEN => $this->getRequest()->getParam(self::OAUTH_TOKEN),
                OAuth::OAUTH_VERIFIER => $this->getRequest()->getParam(self::OAUTH_VERIFIER),
                OAuth::CHECKOUT_URL => $this->getRequest()->getParam(self::CHECKOUT_URL),
            ]);

        $payment->setMethod($this->getMethod());

        $this->paymentInformationManagement->savePaymentInformationAndPlaceOrder(
            $quote->getId(),
            $payment
        );

//        $this->checkoutSession->getQuote()->getPayment()->save();
//        $this->checkoutSession->getQuote()->save();

        //return $this->_redirect('mpgs/review/index');
    }
}