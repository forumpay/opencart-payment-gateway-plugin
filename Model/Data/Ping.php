<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model\Data;

/**
 * Dto for ping response
 */
class Ping
{
    /**
     * Message
     *
     * @var string
     */
    private string $message;

    /**
     * Ping DTO constructor
     *
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Return empty array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
        ];
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
