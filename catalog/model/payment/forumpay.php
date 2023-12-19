<?php

namespace Opencart\Catalog\Model\Extension\ForumPay\Payment;

class ForumPay extends \Opencart\System\Engine\Model
{
    /**
     * @const string
     */
    private const TABLE_NAME = DB_PREFIX . 'forumpay_checkout_transactions';

    /**
     * Payment method detection. Used by OpenCart when listing the active payment methods during the checkout process.
     * https://forum.opencart.com/viewtopic.php?t=231513
     *
     * @param array $address
     * @param $total
     * @return array
     */
    public function getMethods(array $address = [], float $total = 0.0): array
    {
        return [
            'code' => 'forumpay',
            'name' => $this->config->get('payment_forumpay_title'),
            'option' => [
                'forumpay' => [
                    'code' => 'forumpay.forumpay',
                    'name' => 'ForumPay',
                ]
            ],
            'sort_order' => $this->config->get('payment_forumpay_success_order_status'),
        ];
    }

    /**
     * Inserts a new transaction.
     *
     * @param int $orderId
     * @param string $paymentId
     * @param array $data
     * @return mixed
     */
    public function insert(int $orderId, string $paymentId, array $data): bool
    {
        $tableName = self::TABLE_NAME;
        $paymentId = $this->db->escape($paymentId);
        $data = $this->db->escape(json_encode($data));
        $query = $this->db->query(
            "INSERT INTO $tableName
            (order_id, payment_id, data, created_at)
            VALUES
            ($orderId, '$paymentId', '$data', NOW())"
        );

        if (!$query) {
            return false;
        }

        return true;
    }

    /**
     * Updates existing transaction.
     *
     * @param int $orderId
     * @param string $paymentId
     * @param array $data
     * @return bool
     */
    public function update(int $orderId, string $paymentId, array $data): bool
    {
        $transaction = $this->findByPaymentId($paymentId, $orderId);

        if ($transaction === null) {
            return false;
        }

        $updateDataQuery = sprintf(
            "UPDATE `%s` SET `data` = '%s' WHERE `id` = %d",
            self::TABLE_NAME,
            $this->db->escape(json_encode($data)),
            (int) $transaction['id']
        );

        return (bool) $this->db->query($updateDataQuery);
    }

    /**
     * Finds a transaction by its payment id or optionally by its orderId too if it exists.
     *
     * @param string $paymentId
     * @param int|null $orderId
     * @return array|null
     */
    public function findByPaymentId(string $paymentId, int $orderId = null): ?array
    {
        $tableName = self::TABLE_NAME;

        $condition = null !== $orderId
            ? sprintf("`payment_id` = '%s' and `order_id` = '%s'", $this->db->escape($paymentId), (int)$orderId)
            : sprintf("`payment_id` = '%s'", $this->db->escape($paymentId));

        $result = $this->db->query(
            "SELECT * FROM `$tableName` WHERE $condition ORDER BY `created_at` DESC LIMIT 1"
        );

        return $result ? $result->rows[0] : null;
    }

    /**
     * Gets data from transaction for given payment id.
     *
     * @param string $paymentId
     * @return array|null
     */
    public function getData(string $paymentId): ?array
    {
        $transaction = $this->findByPaymentId($paymentId);

        if ($transaction === null) {
            return null;
        }
        if ($transaction['data'] === null) {
            return null;
        }
        try {
          $data = json_decode($transaction['data'], true);
        } catch (\Exception $e) {
            return null;
        }

        return $data;
    }

    /**
     * Gets orderId from transaction for given payment id.
     *
     * @param string $paymentId
     * @return int|null
     */
    public function getOrderId(string $paymentId): ?int
    {
        $transaction = $this->findByPaymentId($paymentId);

        if ($transaction === null) {
            return null;
        }
        if ($transaction['order_id'] === null) {
            return null;
        }

        return (int) $transaction['order_id'];
    }
}
