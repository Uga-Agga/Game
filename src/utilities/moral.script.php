<?php
/*
 * moral.script.php - script handling the biddings on caves
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

// include necessary files
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

// get globals
$config = new Config();
$db     = DbConnect();

echo "STARTING MORAL UPDATE ";
echo "MORAL UPDATE: (".date("d.m.Y H:i:s",time()).") Running...";
/*{
  $query1 =
    "UPDATE Relation ".
    "SET moral = moral + SIGN(fame) ";
  if (RELATION_FAME_MIN_POINTS>0){
    $query1=$query1."WHERE fame > ".RELATION_FAME_MIN_POINTS;
  }
  $query2 =
    "UPDATE Relation ".
    "SET fame = 0";

  if (! $db->query($query1)) {
    echo "FAILED1.\n";
  }
  else {
    if (! $db->query($query2)) {
      echo "FAILED2.\n";
    }
    else {
      echo "SUCCESS.\n";
    }
    
  }
}
*/
echo "STARTING MORAL DECREASE ";
echo "MORAL DECREASE: (".date("d.m.Y H:i:s", time()).") Running...";
{
  $sql = $db->prepare("UPDATE " . PLAYER_TABLE . " SET fame = GREATEST(fame - :fame ,0)");
  $sql->bindValue('fame', FAME_DECREASE_FACTOR, PDO::PARAM_INT);
  if (!$sql->execute()) {
      echo "FAILED.\n";
  } else {
    echo "SUCCESS.\n";
  }
}

?>