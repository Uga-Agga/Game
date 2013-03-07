<?php
/*
 * deletePlayer.script.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
;
include "util.inc.php";


include INC_DIR."tribes.inc.php";
include INC_DIR."Player.php";
include INC_DIR."basic.lib.php";
include INC_DIR."time.inc.php";
include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."rules/game.rules.php";
include INC_DIR."rules/hero.rules.php";
include INC_DIR."rules/effects.list.php";
include INC_DIR."hero.inc.php";
include INC_DIR."artefact.inc.php";

init_Effects();

if ($_SERVER['argc'] != 2) {
  echo "Usage: ".$_SERVER['argv'][0]." playerID\n";
  exit (1);
}

$playerID = $_SERVER['argv'][1];

echo "DELETE PLAYER $playerID: Starting...\n";

if (!($db_login = db_connectToLoginDB())) {
  echo "DELETE PLAYER $playerID: Failed to connect to login db.\n";
  exit(1);
}

$sqlSelect = $db_login->prepare("SELECT * FROM Login WHERE LoginID = :playerID");
$sqlSelect->bindValue('playerID', $playerID, PDO::PARAM_INT);

if ($sqlSelect->rowCountSelect() == 0) {
  echo "DELETE PLAYER $playerID: No such Login\n";
  exit(1);
}

if (!$sqlSelect->execute()) {
  echo "DELETE PLAYER $playerID: No such Login\n";
  exit(1);
}
$sqlSelect->closeCursor();

echo "DELETE PLAYER $playerID: Delete Login ";

$sqlDelete = $db_login->prepare("DELETE FROM Login WHERE loginID = :playerID");
$sqlDelete->bindValue('playerID', $playerID, PDO::PARAM_INT);

if (!$sqlDelete->execute()) {
  echo "FAILURE\n";
  exit(1);
}
echo "SUCCESS\n";

if (!($db_game = DbConnect())) {
  echo "DELETE PLAYER $playerID: Failed to connect to game db.\n";
  exit(1);
}

$db = $db_game;

echo "DELETE PLAYER $playerID: Leave Player ";
if (!Tribe::leaveTribe($playerID)) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete Player ";
$sql = $db_game->prepare("DELETE FROM ". PLAYER_TABLE ." WHERE playerID = :playerID");
$sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
if (!$sql->execute()) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete Cave_takeover";
$sql = $db_game->prepare("DELETE FROM ". CAVE_TAKEOVER_TABLE ." WHERE playerID = :playerID");
$sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

if (!$sql->execute()) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete Election";
$sql = $db_game->prepare("DELETE FROM ". TRIBE_ELECTION_TABLE ." WHERE voterID = :playerID");
$sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

if (!$sql->execute()) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete Hero";
$sql = $db_game->prepare("DELETE FROM ".HERO_TABLE ." WHERE playerID = :playerID");
$sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

if (!$sql->execute()) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete Contacts";
$sql = $db_game->prepare("DELETE FROM ". CONTACTS_TABLE ."
                          WHERE playerID = :playerID
                          OR contactplayerID = :playerID");
$sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

if (!$sql->execute()) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete CaveBookmarks";
$sql = $db_game->prepare("DELETE FROM ". CAVE_BOOKMARKS_TABLE ." WHERE playerID = :playerID");
$sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

if (!$sql->execute()) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete caves...\n";
echo "DLELETE PLAYER $playerID: Retrieving caves ";
$sqlSelect = $db_game->prepare("SELECT caveID
                               FROM ". CAVE_TABLE ."
                               WHERE playerID = :playerID");
$sqlSelect->bindValue('playerID', $playerID, PDO::PARAM_INT);

if (!$sqlSelect->execute()) {
  echo "FAILURE\n";
  exit(1);
}
echo "SUCCESS\n";
$result = $sqlSelect->fetchAll();
foreach ($result AS $row) {
  echo "DELETE PLAYER $playerID: Reset playerID at Cave {$row['caveID']}\n";
  $sqlDelete = $db_game->prepare("UPDATE ". CAVE_TABLE ."
                                    SET playerID = 0,
                                    takeoverable = 2,
                                    protection_end = NOW()+0,
                                    secureCave = 0,
                                    hero = 0
                                  WHERE caveID = :caveID");
  $sqlDelete->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);

  if (!$sqlDelete->execute()) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete unit event ";
  $sql = $db_game->prepare("DELETE FROM ". EVENT_UNIT_TABLE ."
                            WHERE caveID = :caveID");
  $sql->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);

  if (!$sql->execute()) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete improvement event ";
  $sql = $db_game->prepare("DELETE FROM ". EVENT_UNIT_TABLE ."
                            WHERE caveID = :caveID");
  $sql->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);

  if (!$sql->execute()) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete movement event ";
  $sql = $db_game->prepare("DELETE FROM ". EVENT_MOVEMENT_TABLE ."
                            WHERE caveID = :caveID");
  $sql->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);

  if (!$sql->execute()) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete science event ";
  $sql = $db_game->prepare("DELETE FROM ". EVENT_SCIENCE_TABLE ."
                            WHERE caveID = :caveID");
  $sql->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);

  if (!$sql->execute()) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete defenseSystem event ";
  $sql = $db_game->prepare("DELETE FROM ". EVENT_DEFENSE_SYSTEM_TABLE ."
                            WHERE caveID = :caveID");
  $sql->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);

  if (!$sql->execute()) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete hero event ";
  $sql = $db_game->prepare("DELETE FROM ". EVENT_HERO_TABLE ."
                            WHERE caveID = :caveID");
  $sql->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);

  if (!$sql->execute()) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete pet artes";
  $artefacts = artefact_getArtefactByCaveID($row['caveID']);
  if (!empty($artefacts)) {
    foreach ($artefacts as $artefact) {
      if ($artefact['pet'] == 1) {
        artefact_removeEffectsFromCave($artefact['artefactID']);
        artefact_removeArtefactFromCave($artefact['artefactID']);
        artefact_uninitiateArtefact($artefact['artefactID']);
      }
    }
  }
}

echo "DELETE PLAYER $playerID: Delete messages ";
$sql1 = $db_game->prepare("UPDATE ". MESSAGE_TABLE ."
                         SET recipientDeleted = 1
                         WHERE recipientID = :playerID");
$sql1->bindValue('playerID', $playerID, PDO::PARAM_INT);

$sql2 = $db_game->prepare("UPDATE ". MESSAGE_TABLE ."
                           SET senderDeleted = 1
                           WHERE senderID = :playerID");
$sql2->bindValue('playerID', $playerID, PDO::PARAM_INT);

if (!$sql1->execute() || !$sql2->execute()) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete hero";
if (!hero_killHero($playerID)) {
  echo "FAILURE hero_killHero";
}
$sql = $db_game->prepare("DELETE FROM " . HERO_TABLE ."
                          WHERE playerID = :playerID");
$sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

if (!$sql->execute()) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete Session ";
$sql = $db_game->prepare("DELETE FROM ". SESSION_TABLE ." WHERE playerID = :playerID");
$sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

if (!$sql->execute()) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

?>