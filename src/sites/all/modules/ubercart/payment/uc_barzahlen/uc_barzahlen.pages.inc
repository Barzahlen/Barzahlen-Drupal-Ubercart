<?php
/**
 * Barzahlen Payment Module (Ubercart)
 *
 * NOTICE OF LICENSE
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @copyright   Copyright (c) 2012 Zerebro Internet GmbH (http://www.barzahlen.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

require_once 'api/loader.php';

define('BZ_STATE_PENDING', 'pending');
define('BZ_STATE_PAID', 'paid');
define('BZ_STATE_EXPIRED', 'expired');

/**
 * Prepares final checkout success page with Barzahlen information.
 *
 * @global user object $user current user
 * @return array
 */
function uc_barzahlen_complete() {
  global $user;

  $orderId = variable_get('barzahlen_order_id', null);
  variable_del('barzahlen_order_id');
  $infotext1 = variable_get('barzahlen_infotext1');
  variable_del('barzahlen_infotext1');

  if(!$orderId) {
    drupal_goto('cart/checkout');
  }

  $order = uc_order_load(intval($orderId));
  $login = variable_get('uc_new_customer_login', FALSE);

  $type = $order->data['complete_sale'];

  // Log in new users, if requested.
  if ($type == 'new_user' && $login && !$user->uid) {
    $type = 'new_user_logged_in';
    $user = user_load($order->uid);
  }

  $variables['!new_username'] = isset($order->data['new_user']['name']) ? $order->data['new_user']['name'] : '';
  $variables['!new_password'] = isset($order->password) ? $order->password : t('Your password');
  $messages = array(
    'uc_msg_order_submit' => uc_get_message('completion_message'),
    'uc_msg_order_' . $type => uc_get_message('completion_' . $type),
    'uc_msg_continue_shopping' => uc_get_message('continue_shopping'),
  );
  foreach ($messages as $id => &$message) {
    $message = variable_get($id, $message);
    $message = token_replace($message, array('uc_order' => $order));
    if ($id == 'uc_msg_order_' . $type) {
      $message = strtr($message, $variables);
    }
  }
  $output = filter_xss_admin(implode(' ', $messages));

  uc_order_comment_save($order->order_id, 0, t('Payment slip requested and sent successfully.'), 'admin');

  return array(
    '#theme' => 'uc_cart_complete_sale',
    '#message' => $output . '<br><br>' . $infotext1
  );
}

/**
 * Instant payment notification handler.
 *
 * @return null
 */
function uc_barzahlen_ipn() {

  $shopId = variable_get('uc_barzahlen_shopid');
  $notificationKey = variable_get('uc_barzahlen_notificationkey');
  $notification = new Barzahlen_Notification($shopId, $notificationKey, $_GET);

  try {
    $notification->validate();
  }
  catch(Exception $e) {
    watchdog('uc_barzahlen', $e, $_GET, WATCHDOG_ERROR);
  }

  if($notification->isValid()) {
    header("HTTP/1.1 200 OK");
    header("Status: 200 OK");

    $order = uc_order_load($notification->getOrderId());
    if(!_uc_barzahlen_check_values($order, $notification)) {
      return;
    }

    switch($notification->getState()) {
      case 'paid':
        uc_payment_enter($notification->getOrderId(), 'barzahlen', $notification->getAmount(), $order->uid, NULL, t('The payment slip was paid successfully.'));
        uc_cart_complete_sale($order);
        uc_order_comment_save($order->order_id, 0, t('The payment slip was paid successfully.'), 'admin');
        _uc_barzahlen_update_transaction($notification->getTransactionId(), BZ_STATE_PAID);
        break;
      case 'expired':
        uc_order_update_status($order->order_id, 'canceled');
        uc_order_comment_save($order->order_id, 0, t('The time frame for the payment slip expired.'), 'admin');
        _uc_barzahlen_update_transaction($notification->getTransactionId(), BZ_STATE_EXPIRED);
        break;
      default:
        watchdog('uc_barzahlen', 'IPN: unable to handle given state', null, WATCHDOG_ERROR);
        break;
    }
  }
  else {
    header("HTTP/1.1 400 Bad Request");
    header("Status: 400 Bad Request");
  }
}

/**
 * Compares order and notification values.
 *
 * @param uc_order $order requested order
 * @param Barzahlen_Notification $notification current notification object
 * @return boolean
 */
function _uc_barzahlen_check_values($order, $notification) {

  $query = db_select('uc_payment_barzahlen_transactions', 'bzt')
    ->fields('bzt')
    ->condition('transaction_id', $notification->getTransactionId());
  $transaction = $query->execute()->fetchObject();

  if($transaction->transaction_state != BZ_STATE_PENDING) {
    watchdog('uc_barzahlen', 'IPN: unable to change transaction state', null, WATCHDOG_ERROR);
    return false;
  }

  if($transaction->order_id != $order->order_id) {
    watchdog('uc_barzahlen', 'IPN: unable to match transaction and order', null, WATCHDOG_ERROR);
    return false;
  }

  if(round($order->order_total, 2) != round($notification->getAmount(), 2)) {
    watchdog('uc_barzahlen', 'IPN: amount didn\'t match', null, WATCHDOG_ERROR);
    return false;
  }

  if($order->currency != $notification->getCurrency()) {
    watchdog('uc_barzahlen', 'IPN: currency didn\'t match', null, WATCHDOG_ERROR);
    return false;
  }

  return true;
}

/**
 * Updates the transaction table.
 *
 * @param integer $transactionId transaction ID
 * @param string $state new transaction state
 */
function _uc_barzahlen_update_transaction($transactionId, $state) {

  db_update('uc_payment_barzahlen_transactions')
    ->fields(array('transaction_state' => $state))
    ->condition('transaction_id', $transactionId)
    ->execute();
}