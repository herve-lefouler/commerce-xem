<?php


namespace Drupal\commerce_xem;

use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;

class XemCurrency {
  
  const COIN_MARKET_CAP_URL = 'https://api.coinmarketcap.com/v1/ticker/nem';
  
  
  /**
   * Get current Xem Data from Coin Market Cap
   * 
   * @param Price $price
   * @return boolean
   */
  public static function getCurrentXemData($order, $save = FALSE) {
    $xemData = $order->getData('xemData');
    if (empty($xemData)) {
      $price = $order->getTotalPrice();
      $currencyCode = $price->getCurrencyCode();
      $client = \Drupal::httpClient();
      $response =  
      $client->request('GET', XemCurrency::COIN_MARKET_CAP_URL, [
        'query' => [
          'convert' => $currencyCode
        ]
      ]);

      if (!$response || $response->getStatusCode() != 200) {
        return FALSE;
      }

      if (empty($response->getBody())) {
        return FALSE;
      }

      $json = $response->getBody()->getContents();
      $jsonDecoded = Json::decode($json);
      $xemData = reset($jsonDecoded);
      if ($save) {
        $order->setData('xemData', $xemData);
        $order->save();
      }
    }
    return $xemData;
  }
  
  /**
   * Convert a Drupal Commerce Price to a Xem price
   * 
   * @param Price $price
   * @return float Xem price
   */
  public static function convertToXem($order, $save = FALSE) {
    $xemData = XemCurrency::getCurrentXemData($order, $save);
    $currencyCode = $order->getTotalPrice()->getCurrencyCode();
    $xemPrice = $order->getTotalPrice()->getNumber() / $xemData['price_' . strtolower($currencyCode)];
    return round($xemPrice, 2);
  }
  
  
}