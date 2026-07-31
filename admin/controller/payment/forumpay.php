<?php

namespace Opencart\Admin\Controller\Extension\ForumPay\Payment;

require_once __DIR__ . '/../../../vendor/autoload.php';

use ForumPay\PaymentGateway\OpenCartExtension\Form\ConfigurationData;
use ForumPay\PaymentGateway\OpenCartExtension\Exception\ApiHttpException;
use ForumPay\PaymentGateway\OpenCartExtension\Exception\ForumPayException;
use ForumPay\PaymentGateway\OpenCartExtension\Exception\ForumPayHttpException;
use ForumPay\PaymentGateway\OpenCartExtension\Logger\ForumPayLogger;
use ForumPay\PaymentGateway\OpenCartExtension\Logger\PrivateTokenMasker;
use ForumPay\PaymentGateway\OpenCartExtension\Model\CheckoutTransactions;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Configuration;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Payment\ForumPay as ForumPayPayment;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Payment\OrderManager;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Ping;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Request;
use ForumPay\PaymentGateway\OpenCartExtension\Model\Response;

class ForumPay extends \Opencart\System\Engine\Controller
{
    /**
     * The main method of admin controller.
     *
     * @return void
     */
    public function index(): void
    {
        $this->load->language('extension/forumpay/payment/forumpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('localisation/order_status');

        $data = [
            'page_elements' => [
                'header' => $this->load->controller('common/header'),
                'column_left' => $this->load->controller('common/column_left'),
                'footer' => $this->load->controller('common/footer'),
            ],
            'save' => $this->url->link(
                'extension/forumpay/payment/forumpay.save',
                sprintf('user_token=%s', $this->session->data['user_token']),
                true
            ),
            'cancel' => $this->url->link(
                'marketplace/extension',
                sprintf('user_token=%s&type=payment', $this->session->data['user_token']),
                true
            ),
            'breadcrumbs' => [
                [
                    'text' => $this->language->get('text_home'),
                    'href' => $this->url->link(
                        'common/dashboard',
                        sprintf('user_token=%s', $this->session->data['user_token']),
                        true
                    )
                ],
                [
                    'text' => $this->language->get('text_extension'),
                    'href' => $this->url->link(
                        'marketplace/extension',
                        sprintf('user_token=%s&type=payment', $this->session->data['user_token']),
                        true
                    )
                ],
                [
                    'text' => $this->language->get('heading_title'),
                    'href' => $this->url->link(
                        'extension/forumpay/payment/forumpay',
                        sprintf('user_token=%s', $this->session->data['user_token']),
                        true
                    )
                ]
            ],
            'config' => [
                'name' => ConfigurationData::getData(),
                'value' => $this->model_setting_setting->getSetting('payment_forumpay')
            ],
            'admin_gateway_settings' => HTTP_CATALOG . 'extension/forumpay/admin/view/js/admin_gateway_settings.js',
            'ping_url' => $this->url->link(
                'extension/forumpay/payment/forumpay.ping',
                sprintf('user_token=%s', $this->session->data['user_token']),
                true
            ),
            'errors' => $this->session->data['errors'] ?? null,
            'order_statuses' => $this->model_localisation_order_status->getOrderStatuses(),
            'success' => $this->session->data['success'] ?? null,
            'text' => [
                'text_title' => $this->language->get('heading_title'),
                'text_success' => $this->language->get('text_success'),
                'text_form_title' => $this->language->get('text_form_title'),
                'text_form_description' => $this->language->get('text_form_description'),
                'text_form_environment' => $this->language->get('text_form_environment'),
                'text_form_api_user' => $this->language->get('text_form_api_user'),
                'text_form_api_key' => $this->language->get('text_form_api_key'),
                'text_form_api_url_change_notice' => $this->language->get('text_form_api_url_change_notice'),
                'text_form_pos_id' => $this->language->get('text_form_pos_id'),
                'text_form_api_url_override' => $this->language->get('text_form_api_url_override'),
                'text_form_webhook_url' => $this->language->get('text_form_webhook_url'),
                'text_form_initial_order_status' => $this->language->get('text_form_initial_order_status'),
                'text_form_cancelled_order_status' => $this->language->get('text_form_cancelled_order_status'),
                'text_form_success_order_status' => $this->language->get('text_form_success_order_status'),
                'text_form_sort_order' => $this->language->get('text_form_sort_order'),
                'text_form_accept_zero_confirmations' => $this->language->get('text_form_accept_zero_confirmations'),
                'text_form_debug' => $this->language->get('text_form_debug'),
                'text_form_enable' => $this->language->get('text_form_enable'),
                'text_form_network_processing_fee_paid_by' => $this->language->get('text_form_network_processing_fee_paid_by'),
                'text_form_network_processing_fee_paid_by_merchant_notice' => $this->language->get('text_form_network_processing_fee_paid_by_merchant_notice'),
                'edit_text' => $this->language->get('edit_text')
            ]
        ];

        unset($this->session->data['success']);
        unset($this->session->data['errors']);

        $this->response->setOutput($this->load->view('extension/forumpay/payment/forumpay', $data));
    }

    /**
     * Test API credentials from admin settings.
     *
     * @return void
     */
    public function ping(): void
    {
        if (!$this->user->hasPermission('modify', 'extension/forumpay/payment/forumpay')) {
            $this->serializePingError(
                new ForumPayHttpException(
                    'Access denied. Admin privileges required.',
                    4003,
                    ForumPayHttpException::HTTP_FORBIDDEN
                )
            );
            return;
        }

        $logger = (new ForumPayLogger($this->log))
            ->setDebug((bool) $this->config->get(ConfigurationData::FORUMPAY_PAYMENT_DEBUG))
            ->addParser(new PrivateTokenMasker());

        $configuration = new Configuration(
            $this->config,
            $this->language,
            VERSION ?? null,
            DIR_APPLICATION ?? null
        );

        $this->load->model('extension/forumpay/payment/forumpay');

        $session = new \stdClass();
        $session->data = [];

        $orderManager = new OrderManager(
            $session,
            null,
            new CheckoutTransactions($this->model_extension_forumpay_payment_forumpay),
            $this->request,
            $configuration,
            $this->language,
            null,
            null,
            null,
        );

        $forumPay = new ForumPayPayment($configuration, $orderManager, $logger);
        $ping = new Ping($forumPay, $logger);

        try {
            $response = $ping->execute(new Request());
            if ($response !== null) {
                $this->serializePingResponse($response->toArray());
            }
        } catch (ApiHttpException $e) {
            $this->serializePingError($e, $e->getHttpCode());
        } catch (ForumPayHttpException $e) {
            $this->serializePingError($e, $e->getHttpCode());
        } catch (ForumPayException $e) {
            $this->serializePingError(
                new ForumPayHttpException(
                    $e->getMessage(),
                    $e->getCode(),
                    ForumPayHttpException::HTTP_BAD_REQUEST
                ),
                ForumPayHttpException::HTTP_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $this->serializePingError(
                new ForumPayHttpException(
                    $e->getMessage(),
                    $e->getCode(),
                    ForumPayHttpException::HTTP_INTERNAL_ERROR,
                ),
                ForumPayHttpException::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * @param array $response
     * @param int $status
     * @return void
     */
    private function serializePingResponse(array $response, int $status = 200): void
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
    private function serializePingError(\Exception $e, int $status = 500): void
    {
        if ($e instanceof ForumPayHttpException) {
            $status = $e->getHttpCode();
        }

        Response::setHttpResponseCode($status);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ]));
    }

    /**
     * Saves config data to settings table.
     *
     * @return void
     */
    public function save(): void
    {
        if ($this->user->hasPermission('modify', 'extension/forumpay/payment/forumpay')) {
            $this->load->model('setting/setting');
            $currentSettings = $this->model_setting_setting->getSetting('payment_forumpay');
            $post = $this->request->post;

            $configuration = new Configuration(
                $this->config,
                $this->language,
                VERSION ?? null,
                DIR_APPLICATION ?? null
            );

            $postedOverride = trim((string) ($post[ConfigurationData::FORUMPAY_API_URL_OVERRIDE] ?? ''));
            $postedUser = trim((string) ($post[ConfigurationData::FORUMPAY_API_USER] ?? ''));
            $rawSecret = (string) ($post[ConfigurationData::FORUMPAY_API_KEY] ?? '');
            $normalizedSecret = $configuration->normalizePostedSecret($rawSecret);
            $hasStoredSecret = trim((string) ($currentSettings[ConfigurationData::FORUMPAY_API_KEY] ?? '')) !== '';

            $errors = [];

            $postedApiEnv = trim((string) ($post[ConfigurationData::FORUMPAY_API_URL] ?? ''));
            if (!Configuration::isAllowedApiEnv($postedApiEnv)) {
                $errors[] = 'Invalid API environment URL.';
            }

            if ($postedUser === '') {
                $errors[] = 'API User is required.';
            }

            $trimmedRawSecret = trim($rawSecret);
            if ($trimmedRawSecret === '') {
                $errors[] = 'API Secret is required.';
            } elseif ($trimmedRawSecret === Configuration::API_SECRET_MASK) {
                if (!$hasStoredSecret) {
                    $errors[] = 'API Secret is required.';
                }
            }

            if (
                $configuration->requiresExplicitCredentials($postedApiEnv, $postedOverride)
                && $normalizedSecret === ''
            ) {
                $errors[] = 'Enter your API Secret to change the API environment.';
            }

            if (count($errors) === 0) {
                $post[ConfigurationData::FORUMPAY_API_USER] = $postedUser;

                if ($trimmedRawSecret === Configuration::API_SECRET_MASK) {
                    $post[ConfigurationData::FORUMPAY_API_KEY] = $currentSettings[ConfigurationData::FORUMPAY_API_KEY];
                } else {
                    $post[ConfigurationData::FORUMPAY_API_KEY] = $normalizedSecret;
                }

                $errors = ConfigurationData::validate($post, $currentSettings);
            }

            if (count($errors) === 0) {
                $this->model_setting_setting->editSetting('payment_forumpay', $post);
                $this->session->data['success'] = true;
            } else {
                $this->session->data['success'] = false;
                $this->session->data['errors'] = $errors;
            }
        } else {
            $this->session->data['errors'] = 'Insufficient permissions.';
        }

        $this->response->redirect(
            $this->url->link(
                'extension/forumpay/payment/forumpay',
                sprintf('user_token=%s', $this->session->data['user_token']),
                true
            )
        );
    }

    /**
     * This method is executed when install button is pressed in admin dashboard.
     *
     * @return void
     */
    public function install(): void
    {
        if ($this->user->hasPermission('modify', 'extension/payment')) {
            $this->load->model('extension/forumpay/payment/forumpay');

            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting(ConfigurationData::FORUMPAY_TABLE_NAME, [
                ConfigurationData::FORUMPAY_TITLE => 'Pay with crypto',
                ConfigurationData::FORUMPAY_DESCRIPTION => 'Pay with crypto (by ForumPay)',
                ConfigurationData::FORUMPAY_ENABLED => '1',
                ConfigurationData::FORUMPAY_ACCEPT_ZERO_CONFIRMATIONS => '1',
                ConfigurationData::FORUMPAY_INITIAL_ORDER_STATUS => '2',
                ConfigurationData::FORUMPAY_CANCELLED_ORDER_STATUS => '7',
                ConfigurationData::FORUMPAY_SUCCESS_ORDER_STATUS => '5',
                ConfigurationData::FORUMPAY_PAYMENT_DEBUG => '0',
                ConfigurationData::FORUMPAY_NETWORK_PROCESSING_FEE_PAID_BY => 'payer'
            ]);

            $this->model_extension_forumpay_payment_forumpay->install();
        }
    }

    /**
     * This method is executed when uninstall button is pressed in admin dashboard.
     *
     * @return void
     */
    public function uninstall(): void
    {
        if ($this->user->hasPermission('modify', 'extension/payment')) {
            $this->load->model('extension/forumpay/payment/forumpay');

            $this->model_extension_forumpay_payment_forumpay->uninstall();
        }
    }
}
