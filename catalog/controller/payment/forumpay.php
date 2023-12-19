<?php

namespace Opencart\Catalog\Controller\Extension\ForumPay\Payment;

require_once __DIR__ . '/../../../vendor/autoload.php';

use ForumPay\PaymentGateway\OpenCartExtension\Exception\ApiHttpException;
use ForumPay\PaymentGateway\OpenCartExtension\Exception\ForumPayException;
use ForumPay\PaymentGateway\OpenCartExtension\Exception\ForumPayHttpException;
use ForumPay\PaymentGateway\OpenCartExtension\Form\ConfigurationData;
use ForumPay\PaymentGateway\OpenCartExtension\Logger\ForumPayLogger;
use ForumPay\PaymentGateway\OpenCartExtension\Logger\PrivateTokenMasker;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Configuration;
use ForumPay\PaymentGateway\OpenCartExtension\Model\CheckoutTransactions;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Payment\ForumPay as ForumPayPayment;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Payment\OrderManager;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Request;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Response;

class ForumPay extends \Opencart\System\Engine\Controller
{
    /**
     * @var array
     */
    private array $routes = [];

    public function index(): string
    {
        $this->isPluginEnabled(true);

        $language = $this->loadLanguage();

        return $this->load->view('extension/forumpay/payment/forumpay', [
            'confirm_button' => $language->get('confirm_button')
        ]);
    }

    public function widget(): void
    {
        $this->isPluginEnabled(true);

        if (!isset($this->session->data['order_id'])) {
            $this->response->redirect($this->url->link('checkout/checkout'));
        }

        $this->document->setTitle($this->config->get('forumpay_title'));
        $this->document->addScript('extension/forumpay/catalog/view/js/forumpay_widget.js');
        $this->document->addScript('extension/forumpay/catalog/view/js/forumpay.js');
        $this->document->addStyle('extension/forumpay/catalog/view/css/forumpay.css');
        $this->document->addStyle('extension/forumpay/catalog/view/css/forumpay_widget.css');

        $language = $this->loadLanguage();

        $order = $this->loadCheckoutOrder();
        $order->addHistory(
            $this->session->data['order_id'],
            $this->config->get(ConfigurationData::FORUMPAY_INITIAL_ORDER_STATUS),
            $language->get('order_created'),
            true
        );

        $this->response->setOutput($this->load->view('extension/forumpay/payment/widget', [
            'header' => $this->load->controller('common/header'),
            'column_left' => $this->load->controller('common/column_left'),
            'footer' => $this->load->controller('common/footer'),
            'apiBase' => sprintf(
                '%s%s',
                $this->config->get('config_url'),
                'index.php?route=extension/forumpay/payment/forumpay.api'
            ),
            'returnUrl' =>  $this->url->link('checkout/success'),
            'cancelUrl' => $this->url->link('checkout/cart'),
        ]));
    }

    public function api(): void
    {
        $this->isPluginEnabled(true);

        try {
            $route = (new Request())->getRequired('act');
        } catch (\InvalidArgumentException $e) {
            $this->serializeError($e);
            return;
        }

        $this->initRoutes();

        if (!array_key_exists($route, $this->routes)) {
            return;
        }

        try {
            $service = $this->routes[$route];
            $response = $service->execute(new Request());
            if ($response !== null) {
                $this->serializeResponse($response->toArray());
            }
        } catch (ApiHttpException $e) {
            $this->serializeError($e, $e->getHttpCode());
        } catch (ForumPayException $e) {
            $this->serializeError(
                new ForumPayHttpException(
                    $e->getMessage(),
                    $e->getCode(),
                    ForumPayHttpException::HTTP_BAD_REQUEST
                ), ForumPayHttpException::HTTP_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $this->serializeError(
                new ForumPayHttpException(
                    $e->getMessage(),
                    $e->getCode(),
                    ForumPayHttpException::HTTP_INTERNAL_ERROR,
                ), ForumPayHttpException::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * @param array $response
     * @param int $status
     * @return void
     */
    private function serializeResponse(array $response, int $status = 200): void
    {
        Response::setHttpResponseCode($status);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }

    /**
     * @param \Exception $e
     * @param int $status
     * @return void
     */
    private function serializeError(\Exception $e, int $status = 500): void
    {
        Response::setHttpResponseCode($status);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'code' => $e->getCode(),
            'message' => $e->getMessage()
        ]));
    }

    private function initRoutes(): void
    {
        $logger = (new ForumPayLogger($this->log))
            ->setDebug((bool) $this->config->get(ConfigurationData::FORUMPAY_PAYMENT_DEBUG))
            ->addParser(new PrivateTokenMasker());
        $configuration = new Configuration(
            $this->config,
            $this->language,
            VERSION ?? null,
            DIR_APPLICATION ?? null
        );
        $forumPay = new ForumPayPayment(
            $configuration,
            new OrderManager(
                $this->session,
                $this->loadCheckoutOrder(),
                new CheckoutTransactions($this->loadDatabase()),
                $this->request,
                $configuration,
                $this->loadLanguage(),
                $this->loadCheckoutCart(),
                $this->cart,
                $this->customer,
            ),
            $logger,
        );
        $this->routes = [
            'currencies'    => new \ForumPay\PaymentGateway\OpenCartExtension\Model\GetCurrencyList($forumPay, $logger),
            'getRate'       => new \ForumPay\PaymentGateway\OpenCartExtension\Model\GetCurrencyRate($forumPay, $logger),
            'startPayment'  => new \ForumPay\PaymentGateway\OpenCartExtension\Model\StartPayment($forumPay, $logger),
            'checkPayment'  => new \ForumPay\PaymentGateway\OpenCartExtension\Model\CheckPayment($forumPay, $logger),
            'cancelPayment' => new \ForumPay\PaymentGateway\OpenCartExtension\Model\CancelPayment($forumPay, $logger),
            'webhook'       => new \ForumPay\PaymentGateway\OpenCartExtension\Model\Webhook($forumPay, $logger),
            'restoreCart'   => new \ForumPay\PaymentGateway\OpenCartExtension\Model\RestoreCart($forumPay, $logger)
        ];
    }

    /**
     * Loads the necessary database module and then returns it.
     *
     * @return mixed
     */
    private function loadDatabase()
    {
        $this->load->model('extension/forumpay/payment/forumpay');
        return $this->model_extension_forumpay_payment_forumpay;
    }

    /**
     * Loads the necessary checkout/order module and then returns it.
     *
     * @return mixed
     */
    private function loadCheckoutOrder()
    {
        $this->load->model('checkout/order');
        return $this->model_checkout_order;
    }

    /**
     * Loads the necessary checkout/cart module and then returns it.
     *
     * @return mixed
     */
    private function loadCheckoutCart()
    {
        $this->load->model('checkout/cart');
        return $this->model_checkout_cart;
    }

    /**
     * Loads the necessary language module and then returns it.
     *
     * @return mixed
     */
    public function loadLanguage()
    {
        $this->load->language('extension/forumpay/payment/forumpay');
        return $this->language;
    }

    /**
     * Checks whether plugin is enabled.
     *
     * @param bool $redirect
     * @return bool
     */
    private function isPluginEnabled(bool $redirect = false): bool
    {
        $enabled = (bool) $this->config->get(ConfigurationData::FORUMPAY_ENABLED);

        if ($redirect) {
            if (!$enabled) {
                $this->response->redirect($this->url->link('common/home'));
            }
        }

        return $enabled;
    }
}
