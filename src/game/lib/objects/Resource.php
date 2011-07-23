<?php
/*
 * Resource.php - TODO
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
class Resource extends GameObject {

  public function getAuctionValue() {
    return ua_resource_takeovervalue($this->getType(), $this->id);
  }

  public function getProduction() {
    return ua_resource_productionfunction($this->getType(), $this->id);
  }

  public function getRating() {
    return ua_resource_ratingvalue($this->getType(), $this->id);
  }

  public function getSafeStorage() {
    return ua_resource_safestorage($this->getType(), $this->id);
  }

  protected function getType() {
    return UA_RESOURCE;
  }
}

?>