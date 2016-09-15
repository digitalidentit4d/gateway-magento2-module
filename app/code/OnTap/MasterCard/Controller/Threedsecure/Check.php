<?php
/**
 * Copyright (c) 2016. On Tap Networks Limited.
 */

namespace OnTap\MasterCard\Controller\Threedsecure;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Action\Context;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use OnTap\MasterCard\Gateway\Response\ThreeDSecure\CheckHandler;
use Magento\Payment\Gateway\Command\CommandPoolFactory;

/**
 * Class Check
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Check extends \Magento\Framework\App\Action\Action
{
    const CHECK_ENROLMENT = '3ds_enrollment';
    const CHECK_ENROLMENT_TYPE = 'TnsThreeDSecureEnrollmentCommand';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var CommandPoolFactory
     */
    private $commandPoolFactory;

    /**
     * Check constructor.
     * @param CommandPoolFactory $commandPoolFactory
     * @param Session $checkoutSession
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param JsonFactory $jsonFactory
     * @param Context $context
     */
    public function __construct(
        CommandPoolFactory $commandPoolFactory,
        Session $checkoutSession,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        JsonFactory $jsonFactory,
        Context $context
    ) {
        parent::__construct($context);
        $this->commandPoolFactory = $commandPoolFactory;
        $this->checkoutSession = $checkoutSession;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $jsonResult = $this->jsonFactory->create();
        try {
            // @todo: maybe can be done with virtualTypes at di.xml somehow
            $commandPool = $this->commandPoolFactory->create([
                'commands' => [
                    static::CHECK_ENROLMENT => static::CHECK_ENROLMENT_TYPE
                ]
            ]);

            $paymentDataObject = $this->paymentDataObjectFactory->create($quote->getPayment());

            $commandPool
                ->get(static::CHECK_ENROLMENT)
                ->execute([
                    'payment' => $paymentDataObject,
                    'amount' => $quote->getGrandTotal(),
                ]);

            $checkData = $paymentDataObject
                ->getPayment()
                ->getAdditionalInformation(CheckHandler::THREEDSECURE_CHECK);

            $jsonResult->setData([
                'result' => $checkData['status']
            ]);
        } catch (\Exception $e) {
            $jsonResult
                ->setHttpResponseCode(400)
                ->setData([
                    'message' => $e->getMessage()
                ]);
        }

        return $jsonResult;
    }
}
