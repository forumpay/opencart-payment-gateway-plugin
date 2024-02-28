# ForumPay Crypto Payments for OpenCart
# Installation guide

## Requirements

> Make sure you have at least OpenCart Version 4.0.0 or higher.

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
   The label of the payment method that is displayed when user is prompted to choose one. You can leave default or set it to something like *Pay with crypto*.
2. **Description**
   The additional description of the payment method that is displayed under the title.
3. **Environment**
   Dropdown lets you switch between 'Production' and 'Sandbox' modes.
   Use 'Production' for processing real transactions in a live environment and
   'Sandbox' for safe testing without financial implications.
4. **API User**
   This is our identifier that we need to access the payment system.
   It can be found in your **Profile**.
   [Go to profile >](https://dashboard.forumpay.com/pay/userPaymentGateway.api_settings)
5. **API Secret**
   _Important:_ never share it to anyone!
   Think of it as a password.
   API Secret consists of two parts. When generated in [ForumPay dashboard](https://dashboard.forumpay.com/pay/userPaymentGateway.api_settings),
   the first one will be displayed in your profile, while the second part will be sent to your e-mail.
   You need to enter both parts here (one after the other).
6. **POS ID**
   This is how payments coming to your wallets are going to be identified.
   Special characters are not allowed. Allowed characters are: `[A-Za-z0-9._-]` (e.g. `my-shop`, `my_shop`).
7. **Initial Order Status**
   Which status the order gets when user starts the payment.
8. **Canceled Order Status**
   Which status the order gets once user cancels the payment.
9. **Success Order Status**
   Which status the order gets once user successfully completes the payment.
10. **Sort order**
    This value determines the display order of the payment method. A lower number indicates a higher priority, resulting in placement at the top.
11. **Custom environment URL**
    Optional: URL to the API server. This value will override the default setting. Only used for debugging.
12. **Debug**
    When enabled all log levels, including debug log level, will be recorded. Only used for debugging.
13. **Accept Instant (Zero) Confirmations**
    Allows immediate transaction approval without waiting for network confirmations, enhancing speed but with increased risk.


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
