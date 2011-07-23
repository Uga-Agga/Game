<?php
/*
 * Region.php - TODO
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
class Region extends GameObject {

  public function getEffects() {
    return ua_region_effects($this->id);
  }

  protected function getType() {
    return UA_REGION;
  }

  public function isBarren() {
    return (boolean) ua_region_barren($this->getType(), $this->id);
  }

  public function isStartRegion() {
    return (boolean) ua_region_startregion($this->getType(), $this->id);
  }

  public function isTakeoverActivatable() {
    return (boolean) ua_region_takeoveractivatable($this->getType(), $this->id);
  }
}

?>