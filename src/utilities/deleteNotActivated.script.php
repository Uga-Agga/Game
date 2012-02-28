<?php
/*
 * deleteNotActivated.script.php -
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
include INC_DIR."basic.lib.php";


$DELETE_SCRIPT = "deletePlayer.script.php";
$MAX_ACTIVATE_DURATION = 62 * 60 * 60;   // days * minutes * seconds

if ($_SERVER['argc'] != 1) {
  echo "Usage: ".$_SERVER['argv'][0]."\n";
  exit (1);
}

echo "DELETE NOT ACTIVATED: (".date("d.m.Y H:i:s",time()).") Starting...\n";

if (!($db_login = db_connectToLoginDB())) {
  echo "DELETE NOT ACTIVATED: Failed to connect to login db.\n";
  exit(1);
}

$sql = $db_login->prepare("SELECT * 
               FROM Login 
               WHERE activated = 0 
               AND creation < (NOW() - INTERVAL :maxActivateDuration SECOND) +0");
$sql->bindValue('maxActivateDuration', $MAX_ACTIVATE_DURATION, PDO::PARAM_INT);

if (!$sql->execute()) {
  echo "DELETE NOT ACTIVATED: Couldn't retrieve logins\n";
  exit(1);
}

echo "DELETE NOT ACTIVATED: Delete players...\n";
$count = 0;

while ($row = $sql->fetch()) {
  echo "DELETE NOT ACTIVATEDPLAYER: Call $DELETE_SCRIPT\n";
  echo "for user: {$row['LoginID']}, {$row['user']}, {$row['email']}, ".
       "{$row['countResend']} resends, {$row['creation']} \n\n";

  system("\${PHP-php} $DELETE_SCRIPT {$row['LoginID']}");

  echo "\n\n";
  $count++;
}

echo "DELETE NOT ACTIVATED: Deleted $count users.\n";
echo "DELETE NOT ACTIVATED: Done.\n";

?>