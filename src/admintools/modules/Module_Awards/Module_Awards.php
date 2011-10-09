<?php
/*
 * Module_Awards.php -
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
require_once("Module_Awards.lib.php");
require_once("award.lib.php");

DEFINE('AWARDS_IMG_PATH', 'http://www.where-are-my-awards.com/img');

class Module_Awards extends Module_Base {

  function Module_Awards(){
    $this->modi[] = 'award_create';
    $this->modi[] = 'award_list';
    $this->modi[] = 'award_edit';
    $this->modi[] = 'award_delete';
    $this->modi[] = 'award_decorate_player';
    $this->modi[] = 'award_decorate_tribe';
  }

  function getContent($modus){

    global $db_game, $params;

    $content = "";
    $msgs    = array();

    switch ($modus){
      case 'award_create':

        // show form
        if (!isset($params->creator)){
          $content = Module_Awards_show_create();

        // form submitted
        } else {

          // TODO check values
          if (empty($params->awardTag) || empty($params->awardTitle));

          // give feedback on success
          if (!Module_Awards_insert_award($msgs))
            $msgs[] = "Error while inserting the new award!";
          else
            $msgs[] = "Award inserted!";

          $content = Module_Awards_show_list($msgs);
        }
        break;

      case 'award_edit':

        // form submitted
        if (isset($params->editaward)){

          // give feedback on success
          if (!Module_Awards_update_award($msgs))
            $msgs[] = "Error editing the award!";
          else
            $msgs[] = "Award edited!";

          $content = Module_Awards_show_list($msgs);

        // show form
        } else {

          $award = Module_Awards_getAward($params->awardID);

          // db error ..
          if ($award === false){
            $msgs[] = "Error while retrieving award.";
            $content = Module_Awards_show_list($msgs);

          // wrong id
          } else if (!sizeof($award)){
            $msgs[] = "No such award!";
            $content = Module_Awards_show_list($msgs);

          } else {
            $content = Module_Awards_show_edit($award);
          }
        }
        break;

      case 'award_delete':
        Module_Awards_delete($params->awardID);
        $content = Module_Awards_show_list($msgs);
        break;

      case 'award_list':
        $content = Module_Awards_show_list($msgs);
        break;

      case 'award_decorate_player':
        $content = Module_Awards_decorate_player();
        break;

      case 'award_decorate_tribe':
        $content = Module_Awards_decorate_tribe();
        break;
    }
    return $content;
  }

  function getMenu(){
    $menu = new Menu($this->getName());
    $menu->addItem(new Menu_Item("?modus=award_list",     "list"));
    $menu->addItem(new Menu_Item("?modus=award_decorate_player", "decorate player"));
    $menu->addItem(new Menu_Item("?modus=award_decorate_tribe", "decorate tribe"));
    return $menu->getMenu();
  }

  function getName(){
    return "Awards";
  }
}
?>