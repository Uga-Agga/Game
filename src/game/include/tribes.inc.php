<?php
/*
 * tribes.inc.php -
 * Copyright (c) 2004  OGP-Team
 * Copyright (c) 2012 Georg Pitterle
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

DEFINE("TRIBE_MESSAGE_WAR",      1);
DEFINE("TRIBE_MESSAGE_LEADER",   2);
DEFINE("TRIBE_MESSAGE_MEMBER",   3);
DEFINE("TRIBE_MESSAGE_RELATION", 4);
DEFINE("TRIBE_MESSAGE_INFO",    10);

function leaderChoose_processChoiceUpdate($playerID, $voterID, $tag) {
  global $db;

  if ($playerID == 0) {
    if (!leaderChoose_deleteChoiceForPlayer($voterID)) {
      return -29;
    }
    return 9;
  }

  $player = new Player(getPlayerByID($playerID));

  if (!$player || strcasecmp($player->tribe, $tag)) {
    return -29;
  } else {
    $sql = $db->prepare("REPLACE ". ELECTION_TABLE." 
                         SET voterID = :voterID, 
                           playerID = :playerID,
                           tribe = :tribe");
    $sql->bindValue('voterID', $voterID, PDO::PARAM_INT);
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    $sql->bindValue('tribe', $tag, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return -29;
    }

    return 9;
  }
}

function leaderChoose_deleteChoiceForPlayer($voterID) {
  global $db;

  $sql = $db->prepare("DELETE FROM ". ELECTION_TABLE ."
                       WHERE voterID = :voterID ");
  $sql->bindValue('voterID', $voterID, PDO::PARAM_INT);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 0;
  }

  return 1;
}

function leaderChoose_getElectionResultsForTribe($tribe) {
  global $db;
  
  $sql = $db->prepare("SELECT p.name, COUNT(e.voterID) AS votes 
                       FROM ". ELECTION_TABLE ." e 
                         LEFT JOIN Player p ON p.playerID = e.playerID 
                       WHERE e.tribe like :tribe
                       GROUP BY e.playerID, p.name");
  $sql->bindValue('tribe', $tribe, PDO::PARAM_STR);
  if (!$sql->execute()) {
    return 0;
  }

  $votes = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    array_push($votes, $row);
  }
  $sql->closeCursor();

  return $votes;
}

function leaderChoose_getVoteOf($playerID) {
  global $db;
  
  $sql = $db->prepare("SELECT playerID FROM ". ELECTION_TABLE . " WHERE voterID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  if (!$sql->execute()) return false;

  $row = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  return (isset($row['playerID'])) ? $row['playerID'] : false;
}

function government_getGovernmentForTribe($tag) {
  global $db;
  
  $sql = $db->prepare("SELECT governmentID, DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, duration, duration < NOW()+0 AS isChangeable 
                       FROM " . TRIBE_TABLE ." 
                       WHERE tag LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) return array();
  
  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  return $ret;
}

function government_setGovernment($tag, $governmentID) {
  global $db;

  $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                       SET governmentID = :governmentID,
                         duration = (NOW() + INTERVAL ".GOVERNMENT_CHANGE_TIME_HOURS." HOUR)+0
                       WHERE tag LIKE :tag");
  $sql->bindValue('governmentID', $governmentID, PDO::PARAM_INT);
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 0;
  }

  return 1;
}

function government_processGovernmentUpdate($tag, $governmentData) {
  if (!tribe_isLeader($_SESSION['player']->playerID, $tag)) {
    return -1;
  }

  $oldGovernment = government_getGovernmentForTribe($tag);
  if (empty($oldGovernment)) {
    return -27;
  }

  if (!$oldGovernment['isChangeable']) {
    return -28;
  }

  if (!government_setGovernment($tag, $governmentData['governmentID'])) {
    return -27;
  }

  tribe_sendTribeMessage($tag, TRIBE_MESSAGE_LEADER, "Die Regierung wurde geändert",
    "Ihr Stammesanführer hat die Regierung Ihres Stammes auf " . $GLOBALS['governmentList'][$governmentData['governmentID']]['name'] . " geändert.");

  return 8;
}

function relation_checkForRelationAttrib($tag_tribe1, $tag_tribe2, $attribArray) {
  if (!is_array($attribArray)) {
    exit;
  }

  $relation = relation_getRelation($tag_tribe1, $tag_tribe2);
  $result = FALSE;
  
  foreach ($attribArray as $attrib) {
    $result = ($GLOBALS['relationList'][$relation['own']['relationType']][$attrib] == 1) && ($GLOBALS['relationList'][$relation['other']['relationType']][$attrib] == 1);
    if ($result) {
      break;
    }
  }

  return $result;
}

function relation_areAllies($tag_tribe1, $tag_tribe2) {
  $attribs = array();
  $attribs[] = 'isWarAlly';

  $res = relation_checkForRelationAttrib($tag_tribe1, $tag_tribe2, $attribs);
  return $res;
}

function relation_areEnemys($tag_tribe1, $tag_tribe2) {
  $attribs = array();
  $attribs[] = 'isWar';
//  $attribs[] = 'isPrepareForWar';
  $res = relation_checkForRelationAttrib($tag_tribe1, $tag_tribe2, $attribs);
  return $res;
}

function tribe_isAtWar($tag, $includePrepareForWar) {
  $relations = relation_getRelationsForTribe($tag);
  $weAreAtWar = FALSE;
  foreach ($relations['own'] as $actRelation) { 
    if ($GLOBALS['relationList'][$actRelation['relationType']]['isWar']) {
      $weAreAtWar = TRUE;
      break;
    };
    if ($includePrepareForWar && ($GLOBALS['relationList'][$actRelation['relationType']]['isPrepareForWar'])) {
      $weAreAtWar = TRUE;
      break;
    };  
  }
  return $weAreAtWar;
}

function relation_haveSameEnemy($tag_tribe1, $tag_tribe2, $PrepareForWar, $War) {
 
  // now we need the relations auf the two tribes
  $ownRelations = relation_getRelationsForTribe($tag_tribe1);
  $targetRelations = relation_getRelationsForTribe($tag_tribe2);

  foreach ($ownRelations['own'] as $actRelation) {
    foreach ($targetRelations['own'] as $actTargetRelation) {
      if (strcasecmp($actRelation['tribe_target'], $actTargetRelation['tribe_target']) == 0) {
        $ownType = $actRelation['relationType'];
        $targetType = $actTargetRelation['relationType'];

        $weHaveWar   = ($PrepareForWar && $GLOBALS['relationList'][$ownType]['isPrepareForWar']) ||
                      ($War && $GLOBALS['relationList'][$ownType]['isWar']);
        $theyHaveWar = ($PrepareForWar && $GLOBALS['relationList'][$targetType]['isPrepareForWar']) ||
                       ($War && $GLOBALS['relationList'][$targetType]['isWar']);

        if ($weHaveWar && $theyHaveWar) {
          return true;
        }
      }
    }
  }

  return false;
}

function tribe_isTopTribe($tag) {
  global $db;
  
  $sql = $db->prepare("SELECT rank 
                       FROM " . RANKING_TRIBE_TABLE . " 
                       WHERE tribe = :tag
                       LIMIT 0 , 30");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) return false;

  $data = $sql->fetch(PDO::FETCH_ASSOC);

  return (isset($data['rank']) && $data['rank'] <= 10) ? true : false;
}

function relation_processRelationUpdate($tag, $relationData, $FORCE = 0) {

  if (!$FORCE) {
    if (strcasecmp($tag, $relationData['tag']) == 0) {
      return -14;
    }

    if (!($ownTribeInfo = tribe_getTribeByTag($tag))) {
      return -15;
    }

    if (!($targetTribeInfo = tribe_getTribeByTag($relationData['tag']))) {
      return -15;
    }

    if (!$ownTribeInfo['valid']) {
      return -16;
    }

    $relationType = $relationData['relationID'];
    $relationInfo = $GLOBALS['relationList'][$relationType];

    if (!($relation = relation_getRelation($tag, $relationData['tag']))) {
      return -17;
    }
    $relationTypeActual = $relation['own']['relationType'];

    if ($relationTypeActual == $relationType) { // change to actual relation?
      return -18;
    }

    if (!$relation['own']['changeable']) {
      return -19;
    }

    // check if switching to same relation as target or relation is possible
    if ($relation['other']['relationType'] != $relationType && !relation_isPossible($relationType, $relation['own']['relationType'])) {
      return -20;
    }

    $relationFrom = $relation['own']['relationType'];
    $relationTo   = $relationType;

    if (!$FORCE && ($GLOBALS['relationList'][$relationTo]['isWarAlly'])) {
      //generally allowes?
      if (!$GLOBALS['relationList'][$relationFrom]['isAlly']) 
        return -21;
      if (!$GLOBALS['relationList'][$relation['other']['relationType']]['isAlly']) 
        return -22;
      if (!relation_haveSameEnemy($ownTribeInfo['tag'], $targetTribeInfo['tag'], TRUE, TRUE)) 
        return -23;
    }

    $relationTypeOtherActual = $relation['other']['relationType'];
    // check minimum size of target tribe if it's not an ultimatum
    if ((($relationInfo['targetSizeDiffDown'] > 0) || ($relationInfo['targetSizeDiffUp'] > 0)) && (!$GLOBALS['relationList'][$relationTypeOtherActual]['isUltimatum'])) {
      $from_points   = max(0, tribe_getMight($tag));
      $target_points = max(0, tribe_getMight($relationData['tag']));

      if (!tribe_isTopTribe($relationData['tag'])) {
        if (($relationInfo['targetSizeDiffDown'] > 0) &&
            ($from_points - $relationInfo['targetSizeDiffDown'] > $target_points )) {
          return -24;
        }
      }

      if (!tribe_isTopTribe($relationData['tag'])) {
        if (($relationInfo['targetSizeDiffUp'] > 0) &&
            ($from_points + $relationInfo['targetSizeDiffUp'] < $target_points )) {
          return -25;
        }
      }
    }
  }

  // if switching to the same relation of other clan towards us,
  // use their treaty's end_time!
  if ($relationType == $relation['other']['relationType'] && $relationType != 0) {
    $duration = 0;
    $end_time = $relation['other']['duration'];
  } else {
    $duration = $GLOBALS['relationList'][$relationTypeActual]['transitions'][$relationType]['time'];
    $end_time = 0;
  }

  if ($GLOBALS['relationList'][$relationFrom]['isPrepareForWar'] &&  $GLOBALS['relationList'][$relationTo]['isWar']) {
    $OurFame = $relation['own']['fame'];
    $OtherFame = $relation['other']['fame'];
  } else {
    $OurFame = 0;
    $OtherFame = 0;
  }

  if (!relation_setRelation($tag, $targetTribeInfo['tag'], $relationType, $duration, $end_time, $relation['own']['tribe_rankingPoints'], $relation['own']['target_rankingPoints'], $OurFame)) {
    return -3;
  }

  // calculate elo if war ended  
  if ($GLOBALS['relationList'][$relationType]['isWarWon']) {
    ranking_calculateElo($tag, tribe_getMight($tag), $relationData['tag'], tribe_getMight($relationData['tag']));
    ranking_updateWonLost($tag, $targetTribeInfo['tag'], false);
  } else if ($GLOBALS['relationList'][$relationType]['isWarLost']) {
    ranking_calculateElo($relationData['tag'], tribe_getMight($relationData['tag']), $tag, tribe_getMight($tag));
    ranking_updateWonLost($tag, $targetTribeInfo['tag'], true);
  }

  // insert history message
  if (isset($GLOBALS['relationList'][$relationType]['historyMessage'])) {
    relation_insertIntoHistory($tag, relation_prepareHistoryMessage($tag, $targetTribeInfo['tag'], $GLOBALS['relationList'][$relationType]['historyMessage']));
  }

  $relationName = $GLOBALS['relationList'][$relationType]['name'];
  tribe_sendTribeMessage($tag, TRIBE_MESSAGE_RELATION, "Haltung gegenüber {$targetTribeInfo['tag']} geändert",
    "Ihr Stammesanführer hat die Haltung Ihres Stammes gegenüber dem Stamm {$targetTribeInfo['tag']} auf $relationName geändert.");

  tribe_sendTribeMessage($targetTribeInfo['tag'], TRIBE_MESSAGE_RELATION, "Der Stamm $tag ändert seine Haltung",
    "Der Stammesanführer des Stammes $tag hat die Haltung seines Stammes ihnen gegenüber auf $relationName geändert.");

  // switch other side if necessary (and not at this type already)
  if (!$end_time && ($oST = $relationInfo['otherSideTo']) >= 0) {
    if (!relation_setRelation($targetTribeInfo['tag'], $tag, $oST, $duration, 0, $relation['other']['tribe_rankingPoints'], $relation['other']['target_rankingPoints'], $OtherFame)) {
      return -17;
    }

    // insert history
    if (isset($GLOBALS['relationList'][$oST]['historyMessage'])) {
      relation_insertIntoHistory($targetTribeInfo['tag'], relation_prepareHistoryMessage($tag, $targetTribeInfo['tag'], $GLOBALS['relationList'][$oST]['historyMessage']));
    }

    $relationName = $GLOBALS['relationList'][$oST]['name'];
    tribe_sendTribeMessage($targetTribeInfo['tag'], TRIBE_MESSAGE_RELATION, "Haltung gegenüber $tag geändert",
      "Die Haltung Ihres Stammes gegenüber dem Stamm $tag  wurde automatisch auf $relationName geändert.");

    tribe_sendTribeMessage($tag, TRIBE_MESSAGE_RELATION, "Der Stamm {$targetTribeInfo['tag']} ändert seine Haltung",
      "Der Stamm {$targetTribeInfo['tag']} hat die Haltung ihnen gegenüber automatisch auf $relationName geändert.");
  }

  tribe_generateMapStylesheet();

  return 7;
}

function relation_leaveTribeAllowed($tag) {
  $tribeRelations = relation_getRelationsForTribe($tag);

  if (!sizeof($tribeRelations)) {
    return false;
  }

  foreach ($GLOBALS['relationList'] as $relationTypeID => $relationType) {
    if ($relationType['dontLeaveTribe']) {
      foreach ($tribeRelations['own'] as $target => $relation) {
        if ($relation['relationType'] == $relationTypeID) {
          return false;
        }
      }
    }
  }

  return true;
}


function relation_getTribeHistory($tribe) {
  global $db;

  $sql = $db->prepare("SELECT * 
                       FROM " . TRIBE_HISTORY_TABLE . "
                       WHERE tribe LIKE :tribe
                       ORDER BY timestamp ASC");
  $sql->bindValue('tribe', $tribe, PDO::PARAM_STR);
  if (!$sql->execute()) return array();

  $history = $sql->fetchAll(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  return $history;
}

function relation_insertIntoHistory($tribe, $message) {
  global $db;

  $time = getUgaAggaTime(time());
  $month = getMonthName($time['month']);

  $sql = $db->prepare("INSERT INTO " . TRIBE_HISTORY_TABLE . " 
                       (tribe, ingameTime, message) 
                       VALUES (:tribe, :ingameTime, :message)");
  $sql->bindValue('tribe', $tribe, PDO::PARAM_STR);
  $sql->bindValue('ingameTime', "{$time['day']}. $month im Jahr {$time['year']}", PDO::PARAM_STR);
  $sql->bindValue('message', $message, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return false;
  }

  return true;
}

function relation_prepareHistoryMessage($tribe, $target, $message) {
  return str_replace("[TARGET]", $target,
         str_replace("[TRIBE]", $tribe, $message));
}

function tribe_validatePassword($password) {
  return preg_match('/^\w{6,}$/', unhtmlentities($password));
}

function tribe_validateTag($tag) {
  return preg_match('/^[a-zA-Z][a-zA-Z0-9\-]{0,7}$/', unhtmlentities($tag));
}

function tribe_SetTribeInvalid($tag) {
  global $db;

  $tribeRelations = relation_getRelationsForTribe($tag);
  if (!$tribeRelations)
    return 0;

  foreach ($tribeRelations['own'] as $target => $relation) {
    $relationData['tag'] = $target;
    if ($relation['relationType'] == 2) {
      // 2 = Krieg => Kapi
      $relationData['relationID'] =3 ;
      relation_processRelationUpdate($tag, $relationData, 1);
    } 
    elseif ($relation['relationType'] == 3) {
      ;// 3 = kapi, hier machen wir NIX
    }
    else {
      // Alles andere stellen wir auf nix 
      $sql = $db->prepare("DELETE FROM " . RELATION_TABLE . "
                           WHERE relationID= :relationID");
      $sql->bindValue('relationID', $relation['relationID'], PDO::PARAM_INT);
      $sql->execute();
    }
  }

  $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                       SET valid = '0', 
                         validatetime  = (NOW() + INTERVAL ".TRIBE_MINIMUM_LIVESPAN." SECOND) + 0 
                       WHERE tag = :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return false;
  }

  return tribe_sendTribeMessage($tag, TRIBE_MESSAGE_INFO, "Mitgliederzahl", "Ihr Stamm hat nicht mehr genug Mitglieder um Beziehungen eingehen zu dürfen.");
}

function tribe_SetTribeValid($tag) {
  global $db;

  $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                       SET valid = '1' 
                       WHERE tag = :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return false;
  }

  return tribe_sendTribeMessage($tag, TRIBE_MESSAGE_INFO, "Mitgliederzahl", "Ihr Stamm hat nun genug Mitglieder um Beziehungen eingehen zu dürfen.");
}

function tribe_getPoints($tag) {
  global $db;
  
  $sql = $db->prepare("SELECT points_rank FROM ". TRIBE_TABLE." WHERE tribe LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) return -1;

  $row = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  return (isset($row['points_rank'])) ? $row['points_rank'] : 0;
}

/*
 * this function returns the might (points_rank) for the given tribe.
 * the might are the tribe points WITHOUT fame.
 */
