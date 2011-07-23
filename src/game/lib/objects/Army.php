<?php
/*
 * Army.php - TODO
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('include/formula_parser.inc.php');

/**
 * TODO
 *
 * @package    lib
 * @subpackage objects
 *
 * @author Marcus Lunzenauer
 */
class Army {

  protected $movement, $owner, $resources, $target, $units;

  public function __construct(Cave $owner) {

    // init attributes
    $this->movement = NULL;
    $this->resources = array();
    $this->target = NULL;
    $this->units = array();

    // add owner cave
    $this->owner = $owner;
  }

  public function adjustResources() {

    // get max
    $max = $this->getMaxLoad();

    // check each resource
    foreach ($this->resources as $id => $amount) {

      // if $max is empty at index $id, this army cannot carry resource $id
      if (!isset($max[$id]))
        unset($this->resources[$id]);

      else
        $this->resources[$id] = min($amount, $max[$id]);
    }
  }

  public function enoughProvisions() {
    $fuel = new Resource(ID_RESOURCE_FUEL);
    return $this->getProvisions() <= $fuel->getAmountInCave($this->owner);
  }

  public function getMaxLoad() {

    // prepare return value
    $retval = array();

    foreach ($this->units as $unitID => $unitAmount) {

      // check amount
      if ($unitAmount <= 0)
        continue;

      // get unit
      $unit = new Unit($unitID);

      // get load
      $load = $unit->getLoad();

      // add them
      foreach ($load as $resourceID => $resourceAmount) {

        if (isset($retval[$resourceID]))
          $retval[$resourceID] += $resourceAmount * $unitAmount;
        else
          $retval[$resourceID] = $resourceAmount * $unitAmount;
      }
    }

    return $retval;
  }

  public function getMovement() {
    return $this->movement;
  }

  public function getOwner() {
    return $this->owner;
  }

  public function getProvisions() {

    $retval = 0;

    // target and movement set?
    if (!is_null($this->target) && !is_null($this->movement)) {

      // Rationen = "Reisedauer"
      //            mal "Rationen"
      //            mal "Rationengröße"
      //            mal "Bewegungsfaktor"
      $retval = ceil($this->getTravelDuration()
                     * $this->getProvisionsPerCave()
                     * formula_parseToPHP(MOVEMENT_COST, $this->owner)
                     * $this->movement->getProvisions());
    }

    return $retval;
  }

  /** This function computes the amount of food needed to move with
   *  this army from one cave to its direct neighbour.
   */
  protected function getProvisionsPerCave() {

    // prepare return value
    $retval = 0.0;

    foreach ($this->units as $id => $amount) {

      // check amount
      if ($amount <= 0)
        continue;

      // get unit
      $unit = new Unit($id);

      // get provisions
      $provisions = $unit->getProvisions();

      // add them
      $retval += $provisions * $amount;
    }

    return $retval;
  }

  public function getResources() {
    return $this->resources;
  }

  protected function getSpeed() {

    // prepare return value
    $retval = 0.0;

    foreach ($this->units as $id => $amount) {

      // check amount
      if ($amount <= 0)
        continue;

      // get unit
      $unit = new Unit($id);

      // get speed
      $speed = $unit->getSpeed();

      // speed is slower?
      if ($speed > $retval)
        $retval = $speed;
    }

    return $retval;
  }

  public function getTarget() {
    return $this->target;
  }

  public function getTravelDuration() {

    $retval = 0;

    // target and movement set?
    if (!is_null($this->target) && !is_null($this->movement)) {

      // Reisedauer = "Entfernung"
      //              mal "Dauer pro Höhle"
      //              mal "größter Geschwindigkeitsfaktor"
      //              mal "Bewegungsfaktor"
      $retval = ceil($this->owner->getDistance($this->target)
                     * formula_parseToPHP(MOVEMENT_SPEED, $this->owner)
                     * $this->getSpeed()
                     * $this->movement->getSpeed());
    }

    return $retval;
  }

  public function getUnits() {
    return $this->units;
  }

  public function setMovement(Movement $movement) {
    $this->movement = $movement;
  }

  public function setOwner(Cave $owner) {
    $this->owner = $owner;
  }

  public function setResource(Resource $resource, $amount) {
    $this->resources[$resource->getID()] = $amount;
  }

  public function setTarget(Cave $target) {
    $this->target = $target;
  }

  public function setUnit(Unit $unit, $amount) {
    if ($amount > 0)
      $this->units[$unit->getID()] = $amount;
  }
}

?>