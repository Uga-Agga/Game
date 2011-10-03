<?php
/*
 * takeover.php - script handling the biddings on caves
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/* ************************************************************************** */
/* ***** CONSTANTS ***** **************************************************** */
/* ************************************************************************** */

define("BACKUPFILENAME", "takeover/" . date("YmdHis", time()) . ".log");
define("DEBUG", FALSE);

/* ************************************************************************* */
/* ***** TEMPLATES ********************************************************* */
/* ************************************************************************* */

DEFINE("_MSG_SUBJECT_MAXCAVES",         "Missionierung: Zu viele Höhlen");
DEFINE("_MSG_SUBJECT_SUCCEEDEDONCE",    "Missionierung: Gunst gewonnen!");
DEFINE("_MSG_SUBJECT_FAILEDONCE",       "Missionierung: Zu wenig Geschenke");
DEFINE("_MSG_SUBJECT_BIDDINGTOOLOW",    "Missionierung: Geschenke wurden nicht beachtet");
DEFINE("_MSG_SUBJECT_FAILEDCOMPLETELY", "Missionierung: Versagt!");
DEFINE("_MSG_SUBJECT_CAVETRANSFER",     "Missionierung: Höhle unter Kontrolle!");

DEFINE("_MSG_MAXCAVES",
       "Du hast bereits die maximale Anzahl von {numCaves} Höhlen erreicht. ".
       "Du kannst keine weiteren Höhlen missionieren.\n");

DEFINE("_MSG_SUCCEEDEDONCE",
       "Ein Bote bringt eine frohe Botschaft. Du hast in die freie Höhle ".
       "'{name}' ({xCoord}|{yCoord}) Rohstoffe im Wert von {abs_bidding} ".
       "Punkten geschafft. Die Bewohner von '{name}' betrachten diese Gaben ".
       "und bewerten sie mit {rel_bidding} Punkten. Mehr hat ihnen heute ".
       "niemand geschenkt, und ihr steigt in ihrer Gunst.");

DEFINE("_MSG_FAILEDONCE",
       "Ein Bote bringt eine schlechte Botschaft. Du hast in die freie Höhle ".
       "'{name}' ({xCoord}|{yCoord}) Rohstoffe im Wert von {abs_bidding} ".
       "Punkten geschafft. Die Bewohner von '{name}' betrachten diese Gaben ".
       "und bewerten sie mit {rel_bidding} Punkten.<tmpl:WINNER> Die Gaben des ".
       "Häuptlings '{player_name}' haben sie jedoch mit {rel_bidding} Punkten ".
       " bewertet. Er hat heute ihre Gunst gewonnen.</tmpl:WINNER>");


DEFINE("_MSG_BIDDINGTOOLOW",
       "Ein Bote bringt eine schlechte Botschaft. Du hast in die freie Höhle ".
       "'{name}' ({xCoord}|{yCoord}) Rohstoffe im Wert von {abs_bidding} ".
       "Punkten geschafft. Die Bewohner von '{name}' betrachten diese Gaben ".
       "und bewerten sie mit {rel_bidding} Punkten. Damit sie sich überhaupt ".
       "an deine Geschenke erinnern, mußt du ihnen aber mindestens ".
       "{takeoverminresourcevalue} schenken.<tmpl:WINNER> Die Gaben des ".
       "Häuptlings '{player_name}' haben sie jedoch mit {rel_bidding} Punkten ".
       " bewertet. Er hat heute ihre Gunst gewonnen.</tmpl:WINNER>");

DEFINE("_MSG_FAILEDCOMPLETELY",
       "Der Häuptling der freien Höhle '{name}' ({xCoord}|{yCoord}) hat ".
       "heute entschieden, sich einem Häuptling unterzuordnen. ".
       "Leider fiel die Wahl nicht auf Dich.".
       "<tmpl:WINNER> Er folgt nun dem Häuptling '{name}'.</tmpl:Winner>\n");

DEFINE("_MSG_CAVETRANSFER",
       "Der Häuptling der freien Höhle '{name}' ({xCoord}|{yCoord}) hat ".
       "heute entschieden, sich einem Häuptling unterzuordnen.".
       "Die Wahl fiel dabei auf euch. Ihr habt ab nun die Kontrolle über ".
       "'{name}'!");

