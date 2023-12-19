<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Exception;

use Throwable;

/**
 * Order is not found in this session
 */
class OrderNotFoundException extends ForumPayException
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct('OrderID could not be found in request.', $code, $previous);
    }
}