function tribe_getMight($tag) {
  global $db;
  
  $sql = $db->prepare("SELECT points_rank FROM ". RANKING_TRIBE_TABLE ."  WHERE tribe LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) return -1;

  $row = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  return (isset($row['points_rank'])) ? $row['points_rank'] : 0;
}

/**
 * calculate the fame according to the following formula:
 * basis * (V/S) * (V/S) * (S'/V')
 * this is bigger: if, winner had more points,
 * winner gained more points during the battle compared to looser
 */
function relation_calcFame($winner, $winnerOld, $looser, $looserOld) {
  
  $winner = $winner ? $winner : 1;
  $winner_old = $winner ? $winner : 1;
  $looser = $looser ? $looser : 1;
  $looser_old = $looser_old ? $looser_old : 1;

  return
    (100 + ($winnerOld + $looserOld) / 200) *         // basis points
    max(.125, min(8, ($looser / $winner) * ($looser / $winner) * ($winner_old / $looser_old)));
}

function relation_setRelation($from, $target, $relation, $duration, $end_time, $from_points_old, $target_points_old, $fame=0) {
  global $db;

  if (($from_points = tribe_getMight($from)) < 0) {
    $from_points = 0;
  }
  if (($target_points = tribe_getMight($target)) < 0) {
    $from_points = 0;
  }

  // have to remember the number of members of the other side?
  if ($GLOBALS['relationList'][$relation]['storeTargetMembers']) {
    $target_members = tribe_getNumberOfMembers($target);
  }

  if ($relation == 0) {
    $sql = $db->prepare("DELETE FROM " . RELATION_TABLE . " 
                         WHERE tribe = :tribe
                           AND tribe_target = :tribe_target");
    $sql->bindValue('tribe', $from, PDO::PARAM_STR);
    $sql->bindValue('tribe_target', $target, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }
  }
  else {
    $query =
      "REPLACE " . RELATION_TABLE .
      " SET tribe = '$from', ".
      ((isset($target_members) && $target_members != 0) ? "target_members = '$target_members', " : "").
      "tribe_target = '$target', ".
      "timestamp = NOW() +0, ".
      "relationType = '$relation', ".
      "tribe_rankingPoints = '$from_points', ".
      "target_rankingPoints = '$target_points', ".
      "attackerReceivesFame = '".
      $GLOBALS['relationList'][$relation]['attackerReceivesFame']."', ".
      "defenderReceivesFame = '".
      $GLOBALS['relationList'][$relation]['defenderReceivesFame']."', ".
      "defenderMultiplicator = '".
      $GLOBALS['relationList'][$relation]['defenderMultiplicator']."', ".
      "attackerMultiplicator = '".
      $GLOBALS['relationList'][$relation]['attackerMultiplicator']."', ".
      ($end_time ?
       "duration = '$end_time' " :
       "duration = (NOW() + INTERVAL '$duration' HOUR) + 0 ").", ".
       "fame ='$fame'";
    if (!$db->query($query)) {
      return false;
    }
  }

  // calculate the fame update if necessary
  if ($GLOBALS['relationList'][$relation]['fameUpdate'] != 0) {
    if ($GLOBALS['relationList'][$relation]['fameUpdate'] > 0) {
      $fame = relation_calcFame($from_points, $from_points_old,
                                $target_points, $target_points_old);
    }
    else if ($GLOBALS['relationList'][$relation]['fameUpdate'] < 0) {
      // calculate fame: first argument is winner!
      $fame = -1 * relation_calcFame($target_points, $target_points_old,
                                     $from_points, $from_points_old);
    }
    $sql = $db->prepare("UPDATE ". TRIBE_TABLE . "
                         SET fame = fame + :fame
                         WHERE tag LIKE :from");
    $sql->bindValue('fame', $fame, PDO::PARAM_INT);
    $sql->bindValue('from', $from, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return false;
    }
  }

  return 1;
}

function relation_getRelation($from, $target) {
  global $db;
  
  $sql = $db->prepare("SELECT *, DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, (NOW()+0) > duration AS changeable
                       FROM ". RELATION_TABLE. "
                       WHERE tribe LIKE :from
                         AND tribe_target LIKE :target");
  $sql->bindValue('from', $from, PDO::PARAM_STR);
  $sql->bindValue('target', $target, PDO::PARAM_STR);
  if (!$sql->execute()) return false;

  if (!($own = $sql->fetch(PDO::FETCH_ASSOC))) {
    $own = array(
      'tribe'        => $from,
      'tribe_target' => $target,
      'changeable'   => 1,
      'relationType' => 0,
      'tribe_rankingPoints'  => 0,
      'target_rankingPoints' => 0
    );
  }
  $sql->closeCursor();

  $sql = $db->prepare("SELECT *, DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, (NOW()+0) > duration AS changeable
                       FROM ". RELATION_TABLE . "
                       WHERE tribe LIKE :target
                         AND tribe_target LIKE :from");
  $sql->bindValue('from', $from, PDO::PARAM_STR);
  $sql->bindValue('target', $target, PDO::PARAM_STR);
  if (!$sql->execute()) return false;

  if (!($other = $sql->fetch(PDO::FETCH_ASSOC))) {
    $other = array(
      'tribe' => $target,
      'tribe_target' => $from,
      'changeable'   => 1,
      'relationType' => 0,
      'tribe_rankingPoints'  => 0,
      'target_rankingPoints' => 0
    );
  }
  $sql->closeCursor();

  return array("own" => $own, "other" => $other);
}



function relation_isPossible($to, $from) {
  return array_key_exists($to, $GLOBALS['relationList'][$from]['transitions']);
}



function relation_getRelationsForTribe($tag) {
  global $db;

  // get relations from $tag to other tribes
  $sql = $db->prepare("SELECT *,  DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, (NOW()+0) > duration AS changeable 
                       FROM ". RELATION_TABLE . "
                       WHERE tribe LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) return NULL;

  // copy result into an array
  $own = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $own[strtoupper($row['tribe_target'])] = $row;
  }
  $sql->closeCursor();

  // get relations from other tribes to $tag
  $sql = $db->prepare("SELECT *,  DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time
                       FROM ". RELATION_TABLE . "
                       WHERE tribe_target LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) return NULL;

  $other=array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $other[strtoupper($row['tribe'])] = $row;
  }
  $sql->closeCursor();

  return array("own" => $own, "other" => $other);
}

/*
 * returns an array of the current targets in war, the fame of both sides 
 * and both the actual percents and the estimated percents
 * content of the arrays are: target, fame_own, fame_target, percent_actual, 
 * percent_estimated, isForcedSurrenderTheoreticallyPossible, isForcedSurrenderPracticallyPossible, 
 * isForcedSurrenderPracticallyPossibleForTarget
 */
function relation_getWarTargetsAndFame($tag) {
  global $db;

  // first get the id of war
  $warId = 0;
  while( !($GLOBALS['relationList'][$warId]['isWar']) ){
    $warId++;
  }

  $prepareForWarId = 0;
//  while( !($relationList[$prepareForWarId]['isPrepareForWar']) ){
  while( !($GLOBALS['relationList'][$prepareForWarId]['isWar']) ){
    $prepareForWarId++;
  }

  $minTimeForForceSurrenderHours = $GLOBALS['relationList'][$warId]['minTimeForForceSurrenderHours'];
  $maxTimeForForceSurrenderHours = $GLOBALS['relationList'][$warId]['maxTimeForForceSurrenderHours'];

  // generate query for MySQL, get wars
  $sql = $db->prepare("SELECT r_target.tribe as target,
                         r_own.fame as fame_own,
                         r_target.fame as fame_target,
                         ROUND((
                           (GREATEST(0, r_own.fame) / (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame) + ((GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame)) <= 0 )))
                           + (r_own.fame > r_target.fame AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp)) / 3600 >= :maxTimeForForceSurrenderHours AND r_own.fame <= 0 AND r_target.fame <= 0)) * 100, 2)
                           as percent_actual,
                         ROUND(GREATEST((1 - ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp)) / 3600 - :minTimeForForceSurrenderHours) /
                           (2 * (:maxTimeForForceSurrenderHours - :minTimeForForceSurrenderHours) )) * 100, 50) , 2)
                           as percent_estimated,
                         ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= :minTimeForForceSurrenderHours) as isForcedSurrenderTheoreticallyPossible,
                         ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= :minTimeForForceSurrenderHours) 
                           AND ((GREATEST(0, r_own.fame) / (GREATEST(0, r_own.fame) + 
                           GREATEST(0, r_target.fame) + ( (GREATEST(0, r_own.fame) + 
                           GREATEST(0, r_target.fame)) <= 0 )) ) + (r_own.fame > r_target.fame 
                           AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= :maxTimeForForceSurrenderHours
                           AND r_own.fame <= 0 AND r_target.fame <= 0) ) >  GREATEST((1 - ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp)) /
                           3600 - :minTimeForForceSurrenderHours) / (2 * (:maxTimeForForceSurrenderHours - :minTimeForForceSurrenderHours) )), 0.5) 
                           as isForcedSurrenderPracticallyPossible,
                         ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= :minTimeForForceSurrenderHours) 
                           AND ((GREATEST(0, r_target.fame) / (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame)
                           + ((GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame)) <= 0 )))
                           + (r_target.fame > r_own.fame AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= :maxTimeForForceSurrenderHours
                           AND r_own.fame <= 0 AND r_target.fame <= 0) ) >  GREATEST((1 - ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/
                           3600 - :minTimeForForceSurrenderHours) / (2 * (:maxTimeForForceSurrenderHours - :minTimeForForceSurrenderHours) )), 0.5) 
                           as isForcedSurrenderPracticallyPossibleForTarget
                       FROM ". RELATION_TABLE ." r_own, ". RELATION_TABLE ." r_target
                       WHERE r_own.tribe LIKE r_target.tribe_target
                         AND r_target.tribe LIKE r_own.tribe_target
                         AND r_target.relationType = r_own.relationType
                         AND r_own.relationType = '$warId'
                         AND r_own.tribe LIKE '$tag'
                       ORDER BY r_own.timestamp ASC");
  $sql->bindValue(':maxTimeForForceSurrenderHours', $maxTimeForForceSurrenderHours, PDO::PARAM_INT);
  $sql->bindValue(':minTimeForForceSurrenderHours', $minTimeForForceSurrenderHours, PDO::PARAM_INT);
  $sql->execute();

  // copy result into an array
  $warTargets = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $warTargets[strtoupper($row['target'])] = $row;
  }
  $sql->closeCursor();

  return $warTargets;
}

