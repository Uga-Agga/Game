<?php
/*
 * Module_Statistics.php - 
 * Copyright (c) 2011 Sascha Lange
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once("Module_Base.lib.php");
require_once("Menu.lib.php");
require_once("Menu_Item.lib.php");

class Module_Statistics extends Module_Base {

  function Module_Statistics(){
    $this->modi[] = 'statistics_show';
  }

  function getContent($modus){

    global $params, $cfg;

    $content = "";
    switch ($modus){
      default:
      case 'statistics_show':
        $content = $this->_show();
        break;
    }
    return $content;
  }

  function getMenu(){
    $menu = new Menu($this->getName());
    $menu->addItem(new Menu_Item("?modus=statistics_show", "show"));
    return $menu->getMenu();
  }

  function getName(){
    return "Statistics";
  }
    
  function _show(){
    global $db_login, $db_game, $params, $cfg;

    $login = array();
    $game  = array();
    
    $tvars = array();
    
    $template = tmpl_open("modules/Module_Statistics/templates/statistics.ihtml");

    // UNIQUE LOGINS THIS MONTH
    $query = 
      "SELECT COUNT( DISTINCT (
         user
       ) ) AS playersPerMonth
       FROM LoginLog
       WHERE success =1
       AND MONTH( stamp ) = MONTH( CURDATE( ) ) 
       AND YEAR( stamp ) = YEAR( CURDATE( ) )";
     
    $result = $db_login->query($query);

    if ($result && !$result->isEmpty() && ($row = $result->nextRow() )) {
      $playersPerMonth = $row['playersPerMonth'];
    }

    // UNIQUE LOGINS PER DAYS OF THIS MONTH
    $query = 
      "SELECT DATE( stamp ) AS date , COUNT( DISTINCT ( 
        user 
       ) ) AS playersPerDay
       FROM LoginLog 
       WHERE success =1 
       AND MONTH (stamp) = MONTH( CURDATE() )
       AND YEAR (stamp) = YEAR(CURDATE())
       GROUP BY DATE( stamp ) 
       ORDER BY DATE( stamp ) DESC"; 
    
    $result = $db_login->query($query);

    $dayInfo = array();
    if ($result && !$result->isEmpty())
      while ($row = $result->nextRow()) {
        $dayInfo[] = array ( 'date'          => $row['date'],
                             'playersPerDay' => $row['playersPerDay'],
                             'quotient'      => $row['playersPerDay'] / $playersPerMonth );
      }
      
    // UNIQUE LOGINS LAST HOUR
    $query = 
      "SELECT COUNT( DISTINCT (
         user
       ) ) AS playersLastHour,
       COUNT( loginLogID ) AS loginsLastHour
       FROM LoginLog
       WHERE success =1
       AND TIME_TO_SEC( TIMEDIFF(NOW(), stamp )) < 3600";
     
    $result = $db_login->query($query);

    if ($result && !$result->isEmpty() && ($row = $result->nextRow() )) {
      $tvars['playersLastHour'] = $row['playersLastHour'];
      $tvars['lpuLastHour']     = $row['loginsLastHour'] / $row['playersLastHour'];
    }
    
    // UNIQUE LOGINS LAST DAY
    $query = 
      "SELECT COUNT( DISTINCT (
         user
       ) ) AS playersLastDay,
       COUNT( loginLogID ) AS loginsLastDay
       FROM LoginLog
       WHERE success =1
       AND TIME_TO_SEC( TIMEDIFF(NOW(), stamp )) < 3600 * 24";
     
    $result = $db_login->query($query);

    if ($result && !$result->isEmpty() && ($row = $result->nextRow() )) {
      $tvars['playersLastDay'] = $row['playersLastDay'];
      $tvars['lpuLastDay']     = $row['loginsLastDay'] / $row['playersLastDay'];
    }
    
    
    // UNIQUE LOGINS LAST WEEK
    $query = 
      "SELECT COUNT( DISTINCT (
         user
       ) ) AS playersLastWeek,
       COUNT( loginLogID ) AS loginsLastWeek
       FROM LoginLog
       WHERE success =1
       AND TIME_TO_SEC( TIMEDIFF(NOW(), stamp )) < 3600 * 24 * 7";
     
    $result = $db_login->query($query);

    if ($result && !$result->isEmpty() && ($row = $result->nextRow() )) {
      $tvars['playersLastWeek'] = $row['playersLastWeek'];
      $tvars['lpuLastWeek']     = $row['loginsLastWeek'] / $row['playersLastWeek'];
    }
    
    
    // UNIQUE LOGINS LAST MONTH
    $query = 
      "SELECT COUNT( DISTINCT (
         user
       ) ) AS playersLastMonth,
       COUNT( loginLogID ) AS loginsLastMonth
       FROM LoginLog
       WHERE success =1
       AND TIME_TO_SEC( TIMEDIFF(NOW(), stamp )) < 3600 * 24 * 30";
     
    $result = $db_login->query($query);

    if ($result && !$result->isEmpty() && ($row = $result->nextRow() )) {
      $tvars['playersLastMonth'] = $row['playersLastMonth'];
      $tvars['lpuLastMonth']     = $row['loginsLastMonth'] / $row['playersLastMonth'];
    }
    
    $tvars['dailyLogins']       = $dayInfo;
    tmpl_set($template, $tvars);
    
    return tmpl_parse($template);
  }
  
}
?>
