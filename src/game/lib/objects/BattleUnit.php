<?php
/*
 * BattleUnit.php - TODO
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('lib/objects/Expansion.php');

/**
 * TODO
 *
 * @package    lib
 * @subpackage objects
 *
 * @author Marcus Lunzenauer
 */
abstract class BattleUnit extends Expansion {

  public function getStructureDamage() {
    return ua_battle_attackareal($this->getType(), $this->id);
  }

  public function getMeleeDamage() {
    return ua_battle_attackrate($this->getType(), $this->id);
  }

  public function getMeleeAC() {
    return ua_battle_defenserate($this->getType(), $this->id);
  }

  public function getSize() {
    return ua_battle_hitpoints($this->getType(), $this->id);
  }

  public function getRangedAC() {
    return ua_battle_rangeddamageresistance($this->getType(), $this->id);
  }

  public function getAntiSpyChance() {
    return ua_battle_antispychance($this->getType(), $this->id);
  }
}

?>