<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model;

/**
 * Sets correct response code
 */
class Response
{
    /**
     * Sets response status code
     *
     * @param int $statusCode
     * @return void
     */
    public static function setHttpResponseCode(int $statusCode): void
    {
        if (!headers_sent()) {
            if (function_exists('http_response_code')) {
                http_response_code($statusCode);
            } else {
                header(' ', true, $statusCode);
            }
        }
    }
}
