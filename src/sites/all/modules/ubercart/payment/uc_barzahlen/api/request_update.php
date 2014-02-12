<?php
/**
 * Barzahlen Payment Module SDK (Ubercart)
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

class Barzahlen_Request_Update extends Barzahlen_Request_Base {

  protected $_type = 'update'; //!< request type
  protected $_transactionId; //!< origin transaction id
  protected $_orderId; //!< order id

  protected $_xmlAttributes = array('transaction-id', 'result', 'hash'); //!< update xml content

  /**
   * Construtor to set variable request settings.
   *
   * @param string $transactionId origin transaction id
   * @param string $orderId order id
   */
  public function __construct($transactionId, $orderId) {

    $this->_transactionId = $transactionId;
    $this->_orderId = $orderId;
  }

  /**
   * Builds array for request.
   *
   * @param string $shopId merchants shop id
   * @param string $paymentKey merchants payment key
   * @param string $language langauge code (ISO 639-1)
   * @param array $customVar custom variables from merchant
   * @return array for update request
   */
  public function buildRequestArray($shopId, $paymentKey, $language) {

    $requestArray = array();
    $requestArray['shop_id'] = $shopId;
    $requestArray['transaction_id'] = $this->_transactionId;
    $requestArray['order_id'] = $this->_orderId;
    $requestArray['hash'] = $this->_createHash($requestArray, $paymentKey);

    $this->_removeEmptyValues($requestArray);
    return $requestArray;
  }

  /**
   * Returns transaction id from xml array.
   *
   * @return received transaction id
   */
  public function getTransactionId() {
    return $this->getXmlArray('transaction-id');
  }
}
?>