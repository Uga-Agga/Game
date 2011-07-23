<?php
/*
 * Movement.php - TODO
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
class Movement extends GameObject {

  public function getAction() {
    return ua_movement_action($this->getType(), $this->id);
  }

  public function getProvisions() {
    return ua_movement_provisions($this->getType(), $this->id);
  }

  public function getRequirements() {
    return ua_movement_requirements($this->id);
  }

  public function getSpeed() {
    return ua_movement_speed($this->getType(), $this->id);
  }

  protected function getType() {
    return UA_MOVEMENT;
  }

  public function isConquering() {
    return (boolean) ua_movement_conquering($this->getType(), $this->id);
  }

  public function isInvisible() {
    return (boolean) ua_movement_invisible($this->getType(), $this->id);
  }

  public function requirementsFulfilled(Cave $cave) {

    // fetch requirements
    $reqs = $this->getRequirements();

    // iterate reqs
    foreach ($reqs as $req) {

      // fetch dbfield
      $dbfield = ua_object_dbfieldname($req->type, $req->id);

      // minimum
      if ($cave->$dbfield < $rqmt->minimum) return FALSE;

      // maximum
      if ($cave->$dbfield > $rqmt->maximum) return FALSE;
    }

    return TRUE;
  }
}

?>