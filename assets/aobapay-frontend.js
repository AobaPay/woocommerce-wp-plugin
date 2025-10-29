document.addEventListener('DOMContentLoaded', function () {
    class AobaPayCheckout {
        constructor() {
            this.container = document.getElementById('aobapay-container');
            if (!this.container) return;

            this.copyButton = document.getElementById('aobapay-copy-button');
            this.brCodeTextarea = document.getElementById('aobapay-brcode');
            this.statusDiv = document.getElementById('aobapay-status');
            this.statusUrl = this.container.dataset.statusUrl;
            this.orderId = this.container.dataset.orderId;

            this.init();
        }

        init() {
            this.bindEvents();
            this.startStatusCheck();
        }

        bindEvents() {
            if (this.copyButton && this.brCodeTextarea) {
                this.copyButton.addEventListener('click', this.handleCopy.bind(this));
            }
        }

        handleCopy() {
            navigator.clipboard.writeText(this.brCodeTextarea.value).then(() => {
                const originalText = this.copyButton.textContent;
                this.copyButton.textContent = '✅ Copiado!';
                this.copyButton.classList.add('aobapay-copied');
                setTimeout(() => {
                    this.copyButton.textContent = originalText;
                    this.copyButton.classList.remove('aobapay-copied');
                }, 2000);
            }).catch(err => {
                console.error('Falha ao copiar o código: ', err);
                alert('Não foi possível copiar o código. Tente manualmente.');
            });
        }

        startStatusCheck() {
            if (this.statusUrl && this.statusDiv) {
                this.intervalId = setInterval(this.checkStatus.bind(this), 5000);
                this.checkStatus(); // Initial check
            }
        }

        checkStatus() {
            fetch(this.statusUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'paid') {
                        clearInterval(this.intervalId);
                        this.statusDiv.textContent = 'Pagamento Aprovado!';
                        this.statusDiv.classList.remove('aobapay-status-pending');
                        this.statusDiv.classList.add('aobapay-status-paid');
                        if (!window.location.href.includes('aobapay_status=paid')) {
                            window.location.href = window.location.href + '&aobapay_status=paid';
                        }
                    } else if (data.status === 'expired') {
                        clearInterval(this.intervalId);
                        this.statusDiv.textContent = 'PIX Expirado.';
                        this.statusDiv.classList.remove('aobapay-status-pending');
                        this.statusDiv.classList.add('aobapay-status-expired');
                    }
                })
                .catch(err => {
                    console.error('Error checking payment status:', err);
                });
        }
    }

    new AobaPayCheckout();
});