/***** INIT *****/

// increase memory limit
ini_set("memory_limit", "128M");

// include necessary files
include "util.inc.php";
include INC_DIR . "config.inc.php";
include INC_DIR . "db.inc.php";
include INC_DIR . "game_rules.php";
include INC_DIR . "basic.lib.php";

// get globals
$config = new Config();
$db     = DbConnect();

// initialize game rules
init_resources();
init_sciences();

// get extern vars
global $resourceTypeList, $scienceTypeList,
       $TAKEOVERMAXPOPULARITYPOINTS, $TAKEOVERMINRESOURCEVALUE;

echo "---------------------------------------------------------------------\n";
echo "- LOG FILE ----------------------------------------------------------\n";
echo "vom " . date("r") . "\n";
echo "---------------------------------------------------------------------\n";



/* **** BACKUP **** ********************************************************* */

echo "***** BACKUP *****\n";

if (!DEBUG)
  echo "backup: " . (takeover_backup_table() ? "erfolgreich" : ("FEHLER:" . mysql_error())) . "\n";
else
  echo "DEBUG: backup: nicht ausgeführt\n";



/* **** CHECK: PLAYERS WITH MAXCAVES **** *********************************** */

echo "\n***** CHECK: PLAYERS WITH MAXCAVES *****\n";

takeover_remove_maxed_players();



/* **** GET TAKEOVER_CAVES **** ********************************************* */

echo "\n***** GET TAKEOVER_CAVES *****\n";

$takeover_caves = takeover_get_caves();
if ($takeover_caves === null) exit(1);



/***** ITERATE THROUGH ALL THE CAVES *****/

echo "\n***** ITERATE THROUGH ALL THE CAVES *****\n";

foreach ($takeover_caves AS $row) {
  if (DEBUG) echo "DEBUG:  Considering caveID: ".$row['caveID']."\n ";

  // get bidders
  $bidders = takeover_get_bidders($row['caveID']);

  // get potential winner
  $winner = current($bidders);

  // check him for minimum bid
  if ($winner['rel_bidding'] >= $TAKEOVERMINRESOURCEVALUE) {
    array_shift($bidders);

    // get winner's name
    $winnerdata = getPlayerByID($winner['playerID']);
    $winner['player_name'] = $winnerdata['name'];

    // process winner
    takeover_process_winner($winner);

  // clear winner
  } else {
    $winner = NULL;
  }

  // process other bidders
  takeover_process_biddings($bidders, $winner);

  // reset those biddings
  takeover_reset_biddings($row['caveID']);
}



/***** TRANSFER CAVES *****/

// transfer caves to those players with a status >= TAKEOVERMAXPOPULARITYPOINTS
echo "\n***** TRANSFER CAVES TO WINNERS *****\n";

// get biddings with a status >= TAKEOVERMAXPOPULARITYPOINTS
$transfers = takeover_get_transfers();

foreach ($transfers AS $transfer) {

  // other bidders
  takeover_other_bidders($transfer['caveID'], $transfer['playerID']);

  // winner
  takeover_transfer_cave_to($transfer['caveID'], $transfer['playerID']);

  // remove all biddings for that caveID
  takeover_remove_bidding_by_caveID($transfer['caveID']);
}
return 0;



/* ************************************************************************** */
/* ***** FUNCTIONS ***** **************************************************** */
/* ************************************************************************** */

/** This function backups the Cave_takeover Table for debugging purpose
 *
 *  @return true if finished successfully, false otherwise
 */
