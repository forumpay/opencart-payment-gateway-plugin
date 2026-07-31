const forumPayData = function (field) {
  return document.getElementById(field).getAttribute('data');
}

const initPlugin = function () {
  const orderId = forumPayData('forumpay-orderid');
  const orderParams = {
    orderId: orderId,
  };

  const config = {
    baseUrl: forumPayData('forumpay-apibase'),

    restGetCryptoCurrenciesUri: {
      'path': '',
      'params': {
        'act': 'currencies',
        ...orderParams,
      },
    },
    restGetRateUri: {
      'path': '',
      'params': {
        'act': 'getRate',
        ...orderParams,
      },
    },
    restGetRatesUri: {
      'path': '',
      'params': {
        'act': 'getRates',
        ...orderParams,
      },
    },
    restStartPaymentUri: {
      'path': '',
      'params': {
        'act': 'startPayment',
        ...orderParams,
      },
    },
    restCheckPaymentUri: {
      'path': '',
      'params': {
        'act': 'checkPayment',
        ...orderParams,
      },
    },
    restCancelPaymentUri: {
      'path': '',
      'params': {
        'act': 'cancelPayment',
        ...orderParams,
      },
    },
    restRestoreCart: {
      'path': '',
      'params': {
        'act': 'restoreCart'
      }
    },
    restGetWalletAppsUri: {
      'path': '',
      'params': {
        'act': 'getWalletApps',
        ...orderParams,
      }
    },
    successResultUrl: forumPayData('forumpay-returnurl'),
    errorResultUrl: forumPayData('forumpay-cancelurl'),
    invoiceAmount: forumPayData('forumpay-invoiceamount'),
    invoiceCurrency: forumPayData('forumpay-invoicecurrency'),
    payer: {
      'payer_type': '',
      'payer_first_name': forumPayData('forumpay-payerfirstname'),
      'payer_last_name': forumPayData('forumpay-payerlastname'),
      'payer_country_of_residence': forumPayData('forumpay-payercountry'),
      'payer_email': forumPayData('forumpay-payeremail'),
      'payer_date_of_birth': '',
      'payer_country_of_birth': '',
      'payer_company': forumPayData('forumpay-payercompany'),
      'payer_date_of_incorporation': '',
      'payer_country_of_incorporation': forumPayData('forumpay-payercountry'),
    },
    messageReceiver: function (name, data) {
    },
  }
  window.forumPayPaymentGatewayWidget = new ForumPayPaymentGatewayWidget(config);
  window.forumPayPaymentGatewayWidget.init();
}

$( document ).ready(function() {
  initPlugin();
});
