(function () {
  var data = window.wc.wcSettings.getSetting('aobapay_pix_data', {});
  var label = window.wp.htmlEntities.decodeEntities(data.title) || window.wp.i18n.__('AobaPay PIX', 'aobapay-woocommerce');

  var content = function (data) {
    return window.wp.htmlEntities.decodeEntities(data.description || '');
  };

  window.wc.wcBlocksRegistry.registerPaymentMethod({
    name: 'aobapay_pix',
    label: label,
    content: Object(window.wp.element.createElement)(content, { description: data.description }),
    edit: Object(window.wp.element.createElement)(content, { description: data.description }),
    canMakePayment: function () {
      return true;
    },
    ariaLabel: label,
    supports: {
      features: data.supports,
    },
  });
})();