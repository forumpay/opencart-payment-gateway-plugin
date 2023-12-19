<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model;

use ForumPay\PaymentGateway\OpenCartExtension\Exception\ApiHttpException;
use ForumPay\PaymentGateway\OpenCartExtension\Logger\ForumPayLogger;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Payment\ForumPay;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Data\CurrencyList;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Data\CurrencyList\Currency;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\ApiExceptionInterface;

class GetCurrencyList
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
     * @throws ApiHttpException
     * @throws \Exception
     */
    public function execute(Request $request): ?CurrencyList
    {
        try {
            $this->logger->info('GetCurrencyList entrypoint called.');

            $response = $this->forumPay->getCryptoCurrencyList();

            /** @var Data\CurrencyList\Currency[] $currencyDtos */
            $currencyDtos = [];

            /** @var \ForumPay\PaymentGateway\PHPClient\Response\GetCurrencyList\Currency $currency */
            foreach ($response->getCurrencies() as $currency) {
                if ($currency->getStatus() !== 'OK') {
                    continue;
                }

                $currencyDto = new Currency(
                    $currency->getCurrency(),
                    $currency->getDescription(),
                    $currency->getSellStatus(),
                    (bool) $currency->getZeroConfirmationsEnabled(),
                    $currency->getCurrencyFiat(),
                    $currency->getIconUrl(),
                    $currency->getRate()
                );
                $currencyDtos[] = $currencyDto;
            }

            $this->logger->debug(
                'GetCurrencyList response.',
                ['response' => array_map(fn($currency) => $currency->toArray(), $currencyDtos)]
            );
            $this->logger->info('GetCurrencyList entrypoint finished.');

            return new CurrencyList($currencyDtos);
        } catch (ApiExceptionInterface $e) {
            $this->logger->logApiException($e);
            throw new ApiHttpException($e, 1050);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), $e->getTrace());
            throw new \Exception($e->getMessage(), 1100, $e);
        }
    }
}
