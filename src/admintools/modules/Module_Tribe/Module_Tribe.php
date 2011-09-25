<?
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
require_once($cfg['cfgpath'] . "time.inc.php");

class Module_Tribe extends Module_Base {

  function Module_Tribe(){
    $this->modi[] = 'tribe_history';
  }

  function getContent($modus){

    global $db_game, $params;

    $content = "";
    switch ($modus){
      case 'tribe_history':

        $template = tmpl_open("modules/Module_Tribe/templates/history.ihtml");

        // Form Submitted
        if (isset($params->creator)){

          $something_wrong = false;

          //TODO: $tribe has to be checked
          $tribe = $params->historyTribe;

          $day   = intval($params->historyDay);
          if ($day < 1 || $day > DAYS_PER_MONTH){
            $something_wrong = true;
            $message = "Wrong day. Must be between 1 and ".DAYS_PER_MONTH.".";
          }

          $year  = intval($params->historyYear);
          if ($year < STARTING_YEAR){
            $something_wrong = true;
            $message = "Wrong year. Must be &gt;= " . STARTING_YEAR . ".";
          }

          $month = getMonthName($params->historyMonth);

          $entry = $params->historyMessage;

          if ($something_wrong){
            tmpl_set($template, "MESSAGE/message", $message);
          } else {
            $query = "INSERT INTO `TribeHistory` (`tribe`, `timestamp`, ".
                     "`ingameTime`, `message`) VALUES ('$tribe', NULL , ".
                     "'$day. $month<br>im Jahr $year', '$entry')";

            if (!$db_game->query($query))
              die("Error while inserting your entry!");

            tmpl_set($template, "MESSAGE/message", "Entry inserted!");
          }
        }
        
        // iterate months
        $months = array();
        for ($i = 1; $i <= MONTHS_PER_YEAR; ++$i)
          $months[] = array('text' => getMonthName($i), 'value' => $i);
        if (sizeof($months))
          tmpl_set($template, '/MONTH', $months);
          
        
        $content = tmpl_parse($template);
        break;
    }
    return $content;
  }

  function getMenu(){
    $menu = new Menu($this->getName());
    $menu->addItem(new Menu_Item("?modus=tribe_history", "history"));
    return $menu->getMenu();
  }

  function getName(){
    return "Tribe";
  }
}
?>