function relation_forceSurrender($tag, $relationData) {
  // check conditions
  if(!$relationData){
    return -17;
  }

  if (strcasecmp($tag, $relationData['tag']) == 0) {
    return -14;
  }

  if (!($ownTribeInfo = tribe_getTribeByTag($tag))) {
    return -15;
  }

  if (!($targetTribeInfo = tribe_getTribeByTag($relationData['tag']))) {
    return -15;
  }

  $target = $relationData['tag'];
  $tribeWarTargets = relation_getWarTargetsAndFame($tag);

  if(!($relation = $tribeWarTargets[strtoupper($target)])) {
    return -17; 
  }

  if(!$relation['isForcedSurrenderPracticallyPossible']) {
    return -26;
  }

  // find surrender
  $surrenderId = 0;
  while( !($GLOBALS['relationList'][$surrenderId]['isWarLost']) ){
    $surrenderId++;
  }

  $relationDataLooser = array('tag' => $tag, 'relationID' => $surrenderId);

  // refresh relations                              
  $messageID = relation_processRelationUpdate($target, $relationDataLooser);
  if ($messageID < 0) {
    return $messageID;
  }

  // tribe messages for forced surrender
  tribe_sendTribeMessage($ownTribeInfo['tag'], TRIBE_MESSAGE_RELATION, "Zwangskapitulation über $target", "Ihr Stammesanführer hat den Stamm $target zur Aufgabe gezwungen.");
  tribe_sendTribeMessage($targetTribeInfo['tag'], TRIBE_MESSAGE_RELATION, "Zwangskapitulation gegen $tag", "Der Stammesanführer des Stammes $tag hat ihren Stamm zur Aufgabe gezwungen.");

  return $messageID;
}

