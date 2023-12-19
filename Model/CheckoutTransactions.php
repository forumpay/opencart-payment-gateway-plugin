<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model;

class CheckoutTransactions
{
    /**
     * Catalog model for ForumPay transactions.
     *
     * @var
     * @noinspection PhpMissingFieldTypeInspection
     */
    private $database;

    /**
     * Constructor
     *
     * @param $database
     */
    public function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * Responsible for inserting a new transaction.
     *
     * @param int $orderId
     * @param string $paymentId
     * @param array $data
     * @return bool
     */
    public function insert(int $orderId, string $paymentId, array $data): bool
    {
        return $this->database->insert($orderId, $paymentId, $data);
    }

    /**
     * Responsible for updating the existing transaction.
     *
     * @param int $orderId
     * @param string $paymentId
     * @param array $data
     * @return bool
     */
    public function update(int $orderId, string $paymentId, array $data): bool
    {
        return $this->database->update($orderId, $paymentId, $data);
    }

    /**
     * Gets data from transaction for given payment id.
     *
     * @param string $paymentId
     * @return array|null
     */
    public function getData(string $paymentId): ?array
    {
        return $this->database->getData($paymentId);
    }

    /**
     * Gets orderId from transaction for given payment id.
     *
     * @param string $paymentId
     * @return int|null
     */
    public function getOrderId(string $paymentId): ?int
    {
        return $this->database->getOrderId($paymentId);
    }
}
