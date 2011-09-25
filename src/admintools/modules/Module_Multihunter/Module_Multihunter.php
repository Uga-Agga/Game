<?
/*
 * Module_Player.php - 
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once("Module_Base.lib.php");
require_once("Menu.lib.php");
require_once("Menu_Item.lib.php");

class Module_Multihunter extends Module_Base {

  function  Module_Multihunter(){
    $this->modi[] = 'mh_showlist';

  }

  function getContent($modus){

    global $params, $cfg;

    $content = "";
    switch ($modus){
      default:
      case 'mh_showlist':
        $content = $this->_show();
        break;
    }
    return $content;
  }

  function getMenu(){
    $menu = new Menu($this->getName());
    $menu->addItem(new Menu_Item("?modus=mh_showlist", "gesperrte Spieler"));
    return $menu->getMenu();
  }

  function getName(){
    return "Taljäger";
  }
  
  function _search($feedback = NULL){
    $template = tmpl_open("modules/Module_Player/templates/search.ihtml");
    if ($feedback) tmpl_set($template, '/MESSAGE/message', $feedback);
    return tmpl_parse($template);
  }

  function _showold(){
    global $db_login, $db_game, $params, $cfg;

    $login = array();
    $game  = array();
    
   
    $template = tmpl_open("modules/Module_Multihunter/templates/show.ihtml");
    
    $query = "SELECT l.*,b.* FROM `Login` l, `Block` b WHERE l.`multi` = b.blockid ORDER BY l.lastChange DESC";
    $result = $db_login->query($query);
    $blocks = array();
    if ($result && !$result->isEmpty())
      while ($row = $result->nextRow())
        $blocks[] = array('text'     => $row['reasonShort'],
                          'value'    => $row['blockid'],
                          'SELECTED' => $login['multi'] == $row['blockid']
                                        ? array('iterate' => '')
                                        : NULL);
    
    tmpl_set($template, array('name'     => $game['name'],
                              'tribe'    => $game['tribe'],
                              'playerID' => $game['playerID'],
                              'DELETED'  => $login['deleted'] ? array('iterate' => '') : NULL,
                              'MULTI'    => $blocks,
                              'ban'      => $login['ban'],
															'comment'  => $login['comment']));
        
    return tmpl_parse($template);
  }


  
  function _show(){
    global $db_login, $db_game, $params, $cfg;

    $login = array();
    $game  = array();
    
   
    $template = tmpl_open("modules/Module_Multihunter/templates/show.ihtml");
    
    $query = "SELECT l.*,b.* FROM `Login` l, `Block` b WHERE l.`multi` = b.blockid ORDER BY l.lastChange DESC";
    $result = $db_login->query($query);
    if ($result && !$result->isEmpty()) 
    {
      $i = 0;
      while ($row = $result->nextRow()) 
      {
        $i++;
         if ($row['ban']>0) {
           $banYear  = substr($row['ban'], 0, 4);
           $banMonth  = substr($row['ban'], 4, 2);
           $banDay    = substr($row['ban'], 6, 2);
           $banHour  = substr($row['ban'], 8, 2);
           $banMinute = substr($row['ban'], 10, 2);
           $banDate  = $banDay . '.' . $banMonth . '.' . $banYear  . ' ' . $banHour . ':' . $banMinute; 
         } 
         else
         {
           $banDate = "unbeschränkt";
         }
         
         $now = date("Ymd000000");
         
        $lastChange = @ereg_replace("([0-9][0-9][0-9][0-9])-([0-9][0-9])-([0-9][0-9]) ([0-9][0-9]):([0-9][0-9]):[0-9][0-9]","\\3.\\2.\\1 \\4:\\5",$row['lastChange']);

        $newelem = array('name'     => $row['user'],
                          'reason'    => $row['reasonShort'],
                          'id'    => $row['LoginID'],
                          'comment'    => str_replace("\n","<br />",$row['comment']),
                          'lastChange'    => $lastChange,
                          'bg' => $i & 1 ? '#DDDDDD' : '#FFFFFF',
                          'until' => $banDate 
                          );
        if (($row['ban']==0) || ($row['ban']>$now)) 
        {
          $blocks[] = $newelem;
        }
        else
        {
          $blocksOutdatet[]  = $newelem;
        }
      }
    }   
    tmpl_set($template,'ELEM',$blocks);
    tmpl_set($template,'ELEMOUTDATET',$blocksOutdatet);
     
    return tmpl_parse($template);
  }
  
  function _modify(){
    global $db_login, $db_game, $params, $cfg;
    
    // modify the non-namechanging part
    $query = sprintf("UPDATE Login SET deleted = %d, multi = %d, ban = '%s', comment = '%s' ".
                     "WHERE LoginID = %d", $params->deleted,
                     $params->multi, $params->ban, $params->comment, $params->playerID);
    $result = $db_login->query($query);
    $retval = $result ? "Multi/Ban information successfully modified! " : mysql_error();

    if ($params->formername != $params->name) {
      // set new name - Login
      $query = sprintf("UPDATE Login SET user = '%s' WHERE LoginID = %d", $params->name, $params->playerID);
      $result = $db_login->query($query);

      $temp = ($result != FALSE);
      $tempstr = "";
      if (!result) $tempstr .= mysql_error();
      
      // - Game
      $query = sprintf("UPDATE Player SET name = '%s' WHERE playerID = %d", $params->name, $params->playerID);
      $result = $db_game->query($query);

      $temp = temp && ($result != FALSE);
      if (!result) $tempstr .= mysql_error();

      // Update ranking table
      $query = sprintf("UPDATE Ranking SET name = '%s' WHERE playerID = %d", $params->name, $params->playerID);
      $result = $db_game->query($query);

      $temp = temp && ($result != FALSE);
      if (!result) $tempstr .= mysql_error();

      // Update login log
      if ($params->changeloginlog) {
        $query = sprintf("UPDATE LoginLog SET user = '%s' WHERE user = %s", $params->name, $params->formername);
        $result = $db_login->query($query);
        $temp = temp && ($result != FALSE);
        if (!result) $tempstr .= mysql_error();
      }

      // insert player history entry
      if ($params->addplayerhistory) {
        // prepare query
        $query = sprintf("INSERT INTO `player_history` (`playerID`, `timestamp`, ".
                         "`entry`) VALUES (%d, '%s', '%s')",
                         (int) $params->playerID,
                         gmdate("Y-m-d H:i:s", time()),
                         addslashes("Der Name des Spielers wurde auf '".$params->name."' geändert"));
        $result = $db_game->query($query);
        $temp = temp && ($result != FALSE);
        if (!result) $tempstr .= mysql_error();
      }

      // insert tribe history entry
      if ($params->addtribehistory  && $params->tribe != '') {
        $query =
          "INSERT INTO TribeMessage ".
          "(tag, messageClass, messageSubject, messageText, messageTime) ".
          "values( '".$params->tribe."', '10', 'Name des Spielers geändert', 'Der Spieler ".$params->formername." ist nun bekannt als ".$params->name.".', NOW()+0 )";

        $result = $db_game->query($query);
        $temp = temp && ($result != FALSE);
        if (!result) $tempstr .= mysql_error();
      }

      $retval .= $temp ? "Player's name successfully changed to ".$params->name."." : $tempstr;
    }

    return $this->_search($retval);
  }
  
  function _getDetails($name, &$login, &$game){

    global $db_game, $db_login;    
    
    $query = sprintf("SELECT * FROM Player WHERE name = '%s'", $name);
    $result_game = $db_game->query($query);

    $query = sprintf("SELECT * FROM Login WHERE user = '%s'", $name);
    $result_login = $db_login->query($query);

    if (!$result_game || !$result_login)
      return FALSE;
      
    if ($result_game->isEmpty() || $result_login->isEmpty())
      return FALSE;
    
    $game  += $result_game->nextRow();
    $login += $result_login->nextRow();
    
    return TRUE;
  }
}
?>
