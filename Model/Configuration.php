<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model;

use ForumPay\PaymentGateway\OpenCartExtension\Exception\ForumPayException;
use ForumPay\PaymentGateway\OpenCartExtension\Form\ConfigurationData;

class Configuration
{
    /**
     * @var mixed
     */
    private $configuration;

    /**
     * @var mixed
     */
    private $language;

    /**
     * @var string|null
     */
    private ?string $openCartVersion;

    /**
     * @var string|null
     */
    private ?string $appDir;

    public const API_SECRET_MASK = '******';

    public const API_ENV_PRODUCTION = 'https://api.forumpay.com/pay/v2/';

    public const API_ENV_SANDBOX = 'https://sandbox.api.forumpay.com/pay/v2/';

    /**
     * Normalize a posted API secret: trim; treat mask as omitted.
     */
    public function normalizePostedSecret(string $value): string
    {
        $value = trim($value);

        if ($value === self::API_SECRET_MASK) {
            return '';
        }

        return $value;
    }

    /**
     * Constructor
     *
     * @param $configuration
     * @param $language
     * @param string|null $openCartVersion
     * @param string|null $appDir
     */
    public function __construct($configuration, $language, ?string $openCartVersion, ?string $appDir)
    {
        $this->configuration = $configuration;
        $this->language = $language;
        $this->openCartVersion = $openCartVersion;
        $this->appDir = $appDir;
    }

    /**
     * Get api url from settings
     *
     * @return string|null
     */
    public function getApiUrl(): ?string
    {
        $url = $this->getApiUrlOverride();

        if (empty($url)) {
            $url = $this->configuration->get('payment_forumpay_api_url');
        }

        return empty($url) ? null : $url;
    }

    /**
     * Get custom environment URL override from settings (without fallback).
     *
     * @return string|null
     */
    public function getApiUrlOverride(): ?string
    {
        $url = $this->configuration->get(ConfigurationData::FORUMPAY_API_URL_OVERRIDE);

        return empty($url) ? null : $url;
    }

    /**
     * Saved Production/Sandbox environment URL (not the override-merged getApiUrl()).
     *
     * @return string
     */
    public function getSavedApiEnv(): string
    {
        $url = $this->configuration->get(ConfigurationData::FORUMPAY_API_URL);

        return empty($url) ? '' : (string) $url;
    }

    /**
     * Normalize an override URL for comparison: trim, lowercase, strip trailing slashes.
     * Mirrors normalizeUrl() in admin_gateway_settings.js.
     *
     * @param string $url
     * @return string
     */
    public function normalizeOverrideUrl(string $url): string
    {
        return rtrim(strtolower(trim($url)), '/');
    }

