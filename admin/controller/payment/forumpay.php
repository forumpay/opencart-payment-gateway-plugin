<?php

namespace Opencart\Admin\Controller\Extension\ForumPay\Payment;

require_once __DIR__ . '/../../../vendor/autoload.php';

use ForumPay\PaymentGateway\OpenCartExtension\Form\ConfigurationData;

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
                'text_form_pos_id' => $this->language->get('text_form_pos_id'),
                'text_form_api_url_override' => $this->language->get('text_form_api_url_override'),
                'text_form_initial_order_status' => $this->language->get('text_form_initial_order_status'),
                'text_form_cancelled_order_status' => $this->language->get('text_form_cancelled_order_status'),
                'text_form_success_order_status' => $this->language->get('text_form_success_order_status'),
                'text_form_sort_order' => $this->language->get('text_form_sort_order'),
                'text_form_accept_zero_confirmations' => $this->language->get('text_form_accept_zero_confirmations'),
                'text_form_debug' => $this->language->get('text_form_debug'),
                'text_form_enable' => $this->language->get('text_form_enable'),
                'edit_text' => $this->language->get('edit_text')
            ]
        ];

        unset($this->session->data['success']);
        unset($this->session->data['errors']);

        $this->response->setOutput($this->load->view('extension/forumpay/payment/forumpay', $data));
    }

    /**
     * Saves config data to settings table.
     *
     * @return void
     */
    public function save(): void
    {
        if ($this->user->hasPermission('modify', 'extension/forumpay/payment/forumpay')) {
            $errors = ConfigurationData::validate($this->request->post);

            if (count($errors) === 0) {
                $this->load->model('setting/setting');
                $this->model_setting_setting->editSetting('payment_forumpay', $this->request->post);
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
                ConfigurationData::FORUMPAY_PAYMENT_DEBUG => '0'
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
