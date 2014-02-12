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

abstract class Barzahlen_Base {

  const APIDOMAIN = 'https://api.barzahlen.de/v1/transactions/'; //!< call domain (productive use)
  const APIDOMAINSANDBOX = 'https://api-sandbox.barzahlen.de/v1/transactions/'; //!< sandbox call domain

  const HASHALGO = 'sha512'; //!< hash algorithm
  const SEPARATOR = ';'; //!< separator character
  const MAXATTEMPTS = 2; //!< maximum of allowed connection attempts

  protected $_debug = false; //!< debug mode on / off

  /**
   * Sets debug settings. Adjusted for Drupal Ubercart.
   *
   * @param boolean $debug debug mode on / off
   * @param string $logFile position of log file
   */
  public function setDebug($debug) {
    $this->_debug = $debug;
  }

  /**
   * Write debug message to log file. Adjusted for Drupal Ubercart.
   *
   * @param string $message debug message
   * @param array $data related data (optional)
   */
  protected function _debug($message, $data = array()) {

    if($this->_debug == true) {
      $message .= $data != array() ? " | " . serialize($data) : "";
      watchdog('uc_barzahlen', $message, null, WATCHDOG_DEBUG);
    }
  }

  /**
   * Generates the hash for the request array.
   *
   * @param array $requestArray array from which hash is requested
   * @param string $key private key depending on hash type
   * @return hash sum
   */
  protected function _createHash(array $hashArray, $key) {

    $hashArray[] = $key;
    $hashString = implode(self::SEPARATOR, $hashArray);
    return hash(self::HASHALGO, $hashString);
  }

  /**
   * Removes empty values from arrays.
   *
   * @param array $array array with (empty) values
   */
  protected function _removeEmptyValues(array &$array) {

    foreach($array as $key => $value) {
      if($value == '') {
        unset($array[$key]);
      }
    }
  }

  /**
   * Converts ISO-8859-1 strings to UTF-8 if necessary.
   *
   * @param string $string text which is to check
   * @return string with utf-8 encoding
   */
  public function isoConvert($string) {

    if(!preg_match('/\S/u', $string)) {
      $string = utf8_encode($string);
    }

    return $string;
  }
}
?>