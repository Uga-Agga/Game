<?php
/*
 * auth.inc.php -
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

define('AUTH_TRIBE_MSG_PUBLIC', 0);
define('AUTH_TRIBE_MSG_PRIVAT', 1);
define('AUTH_TRIBE_CHANGE_RELATION', 2);
define('AUTH_TRIBE_KICK_PLAYER', 3);
define('AUTH_TRIBE_CHANGE_SETTINGS', 4);

class auth {
  var $perm;

  public function __construct() {
    $this->initPermission();
  }

  private function initPermission() {
    $this->perm[TRIBE_MSG_PUBLIC] = array(
      'value' => 1,  'desc'  => 'Gilden Nachrichten schreiben'
    );
    $this->perm[TRIBE_MSG_PRIVAT] = array(
      'value' => 2,  'desc'  => 'Gilden Nachrichten per Privater Nachricht schreiben'
    );
    $this->perm[TRIBE_CHANGE_RELATION] = array(
      'value' => 4,  'desc'  => 'Beziehungen ändern'
    );
    $this->perm[TRIBE_KICK_PLAYER] = array(
      'value' => 8,  'desc'  => 'Spieler kicken'
    );
    $this->perm[TRIBE_CHANGE_SETTINGS] = array(
      'value' => 16, 'desc'  => 'Gilden Informationen bearbeiten'
    );
  }

  public function checkPermission($userAuth, $authID) {
    if (!isset($this->perm[$authID]) || (int)$userAuth == 0) {
      return false;
    }

    if ($userAuth & $this->perm[$authID]['value']) {
      return true;
    }

    return false;
  }

  public function getAllPermission($userAuth=0) {
    $UserPerm = array();

    foreach ($this->perm as $id => $data) {
      $userPerm[$id] = $this->perm[$id];

      if(!($userAuth & $this->perm[$id]['value'])) {
        $userPerm[$id]['auth'] = false;
      } else {
        $userPerm[$id]['auth'] = true;
      }
    }

    return $userPerm;
  }
}

?>