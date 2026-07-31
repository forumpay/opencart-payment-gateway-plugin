<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model\Payment;

use ForumPay\PaymentGateway\OpenCartExtension\Exception\ForumPayHttpException;
use ForumPay\PaymentGateway\OpenCartExtension\Model\CheckoutTransactions;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Configuration;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Request;

/**
 * Manages internal states of the order and provides
 * and interface for dealing with OpenCart internal
 */
class OrderManager
{
    /**
     * @var object
     */
    private object $session;

    /**
     * @var mixed
     */
    private $checkoutOrder;

    /**
     * @var CheckoutTransactions
     */
    private CheckoutTransactions $checkoutTransactions;

    /**
     * @var object
     */
    private object $request;

    /**
     * @var Configuration
     */
    private Configuration $configuration;

    /**
     * @var mixed
     */
    private $language;

    /**
     * @var mixed
     */
    private $checkoutCart;

    /**
     * @var mixed
     */
    private $customer;

    /**
     * @var mixed
     */
    private $cart;

    /**
     * Constructor
     *
     * @param object $session
     * @param $checkoutOrder
     * @param CheckoutTransactions $checkoutTransactions
     * @param object $request
     * @param Configuration $configuration
     * @param $language
     * @param $checkoutCart
     * @param $cart
     * @param $customer
     */
    public function __construct(
        object $session,
        $checkoutOrder,
        CheckoutTransactions $checkoutTransactions,
        object $request,
        Configuration $configuration,
        $language,
        $checkoutCart,
        $cart,
        $customer
    ) {
        $this->session = $session;
        $this->checkoutOrder = $checkoutOrder;
        $this->checkoutTransactions = $checkoutTransactions;
        $this->request = $request;
        $this->configuration = $configuration;
        $this->language = $language;
        $this->checkoutCart = $checkoutCart;
        $this->cart = $cart;
        $this->customer = $customer;
    }

    /**
     * Get currency for the given order from the database.
     *
     * @param string $orderId
     * @return string|null
     */
    public function getOrderCurrency(string $orderId): ?string
    {
        $order = $this->checkoutOrder->getOrder((int) $orderId);
        if (!$order) {
            return null;
        }

        return $order['currency_code'] ?? null;
    }

    /**
     * Get order total for the given order from the database.
     *
     * @param string $orderId
     * @return string
     */
    public function getOrderTotal(string $orderId): string
    {
        $order = $this->checkoutOrder->getOrder((int) $orderId);
        if (!$order) {
            return '0.00';
        }

        return number_format((float) ($order['total'] ?? 0), 2, '.', '');
    }

