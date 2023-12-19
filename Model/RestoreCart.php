<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model;

use ForumPay\PaymentGateway\OpenCartExtension\Logger\ForumPayLogger;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Request;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Payment\ForumPay;

class RestoreCart
{
    /**
     * ForumPay payment model
     *
     * @var ForumPay
     */
    private ForumPay $forumPay;

    /**
     * @var ForumPayLogger
     */
    private ForumPayLogger $logger;

    /**
     * Constructor
     *
     * @param ForumPay $forumPay
     * @param ForumPayLogger $logger
     */
    public function __construct(ForumPay $forumPay, ForumPayLogger $logger)
    {
        $this->forumPay = $forumPay;
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function execute(Request $request): void
    {
    }
}
