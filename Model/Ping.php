<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model;

use ForumPay\PaymentGateway\OpenCartExtension\Exception\ForumPayException;
use ForumPay\PaymentGateway\OpenCartExtension\Exception\ForumPayHttpException;
use ForumPay\PaymentGateway\OpenCartExtension\Logger\ForumPayLogger;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Payment\ForumPay;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\ApiExceptionInterface;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\InvalidApiResponseException;
use ForumPay\PaymentGateway\PHPClient\Http\Exception\InvalidResponseStatusCodeException;

class Ping
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

    public function execute(Request $request): ?\ForumPay\PaymentGateway\OpenCartExtension\Model\Data\Ping
    {
        try {
            $this->logger->info('Ping entrypoint called.');

            try {
                $apiEnv = $request->getRequired('apiEnv');
                $apiUrlOverride = $request->getRequired('apiUrlOverride');
            } catch (\InvalidArgumentException $e) {
                $this->logger->error($e->getMessage(), $e->getTrace());
                throw new ForumPayException('There has been an error, check OpenCart logs for more.');
            }

            $apiKey = (string) $request->get('apiKey', '');
            $apiSecret = (string) $request->get('apiSecret', '');

            $response = $this->forumPay->ping($apiEnv, $apiKey, $apiSecret, $apiUrlOverride);

            $this->logger->debug('Ping response.', ['response' => $response->toArray()]);
            $this->logger->info('Ping entrypoint finished.');

            return new \ForumPay\PaymentGateway\OpenCartExtension\Model\Data\Ping('OK');

        } catch (ForumPayException $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
            throw $e;
        } catch (InvalidApiResponseException $e) {
            $this->logger->logApiException($e);
            throw new ForumPayHttpException($e->getMessage(), intval(0));
        } catch (InvalidResponseStatusCodeException $e) {
            $this->logger->logApiException($e);
            throw new ForumPayHttpException($e->getMessage(),$e->getResponseStatusCode() ?? 500);
        } catch (ApiExceptionInterface $e) {
            $this->logger->logApiException($e);
            throw new ForumPayHttpException($e->getMessage(),$e->getCode() ?? 500);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), $e->getTrace());
            throw new \Exception($e->getMessage(), 500, $e);
        }
    }
}
