jQuery(document).ready(function($) {
  const SECRET_MASK = '******';
  const pingUrl = $('#payment_forumpay_api_test').data('ping-url');
  const $apiEnvField = $('#payment_forumpay_api_url');
  const $apiUrlOverrideField = $('#payment_forumpay_api_url_override');
  const $apiUserField = $('#payment_forumpay_api_user');
  const $apiSecretField = $('#payment_forumpay_api_key');
  const $credentialNotice = $('#forumpay-api-url-change-notice');
  const $apiTestButton = $('#payment_forumpay_api_test');

  function normalizeUrl(url) {
    url = (url || '').trim();
    if (!url) {
      return '';
    }

    return url.replace(/\/+$/, '').toLowerCase();
  }

  const savedBaseline = {
    apiEnv: normalizeUrl($apiEnvField.val()),
    override: normalizeUrl($apiUrlOverrideField.val()),
  };

  function apiEnvChanged(env) {
    return normalizeUrl(env) !== savedBaseline.apiEnv;
  }

  function overrideChanged(override) {
    return normalizeUrl(override) !== savedBaseline.override;
  }

  function envOrOverrideChanged() {
    return apiEnvChanged($apiEnvField.val()) || overrideChanged($apiUrlOverrideField.val());
  }

  function secretMissingForEnvChange() {
    const value = ($apiSecretField.val() || '').trim();
    return value === '' || value === SECRET_MASK;
  }

  function needsRealSecretForEnvChange() {
    return envOrOverrideChanged() && secretMissingForEnvChange();
  }

  function updateCredentialNotice() {
    $credentialNotice.toggle(needsRealSecretForEnvChange());
  }

  function updateTestButtonState() {
    $apiTestButton.prop('disabled', needsRealSecretForEnvChange());
    updateCredentialNotice();
  }

  $apiEnvField.on('change', updateTestButtonState);
  $apiUrlOverrideField.on('change input', updateTestButtonState);
  $apiSecretField.on('input change', updateTestButtonState);
  updateTestButtonState();

  $apiTestButton.on('click', function(e) {
    e.preventDefault();

    if (needsRealSecretForEnvChange()) {
      updateCredentialNotice();
      $apiSecretField.focus();
      return;
    }

    var $button = $(this);
    var originalText = $button.text();
    $button.prop('disabled', true);
    $button.text('Testing ...');

    $.ajax({
      url: pingUrl,
      type: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({
        apiEnv: $apiEnvField.val(),
        apiKey: $apiUserField.val(),
        apiSecret: ($apiSecretField.val() || '').trim(),
        apiUrlOverride: $apiUrlOverrideField.val(),
      }),
      showLoader: true,
      success: function(response) {
        updateTestButtonState();
        $button.text(originalText);
        alert('Server responded: ' + response?.message);
      },
      error: function(error) {
        updateTestButtonState();
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