    /**
     * True when apiEnv is Production or Sandbox (normalized compare).
     */
    public static function isAllowedApiEnv(string $apiEnv): bool
    {
        $normalized = rtrim(strtolower(trim($apiEnv)), '/');

        foreach ([self::API_ENV_PRODUCTION, self::API_ENV_SANDBOX] as $allowed) {
            if (rtrim(strtolower(trim($allowed)), '/') === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * Stored credentials may only be reused when both the saved Environment and
     * saved override are unchanged. Changing Production↔Sandbox, or changing/clearing
     * the custom environment URL, requires a real secret in the request.
     *
     * @param string $requestedApiEnv
     * @param string $requestedOverride
     * @return bool
     */
    public function requiresExplicitCredentials(string $requestedApiEnv, string $requestedOverride): bool
    {
        $overrideChanged =
            $this->normalizeOverrideUrl($requestedOverride)
            !== $this->normalizeOverrideUrl((string) $this->getApiUrlOverride());

        $envChanged =
            $this->normalizeOverrideUrl($requestedApiEnv)
            !== $this->normalizeOverrideUrl($this->getSavedApiEnv());

        return $overrideChanged || $envChanged;
    }

    /**
     * Resolve the credentials to use for an outbound call, enforcing the security
     * invariant. Returns ['apiUser' => ..., 'apiSecret' => ...].
     *
     * @param string $apiEnv
     * @param string $requestedOverride
     * @param string $requestedUser
     * @param string $requestedSecret
     * @return array{apiUser: string, apiSecret: string}
     * @throws ForumPayException when apiEnv is invalid or credentials are required but missing
     */
    public function resolveCredentials(
        string $apiEnv,
        string $requestedOverride,
        string $requestedUser,
        string $requestedSecret
    ): array {
        if (!self::isAllowedApiEnv($apiEnv)) {
            throw new ForumPayException('Invalid API environment URL.');
        }

        $requestedUser = trim($requestedUser);
        $rawSecret = trim($requestedSecret);

        if ($requestedUser === '') {
            throw new ForumPayException('API User is required.');
        }
        if ($rawSecret === '') {
            throw new ForumPayException('API Secret is required.');
        }

        $isMask = $rawSecret === self::API_SECRET_MASK;
        if ($this->requiresExplicitCredentials($apiEnv, $requestedOverride) && $isMask) {
            throw new ForumPayException(
                'Enter your API Secret to change the API environment.'
            );
        }

        $apiSecret = $isMask ? (string) ($this->getMerchantApiSecret() ?? '') : $rawSecret;
        if ($apiSecret === '') {
            throw new ForumPayException('API Secret is required.');
        }

        return ['apiUser' => $requestedUser, 'apiSecret' => $apiSecret];
    }

    /**
     * Get Api key from settings
     *
     * @return string|null
     */
    public function getMerchantApiUser(): ?string
    {
        $apiUser = $this->configuration->get('payment_forumpay_api_user');

        return empty($apiUser) ? null : $apiUser;
    }

    /**
     * Get Api secret from settings
     *
     * @return string|null
     */
    public function getMerchantApiSecret(): ?string
    {
        $apiKey = $this->configuration->get('payment_forumpay_api_key');

        return empty($apiKey) ? null : $apiKey;
    }

    /**
     * Get default store locale
     *
     * @return string
     */
    public function getStoreLocale(): string
    {
        return $this->language->get('code');
    }

    /**
     * Get OpenCart installation version if possible
     *
     * @return string|null
     */
    public function getOpenCartVersion(): ?string
    {
        return $this->openCartVersion;
    }

    /**
     * Get current ForumPay gateway installation version
     *
     * @return string
     */
    public function getPluginVersion(): string
    {
        $filePath = $this->appDir . '/../extension/forumpay/install.json';

        $unknownVersionText = 'unknown_forumpay_version';

        if (!file_exists($filePath)) {
            return $unknownVersionText;
        }

        try {
            $file = file_get_contents($filePath);
            $json = json_decode($file, true);
        } catch (\Exception $e) {
            return $unknownVersionText;
        }

        return $json['version'] ?? $unknownVersionText;
    }

    /**
     * Return POS ID from settings
     *
     * @return string|null
     */
    public function getPosId(): ?string
    {
        $posId = $this->configuration->get('payment_forumpay_pos_id');

        return empty($posId) ? null : $posId;
    }

    /**
     * Returns url to Webhook api depending.
     *
     * @return string|null
     */
    public function getWebhookUrl(): ?string
    {
        $webhookUrl = $this->configuration->get('payment_forumpay_webhook_url');

        return empty($webhookUrl) ? null : $webhookUrl;
    }

    /**
     * Return weather or not zero confirmation is checked in settings
     *
     * @return bool
     */
    public function isAcceptZeroConfirmations(): bool
    {
        return (bool) $this->configuration->get('payment_forumpay_accept_zero_confirmation');
    }

    /**
     * Returns configured success order status
     *
     * @return string
     */
    public function getSuccessOrderStatus(): string
    {
        return $this->configuration->get(ConfigurationData::FORUMPAY_SUCCESS_ORDER_STATUS);
    }

    /**
     * Returns configured cancelled order status
     *
     * @return string
     */
    public function getCancelledOrderStatus(): string
    {
        return $this->configuration->get(ConfigurationData::FORUMPAY_CANCELLED_ORDER_STATUS);
    }

    public function getNetworkProcessingFeePaidBy(): string
    {
        return $this->configuration->get('payment_forumpay_network_processing_fee_paid_by') ?: 'payer';
    }

    public function getConfigCustomerPrice()
    {
        return $this->configuration->get('config_customer_price');
    }
}
