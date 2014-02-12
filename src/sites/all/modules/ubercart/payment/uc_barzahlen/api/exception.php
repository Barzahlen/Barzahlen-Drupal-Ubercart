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

class Barzahlen_Exception extends Exception {

  /**
   * Constructor to create exception, uses parent function.
   */
  public function __construct($message, $code = 0) {

    parent::__construct($message, $code);
  }

  /**
   * Output exception.
   *
   * @return string with error code and message
   */
  public function __toString() {

    return __CLASS__ . ": [{$this->code}] - {$this->message}\n";
  }
}
?>