    /**
     * Get customer IP address that was used when order is created
     *
     * @return string
     */
    public function getOrderCustomerIpAddress(): string
    {
        $remoteAddressesList = [];
        if (isset($this->request->server['HTTP_X_REAL_IP'])) {
            $remoteAddressesList += preg_split("/,/", $this->request->server['HTTP_X_REAL_IP'], -1, PREG_SPLIT_NO_EMPTY);
        }

        if (isset($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            $remoteAddressesList += preg_split("/,/", $this->request->server['HTTP_X_FORWARDED_FOR'], -1, PREG_SPLIT_NO_EMPTY);
        }

        if (isset($this->request->server['REMOTE_ADDR'])) {
            $remoteAddressesList += preg_split("/,/", $this->request->server['REMOTE_ADDR'], -1, PREG_SPLIT_NO_EMPTY);
        }

        if (!count($remoteAddressesList)) {
            return '';
        }

        foreach ($remoteAddressesList as $remoteAddress) {
            if (filter_var(
                $remoteAddress,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )) {
                return $remoteAddress;
            }
        }

        return $remoteAddressesList[0];
    }

    /**
     * Get customer email address that was used when order is created
     *
     * @return string
     */
    public function getOrderCustomerEmail(): string
    {
        return $this->session->data['customer']['email'];
    }

    /**
     * Get customer ID if registered customer or construct one for guests
     *
     * @return string
     */
    public function getOrderCustomerId(): string
    {
        if (!isset($this->session->data['customer_id'])) {
            return 'Guest';
        }

        return $this->session->data['customer_id'];
    }

    /**
     * Update order with new status
     *
     * @param string $orderId
     * @param string $newStatus
     */
    public function updateOrderStatus(string $orderId, string $newStatus): void
    {
        $newStatus = strtolower($newStatus);
        $order = $this->checkoutOrder->getOrder($orderId);
        if ($newStatus === 'confirmed') {
            $this->setOrderId($orderId);
            $successStatus = $this->configuration->getSuccessOrderStatus();
            if ($order['order_status_id'] !== $successStatus) {
                $this->checkoutOrder->addHistory(
                    $orderId,
                    $successStatus,
                    $this->language->get('customer_success'),
                    true
                );
            }
        } elseif ($newStatus === 'cancelled') {
            $cancelledStatus = $this->configuration->getCancelledOrderStatus();
            if ($order['order_status_id'] !== $cancelledStatus) {
                $this->checkoutOrder->addHistory(
                    $orderId,
                    $cancelledStatus,
                    $this->language->get('customer_cancelled'),
                    true
                );
            }
        }

        if ($newStatus === 'confirmed') {
            $this->clearForumPayOrderId();
        }
    }

    /**
     * Saves metadata.
     *
     * @param string $orderId
     * @param string $paymentId
     * @param array $data
     * @param bool $update
     * @return bool
     */
    public function saveOrderMetaData(string $orderId, string $paymentId, array $data, bool $update = false): bool
    {
        return $update ? $this->checkoutTransactions->update((int) $orderId, $paymentId, $data)
                : $this->checkoutTransactions->insert((int) $orderId, $paymentId, $data);
    }

    /**
     * Get metadata from order
     *
     * @param string $paymentId
     * @return array|null
     */
    public function getOrderMetaData(string $paymentId): ?array
    {
        return $this->checkoutTransactions->getData($paymentId);
    }

    /**
     * Get orderId for given paymentId.
     *
     * @param string $paymentId
     * @return int|null
     */
    public function getOrderIdByPaymentId(string $paymentId): ?int
    {
        return $this->checkoutTransactions->getOrderId($paymentId);
    }

    /**
     * Get current order id
     *
     * @return string
     */
    public function getOrderId(): string
    {
        if (!empty($this->session->data['order_id'])) {
            return (string) $this->session->data['order_id'];
        }

        if (!empty($this->session->data['forumpay_order_id'])) {
            return (string) $this->session->data['forumpay_order_id'];
        }

        return '';
    }

    public function setOrderId(?int $newOrderId): void
    {
        if ($newOrderId) {
            $this->session->data['order_id'] = $newOrderId;
        }
    }

    public function unsetOrderId(): void
    {
        if (isset($this->session->data['order_id'])) {
            unset($this->session->data['order_id']);
        }
    }

    public function setForumPayOrderId(string $orderId): void
    {
        $this->session->data['forumpay_order_id'] = $orderId;
    }

    public function clearForumPayOrderId(): void
    {
        unset($this->session->data['forumpay_order_id']);
    }

    /**
     * Require an active checkout session with a bound order.
     *
     * @throws ForumPayHttpException
     */
    public function requireCheckoutSession(): void
    {
        if ($this->getOrderId() === '') {
            throw $this->accessDeniedOrder();
        }
    }

    /**
     * Resolve order ID from request parameters and session.
     *
     * @param Request $request
     * @return string
     * @throws ForumPayHttpException
     */
    public function resolveOrderId(Request $request): string
    {
        $sessionOrderId = $this->getOrderId();
        if ($sessionOrderId === '') {
            throw $this->accessDeniedOrder();
        }

        $requestOrderId = $request->get('forumpay_order_id');
        if ($requestOrderId === null || $requestOrderId === '') {
            $requestOrderId = $request->get('orderId');
        }

        if ($requestOrderId !== null && $requestOrderId !== '') {
            if ((string) $requestOrderId !== (string) $sessionOrderId) {
                throw $this->accessDeniedOrder();
            }

            return (string) $requestOrderId;
        }

        $paymentId = $request->get('payment_id');
        if ($paymentId !== null && $paymentId !== '') {
            $resolvedOrderId = $this->checkoutTransactions->getOrderId((string) $paymentId);
            if ($resolvedOrderId === null || (string) $resolvedOrderId !== (string) $sessionOrderId) {
                throw $this->accessDeniedOrder();
            }

            return (string) $resolvedOrderId;
        }

        return $sessionOrderId;
    }

    /**
     * Validate that the current user/session has access to the given order.
     *
     * @param string $orderId
     * @throws ForumPayHttpException
     */
    public function validateOrderAccess(string $orderId): void
    {
        $order = $this->checkoutOrder->getOrder($orderId);
        if (!$order) {
            throw $this->accessDeniedOrder();
        }

        $sessionCustomerId = (int) ($this->session->data['customer_id'] ?? 0);
        $orderCustomerId = (int) ($order['customer_id'] ?? 0);

        if ($sessionCustomerId > 0 && $sessionCustomerId === $orderCustomerId) {
            return;
        }

        if ($sessionCustomerId === 0 && $orderCustomerId === 0) {
            $sessionOrderId = $this->getOrderId() ?: null;
            if ($sessionOrderId !== null && (string) $sessionOrderId === (string) $orderId) {
                return;
            }
        }

        throw $this->accessDeniedOrder();
    }

    /**
     * Validate that the payment belongs to the given order.
     *
     * @param string $orderId
     * @param string $paymentId
     * @throws ForumPayHttpException
     */
    public function validatePaymentBelongsToOrder(string $orderId, string $paymentId): void
    {
        $this->getPaymentMetaData($orderId, $paymentId);
    }

    /**
     * Get startPayment metadata for the given order/payment pair.
     *
     * @param string $orderId
     * @param string $paymentId
     * @return array
     * @throws ForumPayHttpException
     */
    public function getPaymentMetaData(string $orderId, string $paymentId): array
    {
        $resolvedOrderId = $this->checkoutTransactions->getOrderId($paymentId);
        if ($resolvedOrderId === null || (string) $resolvedOrderId !== (string) $orderId) {
            throw $this->accessDeniedOrder();
        }

        $meta = $this->checkoutTransactions->getData($paymentId);
        if ($meta === null) {
            throw $this->accessDeniedOrder();
        }

        $currency = $meta['currency'] ?? null;
        $address = $meta['address'] ?? null;
        if ($currency === null || $currency === '' || $address === null || $address === '') {
            throw $this->accessDeniedOrder();
        }

        return $meta;
    }

    /**
     * @return ForumPayHttpException
     */
    private function accessDeniedOrder(): ForumPayHttpException
    {
        return new ForumPayHttpException(
            'Access denied. You do not have permission to access this order.',
            4003,
            ForumPayHttpException::HTTP_FORBIDDEN
        );
    }
}
