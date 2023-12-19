<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model\Payment;

use ForumPay\PaymentGateway\OpenCartExtension\Model\Configuration;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\ApiExceptionInterface;
use ForumPay\PaymentGateway\PHPClient\PaymentGatewayApi;
use ForumPay\PaymentGateway\PHPClient\PaymentGatewayApiInterface;
use ForumPay\PaymentGateway\PHPClient\Response\CheckPaymentResponse;
use ForumPay\PaymentGateway\PHPClient\Response\GetCurrencyListResponse;
use ForumPay\PaymentGateway\PHPClient\Response\GetRateResponse;
use ForumPay\PaymentGateway\PHPClient\Response\GetTransactions\TransactionInvoice;
use ForumPay\PaymentGateway\PHPClient\Response\RequestKycResponse;
use ForumPay\PaymentGateway\PHPClient\Response\StartPaymentResponse;
use Psr\Log\LoggerInterface;

/**
 * ForumPay payment method model
 */
class ForumPay
{
    /**
     * @var PaymentGatewayApiInterface
     */
    private PaymentGatewayApiInterface $apiClient;

    /**
     * @var Configuration
     */
    private Configuration $configuration;

    /**
     * @var OrderManager
     */
    private OrderManager $orderManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $psrLogger;

    /**
     * Constructor
     *
     * @param Configuration $configuration
     * @param OrderManager $orderManager
     * @param LoggerInterface $psrLogger
     */
    public function __construct(
        Configuration $configuration,
        OrderManager $orderManager,
        LoggerInterface $psrLogger
    ) {
        $this->apiClient = new PaymentGatewayApi(
            $configuration->getApiUrl(),
            $configuration->getMerchantApiUser(),
            $configuration->getMerchantApiSecret(),
            sprintf(
                'fp-pgw[%s] PS %s on PHP %s',
                $configuration->getPluginVersion(),
                'WC',
                $configuration->getOpenCartVersion(),
                phpversion()
            ),
            $configuration->getStoreLocale(),
            null,
            $psrLogger
        );

        $this->orderManager = $orderManager;
        $this->configuration = $configuration;
        $this->psrLogger = $psrLogger;
    }

    /**
     * Return the list of all available currencies as defined on merchant account
     *
     * @throws ApiExceptionInterface
     * @throws \Exception
     */
    public function getCryptoCurrencyList(): GetCurrencyListResponse
    {
        $currency = $this->orderManager->getOrderCurrency();

        if ($currency === null) {
            throw new \Exception('Store currency could not be determined');
        }

        return $this->apiClient->getCurrencyList($currency);
    }

    /**
     * Get rate for a requested currency
     *
     * @throws ApiExceptionInterface
     */
    public function getRate(string $currency): GetRateResponse
    {
        return $this->apiClient->getRate(
            $this->configuration->getPosId(),
            $this->orderManager->getOrderCurrency(),
            $this->orderManager->getOrderTotal(),
            $currency,
            $this->configuration->isAcceptZeroConfirmations() ? 'true' : 'false',
            null,
            null,
            null
        );
    }

    /**
     * @return RequestKycResponse
     * @throws ApiExceptionInterface
     */
    public function requestKyc(): RequestKycResponse
    {
        return $this->apiClient->requestKyc($this->orderManager->getOrderCustomerEmail());
    }

    /**
     * Initiate a start payment and crate order on ForumPay
     *
     * @param string $currency
     * @param ?string $kycPin
     * @param string $paymentId
     * @return StartPaymentResponse
     * @throws ApiExceptionInterface
     */
    public function startPayment(
        string $currency,
        ?string $kycPin,
        string $paymentId = ''
    ): StartPaymentResponse {
        $orderId = $this->orderManager->getOrderId();

        $response = $this->apiClient->startPayment(
            $this->configuration->getPosId(),
            $this->orderManager->getOrderCurrency(),
            $paymentId,
            $this->orderManager->getOrderTotal(),
            $currency,
            $orderId,
            $this->configuration->isAcceptZeroConfirmations() ? 'true' : 'false',
            $this->orderManager->getOrderCustomerIpAddress(),
            $this->orderManager->getOrderCustomerEmail(),
            $this->orderManager->getOrderCustomerId(),
            'false',
            '',
            'false',
            null,
            null,
            null,
            null,
            null,
            $kycPin
        );

        $this->orderManager->saveOrderMetaData(
            $orderId,
            $response->getPaymentId(),
            $response->toArray()
        );

        $this->cancelAllPayments($orderId, $response->getPaymentId());
        $this->orderManager->unsetOrderId();
        return $response;
    }


