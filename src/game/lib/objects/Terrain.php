<?php
/*
 * Terrain.php - TODO
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('lib/objects/GameObject.php');

/**
 * TODO
 *
 * @package    lib
 * @subpackage objects
 *
 * @author Marcus Lunzenauer
 */
class Terrain extends GameObject {

  public function getColor() {
    return ua_terrain_color($this->id);
  }

  public function getEffects() {
    return ua_terrain_effects($this->id);
  }

  public function getTakeoverByCombat() {
    return ua_terrain_takeoverbycombat($this->getType(), $this->id);
  }

  protected function getType() {
    return UA_TERRAIN;
  }

  public function isBarren() {
    return (boolean) ua_terrain_barren($this->getType(), $this->id);
  }
}

?>