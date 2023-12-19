<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model\Payment;

use ForumPay\PaymentGateway\OpenCartExtension\Model\CheckoutTransactions;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Configuration;

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
     * Get currency customer used when creating order
     *
     * @return string|null
     */
    public function getOrderCurrency(): ?string
    {
        return $this->session->data['currency'] ?? null;
    }

    /**
     * Get order total by order id from db
     *
     * @return string
     */
    public function getOrderTotal(): string
    {
        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;

        if ($this->customer->isLogged() || !$this->configuration->getConfigCustomerPrice()) {
            //getTotals() method changes value of variable $total by reference.
            ($this->checkoutCart->getTotals)($totals, $taxes, $total);
        }

        return number_format($total, 2, '.', '');
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
        return $update ? $this->checkoutTransactions->update($orderId, $paymentId, $data)
                : $this->checkoutTransactions->insert($orderId, $paymentId, $data);
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
        if (isset($this->session->data['order_id'])) {
            return $this->session->data['order_id'];
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
}
