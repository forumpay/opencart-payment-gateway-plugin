# OpenCart Forumpay payment module
# Installation guide

## Requirements

> Make sure you have at least OpenCart Version 8.1.0 or higher.

> You should already have downloaded the latest release of ForumPay plugin
> from [this link](https://github.com/forumpay/opencart-payment-gateway-plugin/releases/latest).
Download the file named opencart.ocmod.zip

## Installation

On your admin dashboard go to Extensions > Installer and click on the "Upload" button to upload
the zip you downloaded. After submitting the zip file a new plugin should appear in the table bellow.
Click the green installation button and wait.

After installation of plugin is complete go to Extensions > Extensions and chose the extension type 'Payments'.
Find ForumPay plugin in the table bellow and press the green installation button.

## Configuration

Once the plugin has been installed, on the same page (Extensions > Extensions > Choose the extension type > Payments)
click the 'Edit' button to configure the plugin.

### Configuration details:

1. **Title**
   The label of the payment method that is displayed when your customer is prompted to choose one.
   You can leave default or set it to something like *Pay with crypto*.
2. **Description**
   Additional information along with *Title*.
3. **POS ID**
   Identifier for payments from this webshop to be identified in your ForumPay dashboard.
   Must be a unique string. E.g.: opencart1
4. **API User**
   Unique ForumPay API-key identifier that you have to generate in the Forumpay dashboard.
   It can be found in your **Profile** section.
   [Go to profile >](https://dashboard.forumpay.com/pay/userPaymentGateway.api_settings)
5. **API Secret**
   *Important:* never share it to anyone!
   API Secret consists of two parts. When generated in [ForumPay dashboard](https://dashboard.forumpay.com/pay/userPaymentGateway.api_settings), the first one will be displayed in your profile,
   while the second part will be sent to your e-mail. You need to enter both parts here (one after the other).
6. **Sort Order**
   This value determines the display order of the payment method.
   A lower number indicates a higher priority, resulting in placement at the top.
7. **Initial Order Status**
   Is a one of internal order statuses assigned to a new order when ForumPay payment is started.
8. **Canceled Order Status**
   Is a configured status assigned to an existing order when payment gets cancelled.
9. **Success Order Status**
   Is a configured status assigned to an order after customer successfully completes the purchase using ForumPay.
10. **Debug**
    This option determines whether debug-level logs are enabled.


Don't forget to click *Save* button after the settings are filled in.

## Webhook setup

**Webhook** allows us to check order status **independently** of the customer's actions.

For example, if the customer **closes tab** after the payment is started,
the webshop cannot determine what the status of the order is.

If you do not set the webhook notifications, orders may stay in the `Pending` status forever.

### Webhook setup:

Webhook configuration is in your [Profile](https://dashboard.forumpay.com/pay/userPaymentGateway.api_settings#webhook_notifications).
You can find the webhook URL by scrolling down.

Insert **URL** in the webhook URL field:
`YOUR_WEBSHOP/index.php?route=extension/forumpay/payment/forumpay.api&act=webhook`

**YOUR_WEBSHOP** is the URL of your webshop. An example of the complete webhook URL would be:
`https://my.webshop.com/index.php?route=extension/forumpay/payment/forumpay.api&act=webhook`

## Functionality

When the customer clicks on the **Confirm order** button they are being redirected to the payment page,
where cryptocurrency can be selected.

When the currency is selected, details for the cryptocurrency payment will be
displayed: Amount, Rate, Fee, Total, Expected time.

After the customer clicks the **START PAYMENT** button, they have 5 minutes to pay for
the order by scanning the **QR Code** or manually using the blockchain address shown under the QR Code.

## Logs

Logs are located in System > Maintenance > Error Logs.
