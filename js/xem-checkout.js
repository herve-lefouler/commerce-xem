(function ($, Drupal, drupalSettings) {
    "use strict";

    Drupal.behaviors.xem_qrcode = {
        attach: function (context) {
            $(function () {
                var xemPayment = {
                    init: function () {
                        
                    },
                    checkXemTransaction: function () {
                        //this.nanobar.go(25);
                            $.ajax({
                                url: drupalSettings.xem.notifyUrl,
                                type: 'post',
                                data: {
                                    message: drupalSettings.xem.message,
                                    orderId: drupalSettings.xem.orderId
                                }
                            }).done(function (result) {
                                // $('#xem-check').html('<p id="xem-check">Checking..</p>');
                                console.log(result.match);
                                // If a Xem transaction has been found
                                if(result.match === true) { 
                                    location.reload();
                                }
                                setTimeout(function() {
                                    xemPayment.checkXemTransaction();
                                }, 5000);
                            });
                        //this.nanobar.go(100);
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