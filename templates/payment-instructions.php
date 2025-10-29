<?php
if (!defined('ABSPATH')) exit;
?>
<div class="aobapay-container" id="aobapay-container" data-status-url="<?php echo esc_url($status_check_url); ?>" data-order-id="<?php echo esc_attr($order_id); ?>">
    <div class="aobapay-card">
        <div class="aobapay-header">
            <h2>🎉 Pague com PIX para finalizar</h2>
            <p>Escaneie o QR Code ou copie o código para pagar.</p>
        </div>

        <div class="aobapay-payment-area">
            <div class="aobapay-qr-code">
                <img src="<?php echo esc_url($qr_code_img_url); ?>" alt="PIX QR Code"/>
            </div>
            <div id="aobapay-status" class="aobapay-status-pending">Aguardando pagamento...</div>
            <div class="aobapay-copy-paste">
                <textarea readonly id="aobapay-brcode"><?php echo esc_textarea($br_code); ?></textarea>
                <button id="aobapay-copy-button">📋 Copiar Codigo Pix</button>
            </div>
        </div>

        <div style="margin-bottom: 35px; text-align: start; padding: 20px; border: 1px solid #f2f2f2; border-radius: 10px;">
            <div style="margin-bottom: 6px;">
                <p style="font-size: 14px; color: #6B7280;"><strong style="font-weight: 700">1.</strong> Entre no app ou site do seu banco e escolha a opção de pagamento via Pix.</p>
            </div>
            <div style="margin-bottom: 6px;">
                <p style="font-size: 14px; color: #6B7280;"><strong style="font-weight: 700">2.</strong> Escaneie o código QR ou copie e cole o código de pagamento.</p>
            </div>
            <div style="margin-bottom: 6px;">
                <p style="font-size: 14px; color: #6B7280;"><strong style="font-weight: 700">3.</strong> O pagamento será creditado e você receberá um e-mail de confirmação.</p>
            </div>
            <div style="margin-bottom: 6px;">
                <p style="font-size: 14px; color: #6B7280;"><strong style="font-weight: 700">4.</strong> Pronto! Só aproveitar seu produto</p>
            </div>
        </div>

        <div class="aobapay-footer" style="display: flex; align-items: center; justify-content: center; justify-items: center; gap: 3px">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"></path>
            </svg>
            Pagamento seguro via <a href="https://aobapay.com?utm_source=woocommerce">AobaPay</a> 
        </div>

        <img src="<?php echo esc_url(plugins_url('assets/logo-aoba_4.svg', AOBAPAY_PLUGIN_FILE)); ?>" width="60" alt="Logo" style="opacity: 30%; margin: 0 auto">
    </div>
</div>