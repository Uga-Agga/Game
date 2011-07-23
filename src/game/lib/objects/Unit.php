<?php
/*
 * Unit.php - TODO
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('lib/objects/BattleUnit.php');

/**
 * TODO
 *
 * @package    lib
 * @subpackage objects
 *
 * @author Marcus Lunzenauer
 */
class Unit extends BattleUnit {

  public function isVisible() {
    return (boolean) ua_unit_visible($this->getType(), $this->id);
  }

  public function getProvisions() {
    return ua_unit_foodcost($this->getType(), $this->id);
  }

  public function getSpeed() {
    return ua_unit_waycost($this->getType(), $this->id);
  }

  public function getSpyValue() {
    return ua_unit_spyvalue($this->getType(), $this->id);
  }

  public function getSpyChance() {
    return ua_unit_spychance($this->getType(), $this->id);
  }

  public function getSpyQuality() {
    return ua_unit_spyquality($this->getType(), $this->id);
  }

  public function getLoad() {
    return ua_unit_encumbrancelist($this->id);
  }

  protected function getType() {
    return UA_UNIT;
  }
}

?>