function tribe_processAdminUpdate($tag, $data) {
  global $db;

  $auth = new auth;

  if (!tribe_validatePassword($data['password'])){
    return -12;
  }

  // check if avatar is a image
  if (!empty($data['avatar'])) {
    $avatarInfo = checkAvatar($data['avatar']);
    if (!$avatarInfo) {
      return -13;
    } else {
      $data['avatar'] = $avatarInfo;
    }
  }

  $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                        SET name = :name,
                          password = :password,
                          description = :description,
                          avatar = :avatar
                        WHERE tag = :tag");
  $sql->bindValue('name', $data['name'], PDO::PARAM_STR);
  $sql->bindValue('password', $data['password'], PDO::PARAM_STR);
  $sql->bindParam('description', $data['description']);
  $sql->bindValue('avatar', $data['avatar'], PDO::PARAM_STR);
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 6;
  }

  return 5;
}


/**
 * returns all tribes in an associative array (tag => data_array)
 */
function tribe_getAllTribes() {
  global $db;
  
  $sql = "SELECT *, (validatetime < NOW() + 0) AS ValidationTimeOver FROM " . TRIBE_TABLE;
  if (!$sql = $db->query($sql)) {
    return -1;
  }

  $tribes = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $tribes[$row['tag']] = $row;
  }
  $sql->closeCursor();

  return $tribes;
}

function tribe_getAllMembers($tag) {
  global $db;

  $auth = new auth();

  $members = array();
  $sql = $db->prepare("SELECT p.playerID, p.name, p.auth, s.lastAction 
                       FROM ". PLAYER_TABLE ." p
                         LEFT JOIN ". SESSION_TABLE ." s ON s.playerID = p.playerID
                       WHERE tribe LIKE :tag
                       ORDER BY name ASC");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) return array();

  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $row['lastAction'] = date("d.m.Y H:i:s", time_timestampToTime($row['lastAction']));
    $members[$row['playerID']] = $row;

    $userAuth = unserialize($row['auth']);
    $members[$row['playerID']]['tribeAuth'] = $auth->getAllTypePermission('tribe', $userAuth['tribe']);
  }
  $sql->closeCursor();

  return $members;
}

