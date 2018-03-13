(function ($, Drupal, drupalSettings) {
    "use strict";

    Drupal.behaviors.xem_qrcode = {
        attach: function (context) {
            $(function () {
                var xemPayment = {
                    init: function () {
                        
                    },
                    checkXemTransaction: function () {
                            $.ajax({
                                url: drupalSettings.xem.notifyUrl,
                                type: 'post',
                                data: {
                                    message: drupalSettings.xem.message,
                                    orderId: drupalSettings.xem.orderId
                                }
                            }).done(function (result) {
                                console.log(result);
                                // If a Xem transaction has been found and validated
                                if(result.match === true) { 
                                    location.reload();
                                }
                                setTimeout(function() {
                                    xemPayment.checkXemTransaction();
                                }, 5000);
                            });
                    },
                };
                xemPayment.init();
                setTimeout(function() {
                    xemPayment.checkXemTransaction();
                }, 5000);
            });
        }
    }
})(jQuery, Drupal, drupalSettings);