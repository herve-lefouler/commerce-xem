<?php

namespace Drupal\commerce_xem\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\commerce_xem\NemApi;
use Drupal\Core\Url;

/**
 * Provides Xem QR Code payment method.
 * 
 * @todo
 *  Passer le VRAI montant, vérifier le montant
 *  A voir : affichage du prix en XEM
 *  Prévoir classe Helper dédiée
 *
 * @CommercePaymentGateway(
 *   id = "qrcode_xem_payment_method",
 *   label = "QRCode Xem payment method",
 *   display_label = "QRCode Xem payment method",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_xem\PluginForm\Xem\XemQRCodePaymentForm",
 *   }
 * )
 */
class XemOffSite extends OffsitePaymentGatewayBase implements SupportsRefundsInterface{

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['xem_public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('XEM public key'),
      '#default_value' => (!empty($this->configuration['xem_public_key'])) ? 
        $this->configuration['xem_public_key'] : '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   * 
   * @todo Valider la public key en effectuant une requête au WS. 
   *  - prévoir cas MainNet et TestNet
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['xem_public_key'] = $values['xem_public_key'];
    }
  }
  
  /**
   * Return the xem public key
   * 
   * @return string
   */
  public function getXemPublicKey() {
    return $this->configuration['xem_public_key'];
  }

  /**
   * {@inheritdoc}
   * 
   * @todo
   *  Remboursement possible via une adresse XEM
   *  Cf back Office [admin/commerce/orders/8/payments/4/operation/refund]
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    /*$this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    // Validate the requested amount.
    $this->assertRefundAmount($payment, $amount);

    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }

    $gateway = $this->gateway_lib;

    if (!$gateway->getMerchant()->get('cert_path') || !$gateway->getMerchant()->get('key_path')) {
      throw new \InvalidArgumentException($this->t('Could not load the apiclient_cert.pem or apiclient_key.pem files, which are required for WeChat Refund. Did you configure them?'));
    }

    $result = $gateway->refund($payment->getOrderId(), $payment->getOrderId() . date("zHis"), floatval($payment->getAmount()) * 100, floatval($amount->getNumber()) * 100);

    if (!$result->return_code == 'SUCCESS' || !$result->result_code == 'SUCCESS'){
      // For any reason, we cannot get a preorder made by WeChat service
      throw new InvalidRequestException($this->t('WeChat Service cannot approve this request: ') . $result->err_code_des);
    }

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();*/
  }
  
  /**
   * Create a payment entity. 
   */
  private function createPayment($paymentParams = [], $xemTransaction) {
    $query = \Drupal::entityQuery('commerce_payment')
      ->condition('order_id', $paymentParams['orderId'])
      ->addTag('commerce_xem:check_payment');
    $paymentId = $query->execute();
    
    if (empty($paymentId)) {
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      
      $xemAmount = $xemTransaction->transaction->amount / 1000000;
      $requestTime = \Drupal::time()->getRequestTime();
      // Create a new payment entity
      $payment = $payment_storage->create([
        'state' => $paymentParams['state'],
        'amount' => $paymentParams['amount'] ? $paymentParams['amount'] : new Price(strval($xemAmount), 'XEM'),
        'payment_gateway' => $this->entityId,
        'order_id' => $paymentParams['orderId'],
        'test' => $this->getMode() == 'test',
        'remote_id' => $xemTransaction->meta->hash->data,
        'remote_state' => (!empty($paymentParams['remote_state'])) ? $paymentParams['remote_state'] : 'completed',
        'authorized' => $requestTime
      ]);
      // Save the payment entity
      $payment->save();
    }
  }
  
  /**
   * 
   * @param type $order
   */
  private function setOrderState($order, $state = 'place') {
    $orderState = $order->getState();
    $orderStateTransitions = $orderState->getTransitions();
    $orderState->applyTransition($orderStateTransitions[$state]);
    $order->set('state', $orderState->value);
    // Save order state
    $order->save();
  }

  /**
   * {@inheritdoc}
   * 
   * @todo
   *  Vérifier le montant
   */
  public function onNotify(Request $request) {
    $message = $request->get('message');
    $orderId = $request->get('orderId');
    $order = \Drupal::entityTypeManager()->getStorage('commerce_order')->load($orderId);

    // \Drupal::logger('xem')->notice('User message : %message', ['%message' => $message]);
    
    $config = $this->getConfiguration();
    
    // Check the message on last XEM transactions
    $isTest = ($config['mode'] == 'test');
    $nemApi = NemApi::getInstance($isTest);
    $transactions = $nemApi->getLatestTransactions($config['xem_public_key']);
    
    $data = [
      'match' => FALSE
    ];
    $transactionsDecoded = json_decode($transactions);
    foreach($transactionsDecoded->data as $transaction) {
      // Decode transaction message
      $transactionMessage = self::hex2str($transaction->transaction->message->payload);
      if ($transactionMessage == $message) { // If we find the current order message
        // Create a new payment entity
        $payment = $this->createPayment([
          'orderId' => $orderId,
          'state' => 'completed'
        ], $transaction);
        
        \Drupal::logger('xem')->notice('SAVED ORDER : User message : %message', ['%message' => $message]);
        // @todo
        // Vérifier que la totalité du montant est réglé
        
        // Save order state to completed
        $this->setOrderState($order, 'place');
        $order->save();
        
        // Return response for JS
        $data = [
          'match' => TRUE
        ];
        return new JsonResponse($data);
        break;
      }
    }
    return new JsonResponse($data);
  }
  
  /**
   * Convert hexadecimal string to regular string
   * 
   * @param string $hex
   * @return string $str
   */
  private function hex2str($hex) {
    $str = '';
    for ($i = 0; $i < strlen($hex); $i += 2) {
      $str .= chr(hexdec(substr($hex, $i, 2)));
    }
    return $str;
  }
  
  /**
   * Create a unique Xem message to identify the transaction
   * 
   * @param $order
   * @param string $host
   * @param string $mode
   */
  public function getXemUniqueMessage($order, $mode) {
    $str = '';
    $str .= $order->uuid();
    $str .= ' ' . $mode;
    $str .= ' ' . \Drupal::request()->getHost();
    return hash_hmac('md5', $str, \Drupal\Core\Site\Settings::getHashSalt());
  }
}
