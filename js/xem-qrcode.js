(function ($, Drupal, drupalSettings) {
    "use strict";

    Drupal.behaviors.xem_qrcode = {
        attach: function (context) {
            $(function () {
                
                var xemAddress = 'TDQFAWGFUJ3VBCSCPM75YCTD4HLPTGOUPW2JUF7S';
                var message = 'test message';
                this.paymentData = {
                    "v": 1, // Type d'environnement 1 : test, 2 : prod
                    "type": 2,
                    "data": {
                        "addr": xemAddress.toUpperCase().replace(/-/g, ''),
                        "amount": 1 * 1000000, // Envoie 1 XEM
                        "msg": message,
                        "name": "XEM payment to Drupal 8"
                    }
                };
                var qrcode = new QRCode('qr-code-test', {
                    text: JSON.stringify(this.paymentData),
                    width: 256,
                    height: 256,
                    colorDark : "#000000",
                    colorLight : "#FFFFFF",
                    correctLevel : QRCode.CorrectLevel.H
                });
            });
        }
    }
})(jQuery, Drupal, drupalSettings);