<?php

/**
 * Plugin Name: AobaPay for WooCommerce
 * Plugin URI: https://app.aobapay.com/
 * Description: AobaPay gateway for WooCommerce.
 * Version: 1.0.0
 * Author: AobaPay
 * Author URI: https://aobapay.com/
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('AOBAPAY_PLUGIN_FILE')) {
    define('AOBAPAY_PLUGIN_FILE', __FILE__);
}

add_action('before_woocommerce_init', function () {
    if (class_exists('\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', AOBAPAY_PLUGIN_FILE, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', AOBAPAY_PLUGIN_FILE, true);
    }
});

add_action('plugins_loaded', 'woocommerce_aobapay_init', 0);

function woocommerce_aobapay_init()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'woocommerce_aobapay_missing_wc_notice');
        return;
    }

    WC_AobaPay_Plugin::get_instance();
}

function woocommerce_aobapay_missing_wc_notice()
{
    echo '<div class="error"><p><strong>' . esc_html__('AobaPay', 'aobapay-woocommerce') . '</strong> &#8211; ' . esc_html__('requires WooCommerce to be installed and active.', 'aobapay-woocommerce') . '</p></div>';
}

register_activation_hook(AOBAPAY_PLUGIN_FILE, array('WC_AobaPay_Plugin', 'activate'));

class WC_AobaPay_Plugin
{
    const VERSION = '1.0.0';
    protected static $instance = null;

    private function __construct()
    {
        $this->includes();
        add_filter('plugin_action_links_' . plugin_basename(AOBAPAY_PLUGIN_FILE), array($this, 'plugin_action_links'));
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
        add_action('wp_enqueue_scripts', array($this, 'load_plugin_assets'));
        add_action('rest_api_init', array($this, 'register_webhook_route'));
        add_action('admin_menu', array($this, 'admin_menu_logs'));
        add_action('woocommerce_blocks_loaded', array($this, 'woocommerce_blocks_support'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function woocommerce_blocks_support()
    {
        if (class_exists('Automattic\\WooCommerce\\Blocks\\Payments\\PaymentMethodRegistry')) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    if (class_exists('WC_AobaPay_Pix_Block')) {
                        $payment_method_registry->register(new \WC_AobaPay_Pix_Block());
                    }
                }
            );
        }
    }

    private function includes()
    {
        require_once __DIR__ . '/includes/class-wc-gateway-aobapay.php';
        require_once __DIR__ . '/includes/class-wc-aobapay-pix-block.php';
    }

    public function add_gateway($methods)
    {
        $methods[] = 'WC_Gateway_AobaPay';
        return $methods;
    }

    public function plugin_action_links($links)
    {
        $plugin_links = array();
        $plugin_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=aobapay_pix')) . '">' . __('Settings', 'aobapay-woocommerce') . '</a>';
        $plugin_links[] = '<a target="_blank" href="https://docs.aobapay.com">' . __('Documentation', 'aobapay-woocommerce') . '</a>';
        $plugin_links[] = '<a target="_blank" href="https://app.aobapay.com/register">' . __('Sign up', 'aobapay-woocommerce') . '</a>';
        return array_merge($plugin_links, $links);
    }

    public function register_webhook_route()
    {
        register_rest_route('aobapay/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array('WC_Gateway_AobaPay', 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    public function load_plugin_assets()
    {
        wp_register_style('aobapay-frontend', plugins_url('assets/aobapay-frontend.css?v=' . time(), AOBAPAY_PLUGIN_FILE));
        wp_register_script('aobapay-frontend', plugins_url('assets/aobapay-frontend.js?v=' . time(), AOBAPAY_PLUGIN_FILE), array('jquery'), null, true);
    }

    public function admin_menu_logs()
    {
        add_submenu_page('woocommerce', 'AobaPay Logs', 'AobaPay Logs', 'manage_woocommerce', 'aobapay-logs', function () {
            if (!current_user_can('manage_woocommerce')) {
                wp_die('Acesso negado');
                return;
            }
            echo '<div class="wrap"><h1>AobaPay Logs</h1>';
            echo '<p>Últimos logs do plugin AobaPay.</p>';
            $log_file = dirname(AOBAPAY_PLUGIN_FILE) . '/logs/aobapay.log';
            if (file_exists($log_file)) {
                $log_content = file_get_contents($log_file);
                echo '<pre style="max-height:600px;overflow:auto;background:#111;color:#fff;padding:12px;">' . esc_html($log_content) . '</pre>';
            } else {
                echo '<p>Nenhum log encontrado.</p>';
            }
            echo '</div>';
        });
    }

    public static function activate()
    {
        $log_dir = dirname(AOBAPAY_PLUGIN_FILE) . '/logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }

    public function admin_notices()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            echo '<div class="error"><p>' . __('AobaPay: WooCommerce is not active. Please activate WooCommerce to use this plugin.', 'aobapay-woocommerce') . '</p></div>';
        }
    }
}
