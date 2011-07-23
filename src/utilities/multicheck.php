<?php 
/*
 * multicheck.php -
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

global $config;

$config = new Config();

if (!($db_login = db_connectToLoginDB())) {
  echo "Check Multi : Failed to connect to login db.\n";
  exit(1);
}

$sql = $db_login->prepare("SELECT DISTINCT(password) AS pw, COUNT(password) AS cpw 
                           FROM Login 
                           GROUP BY pw");

if (!$sql->execute()) {
  exit(1);
}

$result = $sql->fetchAll(PDO::FETCH_ASSOC);

echo "Anzahl ungleicher Passwoerter: " . $sql->rowCountSelect() . "\n";

if (is_array($result)) {
  foreach ($result AS $row) {
    if ($row['cpw'] > 1) {
      $sql = $db_login->prepare("SELECT user, email, password FROM Login WHERE password = :pw");
      $sql->bindValue('pw', $row['pw'], PDO::PARAM_STR);
      
      if (!$sql->execute()) {
        exit(1);
      }
      echo "\n";
      while($row2 = $sql->fetch(PDO::FETCH_ASSOC)) {
        echo "user: ".$row2['user']."  email: ".$row2['email']."  password: ".$row2['password']."\n";
      }
    }
  }
}

?>