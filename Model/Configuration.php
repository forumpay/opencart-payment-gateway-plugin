<?php

namespace ForumPay\PaymentGateway\OpenCartExtension\Model;

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
        $url = $this->configuration->get('payment_forumpay_api_url_override');

        if (empty($url)) {
            $url = $this->configuration->get('payment_forumpay_api_url');
        }

        return empty($url) ? null : $url;
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

    public function getConfigCustomerPrice()
    {
        return $this->configuration->get('config_customer_price');
    }
}
