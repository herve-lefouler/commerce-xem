<?php

namespace Drupal\xem;

use Drupal\xem\php2nem\NEM;

class NemApi {
  
  private static $instance;
  
  private $isTest;
  
  private static $servers = [
		'bigalice3.nem.ninja',
		'alice2.nem.ninja',
		'go.nem.ninja'
	];

	private static $testservers = [
		'bob.nem.ninja',
		'104.128.226.60',
		'192.3.61.243'
	];
  
  private $nem;
  
  static function getServers() {
    return self::$servers;
  }
  
  function getNem() {
    return $this->nem;
  }

  public static function getInstance($isTest = FALSE) {
		if ( null === self::$instance ) {
			self::$instance = new self();

      if ($isTest) {
        self::$servers = self::$testservers;
      }
		}
		return self::$instance;
	}
  
  private function _nemSend($path) {
    foreach ($this->getServers() as $server){
      $conf = [
        'nis_address' => $server
      ];
      self::$instance->nem = new Nem($conf);
      $content = self::$instance->nem->nis_get($path);
      if (!empty($content)) {
        break;
      }
    }
    return $content;
  }
  
  /**
   * Get account info
   * 
   * @param type $address
   * @return type
   */
  public function getAccountInfo($address) {
    $path = 'account/get?address=' . $address;
    return self::$instance->_nemSend($path);
  }
  
  
  
}
