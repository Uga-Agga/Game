<?php
/*
 * statistic.html.php -
 * Copyright (c) 2010-2012  David Unger <unger.dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');
define('GRAPH_URL', 'http://chart.apis.google.com/chart?');

function statistic_getContent() {
  global $db, $template;

  $template->setFile('statistic.tmpl');

  foreach ($GLOBALS['unitTypeList'] AS $value)
  {
    if (!$value->nodocumentation) {
      $UnitFieldsName[$value->dbFieldName] = $value->name;
    }
  }
  asort($UnitFieldsName);
  foreach ($GLOBALS['scienceTypeList'] AS $value)
  {
    $ScienceFieldsName[$value->dbFieldName] = $value->name;
  }

  $sql = $db->prepare("SELECT * FROM " . STATISTIC_TABLE);
  
  if (!$sql->execute()) {
    return;
  }

  $StatsData = array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $StatsData[$row['type']][$row['name']] = $row['value'];
  }

  if (!sizeof($StatsData)) {
    return;
  }

  /*
  * Game stats
  */

  // Online in der letzten Std:
  $time = time() - 1800;
  $lastActionTime = gmdate("Y-m-d H:i:s", $time);

  $sql = $db->prepare("SELECT count(*) as count
                       FROM " . SESSION_TABLE . "
                       WHERE lastAction > :lastAction");
  $sql->bindValue('lastAction', $lastActionTime, PDO::PARAM_INT);
  if (!$sql->execute()) return;
  $userOnline = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  // erstelle Accounts
  $sql = $db->prepare("SELECT count(*) as count FROM " . PLAYER_TABLE);
  if (!$sql->execute()) return;
  $accounts = $sql->fetch(PDO::FETCH_ASSOC);
  $sql->closeCursor();

  $template->addVars(array(
    'user_online'   => $userOnline['count'],
    'accounts_all'  => $accounts['count'],
    'accounts_free' => MAX_ACCOUNTS - $accounts['count']
  ));

  /*
  * print god stats
  */
  $GodStatsList = array();
  $GodStats = isset($StatsData[GOD_STATS]) ? $StatsData[GOD_STATS] : array();
  ksort($GodStats);
  foreach ($GodStats as $God => $value) {
    $GodStatsList[] =  array("name" => $ScienceFieldsName[$God],
                              "value" => array_pop(unserialize($value)));
  }
  $template->addVars(array('GodStats' => $GodStatsList));

  /*
  * print god halfgod stats
  */
  $HalfGodStatsList = array();
  $HalfGodStats = isset($StatsData[HALFGOD_STATS]) ? $StatsData[HALFGOD_STATS] : array();
  ksort($HalfGodStats);
  foreach ($HalfGodStats as $HalfGod => $value) {
    if (!isset($ScienceFieldsName[$HalfGod])) {
      continue;
    }

    $HalfGodStatsList[] = array("name" => $ScienceFieldsName[$HalfGod],
                              "value" => array_pop(unserialize($value)));
  }
  $template->addVars(array('HalfGodStats' => $HalfGodStatsList));

  /*
  * print storage stats
  */
  $StorageStatsList = array();
  $StorageStats = isset($StatsData[STORAGE_STATS]) ? $StatsData[STORAGE_STATS] : array();
  ksort($StorageStats);
  foreach ($StorageStats as $Storage => $value) {
    $StorageStatsList[] = array("name" => $Storage,
                              "value" => array_pop(unserialize($value)));
  }
  $template->addVars(array('StorageStats' => $StorageStatsList));

  /*
  * print wonder stats
  */
  $WonderStatsList = array();
  if (isset($StatsData[WONDER_STATS]) && !empty($StatsData[WONDER_STATS])) {
    init_Wonders();

    $WonderStats = $StatsData[WONDER_STATS];
    ksort($WonderStats);
    foreach ($WonderStats as $wonder => $value) {
      $value = json_decode($value, true);

      $WonderStatsList[] = array(
        'name'    => $GLOBALS['wonderTypeList'][$wonder]->name,
        'success' => $value['success'],
        'fail'    => $value['fail'],
      );
    }
  }
  $template->addVars(array('WonderStats' => $WonderStatsList));

  /*
   * get Unit stats
   */
  $sql = $db->prepare("SELECT * FROM ". STATISTIC_UNIT_TABLE ." ORDER BY type_sub DESC");

  if (!$sql->execute()) {
    return;
  }

  $StatsData = array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $UnitStats[$row['type']][$row['type_sub']] = $row;
  }

  /*
   * print Unit stats
   */
  $Units = array(); $UnitAll= 0;
  $LastUnitStats = isset($UnitStats[STATS_HOUR]) ? array_pop($UnitStats[STATS_HOUR]) : array();
  foreach ($UnitFieldsName as $Unit => $Name) {
    if (!isset($LastUnitStats[$Unit])) {
      continue;
    }

    $UnitAll = $UnitAll + $LastUnitStats[$Unit];
    $Units[] = array('unit'  => $Unit,
                     'name'  => $Name,
                     'value' => $LastUnitStats[$Unit]);
  }

  if(!empty($LastUnitStats)) {
    $Units[] = array('unit'  => 'all',
                     'name'  => 'Insgesamt:',
                     'value' => $UnitAll);
  }

  $template->addVars(array('Units' => $Units));
  unset($Name, $Unit, $Units, $UnitAll, $LastUnitStats);

  /*
   * show unit details
   */
  $show = Request::getVar('show', '');
  $Unit = Request::getVar('unit', '');
  if ($show == "unit_detail" && !empty($Unit) && $Unit != "all") {
    $template->addVars(array(
      'showUnitDetails' => true, 
      'unitName' => $UnitFieldsName[$Unit]
    ));

    /*
     * get hour stats
     */
    foreach ($UnitStats[STATS_HOUR] as $id => $data) {
        $UnitHourStats[$id] = array('time' => $data['time'], 'value' => $data[$Unit]);
    }
    unset($data, $id);

    $GraphDataHour = array(); $i = 0;
    krsort($UnitHourStats);
    foreach ($UnitHourStats as $id => $data) {
        $GraphDataHour[] = array('id'    => $i++,
                                 'name'  => substr($data['time'], 11, -3),
                                 'value' => $data['value']);
    }
    unset($data, $id);
    $template->addVars(array('GraphDataHour' => $GraphDataHour));

    /*
     * get day stats
     */
    if (isset($UnitStats[STATS_DAY]) && sizeof($UnitStats[STATS_DAY])) {
      foreach ($UnitStats[STATS_DAY] as $id => $data) {
          $UnitDayStats[$id] = array('time' => $data['time'], 'value' => $data[$Unit]);
      }
      unset($data, $id);

      $GraphDataDay = array(); $i = 0;
      krsort($UnitDayStats);
      foreach ($UnitDayStats as $id => $data) {
          $GraphDataDay[] = array('id'    => $i++,
                                  'name'  => substr($data['time'], 5, -3),
                                  'value' => $data['value']);
      }
      unset($data, $id);
      $template->addVars(array('GraphDataDay' => $GraphDataDay));
    }
  }
}

?>
