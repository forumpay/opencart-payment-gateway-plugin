<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Form;

class ConfigurationData
{
    public const FORUMPAY_TABLE_NAME = 'payment_forumpay';
    public const FORUMPAY_TITLE = 'payment_forumpay_title';
    public const FORUMPAY_DESCRIPTION = 'payment_forumpay_description';
    public const FORUMPAY_API_URL = 'payment_forumpay_api_url';
    public const FORUMPAY_API_USER = 'payment_forumpay_api_user';
    public const FORUMPAY_API_KEY = 'payment_forumpay_api_key';
    public const FORUMPAY_POS_ID = 'payment_forumpay_pos_id';
    public const FORUMPAY_API_URL_OVERRIDE = 'payment_forumpay_api_url_override';
    public const FORUMPAY_INITIAL_ORDER_STATUS = 'payment_forumpay_initial_order_status';
    public const FORUMPAY_CANCELLED_ORDER_STATUS = 'payment_forumpay_cancelled_order_status';
    public const FORUMPAY_SUCCESS_ORDER_STATUS = 'payment_forumpay_success_order_status';
    public const FORUMPAY_ACCEPT_ZERO_CONFIRMATIONS = 'payment_forumpay_accept_zero_confirmation';
    public const FORUMPAY_PAYMENT_SORT_ORDER = 'payment_forumpay_sort_order';
    public const FORUMPAY_PAYMENT_DEBUG = 'payment_forumpay_debug';
    public const FORUMPAY_ENABLED = 'payment_forumpay_status';

    /**
     * Returns the config keys.
     *
     * @return string[]
     */
    public static function getData(): array
    {
        return [
            'title' => self::FORUMPAY_TITLE,
            'description' => self::FORUMPAY_DESCRIPTION,
            'api_url' => self::FORUMPAY_API_URL,
            'api_user' => self::FORUMPAY_API_USER,
            'api_key' => self::FORUMPAY_API_KEY,
            'pos_id' => self::FORUMPAY_POS_ID,
            'api_url_override' => self::FORUMPAY_API_URL_OVERRIDE,
            'initial_order_status' => self::FORUMPAY_INITIAL_ORDER_STATUS,
            'cancelled_order_status' => self::FORUMPAY_CANCELLED_ORDER_STATUS,
            'success_order_status' => self::FORUMPAY_SUCCESS_ORDER_STATUS,
            'accept_zero_confirmations' => self::FORUMPAY_ACCEPT_ZERO_CONFIRMATIONS,
            'sort_order' => self::FORUMPAY_PAYMENT_SORT_ORDER,
            'debug' => self::FORUMPAY_PAYMENT_DEBUG,
            'enabled' => self::FORUMPAY_ENABLED
        ];
    }

    /**
     * Validates configuration data. Returns array of all error messages or empty array if validation passes
     *
     * @param array $config
     * @return array
     */
    public static function validate(array $config): array
    {
        $errors = [];
        if (!isset(
            $config[self::FORUMPAY_TITLE],
            $config[self::FORUMPAY_DESCRIPTION],
            $config[self::FORUMPAY_API_URL],
            $config[self::FORUMPAY_API_USER],
            $config[self::FORUMPAY_API_KEY],
            $config[self::FORUMPAY_POS_ID],
            $config[self::FORUMPAY_PAYMENT_SORT_ORDER],
        )) {
            $errors[] = 'Missing required fields.';
            return $errors;
        }

        if (1 !== preg_match('/^[A-Za-z0-9._-]+$/', $config[self::FORUMPAY_POS_ID])) {
            $errors[] = 'POS ID field includes invalid characters. Allowed are: A-Za-z0-9._-';
        }

        if (isset($config[self::FORUMPAY_API_URL_OVERRIDE]) && $config[self::FORUMPAY_API_URL_OVERRIDE]) {
            if (false === filter_var($config[self::FORUMPAY_API_URL_OVERRIDE], FILTER_VALIDATE_URL)) {
                $errors[] = 'Custom environment URL must be valid URL.';
            }
        }

        return $errors;
    }
}