function tribe_getPlayerList($tag, $getGod=false, $getCaves=false) {
  global $db;

  $select = '';
  if ($getGod) {
    foreach (Config::$gods as $god) {
      $select .= ', p.' . $god . ' as ' . $god;
    }
    foreach (Config::$halfGods as $halfGod) {
      $select .= ', p.' . $halfGod . ' as ' . $halfGod;
    }
  }

  $return = array();
  $sql = $db->prepare("SELECT p.playerID, p.name, p.awards, r.rank, r.average AS points, r.caves, r.religion, r.fame, r.fame as kp {$select}
                       FROM ". PLAYER_TABLE ." p
                         LEFT JOIN ".RANKING_TABLE ." r
                           ON r.playerID = p.playerID
                       WHERE p.tribe LIKE :tag
                       ORDER BY r.rank ASC");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) return array();
  while ($member = $sql->fetch(PDO::FETCH_ASSOC)) {
    $return[$member['playerID']] = $member;

    if ($getCaves) { $return[$member['playerID']]['caves'] = array(); }
  }
  $sql->closeCursor();

  if ($getGod) {
    foreach ($GLOBALS['scienceTypeList'] AS $value)
    {
      $ScienceFieldsName[$value->dbFieldName] = $value->name;
    }

    foreach ($return as $id => $data) {
      $return[$id]['god'] = 'keinen';
      $return[$id]['halfgod'] = 'keinen';

      foreach (Config::$gods as $god) {
        if ($return[$id][$god] > 0) {
          $return[$id]['god'] = $ScienceFieldsName[$god];
        }
      }
      foreach (Config::$halfGods as $halfGod) {
        if ($return[$id][$halfGod] > 0) {
          $return[$id]['halfGod'] = $ScienceFieldsName[$halfGod];
        }
      }
    }
  }

  if ($getCaves) {
    $sql = $db->prepare("SELECT caveID, xCoord, yCoord, name, playerID
                         FROM ". CAVE_TABLE ."
                         WHERE playerID IN ('" . implode("', '", array_keys($return)) . "')");
    if (!$sql->execute()) return array();
    while ($caves = $sql->fetch(PDO::FETCH_ASSOC)) {
      $return[$caves['playerID']]['caves'][] = $caves;
    }
    $sql->closeCursor();
  }

  return $return;
}

/**
 * is the tribe old enough, to be deleted?
 */
function tribe_isDeletable($tag) {
  global $db;

  // GOD ALLY is not deletable
  if (!strcmp($tag, GOD_ALLY)) {
    return 0;
  }

  $sqlo = $db->prepare("SELECT *
                        FROM " . TRIBE_TABLE . "
                        WHERE tag LIKE :tag
                          AND validatetime  < (NOW() - INTERVAL ".TRIBE_MINIMUM_LIVESPAN." SECOND) + 0");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()){
    return 0;
  }

  return ($sql->fetch() ? 1 : 0);
}

/**
 * returns the number of the members of a given clan
 * -1 => ERROR !!!!
 */
function tribe_getNumberOfMembers($tag) {
  global $db;
  
  $sql = $db->prepare("SELECT COUNT(playerID) AS members 
                       FROM ". PLAYER_TABLE ."
                       WHERE tribe LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) return -1;
  
  $row = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();
  
  return (isset($row['members'])) ? $row['members'] : -1;
}

function tribe_getTribeByTag($tag) {
  global $db;
  
  $sql = $db->prepare("SELECT t.*, p.name AS leaderName
                       FROM ". TRIBE_TABLE ." t
                         LEFT JOIN ". PLAYER_TABLE ." p ON t.leaderID = p.playerID
                       WHERE t.tag LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) return null;

  $result = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  if (empty($result)) {
    return null;
  }

  $result['description'] = str_replace('<br />', '\n', $result['description']);
  $result['avatar'] = @unserialize($result['avatar']);
  $result['wonderLocked'] = (empty($result['wonderLocked'])) ? array() : @unserialize($result['wonderLocked']);
  if (!is_array($result['wonderLocked'])) {
    $result['wonderLocked'] = array();
  }

  return $result;
}

function tribe_makeLeader($playerID, $tag) {
  global $db;
  
  $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                       SET leaderID = :playerID
                       WHERE tag LIKE :tag ");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 0;
  }

  return 1;
}

function tribe_unmakeLeader($playerID, $tag) {
  global $db;
  
  $sql = $db->prepare("UPDATE ". TRIBE_TABLE . "
                       SET leaderID = 0
                       WHERE tag LIKE :tag ");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 0;
  }

  return 1;
}

function tribe_joinTribe($playerID, $tag) {
  global $db;
  
  $sql = $db->prepare("UPDATE ". PLAYER_TABLE . "
                      SET tribe = :tag
                      WHERE playerID = :playerID
                        AND tribe = ''");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return false;
  }

  return true;
}

function tribe_leaveTribe($playerID, $tag) {
  global $db;
  
  $sql = $db->prepare("UPDATE ". PLAYER_TABLE . "
                       SET tribe = ''
                       WHERE playerID = :playerID
                         AND tribe LIKE :tag");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 0;
  }

  $sql = $db->prepare("DELETE FROM ". ELECTION_TABLE . "
                       WHERE voterID = :playerID
                         OR playerID LIKE :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sql->execute()) {
    return 0;
  }

  return 1;
}

function ranking_sort(){
  global $db;
  
  $sql = "SELECT rankingID FROM ". RANKING_TRIBE_TABLE ." ORDER BY points_rank DESC, -1*(1+playerAverage)";
  if (!$sql = $db->query($sql)) {
    return 0;
  }

  $count = 1;
  while($row = $sql->fetch()) {
    $sql = $db->prepare("UPDATE ". RANKING_TRIBE_TABLE . "
                        SET rank = :rank
                        WHERE rankingID = :rankingID");
    $sql->bindValue('rank', $count++, PDO::PARAM_INT);
    $sql->bindValue('rankingID', $row['rankingID'], PDO::PARAM_INT);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return 0;
    }
  }
}

function tribe_restoreOldRanking($tag, $pw) {
  global $db;
  
  $sql = $db->prepare("SELECT * FROM ". OLD_TRIBES_TABLE . "
                       WHERE tag LIKE :tag
                         AND password = :pw
                         AND used = 0
                       LIMIT 1");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  $sql->bindValue('pw', $pw, PDO::PARAM_STR);
  
  if (!$sql->execute()) return 0;
  $row = $sql->fetch();
  if (!$row) return 1; // bail out if no tribe is found, but with positive return value
  
  $sql = $db->prepare("UPDATE ". RANKING_TRIBE_TABLE . "
                       SET points_rank = :points_rank
                       WHERE tribe LIKE :tag'");
  $sql->bindValue('points_rank', $row['points_rank'], PDO::PARAM_INT);
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 0;
  }

  return 1;
}

function tribe_removeTribeFromOldRanking($tag) {
  global $db;
  
  $sql = $db->prepare("UPDATE ". OLD_TRIBES_TABLE . "
                       SET used = 1
                       WHERE tag = :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) {
    return 0;
  }

  return 1;
}

function tribe_createRanking($tag) {
  global $db;
  
  $sql = $db->prepare("SELECT MAX(rank) +1 AS newrank FROM " . RANKING_TRIBE_TABLE);
  if (!$sql->execute()) {
    return -1;
  }

  if (!($row = $sql->fetch())) {
    return -2;
  }

  $newrank = $row['newrank'];
  if (is_null($newrank))
    $newrank = 1;

  $sql = $db->prepare("INSERT INTO ". RANKING_TRIBE_TABLE ."
                         (tribe, rank, points_rank)
                       VALUES
                         (:tag, :newrank, 1500)");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  $sql->bindValue('newrank', $newrank, PDO::PARAM_INT);
  
  if(!$sql->execute()) {
    return 0;
  }
  return 1;
}

function tribe_createTribe($tag, $name, $password, $leaderID) {
  global $db;
  
  $sql = $db->prepare("INSERT INTO ". TRIBE_TABLE . "
                         (tag, name, leaderID, created, password, governmentID, validatetime, valid)
                       values 
                         (:tag, :name, 0, NOW() + 0, :password, 1, ((NOW() + INTERVAL " . TRIBE_MINIMUM_LIVESPAN . " SECOND ) + 0),0)");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  $sql->bindValue('name', $name, PDO::PARAM_STR);
  $sql->bindValue('password', $password, PDO::PARAM_STR);
  if (!$sql->execute()) {
    return false;
  }

  if(!tribe_createRanking($tag)) {
    return false;
  }

  if (!tribe_joinTribe($leaderID, $tag)) {
    return false;
  }

  if (!tribe_makeLeader($leaderID, $tag)) {
    tribe_leaveTribe($leaderID, $tag);
    return false;
  }

  return 1;
}


function tribe_deleteTribe($tag, $FORCE = 0) {
  global $db;

  if (! $FORCE && ! relation_leaveTribeAllowed($tag)) {
    return 0;
  }
  if (!($tribe = tribe_getTribeByTag($tag))) {
    return 0;
  }

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

  // get relations
  if (!($tribeRelations = relation_getRelationsForTribe($tag))) {
    return 0;
  }
  // end others relations
  foreach ($tribeRelations['other'] AS $otherTag => $relation){
    $relationType = $GLOBALS['relationList'][$relation['relationType']];
    $oDST = $relationType['onDeletionSwitchTo'];
    if ($oDST >= 0){

      // die relation umschalten und zielrelation temporaer eintragen; sie wird
      // am ende dieser funktion ohne weiteres umschalten geloescht. Das
      // temporaere umschalten ist aber noetig, um zum beispiel die
      // ruhmberechnung im siegfall oder aehnliche effekte, die an
      // relation_setRelation haengen abzuarbeiten.

      if (!relation_setRelation($otherTag, $tag, $oDST, 0, 0,
                                $relation['tribe_rankingPoints'],
                                $relation['target_rankingPoints']))
        return 0;

      // insert history
      if (isset($GLOBALS['relationList'][$oDST]['historyMessage'])){
        relation_insertIntoHistory($otherTag, relation_prepareHistoryMessage($tag, $otherTag, $GLOBALS['relationList'][$oDST]['historyMessage']));
      }
      // insert tribe message
      $relationName = $GLOBALS['relationList'][$oDST]['name'];
      tribe_sendTribeMessage($otherTag, TRIBE_MESSAGE_RELATION, "Haltung gegenüber $tag geändert",
        "Die Haltung Ihres Stammes gegenüber dem Stamm $tag  wurde automatisch auf $relationName geändert.");
    }
  }

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

  if ($tribe['leaderID'] && !tribe_unmakeLeader($tribe['leaderID'], $tag))
  {
    return 0;
  }
  if (($members = tribe_getAllMembers($tag)) < 0) {
    return 0;
  }

  foreach ($members AS $playerID => $playerData) {
    if (! tribe_leaveTribe($playerID, $tag)) {
      return 0;
    }

    if (! tribe_setBlockingPeriodPlayerID($playerID)) {
      return 0;
    }

    $messagesClass = new Messages;
    $messagesClass->sendSystemMessage($playerID,
            8,
            "Auflösung des Stammes",
            "Ihr Stamm $tag wurde soeben aufgelöst. ".
            "Sollten Sie Probleme mit dem ".
            "Stammesmenü haben, loggen Sie sich ".
            "bitte neu ein.");

    Player::addHistoryEntry($playerID, sprintf(_("verlässt den Stamm '%s'"), $tag));
  }

  $sql = $db->prepare("DELETE FROM ". TRIBE_TABLE . " WHERE tag LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  if (!$sql->execute()) {
    return 0;
  }

  $sql = $db->prepare("DELETE FROM ". RELATION_TABLE . "
                       WHERE tribe LIKE :tag
                         OR tribe_target LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  if (!$sql->execute()) {
    return 0;
  }

  $sql = $db->prepare("DELETE FROM ". TRIBE_MESSAGE_TABLE . " WHERE tag LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  if (!$sql->execute()) {
    return 0;
  }

  $sql = $db->prepare("DELETE FROM ". TRIBE_HISTORY_TABLE . " WHERE tribe LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  if (!$sql->execute()) {
    return 0;
  }

  $sql = $db->prepare("DELETE FROM ". ELECTION_TABLE . " WHERE tribe LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  if (!$sql->execute()) {
    return 0;
  }

  $sql = $db->prepare("SELECT rank FROM ". RANKING_TRIBE_TABLE . " WHERE tribe LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  if (!$sql->execute()) {
    return 0;
  }

  if (!($row = $sql->fetch())) {
    return 0;
  }
  $rank = $row['rank'];

  $sql = $db->prepare("DELETE FROM ". RANKING_TRIBE_TABLE . " WHERE tribe LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  
  if (!$sql->execute()) {
      return 0;
  }

  $sql = $db->prepare("UPDATE ". RANKING_TRIBE_TABLE ." SET rank = rank - 1 ".
    "WHERE rank > :rank");
  $sql->bindValue('rank', $rank, PDO::PARAM_STR);
  
  if (!$sql->execute()) {
      return 0;
  }

  Player::addHistoryEntry($tribe['leaderID'], sprintf(_("löst den Stamm '%s' auf"), $tag));

  return 1;
}

function tribe_recalcLeader($tag, $oldLeaderID) {

  // find the new leader
  if(!($government = government_getGovernmentForTribe($tag))) {
    return -1;
  }

  $det = $GLOBALS['governmentList'][$government['governmentID']]['leaderDeterminationID'];

  switch ($det) {
    case 1:
      $newLeadership = tribe_recalcLeader1($tag);
      break;
    case 2:
      $newLeadership = tribe_recalcLeader2($tag);
      break;
  }
  if (!is_array($newLeadership)) {
    return $newLeadership;
  }

  // change the leader
  return tribe_ChangeLeader($tag, $newLeadership, $oldLeaderID);
}

function tribe_ChangeLeader($tag, $newLeadership, $oldLeaderID) {
  if ($newLeadership[0] == $oldLeaderID) {
    return 0;  //nothing changed
  }

  if ($newLeadership[0] <> $oldLeaderID) {
    if ($oldLeaderID && !tribe_unmakeLeader($oldLeaderID, $tag)) {
      return -2;
    }
    if ($newLeadership[0] && !tribe_makeLeader($newLeadership[0], $tag)) {
      return -3;
    }
  } 

  tribe_SendMessageLeaderChanged($tag, $newLeadership);

  return $newLeadership;
}


/**
 * Send Message for Tribe Leadership change
 */
function tribe_SendMessageLeaderChanged($tag, $newLeadership) {
  if (!$newLeadership[0]) {
    tribe_sendTribeMessage($tag, TRIBE_MESSAGE_LEADER, "Stammesführung",
      "Ihr Stamm hat momentan keinen Anführer mehr");
  }

  $player = getPlayerByID($newLeadership[0]);
  $newLeadershipName = $player ? $player['name'] : $newLeadership[0];
  if ($newLeadership[0] && !$newLeadership[1]) {
    tribe_sendTribeMessage($tag, TRIBE_MESSAGE_LEADER, "Stammesführung",
      "Ihr Stamm hat eine neue Stammesführung:\nStammesanführer: ".$newLeadershipName);
  }

  if ($newLeadership[0] && $newLeadership[1]) {
    tribe_sendTribeMessage($tag, TRIBE_MESSAGE_LEADER, "Stammesführung",
      "Ihr Stamm hat eine neue Stammesführung:\nStammesanführer: ".$newLeadershipName);
  }
}


/**
 * recalc the leader for government ID 1
 */
function tribe_recalcLeader1($tag) {
  global $db;

  $sql = $db->prepare("SELECT p.playerID, p.name
                       FROM ". PLAYER_TABLE ." p
                         LEFT JOIN ".RANKING_TABLE." r ON p.playerID = r.playerID
                       WHERE p.tribe LIKE :tag
                         AND r.playerID IS NOT NULL
                       ORDER BY r.rank ASC
                       LIMIT 0, 2");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  if (!$sql->execute()) {
    return -1;
  }

  $result = array();
  $result[0]=0;
  $result[1]=0;
  $i=0;
  while ($row = $sql->fetch()) {
    $result[$i]=$row['playerID'];
    $i+=1;
  }
  return $result;
}

/**
 * recalc the leader for government ID 2
 */
function tribe_recalcLeader2($tag) {
  global $db;
  
  $sql = $db->prepare("SELECT e.playerID, COUNT(e.voterID) AS votes
                       FROM ". ELECTION_TABLE ." e
                         LEFT JOIN ". PLAYER_TABLE ." p ON p.playerID = e.playerID
                       WHERE e.tribe like :tag
                       GROUP BY e.playerID, p.name
                       ORDER BY votes DESC
                       LIMIT 0,1");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) {
    return -1;
  }
  if (!($row = $sql->fetch())) {
    return array(0 => 0, 1 => 0); // no leader!
  }

  if ($row['votes'] <=  tribe_getNumberOfMembers($tag) / 2)
  {          // more than 50% ?
    return array(0 => 0, 1 => 0); // no leader!
  }

  return array(0 => $row['playerID'], 1 => 0);
}


function tribe_processJoin($playerID, $tag, $password) {
  global $db;

  if (!tribe_changeTribeAllowedForPlayerID($playerID)) {
    return -10;
  }
  if (!relation_leaveTribeAllowed($tag) ) {
    return -6;
  }

  $sql = $db->prepare("SELECT tag
                       FROM ". TRIBE_TABLE . "
                       WHERE tag LIKE :tag
                         AND password = BINARY :password");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  $sql->bindValue('password', $password, PDO::PARAM_STR);
  if (!$sql->rowCountSelect()) {
    return -1;
  }

  if (!($player = getPlayerByID($playerID))) {
    return -3;
  }

  $tribeData = tribe_getTribeByTag($tag);

  if ((int) TRIBE_MAXIMUM_SIZE > 0) {
    $sql = $db->prepare("SELECT count(*) < " . (int) TRIBE_MAXIMUM_SIZE . " as IsOk 
                         FROM " . PLAYER_TABLE . "
                         WHERE tribe LIKE :tag");
    $sql->bindValue('tag', $tribeData['tag'], PDO::PARAM_STR);
    if (!$sql->execute()) {
      return -7;
    }
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $sql->closeCursor();

    if (!$row['IsOk']) {
      return -7;
    }
  }

  if (!tribe_joinTribe($playerID, $tribeData['tag'])) {
    return -3;
  }

  tribe_setBlockingPeriodPlayerID($playerID);

  Player::addHistoryEntry($playerID, sprintf(_("tritt dem Stamm '%s' bei"), $tribeData['tag']));

  tribe_sendTribeMessage($tribeData['tag'], TRIBE_MESSAGE_MEMBER, "Spielerbeitritt", "Der Spieler {$player['name']} ist soeben dem Stamm beigetreten.");

  return 1;
}

function tribe_processJoinFailed () {
  return -14;
}

function tribe_setBlockingPeriodPlayerID($playerID) {
  global $db;
  
  $sql = $db->prepare("UPDATE ". PLAYER_TABLE . "
                       SET tribeBlockEnd = (NOW() + INTERVAL ". TRIBE_BLOCKING_PERIOD_PLAYER." SECOND)+0
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return false;
  }

  return true;
}

function tribe_changeTribeAllowedForPlayerID($playerID) {
  global $db;
  
  $sql = $db->prepare("SELECT (tribeBlockEnd > NOW()+0) AS blocked
                       FROM ". PLAYER_TABLE . "
                       WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_STR);
  if (!$sql->execute()) {
    return false;
  }

  $row = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  return $row['blocked'] != 1;
}


function tribe_processLeave($playerID, $tag, $FORCE = 0) {
  if (!$FORCE && !relation_leaveTribeAllowed($tag)) {
    return -2;
  }

  if (!$FORCE && !tribe_changeTribeAllowedForPlayerID($playerID)) {
    return -3;
  }

  if (tribe_isLeader($playerID, $tag)) {
    if (!$FORCE && !tribe_unmakeLeader($playerID, $tag)) {
      return -4;
    }
  }

  if (!($player = getPlayerByID($playerID))) {
    return -5;
  }
  if (!tribe_leaveTribe($playerID, $tag)) {
    return -5;
  }

  Player::addHistoryEntry($playerID, sprintf(_("verläßt den Stamm '%s'"), $tag));

  tribe_setBlockingPeriodPlayerID($playerID);

  tribe_sendTribeMessage($tag, TRIBE_MESSAGE_MEMBER, "Spieleraustritt", "Der Spieler {$player['name']} ist soeben aus dem Stamm ausgetreten.");

  if (tribe_getNumberOfMembers($tag) == 0) {  // tribe has to be deleted
    tribe_deleteTribe($tag, $FORCE);
    return 2;
  }

  return 1;
}

function tribe_processKickMember($playerID, $tag) {

  if (empty($playerID)) {
    return -38;
  }

  if (tribe_isLeader($playerID, $tag)) {
    return -39;
  }

  // do not kick in wartime
  if (!relation_leaveTribeAllowed($tag))
    return -40;

  // blocked
  if (!tribe_changeTribeAllowedForPlayerID($playerID)) {
    return -3;
  }

  // get player
  $player = getPlayerByID($playerID);

  // no such player
  if (!$player) {
    return -41;
  }

  // remove player
  if (!tribe_leaveTribe($playerID, $tag)) {
    return -41;
  }

  Player::addHistoryEntry($playerID, sprintf(_("wird aus dem Stamm '%s' geworfen"), $tag));

  // block player
  tribe_setBlockingPeriodPlayerID($playerID);

  tribe_sendTribeMessage($tag, TRIBE_MESSAGE_MEMBER, "Spieler rausgeschmissen", "Der Spieler {$player['name']} wurde soeben vom Anführer aus dem Stamm ausgeschlossen.");

  $messagesClass = new Messages;
  $messagesClass->sendSystemMessage($playerID, 8, "Stammausschluss.", "Sie wurden aus dem Stamm $tag ausgeschlossen. Bitte loggen Sie sich aus und melden Sie sich wieder an, damit das Stammesmenü bei Ihnen wieder richtig funktioniert.");

  return 13;
}

function tribe_processSendTribeIngameMessage($leaderID, $tag, $message) {
  global $db;

  // init messages class
  $messagesClass = new Messages;

  // get all members
  $sql = $db->prepare("SELECT name FROM ". PLAYER_TABLE ." WHERE tribe LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  if(!$sql->execute()) {
    return -7;
  }

  while ($member = $sql->fetch(PDO::FETCH_ASSOC)) {
    if(!$messagesClass->insertMessageIntoDB($member['name'], "Nachricht vom Stammesanführer", $message, true, true)) {
      return -7;
    }
  }

  return 3;
}

function tribe_processSendTribeMessage($leaderID, $tag, $message) {

  if (!tribe_sendTribeMessage($tag, TRIBE_MESSAGE_LEADER, "Nachricht vom Stammesanführer", $message)) {
    return -7;
  }

  return 3;
}

function tribe_sendTribeMessage($tag, $type, $heading, $message) {
  global $db;

  $sql = $db->prepare("INSERT INTO " . TRIBE_MESSAGE_TABLE . " 
                         (tag, messageClass, messageSubject, messageText, messageTime) 
                       VALUES
                         (:tag, :messageClass, :messageSubject, :messageText, NOW()+0)");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  $sql->bindValue('messageClass', $type, PDO::PARAM_INT);
  $sql->bindValue('messageSubject', $heading, PDO::PARAM_STR);
  $sql->bindValue('messageText', $message, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return false;
  }

  return true;
}

function tribe_getTribeMessages($tag) {
  global $db;
  
  $sql = $db->prepare("SELECT *, DATE_FORMAT(messageTime, '%d.%m.%Y %H:%i') AS date 
                       FROM ". TRIBE_MESSAGE_TABLE . "
                       WHERE tag LIKE :tag
                       ORDER BY messageTime DESC
                       LIMIT 0, 30");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()){
    return null;
  }

  $messages = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $messages[$row['tribeMessageID']] = $row;
  }
  $sql->closeCursor();

  return $messages;
}

function tribe_processCreate($leaderID, $tag, $password, $restore_rank = false) {
  global $db;
  
  if (!tribe_changeTribeAllowedForPlayerID($leaderID)) {
    return -10;
  }

  $sql = $db->prepare("SELECT name
                       FROM ". TRIBE_TABLE . "
                       WHERE tag LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if ($sql->rowCountSelect() > 0) {
    return -4;
  }

  if (!tribe_createTribe($tag, $tag, $password, $leaderID)) {
    return -5;
  }

  if ($restore_rank) {
    if (!tribe_restoreOldRanking($tag, $password)) {
      return -1;
    }
  }

  if (!tribe_removeTribeFromOldRanking($tag)) {
    return -1;
  }

  Player::addHistoryEntry($leaderID, sprintf(_("gründet den Stamm '%s'"), $tag));

  return 2;
}

function tribe_setPassword($tag, $password) {
  global $db;
  
  $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                       SET password = :password
                       WHERE tag LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  $sql->bindValue('password', $password, PDO::PARAM_STR);

  if (!$sql->execute()) {
    return 0;
  }

  return 1;
}

function tribe_processChangePassword($tag, $password) {
  return tribe_setPassword($tag, $password) ? 0 : -7;
}

function tribe_getTagOfPlayerID($playerID) {
  global $db;
  
  $sql= $db->prepare("SELECT tribe
                      FROM ". PLAYER_TABLE . "
                      WHERE playerID = :playerID ");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_STR);

  if (!$sql->execute()) {
    return 0;
  }
  if (!($row = $sql->fetch())) {
    return 0;
  }
  return $row['tribe'];
}

function tribe_getLeaderID($tribe) {
  global $db;
  
  $sql = $db->prepare("SELECT leaderID
                       FROM ". TRIBE_TABLE . "
                       WHERE tag LIKE :tribe ");
  $sql->bindValue('tribe', $tribe, PDO::PARAM_STR);

  if (!$sql->execute()) {
    return 0;
  }
  if (!$row = $sql->fetch()) {
    return 0;
  }
  return $row['leaderID'];
}

function tribe_isLeader($playerID, $tribe) {
  global $db;
  
  $sql = $db->prepare("SELECT name
                       FROM ". TRIBE_TABLE . "
                       WHERE tag LIKE :tribe
                         AND leaderID = :playerID");
  $sql->bindValue('tribe', $tribe, PDO::PARAM_STR);
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if ($sql->rowCountSelect() == 0) {
    return 0;
  }

  return 1;
}

function tribe_generateMapStylesheet() {
  global $db;

  if ($_SESSION['player']->tribe == '')
    return;

  $outfilename = "./images/temp/tribe_".$_SESSION['player']->tribe.".css";
  $outfile     = @fopen($outfilename, "wb");

  if (!$outfile)
    die("Could not create file!");

  $sql = $db->prepare("SELECT *
                       FROM ". RELATION_TABLE . "
                       WHERE tribe = :tribe
                         OR tribe_target = :tribe");
  $sql->bindValue('tribe', $_SESSION['player']->tribe, PDO::PARAM_STR);

  fwrite($outfile, "a.t_".$_SESSION['player']->tribe." {\n");
  fwrite($outfile, "  width: 100%;\n");
  fwrite($outfile, "  border-top: 2px solid darkgreen;\n");
  fwrite($outfile, "}\n\n");

  if ($sql->execute()) {
    while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
      fwrite($outfile, "a.t_".($row['tribe'] == $_SESSION['player']->tribe ? $row['tribe_target'] : $row['tribe'])." {\n");
      fwrite($outfile, "  width: 100%;\n");
      fwrite($outfile, "  border-top: ");
      switch ($row['relationType']) {
        case 0:  // keine
          fwrite($outfile, "0px solid transparent");
          break;
        case 1:  // Ulti
          fwrite($outfile, "2px dotted red");
          break;
        case 2:  // Krieg
          fwrite($outfile, "2px solid red");
          break;
        case 3:  // Kapitulation
          fwrite($outfile, "0px solid transparent");
          break;
        case 4:  // Besatzung
          fwrite($outfile, "0px solid transparent");
          break;
        case 5:  // Waffenstillstand
          fwrite($outfile, "2px dashed blue");
          break;
        case 6:  // NAP
          fwrite($outfile, "2px solid blue");
          break;
        case 7:  // Bündnis
          fwrite($outfile, "2px solid green");
          break;
      }
      fwrite($outfile, "\n}\n\n");
    }
  }
  fclose($outfile);
}

function relation_deleteRelations($tag) {
  global $db;

  $sql = $db->prepare("DELETE FROM ". RELATION_TABLE . "
                       WHERE tribe LIKE :tag
                         OR tribe_target LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  return $sql->execute();
}


function ranking_calculateElo($winnertag, $winnerpoints, $losertag, $loserpoints) {
  global $db;
  
  // get actual points
  $winnerpoints_actual = tribe_getMight($winnertag);
  $loserpoints_actual = tribe_getMight($losertag);
  $faktor = 2;
  
  //k faktor bestimmen
  //echo($winnertag. " ". $winnerpoints." ". $loser." ". $loserpoints);
  $k = 10;
  if($winnerpoints < 2400){
    $query = 
      "SELECT calculateTime FROM ". RANKING_TRIBE_TABLE ." WHERE tribe LIKE '$winnertag'";
    $res = $db->query($query);
    if(!$res)
      return 0;
    $res = $res->fetch(PDO::FETCH_ASSOC);
    if($res['calculateTime'] > 30)
      $k = 15;
    else
      $k = 25;
  }
  $eloneu = $winnerpoints_actual + max(2,$k * $faktor * (1 - (1/(1+pow(10, ($loserpoints - $winnerpoints)/400)))));
  $sql = $db->prepare("UPDATE ". RANKING_TRIBE_TABLE ." SET 
                        points_rank = :points_rank, 
                        calculateTime = calculateTime+1 
                      WHERE tribe like :winnertag");
  $sql->bindValue('points_rank', $eloneu, PDO::PARAM_INT);
  $sql->bindValue('winnertag', $winnertag, PDO::PARAM_STR);
  
  if(!$sql->execute())
    return 0;
  
    $k = 10;
  if($loserpoints < 2400){
    $query =
      "SELECT calculateTime FROM ". RANKING_TRIBE_TABLE ." WHERE tribe LIKE '$losertag'";
    $res = $db->query($query);
    if(!$res)
      return 0;
    $res = $res->fetch(PDO::FETCH_ASSOC);
    if($res['calculateTime'] > 30)
      $k = 15;
    else
      $k = 25;
  }
  $eloneu = $loserpoints_actual + min(-2,$k * $faktor * (0 - (1/(1+pow(10, ($winnerpoints - $loserpoints)/400)))));

  $sql = $db->prepare("UPDATE " . RANKING_TRIBE_TABLE . "
                       SET points_rank = :points_rank,
                         calculateTime = calculateTime + 1
                       WHERE tribe LIKE :tag");
  $sql->bindValue('points_rank', $eloneu, PDO::PARAM_INT);
  $sql->bindValue('tag', $losertag, PDO::PARAM_STR);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return 0;
  }
}

function ranking_updateWonLost($tag, $targettag, $targetwon) {
  global $db;

  if ($targetwon) {
    $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                         SET war_lost = war_lost + 1
                         WHERE tag LIKE :tag ");
    $sql->bindValue('tag', $tag, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return 0;
    }
    
    $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                         SET war_won = war_won + 1
                         WHERE tag LIKE :tag ");
    $sql->bindValue('tag', $targettag, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return 0;
    }

    return 1;
  }
  else if (!$targetwon) {
    $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                         SET war_won = war_won + 1
                         WHERE tag LIKE :tag ");
    $sql->bindValue('tag', $tag, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return 0;
    }
    
    $sql = $db->prepare("UPDATE " . TRIBE_TABLE . "
                         SET war_lost = war_lost + 1
                         WHERE tag LIKE :tag ");
    $sql->bindValue('tag', $targettag, PDO::PARAM_STR);
    if (!$sql->execute() || $sql->rowCount() == 0) {
      return 0;
    }

    return 1;
  }
}

function tribe_getTribeStorageDonations ($tag) {
  global $db;
  
  // Resourcenstring zusammenbasteln
  $fields = array();
  foreach($GLOBALS['resourceTypeList'] as $resource) {
    $fields[] = "SUM(t." . $resource->dbFieldName . ") as " . $resource->dbFieldName;
  }
  
  $sql = $db->prepare("SELECT p.name, ". implode(", ", $fields) ." FROM (" . TRIBE_STORAGE_DONATIONS_TABLE . " t
                LEFT JOIN " . PLAYER_TABLE . " p
                  ON t.playerID = p.playerID)
                WHERE t.tribe LIKE :tag
                  GROUP BY t.playerID");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  
  if ($sql->execute()) {
    return $sql->fetchAll(PDO::FETCH_ASSOC);
  } else {
    return NULL;
  }
}

function tribe_getTribeWonderTargets($tag) {
  global $db;
  
  $sql = $db->prepare("SELECT c.playerID, c.caveID FROM " . CAVE_TABLE . " c
                          LEFT JOIN " . PLAYER_TABLE . " p 
                            ON p.playerID = c.playerID
                        WHERE p.tribe LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  
  if (!$sql->execute()) {
    return NULL;
  }
  
  return $sql->fetchAll(PDO::FETCH_ASSOC);
}

/*
 * get Last Donation for tribe storage
 */
function tribe_getLastDonationForTribeStorage ($playerID) {
  global $db; 
  
  $sql = $db->prepare("SELECT MAX(timestamp) as timestamp FROM " . TRIBE_STORAGE_DONATIONS_TABLE . "
                        WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  
  $sql->execute();
  $ret = $sql->fetch(PDO::FETCH_ASSOC);
  if (empty($ret)) {
    return NULL;
  } else {
    return $ret['timestamp'];
  }
}

function tribe_donateResources($value_array, $caveID, &$caveData) {
  global $db;

  $playerID = $_SESSION['player']->playerID;

  if (!sizeof($value_array)) {
    return -8;
  }

  $fields_cave = $fields_storage = $fields_donations = $fields_resources = $where = array();
  foreach ($value_array as $resourceID => $value) {
    if ($value) {
      if (isset($GLOBALS['resourceTypeList'][$resourceID])) {
        $resource = $GLOBALS['resourceTypeList'][$resourceID];

        // wartezeit einer Ressource nicht abgewartet? Abbruch!
        if (isset($_SESSION['player']->donateLocked['tribe'][$resource->dbFieldName]) && $_SESSION['player']->donateLocked['tribe'][$resource->dbFieldName] > time()) {
          return -9;
        }

        // check if resource is over maxDonation value
        if ($resource->maxTribeDonation < $value) {
          return -9;
        }
        // check for enough resources in cave
        if ($caveData[$resource->dbFieldName] < $value) {
          return -10;
        }
        $fields_cave[] = $resource->dbFieldName . " = " . $resource->dbFieldName . " - " . $value;
        $fields_storage[] = $resource->dbFieldName . " = " . $resource->dbFieldName . " + " . $value;
        $fields_resources[] = $resource->dbFieldName;
        $fields_donations[] = $value;
        $where[] = " AND " . $resource->dbFieldName . " >= " . $value;
      }
    }
  }

  $sql = $db->prepare("INSERT INTO " . TRIBE_STORAGE_DONATIONS_TABLE . 
                        "(playerID, tribe, timestamp, ".implode (", ", $fields_resources) . ")
                        VALUES (:playerID, :tribe, :timestamp, " . implode(", ", $fields_donations). ")");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('tribe', $_SESSION['player']->tribe, PDO::PARAM_STR);
  $sql->bindValue('timestamp', time(), PDO::PARAM_INT);
  if (!$sql->execute()) {
    return -11;
  }
  
  $sql = $db->prepare("UPDATE " . TRIBE_TABLE . " SET
                        " . implode(", ", $fields_storage) . "
                       WHERE tag LIKE :tribe");
  $sql->bindValue('tribe', $_SESSION['player']->tribe, PDO::PARAM_STR);
  if (!$sql->execute()) {
    return -11;
  }

  $sql = $db->prepare("UPDATE " . CAVE_TABLE . " SET 
                        " . implode (", ", $fields_cave) . "
                        WHERE caveID = :caveID 
                        " . implode(" ", $where));
  $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    return -20;
  }

  // update caves
  $caveData = getCaveByID($caveID);

  // update Timeout
  foreach ($value_array as $resourceID => $value) {
    if ($value) {
      if (isset($GLOBALS['resourceTypeList'][$resourceID])) {
        $newTime = time() + (TRIBE_STORAGE_DONATION_INTERVAL*60*60);
        Player::setDonateLocked($_SESSION['player']->playerID, 'tribe', $GLOBALS['resourceTypeList'][$resourceID]->dbFieldName, $newTime);
      }
    }
  }

  return 4;
}


function tribe_hasRelation($relationID, $relations) {
  foreach ($relations as $checkRelation) {
    if ($checkRelation['relationType'] == $relationID) {
      return true;
    }
  }

  return false;
}

?>