function takeover_backup_table() {
  global $db;

  // alles auslesen
  $sql = $db->prepare("SELECT * FROM " . CAVE_TAKEOVER_TABLE);
  if (!$sql->execute()) {
    return false;
  }
  $logarray = $sql->fetchAll(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  // abspeichern
  $fp = fopen (BACKUPFILENAME, "aw");
  fputs ($fp, var_export($logarray, TRUE));

  fclose($fp);

  return true;
}

/** This function removes a players bidding.
 *  Exits the script in case of an error.
 *
 *  @param playerID the player whose bidding shall be deleted
 */
function takeover_remove_bidding_by_playerID($playerID) {
  global $db;

  $sql = $db->prepare("DELETE FROM " . CAVE_TAKEOVER_TABLE . " WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->execute();
}

/** This function removes all biddings for a certain cave.
 *  Exits the script in case of an error.
 *
 *  @param caveID the cave whose biddings shall be deleted
 */
function takeover_remove_bidding_by_caveID($caveID) {
  global $db;

  $sql = $db->prepare("DELETE FROM " . CAVE_TAKEOVER_TABLE . " WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  $sql->execute();
}

/** This function removes all biddings of players who own MaxCaves
 *  Exits the script in case of an error.
 *
 */
function takeover_remove_maxed_players() {
  global $db;

  // get players with maxcaves
  $sql = $db->prepare("SELECT t.playerID, p.name, p.takeover_max_caves
                       FROM " . CAVE_TAKEOVER_TABLE . " t
                         LEFT JOIN " . PLAYER_TABLE . " p ON t.playerID = p.playerID
                         LEFT JOIN " . CAVE_TABLE . " c ON t.playerID = c.playerID
                       GROUP BY t.playerID
                       HAVING COUNT(c.caveID) >= p.takeover_max_caves");
  if (!$sql->execute()) {
    exit(1);
  }

  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {

    // remove the bidding
    takeover_remove_bidding_by_playerID($row['playerID']);

    // send a message
    takeover_send_max_caves($row['playerID'], $row['takeover_max_caves']);
  }
}

/** This function resets all biddings to a certain cave
 *
 *  @param caveID the cave whose biddings shall be reset
 *
 *  @return true if finished successfully, false otherwise
 */
function takeover_reset_biddings($caveID) {
  global $db, $resourceTypeList;
  static $resources;

  if (empty($resources)) {
    $resources = array();
    foreach ($resourceTypeList AS $resource) {
      $resources[] = $resource->dbFieldName . " = 0";
    }
    $resources = implode(", ", $resources);
  }

  $sql = $db->prepare("UPDATE " . CAVE_TAKEOVER_TABLE . " SET $resources WHERE caveID = :caveID");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) {
      return false;
  }

  return true;
}

/** This function returns an array of all caveIDs with biddings to that cave
 *
 *  @return null in case of an error, an array containing caveIDs
 */
function takeover_get_caves() {
  global $db;

  $sql = $db->prepare("SELECT caveID FROM " . CAVE_TAKEOVER_TABLE . " GROUP BY caveID");
  if (!$sql->execute()) {
    return null;
  }

  return $sql->fetchAll(PDO::FETCH_ASSOC);
}

/** This function returns an array of all biddings for a cave
 *
 *  @param caveID specifying the cave for which all biddings shall be returned
 *
 *  @return an array with all biddings for a cave, an empty array in case of an error
 */
function takeover_get_bidders($caveID) {
  global $db, $resourceTypeList;
  static $query_resource;

  // initialize $query_resource
  if (empty($query_resource)) {
    $query_resource = array();

    foreach ($resourceTypeList AS $resource) {
      if ($resource->takeoverValue > 0) {
        $query_resource[] = $resource->takeoverValue . " * ct." . $resource->dbFieldName;
      }
     }
    $query_resource = implode(" + ", $query_resource);
  }

  $sql = $db->prepare("SELECT ct.*, ($query_resource) AS abs_bidding, 
                         COUNT(*) AS caves,
                         ($query_resource)/(COUNT(*)*COUNT(*)) AS rel_bidding
                       FROM " . CAVE_TAKEOVER_TABLE . " ct
                         LEFT JOIN " . CAVE_TABLE . " c USING (playerID)
                       WHERE ct.caveID = :caveID
                       GROUP BY ct.playerID
                       ORDER BY rel_bidding DESC");
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    return array();
  }

  return $sql->fetchAll(PDO::FETCH_ASSOC);
}

/** This function increases the status of the winner's bidding
 *
 *  @param winner the winner's playerID
 */
function takeover_process_winner($winner) {
  global $db;
  
  $sql = $db->prepare("UPDATE " . CAVE_TAKEOVER_TABLE . "
                       SET status = status + 1
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $winner['playerID'], PDO::PARAM_INT);
  $sql->execute();

  takeover_send_success($winner['playerID'], $winner);
}

function takeover_process_biddings($biddings, $winner) {
  global $TAKEOVERMINRESOURCEVALUE;

  foreach ($biddings AS $bidding) {
    if ($bidding['rel_bidding'] >= $TAKEOVERMINRESOURCEVALUE) {
      takeover_send_failed($bidding['playerID'], $bidding, $winner);
    } else {
      takeover_send_bidding_too_low($bidding['playerID'], $bidding, $winner);
    }
  }
}

function takeover_get_transfers() {
  global $db, $TAKEOVERMAXPOPULARITYPOINTS;

  $sql = $db->prepare("SELECT * FROM " . CAVE_TAKEOVER_TABLE . " WHERE status >= " . $TAKEOVERMAXPOPULARITYPOINTS);
  if (!$sql->execute()) {
    return array();
  }

  return $sql->fetchAll(PDO::FETCH_ASSOC);
}

function takeover_other_bidders($caveID, $playerID) {
  global $db;

  // get winner's data
  $winner = getPlayerByID($playerID);

  $sql = $db->prepare("SELECT * 
                       FROM Cave_takeover
                       WHERE caveID = :caveID
                         AND playerID != :playerID");
  $sql->bindValue('caveID', $caveID);
  $sql->bindValue('playerID', $playerID);
  if (!$sql->execute()) {
    echo "ERROR (takeover_other_bidders):";
    exit(1);
  }

  // message the other bidders, that this cave was transfered to the winner
  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    takeover_send_failed_completely($row['playerID'], $row, $winner);
  }
  $sql->closeCursor();
}

function takeover_transfer_cave_to($caveID, $playerID) {
  global $db, $scienceTypeList;

  // check parameters
  $caveID   = (int) $caveID;
  $playerID = (int) $playerID;

  // get player
  $winner = getPlayerByID($playerID);
  if (!sizeof($winner)){
    echo "ERROR (takeover_transfer_cave_to): Could not transfer Cave $caveID to Player $playerID.\n";
    return FALSE;
  }

  // secureCaveCredits
  $hasCredits = $winner['secureCaveCredits'] > 0 ? 1 : 0;

  // transfer cave to player
  $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                       SET playerID = :playerID,
                         takeoverable = 0,
                         secureCave = :secureCave
                       WHERE caveID = :caveID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('secureCave', $hasCredits, PDO::PARAM_INT);
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    return FALSE;
  }

  // update secureCaveCredits
  $sql = $db->prepare("UPDATE " . PLAYER_TABLE . "
                       SET secureCaveCredits = GREATEST(0, secureCaveCredits - 1)
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    echo "ERROR (takeover_transfer_cave_to): Could not update secureCaveCredits of Player $playerID.\n";
    return FALSE;
  }

  // copy sciences
  if (sizeof($scienceTypeList)) {
    $set = array();
    foreach ($scienceTypeList AS $science) {
      $temp = $science->dbFieldName;
      $set[] = "$temp = '{$winner[$temp]}'";
    }
    $set = implode(", ", $set);

    $sql = $db->prepare("UPDATE " . CAVE_TABLE . "
                         SET " . $set . "
                         WHERE playerID = :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    if (!$sql->execute()) {
      echo "ERROR (takeover_transfer_cave_to): Could not update sciences of Player $playerID.\n";
      return FALSE;
    }
  }

  // get the cave's data
  $cave = getCaveByID($caveID);

  takeover_send_transfer($playerID, $cave);
}

/* ************************************************************************** */
/* ***** MESSAGES ***** ***************************************************** */
/* ************************************************************************** */


/** This function sends a system message
 *
 *  @param receiverID  the receiver's playerID
 *  @param type        the type of the message; 0 for "Information"
 *  @param betreff     the subject of that message
 *  @param nachricht   the body of that message
 *
 *  @return true if succes, false otherwise
 */
function takeover_system_message($receiverID, $betreff, $nachricht) {
  global $db;

  $type = 0;
  $sql = $db->prepare("INSERT INTO ". MESSAGE_TABLE ."
                         (recipientID,
                         senderID,
                         messageClass,
                         messageSubject,
                         messageText,
                         messageTime)
                       VALUES (
                         :recipientID,
                         :senderID,
                         :messageClass,
                         :messageSubject,
                         :messageText,
                         NOW()+0)");
  $sql->bindValue('recipientID', $receiverID, PDO::PARAM_INT);
  $sql->bindValue('senderID', 0, PDO::PARAM_INT);
  $sql->bindValue('messageClass', $type, PDO::PARAM_INT);
  $sql->bindValue('messageSubject', $betreff, PDO::PARAM_STR);
  $sql->bindValue('messageText', $nachricht, PDO::PARAM_STR);
  $sql->execute();
}

/** This function sends a message, that a bidding was deleted, as the bidder
 *  already has MaxCaves
 *
 *  @param receiverID  the receiver's playerID
 *  @param numCaves    the number of the receiver's MaxCaves
 *
 *  @return true if succes, false otherwise
 */
function takeover_send_max_caves($receiverID, $numCaves) {
  $template = tmpl_load(_MSG_MAXCAVES);
  tmpl_set($template, compact('receiverID', 'numCaves'));
  return takeover_system_message($receiverID, _MSG_SUBJECT_MAXCAVES, tmpl_parse($template));
}

/** This function sends a message, that a bidding was the maximum bidding
 *
 *  @param receiverID  the receiver's playerID
 *  @param bidding     the receiver's bidding details
 *
 *  @return true if succes, false otherwise
 */
function takeover_send_success($receiverID, $bidding) {
  $template = tmpl_load(_MSG_SUCCEEDEDONCE);
  tmpl_set($template, $bidding);
  return takeover_system_message($receiverID, _MSG_SUBJECT_SUCCEEDEDONCE, tmpl_parse($template));
}

/** This function sends a message, that a bidding was lower than the maximum bidding
 *
 *  @param receiverID  the receiver's playerID
 *  @param bidding     the receiver's bidding details
 *  @param winner      the winner's bidding details
 *
 *  @return true if succes, false otherwise
 */
function takeover_send_failed($receiverID, $bidding, $winner) {
  $template = tmpl_load(_MSG_FAILEDONCE);
  tmpl_set($template, $bidding);
  tmpl_set($template, 'WINNER', $winner);
  return takeover_system_message($receiverID, _MSG_SUBJECT_FAILEDONCE, tmpl_parse($template));
}

/** This function sends a message, that a bidding was lower than the minimum bidding
 *
 *  @param receiverID  the receiver's playerID
 *  @param bidding     the receiver's bidding details
 *  @param winner      the winner's bidding details
 *
 *  @return true if succes, false otherwise
 */
function takeover_send_bidding_too_low($receiverID, $bidding, $winner) {
  global $TAKEOVERMINRESOURCEVALUE;

  $template = tmpl_load(_MSG_BIDDINGTOOLOW);
  tmpl_set($template, $bidding);
  tmpl_set($template, 'takeoverminresourcevalue', $TAKEOVERMINRESOURCEVALUE);
  tmpl_set($template, 'WINNER', $winner);
  return takeover_system_message($receiverID, _MSG_SUBJECT_BIDDINGTOOLOW, tmpl_parse($template));
}


/** This function sends a message, that a cave was transfered to another player
 *
 *  @param receiverID  the receiver's playerID
 *  @param bidding     the receiver's bidding details
 *  @param winner      the winner's details
 *
 *  @return true if succes, false otherwise
 */
function takeover_send_failed_completely($receiverID, $bidding, $winner) {
  $template = tmpl_load(_MSG_FAILEDCOMPLETELY);
  tmpl_set($template, $bidding);
  tmpl_set($template, 'WINNER', $winner);
  return takeover_system_message($receiverID, _MSG_SUBJECT_FAILEDCOMPLETELY, tmpl_parse($template));
}

/** This function sends a message, that a bidding got a cave
 *
 *  @param receiverID  the receiver's playerID
 *  @param caveID      the transfered cave's data
 *
 *  @return true if succes, false otherwise
 */
function takeover_send_transfer($receiverID, $cave) {
  $template = tmpl_load(_MSG_CAVETRANSFER);
  tmpl_set($template, $cave);
  return takeover_system_message($receiverID, _MSG_SUBJECT_CAVETRANSFER, tmpl_parse($template));
}

?>