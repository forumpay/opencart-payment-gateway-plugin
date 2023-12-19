<?php

namespace Opencart\Admin\Model\Extension\ForumPay\Payment;

class ForumPay extends \Opencart\System\Engine\Model
{
    /**
     * @const string
     */
    private const TABLE_NAME = DB_PREFIX . 'forumpay_checkout_transactions';

    public function install(): void
    {
        $tableName = self::TABLE_NAME;

        $this->db->query(
            "CREATE TABLE `$tableName` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `order_id` INT NOT NULL,
                `payment_id` VARCHAR(255) NOT NULL,
                `data` TEXT NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`))"
        );

        $this->db->query("CREATE INDEX idx_order_id ON `$tableName` (`order_id`)");
        $this->db->query("CREATE INDEX idx_payment_id ON `$tableName` (`payment_id`)");
    }

    public function uninstall(): void
    {
        $tableName = self::TABLE_NAME;

        $this->db->query("DROP TABLE `$tableName`");
    }
}
