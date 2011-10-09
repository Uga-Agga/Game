<?php
/*
 * Module_Tribe.php - 
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

//global $cfg;

require_once("Module_Base.lib.php");
require_once("Menu.lib.php");
require_once("Menu_Item.lib.php");

class Module_Suggestion extends Module_Base {

  function Module_Suggestion(){
    $this->modi[] = 'suggestion_list';
    $this->modi[] = 'suggestion_view';
    $this->modi[] = 'suggestion_delete';
  }

  function getContent($modus){

    global $db_game, $params;

    $content = "";
    switch ($modus){
      case 'suggestion_list':

        $template = tmpl_open("modules/Module_Suggestion/templates/list.ihtml");
        
        $query = "SELECT Suggestions.*, Player.Name ".
                 "FROM Suggestions ".
                 "LEFT JOIN Player ".
                 "ON Suggestions.playerID = Player.playerID";
        $result = $db_game->query($query);
        $tmp = 0;
        while ($row = $result->nextRow(MYSQL_ASSOC)) {
          $tmp = ($tmp+1) % 2;
          $suggestions[] = array('player'        => $row['Name'],
                                 'suggestion'    => lib_shorten_html(lib_unhtmlentities(stripslashes($row['Suggestion'])), 100),
                                 'suggestion_id' => $row['suggestionID'],
                                 'class'       => $tmp ? 'alternate' : '');
        }
        if (sizeof($suggestions)) {
          tmpl_set($template, '/ROW', $suggestions);
        } else {
          tmpl_set($template, '/NOENTRIES', array('iterate' => ''));
        }

        $content = tmpl_parse($template);
        break;
      case 'suggestion_view':
        
        $template = tmpl_open("modules/Module_Suggestion/templates/view.ihtml");

        $query = "SELECT Suggestions.*, Player.Name ".
                 "FROM Suggestions ".
                 "LEFT JOIN Player ".
                 "ON Suggestions.playerID = Player.playerID ".
                 "WHERE Suggestions.suggestionID=".$params->suggestionID;
        $result = $db_game->query($query);
        $row = $result->nextRow(MYSQL_ASSOC);
        tmpl_set($template, array('player'        => $row['Name'],
                                  'suggestion'    => nl2br(stripslashes($row['Suggestion'])),
                                  'suggestion_id' => $params->suggestionID));

        $content = tmpl_parse($template);
        break;
      case 'suggestion_delete':

        $template = tmpl_open("modules/Module_Suggestion/templates/view.ihtml");

        $query = "SELECT Suggestions.*, Player.Name ".
                 "FROM Suggestions ".
                 "LEFT JOIN Player ".
                 "ON Suggestions.playerID = Player.playerID ".
                 "WHERE Suggestions.suggestionID=".$params->suggestionID;
        $result = $db_game->query($query);
        $row = $result->nextRow(MYSQL_ASSOC);
        tmpl_set($template, array('player'        => $row['Name'],
                                  'suggestion'    => nl2br(stripslashes($row['Suggestion'])),
                                  'suggestion_id' => $params->suggestionID));

        $query = "DELETE FROM Suggestions ".
                 "WHERE Suggestions.suggestionID=".$params->suggestionID;
        $result = $db_game->query($query);
        if ($result) {
          tmpl_set($template, '/MESSAGE', array('message' => 'Successfully deleted.'));
        } else {
          tmpl_set($template, '/MESSAGE', array('message' => 'An error occured while deleting the suggestion below: '.mysql_error()));
        }

        $content = tmpl_parse($template);
        break;
    }
    return $content;
  }

  function getMenu(){
    $menu = new Menu($this->getName());
    $menu->addItem(new Menu_Item("?modus=suggestion_list", "list"));
    return $menu->getMenu();
  }

  function getName(){
    return "Suggestions";
  }
}
?>