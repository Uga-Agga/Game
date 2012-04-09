<?php
/*
 * auth.inc.php -
 * Copyright (c) 2011-2012  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

class auth {
  var $perm;

  public function __construct() {
    $this->initPermission();
  }

  private function initPermission() {
    $this->perm['tribe']['msg_tribe'] = array(
      'value' => 1,  'desc'  => 'Stammesnachrichten schreiben'
    );
    $this->perm['tribe']['msg_public'] = array(
      'value' => 2,  'desc'  => 'Stammesnachrichten per Privater Nachricht schreiben'
    );
    $this->perm['tribe']['change_relation'] = array(
      'value' => 4,  'desc'  => 'Beziehungen ändern'
    );
    $this->perm['tribe']['kick_member'] = array(
      'value' => 8,  'desc'  => 'Spieler kicken'
    );
    $this->perm['tribe']['change_settings'] = array(
      'value' => 16, 'desc'  => 'Gilden Informationen bearbeiten'
    );
    $this->perm['tribe']['wonder'] = array(
      'value' => 32, 'desc'  => 'Stammeswunder wirken'
    );

  }

  public function checkPermission($authType, $authID, $userAuth) {
    if (!isset($this->perm[$authType][$authID]) || intval($userAuth) == 0) {
      return false;
    }

    if ($userAuth & $this->perm[$authType][$authID]['value']) {
      return true;
    }

    return false;
  }

  public function getAllTypePermission($authType, $userAuth=0) {
    $UserPerm = array();

    foreach ($this->perm[$authType] as $id => $data) {
      if(!($userAuth & $this->perm[$authType][$id]['value'])) {
        $userPerm[$id] = false;
      } else {
        $userPerm[$id] = true;
      }
    }

    return $userPerm;
  }

  public function setPermission($authType, $newUserAuth, $playerID) {
    global $db;

    if (empty($authType) || empty($playerID)) {
      return false;
    }

    // read user auth
    $sql = $db->prepare("SELECT auth
                         FROM " . PLAYER_TABLE . "
                         WHERE playerID = :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute()) return false;

    $auth = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    // parse & update
    $userAuth = unserialize($auth['auth']);
    $userAuth[$authType] = $newUserAuth;

    // update new permission
    $sql = $db->prepare("UPDATE " . PLAYER_TABLE . "
                         SET auth = :auth
                         WHERE playerID = :playerID");
    $sql->bindValue('auth', serialize($userAuth), PDO::PARAM_STR);
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute()) {
      return false;
    }

    if ($_SESSION['player']->playerID == $playerID) {
      $_SESSION['player']->auth[$authType] = $newUserAuth;
    }

    return true;
  }
}

?>