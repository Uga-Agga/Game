<?php
/*
 * createDailyPlayerSheet.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

$db     = DbConnect();

$sql = $db->prepare("SELECT t1.tag, t2.tag, relationType
                     FROM " . RELATION_TABLE . " r
                       LEFT JOIN " . TRIBE_TABLE . " t1 ON t1.tribeID = r.tribeID
                       LEFT JOIN " . TRIBE_TABLE . " t2 ON t2.tribeID = r.tribeID_target");
if (!$sql->execute()) {
  die("Fehler beim Auslesen.");
}

$row = $sql->fetchAll(PDO::FETCH_ASSOC);
echo count($row) . "\t" . time() . "\n";
foreach ($row as $data) {
  echo implode($data, "\t") . "\n";
}

?>