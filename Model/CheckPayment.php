<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model;

use ForumPay\PaymentGateway\PHPClient\Http\Exception\ApiExceptionInterface;
use ForumPay\PaymentGateway\OpenCartExtension\Exception\ApiHttpException;
use ForumPay\PaymentGateway\OpenCartExtension\Exception\TransactionDetailsMissingException;
use ForumPay\PaymentGateway\OpenCartExtension\Logger\ForumPayLogger;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Data\PaymentDetails;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Data\PaymentDetails\Underpayment;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Payment\ForumPay;

class CheckPayment
{
    /**
     * ForumPay payment model
     *
     * @var ForumPay
     */
    private ForumPay $forumPay;

    /**
     * @var ForumPayLogger
     */
    private ForumPayLogger $logger;

    /**
     * Constructor
     *
     * @param ForumPay $forumPay
     * @param ForumPayLogger $logger
     */
    public function __construct(ForumPay $forumPay, ForumPayLogger $logger)
    {
        $this->forumPay = $forumPay;
        $this->logger = $logger;
    }

    /**
     * @throws TransactionDetailsMissingException
     * @throws ApiHttpException
     * @throws \Exception
     */
    public function execute(Request $request): PaymentDetails
    {
        try {
            $paymentId = $request->getRequired('payment_id');

            $this->logger->info('CheckPayment entrypoint called.', ['paymentId' => $paymentId]);

            $response = $this->forumPay->checkPayment($paymentId);

            if ($response->getUnderpayment()) {
                $underPayment = new Underpayment(
                    $response->getUnderpayment()->getAddress(),
                    $response->getUnderpayment()->getMissingAmount(),
                    $response->getUnderpayment()->getQr(),
                    $response->getUnderpayment()->getQrAlt(),
                    $response->getUnderpayment()->getQrImg(),
                    $response->getUnderpayment()->getQrAltImg()
                );
                $this->logger->debug('CheckPayment - Underpayment.', ['paymentId' => $paymentId]);
            }

            $paymentDetails = new PaymentDetails(
                $response->getReferenceNo(),
                $response->getInserted(),
                $response->getInvoiceAmount(),
                $response->getType(),
                $response->getInvoiceCurrency(),
                $response->getAmount(),
                $response->getMinConfirmations(),
                $response->isAcceptZeroConfirmations(),
                $response->isRequireKytForConfirmation(),
                $response->getCurrency(),
                $response->isConfirmed(),
                $response->getConfirmedTime(),
                $response->getReason(),
                $response->getPayment(),
                $response->getSid(),
                $response->getConfirmations(),
                $response->getAccessToken(),
                $response->getAccessUrl(),
                $response->getWaitTime(),
                $response->getStatus(),
                $response->isCancelled(),
                $response->getCancelledTime(),
                $response->getPrintString(),
                $response->getState(),
                $underPayment ?? null,
            );

            $this->logger->info('CheckPayment entrypoint finished.');

            return $paymentDetails;
        } catch (TransactionDetailsMissingException $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
            throw new TransactionDetailsMissingException($e->getMessage(), 4006, $e);
        } catch (ApiExceptionInterface $e) {
            $this->logger->logApiException($e);
            throw new ApiHttpException($e, 4050);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), $e->getTrace());
            throw new \Exception($e->getMessage(), 4100, $e);
        }
    }
}
