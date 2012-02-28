<?php
/*
 * calcStartValues.script.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


include("util.inc.php");

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."startvalues.php";

echo "CALCULATING START VALUES...\n";

if (!($db = DbConnect())) {
  echo "CALCSTARTVALUES: Failed to connect to game db.\n";
  exit(1);
}

$countStr = array();

foreach($start_values AS $id => $max) {
  $countStr[] = ($max ? "LEAST( " : "") .
    "SUM(".$id.") / COUNT(*) * ".STARTVALUES_AVERAGE_MULTIPLIER.
    ($max ? ", $max )" : ""). " AS ".$id ;
}

$sql = $db->prepare("SELECT ". implode($countStr, ", ") ." 
                     FROM " . CAVE_TABLE . "
                     WHERE playerID != 0 ");

if (!$sql->execute() || !($row = $sql->fetch(PDO::FETCH_ASSOC))) {
  echo "CALCSTARTVALUES: Failed to count entities.\n";
  echo $query."\n";
  exit(1);
}
$sql->closeCursor();

if (!$db->exec("DELETE FROM StartValue")) {
    echo "CALCSTARTVALUES: Failed to delete old values.\n";
    exit(1);
  }

foreach($row AS $field => $value) {
  $sql = $db->prepare("INSERT INTO ". START_VALUE_TABLE ." 
                       VALUES ($field, :value, 0)");
  $sql->bindValue('value', PDO::PARAM_INT);
  
  if (!$sql->execute()) {
    echo "CALCSTARTVALUES: Failed to insert value.\n";
    exit(1);
  }
}

echo "FINISHED\n";

?>