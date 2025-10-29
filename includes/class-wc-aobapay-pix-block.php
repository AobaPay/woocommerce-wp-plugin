<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!class_exists('WC_AobaPay_Pix_Block')) {

    final class WC_AobaPay_Pix_Block extends AbstractPaymentMethodType
    {
        private $gateway;
        protected $name = 'aobapay_pix';

        public function initialize()
        {
            $gateways       = WC()->payment_gateways->payment_gateways();
            $this->gateway  = $gateways['aobapay_pix'] ?? null;
        }

        public function is_active()
        {
            return $this->gateway && $this->gateway->is_available();
        }

        public function get_payment_method_script_handles()
        {
            $asset_path = dirname(AOBAPAY_PLUGIN_FILE) . '/assets/aobapay-block.asset.php';
            $asset_url = plugins_url('assets/aobapay-block.js', AOBAPAY_PLUGIN_FILE);
            $asset = file_exists($asset_path) ? require $asset_path : ['dependencies' => [], 'version' => null];

            wp_register_script(
                'aobapay-block',
                $asset_url,
                $asset['dependencies'],
                $asset['version'],
                true
            );

            return ['aobapay-block'];
        }

        public function get_payment_method_data()
        {
            if (!$this->gateway) {
                return [];
            }
            return [
                'title'       => $this->gateway->get_title(),
                'description' => $this->gateway->get_description(),
                'supports'    => $this->gateway->supports
            ];
        }
    }
}