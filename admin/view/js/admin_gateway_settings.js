jQuery(document).ready(function($) {
  let baseUrl = '';
  if (!baseUrl) {
    const protocol = window.location.protocol;
    const host = window.location.host;
    baseUrl = protocol + "//" + host + "/index.php?route=extension/forumpay/payment/forumpay.api&act=ping";
  }

  $('#payment_forumpay_api_test').on('click', function(e) {
    e.preventDefault();

    var $button = $(this);
    var originalText = $button.text();
    $button.prop('disabled', true);
    $button.text('Testing ...');

    $.ajax({
      url: baseUrl,
      type: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({
        apiEnv: $('#payment_forumpay_api_url').val(),
        apiKey: $('#payment_forumpay_api_user').val(),
        apiSecret: $('#payment_forumpay_api_key').val(),
        apiUrlOverride: $('#payment_forumpay_api_url_override').val(),
      }),
      showLoader: true,
      success: function(response) {
        $button.prop('disabled', false);
        $button.text(originalText);
        alert('Server responded: ' + response?.message);
      },
      error: function(error) {
        $button.prop('disabled', false);
        $button.text(originalText);

        var message = '';
        if (error?.responseJSON?.code > 0) {
          message += error.responseJSON.code + ' - ';
        }
        message += error?.responseJSON?.message ?? "Unknown error occurred. Please contact support.";
        alert(message);
      }
    });
  });

  const fields = [
    'accept_underpayment',
    'accept_overpayment',
  ];

  function initializeFieldHandlers(field) {
    $(`input[name="payment_forumpay_${field}"]`).change(() => {
      togglePaymentOptions(field);
    });

    $(`input[name="payment_forumpay_${field}_modify_order_total"]`).change(() => {
      toggleModifyOrderDescription(field);
    });
  }

  function togglePaymentOptions(field) {
    const isChecked = $(`input[name="payment_forumpay_${field}"]:checked`).val() === "1";

    $(`input[name="payment_forumpay_${field}_threshold"]`).closest('.row').toggle(isChecked);
    $(`input[name="payment_forumpay_${field}_modify_order_total"]`).closest('.row').toggle(isChecked);

    validateThreshold(field, isChecked);
    toggleModifyOrderDescription(field);
  }

  function validateThreshold(field, isChecked) {
    const thresholdElement = $(`input[name="payment_forumpay_${field}_threshold"]`);

    if (thresholdElement.length === 0) {
      return;
    }

    const maxValue = 99.99;
    const message = field === 'accept_underpayment'
      ? 'Please enter a valid percentage between 0 and 100 or leave blank to accept any underpayment amount.'
      : 'Please enter a valid percentage between 0 and 100 or leave blank to accept any overpayment amount.';

    if (!isChecked) {
      thresholdElement[0].setCustomValidity('');
      return;
    }

    thresholdElement.off('input').on('input', function () {
      const value = this.value;
      const floatValue = parseFloat(value);
      const isValid = value === '' || (!isNaN(floatValue) && floatValue >= 0.01 && floatValue <= maxValue);
      this.setCustomValidity(isValid ? '' : message);
      this.reportValidity();
    });

    thresholdElement.off('blur').on('blur', function () {
      const value = this.value.trim();

      if (!isNaN(value) && value !== '') {
        const floatValue = Number(value);
        const decimals = (value.split('.')[1] || '').length;

        if (decimals > 2) {
          this.value = floatValue.toFixed(2);
        }
      }

      if (/^0\d+/.test(value) || parseFloat(value) === 0) {
        this.value = 0;
      }

      const normalizedValue = this.value;
      const floatNormalized = parseFloat(normalizedValue);
      const isValid = normalizedValue === '' || (!isNaN(floatNormalized) && floatNormalized >= 0.01 && floatNormalized <= maxValue);
      this.setCustomValidity(isValid ? '' : message);
    });
  }

  function toggleModifyOrderDescription(field) {
    const isModifyOrderChecked = $(`input[name="payment_forumpay_${field}_modify_order_total"]:checked`).val() === "1";
    const isModifyOrderVisible = $(`input[name="payment_forumpay_${field}"]:checked`).val() === "1";

    $(`input[name="payment_forumpay_${field}_modify_order_total_description"]`).closest('.row').toggle(isModifyOrderVisible && isModifyOrderChecked);
    $(`input[name="payment_forumpay_${field}_modify_order_total_description"]`).prop('required', isModifyOrderVisible && isModifyOrderChecked);
  }

  fields.forEach(field => {
    togglePaymentOptions(field);
    initializeFieldHandlers(field);
  });
});
