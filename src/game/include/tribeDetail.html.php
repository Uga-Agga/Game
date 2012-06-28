<?php
/*
 * tribeDetail.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribe_getContent($caveID, $tag) {
  global $db, $template;

  if (!$tag) {
    $template->throwError('Es wurde kein Stamm ausgewählt.');
    return;
  }

  // open template
  $template->setFile('tribeDetail.tmpl');
  $template->setShowRresource(false);

  $sql = $db->prepare("SELECT t.*, p.playerID, p.name AS leader_name
                       FROM ". TRIBE_TABLE ." t
                         LEFT JOIN ". PLAYER_TABLE ." p
                           ON p.playerID = t.leaderID
                       WHERE t.tag LIKE :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if(!$sql->execute()) {
    $template->throwError('Fehler in der Datenbank.');
    return;
  }

  if (!$row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $template->throwError('Es konnte kein Stamm mit dem Namen gefunden werden.');
    return;
  }
  $sql->closeCursor();

  $JuniorAdmin = $targetPlayer = new Player(getPlayerByID($row['juniorLeaderID']));

  if (!empty($row['awards'])){
    $tmp = explode('|', $row['awards']);
    $awards = array();

    foreach ($tmp AS $tag1) {
      $awards[] = array(
        'award_tag' => $tag1,
        'award_modus' => AWARD_DETAIL
      );
    }

    $row['award'] = $awards;
  }

  if ($row['avatar']) {
    $row['avatar'] = @unserialize($row['avatar']);
    $row['avatar_path'] = $row['avatar']['path'];
    $row['avatar_width'] = $row['avatar']['width'];
    $row['avatar_height'] = $row['avatar']['height'];
  }

  foreach($row as $k => $v) {
    if (!$v && $k != 'avatar') {
      $row[$k] = "k.A.";
    }
  }

  $row['rank_offset'] = rankingTribe_checkOffset($tag);

  // parse tribe message
  $parser = new parser;
  $row['description'] = $parser->p($row['description']);

  // get Tribe Rank
  $sql = $db->prepare("SELECT rank FROM ". RANKING_TRIBE_TABLE ." WHERE tribe = :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  if (!$sql->execute()) {
    $template->throwError('Fehler in der Datenbank.');
    return;
  }

  if ($ranking = $sql->fetch(PDO::FETCH_ASSOC)) {
    $row['rank'] = $ranking['rank'];
  } else {
    $row['rank'] = '';
  }

  $template->addVar('tribe_details', $row);

  // history
  $history = relation_getTribeHistory($tag);
  $template->addVar('tribe_history', $history);

  // player list
  $playerList = tribe_getPlayerList($tag, true, true);
  foreach($playerList AS $id => $playerData) {
    if (!empty($playerData['awards'])) {
      $playerData['awards'] = explode('|', $playerData['awards']);

      $awards = array();
      foreach ($playerData['awards'] AS $award) {
        $awards[] = array('tag' => $award, 'award_modus' => AWARD_DETAIL);
      }

      $playerData['award'] = $awards;
    }

    foreach($playerData as $k => $v) {
      if ($k == 'awards' || $k == 'religion') {
        continue;
      }

      if (!$v) {
        $playerData[$k] = _('k.A.');
      }
    }

    $playerList[$id] = $playerData;
  }

  $template->addVar('tribe_player_list', $playerList);

  // relations
  $relations = relation_getRelationsForTribe($tag);
  $relationsData = array();
  if (isset($relations['own'])) {
    foreach($relations['own'] AS $target => $relationData) {
      $relationsData[$target] = array (
        'tribe'         => $relationData['tribe_target'],
        'relation_to'   => $relationList[$relationData['relationType']]['name'],
        'relation_from' => (isset($relations['other'][$target]) && $relations['other'][$target]) ? $relationList[$relations['other'][$target]['relationType']]['name'] : $relationList[0]['name'],
      );
    }
  }

  if (isset($relations['other'])) {
    foreach($relations['other'] AS $target => $relationData) {
      // already printed out this relation
      if (isset($relationsData[$target])) {
        continue;
      }

      $relationsData[$target] = array (
        'tribe'         => $relationData['tribe'],
        'relation_to'   => $relationList[0]['name'],
        'relation_from' => $relationList[$relationData['relationType']]['name'],
      );
    }
  }

  $template->addVar('relations_data', $relationsData);
}

?>