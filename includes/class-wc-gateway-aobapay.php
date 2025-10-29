<?php
if (! defined('ABSPATH')) exit;

class WC_Gateway_AobaPay extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'aobapay_pix';
        $this->icon = plugins_url('../assets/aoba-logo.png', __FILE__);
        $this->has_fields = false;
        $this->method_title = __('AobaPay PIX', 'aobapay-woocommerce');
        $this->method_description = __('Aceite pagamentos com PIX através do gateway AobaPay.', 'aobapay-woocommerce');
        $this->supports = array('products', 'refunds');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', 'PIX Rápido e Seguro');
        $this->description = $this->get_option('description', 'Pague com PIX e finalize sua compra. O QR Code será exibido na próxima tela.');
        $this->api_key = $this->get_option('api_key');
        $this->webhook_secret = $this->get_option('webhook_secret');
        $this->webhook_url = home_url('/wp-json/aobapay/v1/webhook');

        $this->logger = wc_get_logger();
        $this->log_context = array('source' => 'aobapay-woocommerce');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('admin_notices', array($this, 'check_requirements'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
    }

    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $br_code = $order->get_meta('_aobapay_br_code');
        $qr_code_img_url = $order->get_meta('_aobapay_br_code_image');

        if (empty($br_code)) return;

        wp_enqueue_style('aobapay-frontend');
        wp_enqueue_script('aobapay-frontend');

        $status_check_url = get_rest_url(null, "aobapay/v1/status/{$order_id}");

        wc_get_template(
            'payment-instructions.php',
            array(
                'order_id' => $order_id,
                'status_check_url' => $status_check_url,
                'br_code' => $br_code,
                'qr_code_img_url' => $qr_code_img_url,
            ),
            'aobapay-woocommerce/',
            plugin_dir_path(__FILE__) . '../templates/'
        );
    }

    public function register_rest_routes() {
        register_rest_route('aobapay/v1', '/status/(?P<order_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_payment_status'),
            'permission_callback' => '__return_true', // Anyone can check status
        ));
    }

    public function get_payment_status($request) {
        $order_id = $request['order_id'];
        $order = wc_get_order($order_id);

        if (!$order || $order->get_payment_method() !== 'aobapay_pix') {
            return new WP_REST_Response(array('status' => 'invalid'), 404);
        }

        $status = $order->get_status();
        $response_status = 'pending';

        if ($order->is_paid() || in_array($status, array('processing', 'completed'))) {
            $response_status = 'paid';
        } elseif (in_array($status, array('cancelled', 'failed', 'refunded'))) {
            $response_status = 'expired'; // Or a more specific status
        }

        return new WP_REST_Response(array('status' => $response_status), 200);
    }

    public function check_requirements() {
        if ($this->enabled === 'no') {
            return;
        }

        if (get_woocommerce_currency() !== 'BRL') {
            echo '<div class="error"><p>' . __('AobaPay: A moeda da sua loja precisa ser BRL para que o gateway funcione.', 'aobapay-woocommerce') . '</p></div>';
        }

        if (empty($this->api_key)) {
            echo '<div class="error"><p>' . __('AobaPay: Sua chave de API não foi informada.', 'aobapay-woocommerce') . '</p></div>';
        }
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Habilitar/Desabilitar', 'aobapay-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Habilitar AobaPay PIX', 'aobapay-woocommerce'),
                'default' => 'yes',
                'description' => __('Habilita ou desabilita o AobaPay como uma opção de pagamento no checkout.', 'aobapay-woocommerce'),
                'desc_tip' => true,
            ),
            'title' => array(
                'title' => __('Título', 'aobapay-woocommerce'),
                'type' => 'text',
                'default' => __('PIX Rápido e Seguro', 'aobapay-woocommerce'),
                'description' => __('Título que o cliente verá na tela de checkout.', 'aobapay-woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Descrição', 'aobapay-woocommerce'),
                'type' => 'textarea',
                'default' => __('Pague com PIX de forma rápida e segura através da AobaPay.', 'aobapay-woocommerce'),
                'description' => __('Descrição que o cliente verá ao selecionar o método de pagamento.', 'aobapay-woocommerce'),
                'desc_tip' => true,
            ),
            'api_key' => array(
                'title' => __('AobaPay API Key', 'aobapay-woocommerce'),
                'type' => 'password',
                'description' => __('Sua chave de API (secret key) para se conectar com a AobaPay.', 'aobapay-woocommerce'),
                'desc_tip' => true,
            ),
            'webhook_secret' => array(
                'title' => __('Webhook Secret', 'aobapay-woocommerce'),
                'type' => 'text',
                'default' => uniqid('aobapay_'),
                'description' => __('Uma chave secreta para validar os webhooks recebidos da AobaPay, garantindo a segurança.', 'aobapay-woocommerce'),
                'desc_tip' => true,
            ),
            // 'show_qr_in_modal' => array(
            //     'title' => __('Design da Página de Pagamento', 'aobapay-woocommerce'),
            //     'type' => 'checkbox',
            //     'label' => __('Ativar design moderno na página de pagamento', 'aobapay-woocommerce'),
            //     'default' => 'yes',
            //     'description' => __('Quando ativado, exibe uma interface aprimorada e amigável na página de agradecimento, com QR Code e instruções claras.', 'aobapay-woocommerce'),
            //     'desc_tip' => true,
            // ),
        );
    }

    public function is_available() {
        if ('yes' !== $this->get_option('enabled')) {
            $this->logger->info('Gateway disabled in settings.', $this->log_context);
            return false;
        }

        if (empty($this->api_key)) {
            $this->logger->info('API key is empty.', $this->log_context);
            return false;
        }

        if (get_woocommerce_currency() !== 'BRL') {
            $this->logger->info('Store currency is not BRL.', $this->log_context);
            return false;
        }

        return true;
    }

    public function admin_options() {
        echo '<h2>' . esc_html($this->get_method_title()) . '</h2>';
        echo wpautop($this->get_method_description());
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';

        echo '<h3>' . esc_html__('Webhook Endpoint', 'aobapay-woocommerce') . '</h3>';
        echo '<p>' . esc_html__('Copie esta URL e cole no dashboard da AobaPay (use HTTPS).', 'aobapay-woocommerce') . '</p>';
        echo '<p><input type="text" readonly="readonly" onfocus="this.select();" style="width:100%;padding:6px;border:1px solid #ddd" value="' . esc_attr($this->webhook_url) . '"></p>';
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        if (! $order) {
            wc_add_notice(__('Pedido inválido', 'aobapay-woocommerce'), 'error');
            return array('result' => 'failure');
        }

        $amount_cents = intval(round($order->get_total() * 100));
        $external_id = 'wc_order_' . $order_id;

        $payload = array(
            'amount' => $amount_cents,
            'externalID' => $external_id,
            'expiration' => 3600, // 1 hour
            'comment' => 'Pedido #' . $order_id,
            'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'email' => $order->get_billing_email(),
            'phone' => preg_replace('/\D+/', '', $order->get_billing_phone() ?: ''),
            'document' => preg_replace('/\D+/', '', $order->get_meta('_billing_cpf') ?: $order->get_meta('_billing_cnpj') ?: ''),
        );

        $api_key = $this->api_key;
        if (! $api_key) {
            $this->logger->error('API key missing in process_payment', $this->log_context);
            wc_add_notice(__('Erro interno do gateway', 'aobapay-woocommerce'), 'error');
            return array('result' => 'failure');
        }

        $response = wp_remote_post('https://api.aobapay.com/v1/charge/pix/create', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
            'timeout' => 20,
        ));

        if (is_wp_error($response)) {
            $this->logger->error('HTTP error creating charge: ' . $response->get_error_message(), $this->log_context);
            wc_add_notice(__('Erro na comunicação com AobaPay', 'aobapay-woocommerce'), 'error');
            return array('result' => 'failure');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (! $body || empty($body['data']) || empty($body['data']['id'])) {
            $this->logger->error('Invalid response creating charge: ' . wp_remote_retrieve_body($response), $this->log_context);
            wc_add_notice(__('Resposta inválida do gateway', 'aobapay-woocommerce'), 'error');
            return array('result' => 'failure');
        }

        $charge = $body['data'];
        $order->update_meta_data('_aobapay_charge_id', sanitize_text_field($charge['id']));
        if (! empty($charge['brCode'])) $order->update_meta_data('_aobapay_br_code', sanitize_text_field($charge['brCode']));
        if (! empty($charge['brCodeImageUrl'])) $order->update_meta_data('_aobapay_br_code_image', esc_url_raw($charge['brCodeImageUrl']));
        if (! empty($charge['expiresAt'])) $order->update_meta_data('_aobapay_expires_at', sanitize_text_field($charge['expiresAt']));
        $order->save();

        $order->update_status('on-hold', __('Aguardando pagamento via AobaPay PIX', 'aobapay-woocommerce'));
        WC()->cart->empty_cart();

        return array('result' => 'success', 'redirect' => $this->get_return_url($order));
    }

    public static function handle_webhook($request) {
        $headers = $request->get_headers();
        $body = json_decode($request->get_body(), true);

        $settings = get_option('woocommerce_aobapay_pix_settings', array());
        $secret = isset($settings['webhook_secret']) ? $settings['webhook_secret'] : null;

        $incoming_sig = null;
        if (isset($headers['signature'][0])) $incoming_sig = $headers['signature'][0];

        if ($secret && $incoming_sig !== $secret) {
            return new WP_REST_Response(array('error' => 'invalid signature'), 401);
        }

        if (! $body || ! isset($body['event'])) {
            return new WP_REST_Response(array('error' => 'invalid payload'), 400);
        }

        $event = $body['event'];
        $data = isset($body['data']) ? $body['data'] : array();
        $event_id = isset($body['id']) ? $body['id'] : (isset($data['id']) ? $data['id'] : null);

        $external = isset($data['externalID']) ? $data['externalID'] : null;
        $order_id = null;
        if ($external && preg_match('/wc_order_(\d+)/', $external, $m)) {
            $order_id = intval($m[1]);
        }

        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                if ($event_id && get_post_meta($order_id, '_aobapay_handled_event_' . $event_id, true)) {
                    return new WP_REST_Response(array('received' => true), 200);
                }
                if ($event_id) update_post_meta($order_id, '_aobapay_handled_event_' . $event_id, time());

                if ($event === 'EVENT:CHARGE_PAID' || (isset($data['status']) && strtoupper($data['status']) === 'PAID')) {
                    if (! in_array($order->get_status(), array('processing','completed'))) {
                        $order->payment_complete(isset($data['id']) ? $data['id'] : '');
                        $order->add_order_note('Pagamento confirmado pela AobaPay. Dados: ' . wp_json_encode($data));
                    }
                } elseif ($event === 'EVENT:CHARGE_EXPIRED') {
                    $order->update_status('failed', 'Cobrança expirada na AobaPay.');
                    $order->add_order_note('Cobrança expirada (AobaPay).');
                } elseif ($event === 'EVENT:CHARGE_REFUND') {
                    $order->add_order_note('Evento de reembolso recebido da AobaPay. Dados: ' . wp_json_encode($data));
                } else {
                    $order->add_order_note('Webhook AobaPay recebido: ' . $event . ' - ' . wp_json_encode($data));
                }
            }
        } else {
            // write raw webhook to plugin log file for debug
            $log_dir = dirname(__FILE__) . '/logs';
            if (! file_exists($log_dir)) mkdir($log_dir, 0755, true);
            $log_file = $log_dir . '/aobapay.log';
            $entry = date('c') . ' - webhook_no_order - ' . wp_json_encode($body) . "\n";
            file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
            $logger = wc_get_logger();
            $logger->info('AobaPay webhook sem ordem (saved to log file)', array('source' => 'aobapay-woocommerce'));
        }

        return new WP_REST_Response(array('received' => true), 200);
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        if (! $order) return new WP_Error('invalid_order', 'Pedido inválido');

        $charge_id = $order->get_meta('_aobapay_charge_id');
        if (! $charge_id) {
            return new WP_Error('no_charge', 'Cobrança AobaPay não encontrada no pedido.');
        }

        $api_key = $this->api_key;
        if (! $api_key) {
            return new WP_Error('no_api_key', 'API Key AobaPay não configurada.');
        }

        // placeholder endpoint for refund - adjust if docs specify another path
        $refund_endpoint = 'https://api.aobapay.com/v1/charge/' . rawurlencode($charge_id) . '/refund';

        $payload = array(
            'amount' => intval(round(($amount === null ? $order->get_total() : $amount) * 100)),
            'reason' => $reason,
        );

        $response = wp_remote_post($refund_endpoint, array(
            'headers' => array('Authorization' => 'Bearer ' . $api_key, 'Content-Type' => 'application/json'),
            'body' => wp_json_encode($payload),
            'timeout' => 20,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('http_error', $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($body === null) {
            return new WP_Error('invalid_response', 'Resposta inválida do servidor AobaPay');
        }

        if (isset($body['error']) || isset($body['errors'])) {
            return new WP_Error('aobapay_error', wp_json_encode($body));
        }

        $order->add_order_note('Solicitado reembolso na AobaPay. Resposta: ' . wp_json_encode($body));
        return true;
    }

    public function admin_notice_missing_requirements() {
        $screen = get_current_screen();
        if (! $screen) return;
        if ($screen->id !== 'woocommerce_page_wc-settings') return;

        if ('yes' === $this->get_option('enabled') && empty($this->api_key)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('AobaPay: API Key não configurada. O método ficará indisponível no checkout.', 'aobapay-woocommerce') . '</p></div>';
        }
    }
}
