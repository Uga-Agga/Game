<?php
/*
 * Module_DoYouKnow.php - 
 * Copyright (c) 2009  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once("Module_Base.lib.php");
require_once("Menu.lib.php");
require_once("Menu_Item.lib.php");

class Module_DoYouKnow extends Module_Base {

  var $error;
  var $item;

  function Module_DoYouKnow(){

    $this->modi[] = 'doyouknow_list';
    $this->modi[] = 'doyouknow_add';
    $this->modi[] = 'doyouknow_edit';
    $this->modi[] = 'doyouknow_remove';

    $this->error  = false;
    $this->item   = array();
  }

  function getContent($modus){

    $content = "";
    switch ($modus){
      default:
      case 'doyouknow_list':
        $content = $this->_list();
        break;
      case 'doyouknow_add':
        $content = $this->_add();
        break;
      case 'doyouknow_edit':
        $content = $this->_edit();
        break;
      case 'doyouknow_remove':
        $content = $this->_remove();
        break;
    }
    return $content;
  }

  function getMenu(){

    $menu = new Menu($this->getName());
    $menu->addItem(new Menu_Item("?modus=doyouknow_list", "list"));
    return $menu->getMenu();
  }

  function getName(){

    return "DoYouKnow";
  }

  function _list($feedback = NULL){

    global $db_game, $params;

    $this->_getDetails('all');

    if (empty($this->item)){
      $feedback = 'Keine Einträge vorhanden.';
    }

    $template = tmpl_open("modules/Module_DoYouKnow/templates/list.ihtml");

    foreach ($this->item as $item){
      tmpl_iterate($template, '/DOYOUKNOW');
      tmpl_set($template, '/DOYOUKNOW', $item);
    }
    if ($feedback) tmpl_set($template, '/MESSAGE/message', $feedback);

    return tmpl_parse($template);
  }

  function _add(){

    global $db_game, $params;

    if (isset($_POST['submit'])){
      $query = sprintf("INSERT INTO doYouKnow (titel, content) VALUES ('%s', '%s')", 
			$params->titel,
			$params->content);
      $result = $db_game->query($query);
      $retval = $result ? "DoYouKnow information successfully modified! " : mysql_error();

      return $this->_list($retval);
    }

    $template = tmpl_open("modules/Module_DoYouKnow/templates/add.ihtml");
    return tmpl_parse($template);
  }

  function _remove(){

    global $db_game, $params;

    $query = sprintf("DELETE FROM doYouKnow WHERE id = %d", $params->id);
    $result = $db_game->query($query);
    $retval = $result ? "DoYouKnow eintrag erfolgreich gelöscht! " : mysql_error();

    return $this->_list($retval);
  }

  function _edit(){

    global $db_game, $params;

    if (isset($_POST['submit'])){
      $query = sprintf("UPDATE doYouKnow SET titel = '%s', content = '%s' ".
                       "WHERE id = %d", $params->titel, $params->content, $params->id);
      $result = $db_game->query($query);
      $retval = $result ? "DoYouKnow information successfully modified! " : mysql_error();

      return $this->_list($retval);
    }

    $this->_getDetails();
    if (empty($this->item)){
      return $this->_list('Could not find item');
    }
    $this->item = $this->item[0];

    $template = tmpl_open("modules/Module_DoYouKnow/templates/edit.ihtml");
    tmpl_set($template,  array('id'       => $this->item['id'],
                               'titel'    => $this->item['titel'],
                               'content'  => $this->item['content']));

    return tmpl_parse($template);
  }

  function _getDetails($get=''){

    global $db_game, $params;

    if (!empty($params->id) && $get != 'all'){
      $sql_where = sprintf("WHERE id = %d LIMIT 1", $params->id);
     }

    $query = sprintf("SELECT * FROM doYouKnow %s", $sql_where);
    $dbresult = $db_game->query($query);

    if (!$dbresult || $dbresult->isEmpty()){
      return FALSE;
    }

    $this->item = array();
    while($row = $dbresult->nextrow(MYSQL_ASSOC))
      $this->item[] = $row;

    return TRUE;
  }

}
?>