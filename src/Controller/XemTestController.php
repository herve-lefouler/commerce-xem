<?php

namespace Drupal\xem\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\xem\php2nem\NEM;
use Drupal\xem\NemApi;

/**
 * 
 */
class XemTestController extends ControllerBase {
  
  /**
   * Connection test to Xem blockchain. 
   */
  public function connectionTest() {
    $nemApi = NemApi::getInstance(TRUE);
    // Adresse publique de test
    $address = 'TDQFAWGFUJ3VBCSCPM75YCTD4HLPTGOUPW2JUF7S';
    $opt = $nemApi->getAccountInfo($address);
    print_r($opt);
  }
  
  /**
   * QR Code test
   */
  public function qrCodeTest() {
    return [
      '#markup' => '<div id="qr-code-test"></div>',
      '#attached' => [
        'library' => ['xem/xem-qrcode']
      ]
    ];
  }
}