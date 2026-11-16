<?php
// WCML handles product, variation, coupon, shipping, cart and order currencies.
// Add its keyless daily exchange-rate provider without modifying the plugin.
add_action('init', function () {
    global $woocommerce_wpml;
    $exchange = $woocommerce_wpml->multi_currency->exchange_rate_services ?? null;
    if (!$exchange || !class_exists('WCML\\MultiCurrency\\ExchangeRateServices\\Service')) return;
    $service = new class extends \WCML\MultiCurrency\ExchangeRateServices\Service {
        public function getId() { return 'dona-open-exchange'; }
        public function getName() { return 'ExchangeRate-API (daily, no API key)'; }
        public function getUrl() { return 'https://www.exchangerate-api.com'; }
        public function getApiUrl() { return 'https://open.er-api.com/v6/latest/%1$s'; }
        public function isKeyRequired() { return false; }
        protected function isInvalidResponse($data) {
            return !is_object($data) || ($data->result ?? '') !== 'success' || empty($data->rates);
        }
        protected function extractRates($data, $from, $tos) {
            $rates = [];
            foreach ($tos as $code) {
                $rate = $data->rates->{$code} ?? null;
                if (!is_numeric($rate) || !is_finite((float)$rate) || $rate <= 0) {
                    throw new \Exception('Missing valid exchange rate for '.sanitize_text_field($code));
                }
                $rates[$code] = round((float)$rate, 6);
            }
            return $rates;
        }
    };
    $exchange->add_service($service->getId(), $service);
}, 3);

