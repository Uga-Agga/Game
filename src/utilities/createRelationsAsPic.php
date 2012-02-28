<?php
/*
 * createRelationsAsPic.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

$colors = array();
$colors[1] = "#EB9898";
$colors[2] = "#FF0000";
$colors[3] = "#AE3CFF";
$colors[4] = "#FFFF00";
$colors[5] = "";
$colors[6] = "#7E7E7E";
$colors[7] = "#029202";
$colors[8] = "#00FF00";

include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

$db     = DbConnect();

$sql = $db->prepare("SELECT tribe, tribe_target, relationType
                     FROM " . RELATION_TABLE . "
                     ORDER BY relationType ASC");
if (!$sql->execute()) {
  die("Fehler beim Auslesen der Beziehungen\n");
  return -17;
}

$res = "digraph Bez {"."\n";
$res .= "\t" . "graph [bgcolor=black];" . "\n";
$res .= "\t" . "size=\"20, 20\";" . "\n";
$res .= "\t" . "node [color=\"#7E7E7E\", style=filled];" . "\n";
$old = 0;
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  if ($row['relationType'] != $old) {
    $old = $row['relationType'];
    $res .= "\t" . " edge [color=\"" . $colors[$old] . "\"];" . "\n";
  }
  $res .= "\t\t" . "\"".$row['tribe']."\" -> \"".$row['tribe_target']."\";" . "\n";
}
$sql->closeCursor();
$res .=  "}" . "\n";
echo $res;

?>