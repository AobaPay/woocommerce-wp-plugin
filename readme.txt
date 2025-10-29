AobaPay WooCommerce Gateway (v3 Refatorado)

Instalação rápida:
1. Envie a pasta 'aobapay-woocommerce-v3' para wp-content/plugins/.
2. Ative o plugin no admin do WordPress.
3. Vá em WooCommerce -> Configurações -> Pagamentos -> AobaPay PIX e preencha:
   - API Key (produção neste caso)
   - Webhook Secret (uma string que você configurará também no dashboard da AobaPay)
4. Copie a URL do webhook exibida e cole no dashboard da AobaPay: https://seusite.com/wp-json/aobapay/v1/webhook
5. Teste um pedido: finalize checkout (com moeda BRL e valor > 0) e confirme que o método aparece.

Notas:
- O endpoint de criação de cobrança usado é: POST https://api.aobapay.com/v1/charge/pix/create (ajuste caso sua conta precise de outro caminho).
- O endpoint de reembolso é um placeholder e pode precisar de ajuste conforme a AobaPay (alterar em process_refund()).
- Logs simples são gravados em wp-content/plugins/aobapay-woocommerce-v3/logs/aobapay.log e também renderizados em WooCommerce -> AobaPay Logs.

License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
