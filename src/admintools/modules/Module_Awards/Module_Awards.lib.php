<?
/*
 * Module_Awards.lib.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function Module_Awards_check_tag($tag){
  return ereg("^[0-9a-zA-Z]+$", $tag);
}

function Module_Awards_show_list($msgs = array()){

  global $db_game, $params;

  $template = tmpl_open("modules/Module_Awards/templates/list.ihtml");

  $awards = award_lib_get_awards($db_game);

  tmpl_set($template, 'AWARD', $awards);

  if (sizeof($msgs)){
    foreach ($msgs AS $msg){
      tmpl_iterate($template, "MESSAGE");
      tmpl_set($template, "MESSAGE/message", $msg);
    }
  }

  return tmpl_parse($template);
}

function Module_Awards_delete($awardID){

  global $db_game;

  $award = Module_Awards_getAward($awardID);

  $sql = "DELETE FROM Awards WHERE awardID = {$awardID} LIMIT 1";
  $db_game->query($sql);

  if (!award_lib_delete_tag_from_players($db_game, $award['tag']))
    // FIXME should not be a "die" here
    die("Could not delete tags from players..");
}

function Module_Awards_show_create(){

  $template = tmpl_open("modules/Module_Awards/templates/create.ihtml");
  return tmpl_parse($template);
}

function Module_Awards_update_award(&$msgs){

  global $db_game, $params;

  $award = Module_Awards_getAward($awardID);

  if (!Module_Awards_check_tag($params->awardTag)){
    $msgs[] = "Tag has to match '^[0-9a-zA-Z]+$'";
    return false;
  }

  $description = lib_bb_code($params->awardDescription);
  $sql = "UPDATE Awards SET tag = '{$params->awardTag}', ".
         "title = '{$params->awardTitle}', ".
         "description = '{$description}' ".
         "WHERE awardID = '{$params->awardID}' LIMIT 1";

  if (!$db_game->query($sql)) return false;

  if (!award_lib_change_tag_from_players($db_game, $award['tag'], $params->awardTag))
    // FIXME should not be a "die" here
    die("Could not delete tags from players..");

  return true;
}

function Module_Awards_insert_award(&$msgs){

  global $db_game, $params;

  if (!Module_Awards_check_tag($params->awardTag)){
    $msgs[] = "Tag has to match '^[0-9a-zA-Z]+$'";
    return false;
  }
  $description = lib_bb_code($params->awardDescription);

  $sql = "INSERT INTO Awards (tag, title, description) ".
         "VALUES ('{$params->awardTag}', '{$params->awardTitle}', ".
         "'{$description}')";

  if (!$db_game->query($sql)){
    $msgs[] = $db_game->get_error();
    return false;
  }
  return true;
}

function Module_Awards_getAward($awardID){

  global $db_game, $params;

  $sql = "SELECT * FROM Awards WHERE awardID = {$params->awardID}";
  $result = $db_game->query($sql);

  if (!$result) return false;

  if ($result->isEmpty()) return array();

  return $result->nextRow();
}

function Module_Awards_show_edit($row){

  $template = tmpl_open("modules/Module_Awards/templates/edit.ihtml");
  $row['description'] = lib_bb_decode($row['description']);
  tmpl_set($template, '/', $row);
  return tmpl_parse($template);
}

function Module_Awards_show_decorate(&$msgs){

  global $db_game, $params;

  $template = tmpl_open("modules/Module_Awards/templates/decorate.ihtml");

  $awards = award_lib_get_awards($db_game);
  if (sizeof($awards))
    tmpl_set($template, 'FORM/AWARD', $awards);
  else {
    $msgs[] = "There are no awards.. Create at least one first...";
  }

  if (sizeof($msgs)){
    foreach ($msgs AS $msg){
      tmpl_iterate($template, "MESSAGE");
      tmpl_set($template, "MESSAGE/message", $msg);
    }
  }

  return tmpl_parse($template);
}

function Module_Awards_decorate_player(){
  global $db_game, $params;

  // init msgs
  $msgs = array();

  // open template
  $template = tmpl_open("modules/Module_Awards/templates/decorate_player.ihtml");

  // get all awards
  $awards = award_lib_get_awards($db_game);

  // no awards yet
  if (!sizeof($awards)){
    $msgs[] = "There are no awards.. Create at least one first...";

  } else {

    // choose player
    if (isset($params->decorate_choose)){

      // empty name
      if (empty($params->decoratePlayer)){
        $msgs[] = "A player's name must be filled in!";


      } else {

        // get Player
        $player = getPlayerByName($db_game, $params->decoratePlayer);
        if (!sizeof($player)){
          $msgs[] = "No such player: '{$params->decoratePlayer}'!";

        } else {

          // get awards
          $p_awards = award_lib_get_player_awards($db_game, $player['playerID']);

          // create array from packed awards field of that player
          if (!empty($p_awards)) $p_awards = explode('|', $p_awards);
          else $p_awards = array();
          $p_awards = array_unique($p_awards);
          $p_awards = array_filter($p_awards, create_function('$tag', 'return ereg("^[0-9a-zA-Z]+$", $tag);'));

          foreach ($awards AS $award){

            if (in_array($award['tag'], $p_awards))
              $award['CHOSEN'] = array('dummy' => "");

            tmpl_iterate($template, "/FORM_DECORATE/AWARD");
            tmpl_set($template, "/FORM_DECORATE/AWARD", $award);
          }
          tmpl_set($template, "/FORM_DECORATE", $player);
        }
      }

    // decorated
    } else if (isset($params->decorator)){

      $playerID = (int) $params->decoratePlayerID;

      if (!award_lib_decorate($db_game, $playerID, $params->decorateAward)){
        $msgs[] = "Error while decorating '{$params->decoratePlayer}'!";
      } else {
        $msgs[] = "'{$params->decoratePlayer}' decorated!";

  //////

        // get Player
        $player = getPlayerByName($db_game, $params->decoratePlayer);
        if (!sizeof($player)){
          $msgs[] = "No such player: '{$params->decoratePlayer}'!";

        } else {

          // get awards
          $p_awards = award_lib_get_player_awards($db_game, $player['playerID']);

          // create array from packed awards field of that player
          if (!empty($p_awards)) $p_awards = explode('|', $p_awards);
          else $p_awards = array();
          $p_awards = array_unique($p_awards);
          $p_awards = array_filter($p_awards, create_function('$tag', 'return ereg("^[0-9a-zA-Z]+$", $tag);'));

          foreach ($awards AS $award){

            if (in_array($award['tag'], $p_awards))
              $award['CHOSEN'] = array('dummy' => "");

            tmpl_iterate($template, "/FORM_DECORATE/AWARD");
            tmpl_set($template, "/FORM_DECORATE/AWARD", $award);
          }
          tmpl_set($template, "/FORM_DECORATE", $player);
        }

  //////


      }
    }
    tmpl_set($template, "/FORM_CHOOSE/name", $params->decoratePlayer);
  }

  // show messages
  if (sizeof($msgs)){
    foreach ($msgs AS $msg){
      tmpl_iterate($template, "MESSAGE");
      tmpl_set($template, "MESSAGE/message", $msg);
    }
  }

  return tmpl_parse($template);
}

function Module_Awards_decorate_tribe(){
  global $db_game, $params;

  // init msgs
  $msgs = array();

  // open template
  $template = tmpl_open("modules/Module_Awards/templates/decorate_tribe.ihtml");

  // get all awards
  $awards = award_lib_get_awards($db_game);

  // there are awards; continue
  if (sizeof($awards)){

    // *** tribe chosen ***
    if (isset($params->decorate_choose)){

      // ERROR: tag is empty
      if (empty($params->decorateTribe)){
        $msgs[] = "A tribe's tag must be filled in!";

      // tag is properly filled
      } else {

        // get tribe
        $tribe = getTribeByTag($db_game, $params->decorateTribe);
        if (!sizeof($tribe)){
          $msgs[] = "No such tribe: '{$params->decorateTribe}'!";

        } else {

          // get awards
          $p_awards = $tribe['awards'];

          // create array from packed awards field of that tribe
          if (!empty($p_awards))
            $p_awards = explode('|', $p_awards);
          else
           $p_awards = array();

          $p_awards = array_unique($p_awards);
          $p_awards = array_filter($p_awards, create_function('$tag', 'return ereg("^[0-9a-zA-Z]+$", $tag);'));

          foreach ($awards AS $award){
            if (in_array($award['tag'], $p_awards))
              $award['CHOSEN'] = array('dummy' => "");

            tmpl_iterate($template, "/FORM_DECORATE/AWARD");
            tmpl_set($template, "/FORM_DECORATE/AWARD", $award);
          }
          tmpl_set($template, "/FORM_DECORATE", $tribe);
        }
      }

    // *** decorate tribe ***
    } else if (isset($params->decorator)) {

      // get tag
      $tag = $params->decorateTribe;

      // ERROR: Could not decorate Tribe.
      if (!award_lib_decorate_tribe($db_game, $tag, $params->decorateAward)){
        $msgs[] = "Error while decorating '$tag'!";

      // tribe decorated
      } else {
        $msgs[] = "'$tag' decorated!";

        // get tribe
        $tribe = getTribeByTag($db_game, $tag);

        // ERROR: no such tribe
        if (!sizeof($tribe)){
          $msgs[] = "No such tribe: '$tag'!";

        // show awards
        } else {

          // get awards
          $p_awards = $tribe['awards'];

          // create array from packed awards field of that tribe
          if (!empty($p_awards))
            $p_awards = explode('|', $p_awards);
          else
           $p_awards = array();

          $p_awards = array_unique($p_awards);
          $p_awards = array_filter($p_awards, create_function('$tag', 'return ereg("^[0-9a-zA-Z]+$", $tag);'));

          foreach ($awards AS $award){
            if (in_array($award['tag'], $p_awards))
              $award['CHOSEN'] = array('dummy' => "");

            tmpl_iterate($template, "/FORM_DECORATE/AWARD");
            tmpl_set($template, "/FORM_DECORATE/AWARD", $award);
          }
          tmpl_set($template, "/FORM_DECORATE", $tribe);
        }
      }
    }

    // *** insert tribe's tag ***
    tmpl_set($template, "/FORM_CHOOSE/tag", $params->decorateTribe);

  // no awards yet
  } else {
    $msgs[] = "There are no awards.. Create at least one first...";
  }

  // show messages
  if (sizeof($msgs))
    foreach ($msgs AS $msg){
      tmpl_iterate($template, "MESSAGE");
      tmpl_set($template, "MESSAGE/message", $msg);
    }

  return tmpl_parse($template);
}
?>