<?php

$_['heading_title'] = 'ForumPay Crypto Payments';
$_['edit_settings'] = 'Edit ForumPay Settings';
$_['text_home'] = 'Home';
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Configuration saved successfully!';
$_['edit_text'] = 'Edit ForumPay Settings';
$_['text_form_title'] = 'This controls the title which the user sees during checkout.';
$_['text_form_description'] = 'This controls the description which the user sees during checkout.';
$_['text_form_environment'] = 'ForumPay environment.';
$_['text_form_api_user'] = 'You can generate API key in your ForumPay account.';
$_['text_form_api_key'] = 'You can generate API secret in your ForumPay account.';
$_['text_form_pos_id'] = 'Enter your webshop identifier (POS ID). Special characters not allowed. Allowed pos IDs are: [A-Za-z0-9._-] Eg opencart-1, opencart-2';
$_['text_form_api_url_override'] = 'Optional: URL to the API server. This value will override the default setting. Only used for debugging.';
$_['text_form_webhook_url'] = 'Optional: This URL should point to the endpoint that will handle the webhook events.<br> Typically, it should be: <b><i>YOUR_WEBSHOP/index.php?route=extension/forumpay/payment/forumpay.api&act=webhook</i></b><br> This URL will override the default setting for your API keys on your ForumPay account.<br> Ensure that the URL is publicly accessible and can handle the incoming webhook events securely.';
$_['text_form_initial_order_status'] = 'Order status assigned to a newly created order.';
$_['text_form_cancelled_order_status'] = 'Order status assigned to cancelled order.';
$_['text_form_success_order_status'] = 'Order status assigned to successful orders.';
$_['text_form_sort_order'] = 'This value determines the display order of the payment method. A lower number indicates a higher priority, resulting in placement at the top.';
$_['text_form_accept_zero_confirmations'] = 'Enable Accept Zero Confirmations.';
$_['text_form_debug'] = 'Enable debug log level.';
$_['text_form_enable'] = 'Enable ForumPay Crypto Payments.';
$_['text_form_network_processing_fee_paid_by'] = 'Set who will pay for the network processing fee.';
$_['text_form_network_processing_fee_paid_by_merchant_notice'] = 'This option must be enabled for your account before you can select \'Merchant\'. Contact your account representative or <a href="mailto:support@forumpay.com">support@forumpay.com</a>';
