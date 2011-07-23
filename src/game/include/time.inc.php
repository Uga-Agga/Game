<?php
/*
 * time.inc.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function time_fromDatetime($datetime) {
  return strtotime($datetime . " UTC");
}

function time_toDatetime($timestamp) {
  return gmdate("Y-m-d H:i:s", $timestamp);
}

function time_formatDatetime($timestamp) {
  
  $timecorrection = $_SESSION['player']->getTimeCorrection();
  return gmdate("d.m.Y H:i:s", time_fromDatetime($timestamp) + $timecorrection);
}

function time_timestampToTime($timestamp) {

  // in mysql versions > 4, timestamps have already the right format
  if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timestamp))
    return strtotime($timestamp);

  $time=
    substr($timestamp, 0,4)."-".
    substr($timestamp, 4,2)."-".
    substr($timestamp, 6,2)." ".
    substr($timestamp, 8,2).":".
    substr($timestamp,10,2).":".
    substr($timestamp,12,2);

  return strtotime($time);     // NOT TESTED !!!!
}

function time_formatDuration($seconds) {
  
  return sprintf("%02d:%02d:%02d",
     ($seconds/3600),
     ($seconds/60)%60,
     $seconds%60);
}


define("STARTING_YEAR",   1);
define("MONTHS_PER_YEAR", 10);
define("DAYS_PER_MONTH",  24);
define("HOURS_PER_DAY",   24);

define("SPEED_RATIO",     24);

/* Mon Sep  2 00:00:00 CEST 2002 */
define("START_TIME",      1030917600);

/* getUgaAggaTime returns an associative array
* containig the following keys
*
* year
* month
* day
* hour
*/
function getUgaAggaTime($time = NULL) {

  $retval = array();

  if ($time === NULL)
    $time = time();

  $hours  = (int)(($time - START_TIME) * SPEED_RATIO / (60 * 60));
  $days   = (int)($hours / HOURS_PER_DAY);
  $months = (int)($days / DAYS_PER_MONTH);

  $retval['hour']  = $hours  % HOURS_PER_DAY;
  $retval['day']   = $days   % DAYS_PER_MONTH  + 1;
  $retval['month'] = $months % MONTHS_PER_YEAR + 1;
  $retval['year']  = (int)($months / MONTHS_PER_YEAR) + STARTING_YEAR;

  return $retval;
}

function getUgaAggaTimeDiff($from, $to) {

  $retval = array();

  $hours  = (int)(($to - $from) * SPEED_RATIO / (60 * 60));
  $days   = (int)($hours / HOURS_PER_DAY);
  $months = (int)($days / DAYS_PER_MONTH);

  $retval['hour']  = $hours  % HOURS_PER_DAY;
  $retval['day']   = $days   % DAYS_PER_MONTH;
  $retval['month'] = $months % MONTHS_PER_YEAR;
  $retval['year']  = (int)($months / MONTHS_PER_YEAR);

  return $retval;
}

function getUgaAggaTimeFromTimeStamp($timestamp) {

  $time = mktime(substr($timestamp, 8,2),
                 substr($timestamp,10,2),
                 substr($timestamp,12,2),
                 substr($timestamp, 4,2),
                 substr($timestamp, 6,2),
                 substr($timestamp, 0,4));

  return getUgaAggaTime($time);
}


/*
 * getMonthName() returns the name of the specified month number
 * $month is a number between 1 and MONTHS_PER_YEAR
 */

function getMonthName($month) {
  static $monthNames = array('Agga',
                             'Eisigkeit',
                             'Schnehbrandh',
                             'Binenschtich',
                             'Brrunfhd',
                             'Uga',
                             'Ernte',
                             'Duesternis',
                             'Verderb',
                             'Frrost');

  return $monthNames[($month - 1) % sizeof($monthNames)];
}

?>