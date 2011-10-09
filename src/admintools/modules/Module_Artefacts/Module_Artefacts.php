<?php
/*
 * Module_Artefacts.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

global $effectTypeList;

require_once("Module_Base.lib.php");
require_once("Menu.lib.php");
require_once("Menu_Item.lib.php");
require_once("artefact.lib.php");
require_once($cfg['cfgpath'] . "effect_list.php");

class Module_Artefacts extends Module_Base {

  var $msgs;

  function Module_Artefacts(){
    // set modi
    $this->modi[] = 'artefacts_list';
    $this->modi[] = 'artefacts_remove';
    $this->modi[] = 'artefacts_put';

    // set msgs
    $this->msgs = array();
  }

  function getContent($modus){

    $content = "";
    $msgs    = array();

    switch ($modus){
      case 'artefacts_list':
        $content = $this->getList();
        break;
      case 'artefacts_remove':
        $content = $this->remove();
        break;
      case 'artefacts_put':
        $content = $this->put();
        break;
    }
    return $content;
  }

  function getMenu(){
    $menu = new Menu($this->getName());
    $menu->addItem(new Menu_Item("?modus=artefacts_list", "artefacts"));
    return $menu->getMenu();
  }

  function getName(){
    return "Artefacts";
  }

  function getList(){
    global $db_game, $params;

    $template = tmpl_open("modules/Module_Artefacts/templates/list.ihtml");

    $artefacts = artefact_lib_get_artefacts();

    //tmpl_set($template, 'ARTEFACT', $artefacts);
    foreach ($artefacts as $artefact){

      // in cave
      if ($artefact['caveID'] && !$artefact['event_movementID']){
        tmpl_iterate($template, '/ARTEFACTINCAVE');
        tmpl_set($template, '/ARTEFACTINCAVE', $artefact);
      }

      // moving
      else if ($artefact['event_movementID']){
        tmpl_iterate($template, '/ARTEFACTMOVING');
        tmpl_set($template, '/ARTEFACTMOVING', $artefact);
      }

      // limbo
      else {
        tmpl_iterate($template, '/ARTEFACTLIMBO');
        tmpl_set($template, '/ARTEFACTLIMBO', $artefact);
      }
    }

    // show messages
    if (sizeof($this->msgs)){
      foreach ($this->msgs AS $msg){
        tmpl_iterate($template, "/MESSAGE");
        tmpl_set($template, "/MESSAGE/message", $msg);
      }
    }

    return tmpl_parse($template);
  }

  function remove(){
    global $db_game, $params;

    // get artefact
    $artefactID = intval($params->artefactID);
    $artefact = artefact_getArtefactByID($artefactID);
    if (!sizeof($artefact)){
      $this->msgs[] = 'No such cave: (' . $params->xCoord . '|' . $params->yCoord . ')';
      return $this->getList();
    }

    // remove effects
    if (!artefact_removeEffectsFromCave($db_game, $artefact)){
      $this->msgs[] = 'FATAL ERROR: Could not remove artefact\'s effects from it\'s cave';
      return $this->getList();
    }

    // uninitiate artefact
    if (!artefact_uninitiateArtefact($db_game, $artefactID)){
      $this->msgs[] = 'FATAL ERROR: Could not uninitiate artefact';
      return $this->getList();
    }    

    // remove from cave
    if (!artefact_removeArtefactFromCave($db_game, $artefact)){
      $this->msgs[] = 'FATAL ERROR: Could not remove artefact from it\'s cave';
      return $this->getList();
    }

    $this->msgs[] = 'Successfully removed artefact.';
    return $this->getList();
  }

  function put(){
    global $db_game, $params;

    // get artefact
    $artefactID = intval($params->artefactID);
    $artefact = artefact_getArtefactByID($artefactID);
    if (!sizeof($artefact)){
      $this->msgs[] = 'No such cave: (' . $params->xCoord . '|' . $params->yCoord . ')';
      return $this->getList();
    }

    // artefact has to be in limbo
    if ($artefact['initiated'] || $artefact['caveID'] ||
        $artefact['event_movementID']) {
      $this->msgs[] = 'Artefact is not in limbo';
      return $this->getList();
    }

    // put form submitted
    if ($params->artefacts_put_submit){

      // get cave
      $cave = getCaveByCoords($db_game, $params->xCoord, $params->yCoord);
      // no such cave
      if (!sizeof($cave))
        $this->msgs[] = 'No such cave: (' . $params->xCoord . '|' . $params->yCoord . ')';
      // put artefact
      else {
        $this->msgs[] = artefact_putArtefactIntoCave($db_game, $artefactID, $cave['caveID'])
                        ? "Artefact put into cave."
                        : "Error puttin artefact into cave." ;
      }

      return $this->getList();
    }

    $template = tmpl_open("modules/Module_Artefacts/templates/put.ihtml");

    // insert artefact data
    tmpl_set($template, '/', $artefact);

    return tmpl_parse($template);
  }
}
?>