<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model;

use ForumPay\PaymentGateway\PHPClient\Http\Exception\ApiExceptionInterface;
use ForumPay\PaymentGateway\OpenCartExtension\Exception\ApiHttpException;
use ForumPay\PaymentGateway\OpenCartExtension\Logger\ForumPayLogger;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Data\Payment;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Payment\ForumPay;

class StartPayment
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
     * @throws ApiHttpException
     * @throws \Exception
     * @throws ApiExceptionInterface
     */
    public function execute(Request $request): Payment
    {
        try {
            $currency = $request->getRequired('currency');
            $kycPin = $request->get('kycPin');
            $this->logger->info('StartPayment entrypoint called.', ['currency' => $currency]);

            $response = $this->forumPay->startPayment($currency, $kycPin);

            $notices = [];
            foreach ($response->getNotices() as $notice) {
                $notices[] = new Payment\Notice($notice['code'], $notice['message']);
            }

            $payment = new Payment(
                $response->getPaymentId(),
                $response->getAddress(),
                '',
                $response->getMinConfirmations(),
                $response->getFastTransactionFee(),
                $response->getFastTransactionFeeCurrency(),
                $response->getQr(),
                $response->getQrAlt(),
                $response->getQrImg(),
                $response->getQrAltImg(),
                $notices
            );

            $this->logger->info('StartPayment entrypoint finished.');

            return $payment;
        } catch (ApiExceptionInterface $e) {
            $this->logger->logApiException($e);
            $errorCode = $e->getErrorCode();

            if ($errorCode === null) {
                throw new ApiHttpException($e, 3050);
            }

            if ($errorCode === 'payerAuthNeeded' ||
                $errorCode === 'payerKYCNotVerified' ||
                $errorCode === 'payerKYCNeeded' ||
                $errorCode === 'payerEmailVerificationCodeNeeded'
            ) {
                try {
                    $this->forumPay->requestKyc();
                } catch (ApiExceptionInterface $e) {
                    throw new ApiHttpException($e, 3050);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage(), 3050, $e);
                }
                throw new ApiHttpException($e, 3051);
            } elseif (substr($errorCode, 0, 5) === 'payer') {
                throw new ApiHttpException($e, 3052);
            } else {
                throw new ApiHttpException($e, 3050);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), $e->getTrace());
            throw new \Exception($e->getMessage(), 3100, $e);
        }
    }
}
