<?php
/*
 * Autoloader.php -
 * Copyright (c) 2012  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set Namespace **/
namespace Lib\Model;

class Cave {
  /*
   * This function returns the cave data for given cave coordinates
   */
  function getCaveByCoords($xCoord, $yCoord){
    global $db;

    $sql = $db->prepare("SELECT *
                         FROM " . CAVE_TABLE . "
                         WHERE xCoord = :xCoord
                           AND yCoord = :yCoord");
    $sql->bindValue('xCoord', $xCoord, \PDO::PARAM_INT);
    $sql->bindValue('yCoord', $yCoord, \PDO::PARAM_INT);
    if (!$sql->execute()) return array();

    $ret = $sql->fetch(\PDO::FETCH_ASSOC);
    $sql->closeCursor();

    return $ret;
  }
}
?>