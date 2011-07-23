<?php
/*
 * deleteOutdatedMessages.php -
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

$DAYS = 7; // how long should the messages be kept?

/*
 * this version of deleteOutdatedMessages is a lot slower than the
 * older one-query-solution, but doesn't block the message table
 * that long.
 */

echo "STARTING deleteOutdatedMessages.php\n";

$sql = $db->prepare("SELECT messageID 
                     FROM ". MESSAGE_TABLE);

if (!$sql->execute()) {
  echo "Error getting messages";
  exit;
}

echo "START to check every message\n";

$result = $sql->fetchAll();

foreach($result AS $row) {
  $sql = $db->prepare("SELECT messageID, senderID, recipientDeleted, senderDeleted, 
                       messageTime < (NOW() - INTERVAL :days DAY) + 0 AS outdated 
                       FROM " . MESSAGE_TABLE . "
                       WHERE messageID = :messageID");
  $sql->bindValue('days', $DAYS, PDO::PARAM_INT);
  $sql->bindValue('messageID', $row['messageID'], PDO::PARAM_INT);
  
  if (!$sql->execute() || ! ($message = $sql->fetch())) {
    echo "ERROR Getting the message {$row['messageID']}\n";
    continue;
  }
  $sql->closeCursor();
  
  if ($message['outdated']) {
    echo "DELETED outdated message {$message['messageID']}\n";
    delete($message['messageID']);
  }
  else if ($message['recipientDeleted'] && $message['senderID'] == 0) {
    echo "DELETED deleted system message {$message['messageID']}\n";
    delete($message['messageID']);
  }
  else if ($message['recipientDeleted'] && $message['senderDeleted']) {
    echo "DELETED deleted player2player message {$message['messageID']}\n";
    delete($message['messageID']);
  }

  $sql->closeCursor();
}


function delete($messageID) {
  global $db;
  
  $sql = $db->prepare("DELETE FROM ". MESSAGE_TABLE ." WHERE messageID = :messageID");
  $sql->bindValue('messageID', $messageID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    echo "FAILED.\n";
  }
}

?>