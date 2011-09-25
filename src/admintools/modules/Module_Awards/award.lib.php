<?
/*
 * award.lib.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function award_lib_get_player_awards($db_game, $playerID){

  $playerID = (int) $playerID;

  $sql = "SELECT awards FROM Player WHERE playerID = '{$playerID}' LIMIT 1";
  $result = $db_game->query($sql);

  if (!$result || $result->isEmpty()) return false;
  $row = $result->nextRow();
  return $row['awards'];
}

function award_lib_get_awards($db_game, $awardIDs = array()){

  if (sizeof($awardIDs)){
    $awardIDs = implode(",", $awardIDs);
    $sql = "SELECT * FROM Awards WHERE awardID IN ({$awardIDs}) ORDER BY awardID ASC";
  } else {
    $sql = "SELECT * FROM Awards ORDER BY awardID ASC";
  }

  $result = $db_game->query($sql);
  if (!$result) return FALSE;

  $awards = array();
  while ($row = $result->nextRow()){
    $row['awards_img_path']   = AWARDS_IMG_PATH;
    $row['description_short'] = lib_shorten_html(lib_bb_decode($row['description']), 64);
    $awards[] = $row;
  }

  return $awards;
}


function award_lib_get_award_by_awardID($db_game, $awardID){

  $sql = "SELECT * FROM Awards WHERE awardID = {$awardID}";
  $result = $db_game->query($sql);

  if (!$result) return false;
  if ($result->isEmpty()) return array();
  return $result->nextRow();
}

function award_lib_decorate($db_game, $playerID, $awardIDs){

  if (sizeof($awardIDs)){
    $awards = award_lib_get_awards($db_game, $awardIDs);
    if ($awards === false)
      return false;

    $tags = array();
    foreach ($awards AS $award)
      $tags[] = $award['tag'];

    $tags = implode("|", $tags);
  } else {
    $tags = "";
  }

  $sql = "UPDATE Player SET awards = '{$tags}' WHERE playerID = '{$playerID}' LIMIT 1";
  $result = $db_game->query($sql);
  return $result != FALSE;
}

function award_lib_decorate_tribe($db_game, $tag, $awardIDs){

  if (sizeof($awardIDs)){
    $awards = award_lib_get_awards($db_game, $awardIDs);
    if ($awards === false)
      return false;

    $tags = array();
    foreach ($awards AS $award)
      $tags[] = $award['tag'];

    $tags = implode("|", $tags);
  } else {
    $tags = "";
  }

  $sql = "UPDATE Tribe SET awards = '$tags' WHERE tag = '$tag' LIMIT 1";
  $result = $db_game->query($sql);
  return $result != FALSE;
}

function award_lib_get_players_with_tag($db_game, $tag){

  $sql = "SELECT * FROM `Player` WHERE awards = '$tag' OR awards = '%|$tag' OR ".
         "awards = '$tag|%' OR awards = '%|$tag|%'";

  $result = $db_game->query($sql);
  if (!$result) return FALSE;

  $players = array();
  while ($row = $result->nextRow()) $players[] = $row;
  return $players;
}

function award_lib_delete_tag_from_players($db_game, $tag){

  $players = award_lib_get_players_with_tag($db_game, $tag);
  if ($players === FALSE) return false;
  if (!sizeof($players)) return true;

  foreach ($players AS $player){
    $tags = explode("|", $player['awards']);
    $tags = array_unique($tags);
    $tags = array_filter($tags, create_function('$tag', 'return ereg("^[0-9a-zA-Z]+$", $tag);'));

    $key = array_search($tag, $tags);

    // im array
    if ($key !== FALSE) unset($tags[$key]);
    // nicht im array
    else return FALSE;

    $tags = implode("|", $tags);
    $sql = "UPDATE Player SET awards = '{$tags}' WHERE playerID = '{$player['playerID']}' LIMIT 1";
    $result = $db_game->query($sql);
    if (!$result) return FALSE;
  }
  return true;
}

function award_lib_change_tag_from_players($db_game, $tag, $new_tag){

  $players = award_lib_get_players_with_tag($db_game, $tag);
  if ($players === FALSE) return false;
  if (!sizeof($players)) return true;

  foreach ($players AS $player){
    $tags = explode("|", $player['awards']);
    $tags = array_unique($tags);
    $tags = array_filter($tags, create_function('$tag', 'return ereg("^[0-9a-zA-Z]+$", $tag);'));

    $key = array_search($tag, $tags);

    // im array
    if ($key !== FALSE) $tags[$key] = $new_tag;
    // nicht im array
    else return FALSE;

    $tags = implode("|", $tags);
    $sql = "UPDATE Player SET awards = '{$tags}' WHERE playerID = '{$player['playerID']}' LIMIT 1";
    $result = $db_game->query($sql);
    if (!$result) return FALSE;
  }
  return true;
}
?>