    /**
     * Get detailed payment information for ForumPay
     *
     * @throws ApiExceptionInterface
     */
    public function checkPayment(string $paymentId): CheckPaymentResponse
    {
        $meta = $this->orderManager->getOrderMetaData($paymentId);
        $cryptoCurrency = $meta['currency'];
        $address = $meta['address'];

         $response = $this->apiClient->checkPayment(
            $this->configuration->getPosId(),
            $cryptoCurrency,
            $paymentId,
            $address
        );

        $orderId = $this->orderManager->getOrderIdByPaymentId($paymentId);

        if (strtolower($response->getStatus()) === 'cancelled') {
            if (!$this->checkAllPaymentsAreCanceled($orderId)) {
                $this->orderManager->updateOrderStatus($orderId, $response->getStatus());
                return $response;
            }
        }

        $updatedData = array_merge($meta, $response->toArray());

        $this->orderManager->saveOrderMetaData(
            $orderId,
            $paymentId,
            $updatedData,
            true
        );

        $this->orderManager->updateOrderStatus($orderId, $response->getStatus());

        return $response;
    }

    /**
     * Cancel give payment on ForumPay
     *
     * @param string $paymentId
     * @param string $reason
     * @param string $description
     * @return void
     *
     * @throws ApiExceptionInterface
     */
    public function cancelPaymentByPaymentId(
        string $paymentId,
        string $reason = '',
        string $description = ''
    ): void {
        $meta = $this->orderManager->getOrderMetaData($paymentId);
        $currency = $meta['currency'];
        $address = $meta['address'];
        $this->cancelPayment($paymentId, $currency, $address, $reason, $description);
    }

    /**
     * Cancel give payment on ForumPay
     *
     * @param string $paymentId
     * @param string $currency
     * @param string $address
     * @param string $reason
     * @param string $description
     *
     * @throws ApiExceptionInterface
     */
    public function cancelPayment(
        string $paymentId,
        string $currency,
        string $address,
        string $reason = '',
        string $description = ''
    ): void {
        $this->apiClient->cancelPayment(
            $this->configuration->getPosId(),
            $currency,
            $paymentId,
            $address,
            $reason,
            substr($description, 0, 255),
        );
    }

    /**
     * Cancel all except existingPayment on ForumPay
     *
     * @throws ApiExceptionInterface
     * @throws \Exception
     */
    private function cancelAllPayments(string $orderId, string $existingPaymentId): void
    {
        $existingPayments = $this->apiClient->getTransactions(null, null, $orderId);

        /** @var TransactionInvoice $existingPayment */
        foreach ($existingPayments->getInvoices() as $existingPayment) {
            if (
                $existingPayment->getPaymentId() === $existingPaymentId
                || strtolower($existingPayment->getStatus()) !== 'waiting'
            ) {
                // newly created
                continue;
            }

            $this->cancelPayment(
                $existingPayment->getPaymentId(),
                $existingPayment->getCurrency(),
                $existingPayment->getAddress()
            );
        }
    }

    /**
     * Check if all payments for a given order are canceled on ForumPay
     *
     * @param string $orderId
     * @return bool
     * @throws ApiExceptionInterface
     * @throws \Exception
     */
    private function checkAllPaymentsAreCanceled(string $orderId): bool
    {
        $existingPayments = $this->apiClient->getTransactions(null, null, $orderId);

        /** @var TransactionInvoice $existingPayment */
        foreach ($existingPayments->getInvoices() as $existingPayment) {
            if (
                strtolower($existingPayment->getStatus()) !== 'cancelled'
                && $existingPayment->getPosId() === $this->configuration->getPosId()
            ) {
                return false;
            }
        }

        return true;
    }
}
