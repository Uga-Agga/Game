<?php
/*
 * createDailyPlayerSheet.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

$config = new Config();
$db     = DbConnect();

$sql = $db->prepare("SELECT tribe, tribe_target, relationType
                     FROM " . RELATION_TABLE);
if (!$sql->execute()) {
  die("Fehler beim Auslesen.");
}

$row = $sql->fetchAll(PDO::FETCH_ASSOC);
echo count($row) . "\t" . time() . "\n";
foreach ($row as $data) {
  echo implode($data, "\t") . "\n";
}

?>