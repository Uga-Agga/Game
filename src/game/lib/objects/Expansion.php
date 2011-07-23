<?php
/*
 * Expansion.php - TODO
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
abstract class Expansion extends GameObject {

  public function getCosts() {
    return ua_expansion_productioncost($this->getType(), $this->id);
  }

  public function getPosition() {
    return ua_expansion_position($this->getType(), $this->id);
  }

  public function getProductionTimeFunction() {
    return ua_expansion_productiontimefunction($this->getType(), $this->id);
  }

  public function getRating() {
    return ua_expansion_ratingvalue($this->getType(), $this->id);
  }

  public function getRequirements() {
    return ua_expansion_requirements($this->getType(), $this->id);
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