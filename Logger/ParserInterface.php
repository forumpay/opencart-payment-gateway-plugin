<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Logger;

interface ParserInterface
{
    /**
     * Parse given data.
     *
     * @param array $keys
     * @param array $data
     * @return array
     */
    public function parse(array $keys, array $data): array;
}
