<?php
/*
 * New.php - TODO
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Model.php');

class Messages_New_Model extends Model {

  function getCount() {
    global $db;

    // get user messages
    $sql = $db->prepare("SELECT COUNT(*) as num
                         FROM " . MESSAGE_TABLE . "
                         WHERE recipientID = :recipientID
                           AND `read` = 0
                           AND recipientDeleted = 0");
    $sql->bindValue('recipientID', $_SESSION['player']->playerID, PDO::PARAM_INT);
    $sql->execute();
    $ret = $sql->fetchColumn();
    $sql->closeCursor();

    return $ret;
  }
}

?>