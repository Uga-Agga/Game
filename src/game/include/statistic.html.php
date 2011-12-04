<?php
/*
 * statistic.html.php -
 * Copyright (c) 2010  David Unger
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
  * print god stats
  */
  $GodStatsList = array();
  $GodStats = $StatsData[GOD_STATS];
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
  $HalfGodStats = $StatsData[HALFGOD_STATS];
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
  $StorageStats = $StatsData[STORAGE_STATS];
  ksort($StorageStats);
  foreach ($StorageStats as $Storage => $value) {
    $StorageStatsList[] = array("name" => $Storage,
                              "value" => array_pop(unserialize($value)));
  }
  $template->addVars(array('StorageStats' => $StorageStatsList));

  /*
   * het Unit stats
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
  $LastUnitStats = array_pop($UnitStats[STATS_HOUR]);
  foreach ($UnitFieldsName as $Unit => $Name) {
    if (!isset($LastUnitStats[$Unit])) {
      continue;
    }

    $UnitAll = $UnitAll + $LastUnitStats[$Unit];
    $Units[] = array('unit'  => $Unit,
                     'name'  => $Name,
                     'value' => $LastUnitStats[$Unit]);
  }
  $Units[] = array('unit'  => 'all',
                   'name'  => 'Insgesamt:',
                   'value' => $UnitAll);

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
//    tmpl_set($template, 'UNITDETAILS/title', 'Einheiten Statistik');

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
