<?php
/*
 * statistic.html.php -
 * Copyright (c) 2003  OGP Team
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
  global $db, $template, $scienceTypeList, $unitTypeList;

  $template->throwError('Diese Seite wird noch überarbeitet.');
  return;

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'statistic.ihtml');

  foreach ($unitTypeList AS $value)
  {
    if (!$value->nodocumentation) {
      $UnitFieldsName[$value->dbFieldName] = $value->name;
    }
  }
  asort($UnitFieldsName);
  foreach ($scienceTypeList AS $value)
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
  $GodStats = $StatsData[GOD_STATS];
  ksort($GodStats);
  foreach ($GodStats as $God => $value) {
    tmpl_iterate($template, "GODS");
    tmpl_set($template, array("GODS/name"  => $ScienceFieldsName[$God],
                              "GODS/value" => array_pop(unserialize($value))));
  }

  /*
   * print god halfgod stats
   */
  $HalfGodStats = $StatsData[HALFGOD_STATS];
  ksort($HalfGodStats);
  foreach ($HalfGodStats as $HalfGod => $value) {
    tmpl_iterate($template, "HALFGODS");
    tmpl_set($template, array("HALFGODS/name"  => $ScienceFieldsName[$HalfGod],
                              "HALFGODS/value" => array_pop(unserialize($value))));
  }

  /*
   * print storage stats
   */
  $StorageStats = $StatsData[STORAGE_STATS];
  ksort($StorageStats);
  foreach ($StorageStats as $Storage => $value) {
    tmpl_iterate($template, "STORAGE");
    tmpl_set($template, array("STORAGE/name"  => $Storage,
                              "STORAGE/value" => array_pop(unserialize($value))));
  }

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

  tmpl_set($template, 'UNIT', $Units);
  unset($Name, $Unit, $Units, $UnitAll, $LastUnitStats);

  /*
   * show unit details
   */
  if ((request_var('show', "") == "unit_detail" && request_var('unit', "")) && request_var('show', "") != "all") {
    $Unit = request_var('unit', "");

    tmpl_iterate($template, "UNITDETAILS");
    tmpl_set($template, 'UNITDETAILS/title', 'Einheiten Statistik');
    tmpl_set($template, 'UNITDETAILS/name', $UnitFieldsName[$Unit]);

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
    tmpl_set($template, 'UNITDETAILS/UNITDETAILHOUR', $GraphDataHour);

    /*
     * get day stats
     */
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
    tmpl_set($template, 'UNITDETAILS/UNITDETAILDAY', $GraphDataDay);
  }

  return tmpl_parse($template);
}

?>
