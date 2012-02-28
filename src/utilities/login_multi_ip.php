<?php
/*
 * login_multi_ip.php - Finding users logging in using the same IP
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/***** COMMAND LINE *****/

// include PEAR::Console_Getopt
require_once('Console/Getopt.php');

// check for command line options
$options = Console_Getopt::getOpt(Console_Getopt::readPHPArgv(), 'dht:');
if (PEAR::isError($options)) {
  multiip_usage();
  exit(1);
}

// check for options
$debugFlag = FALSE;
$time_intervall = 12;
foreach ($options[0] as $option) {

  // option h
  if ('h' == $option[0]) {
    multiip_usage(); exit(1);

  // option d
  } else if ('d' == $option[0]) {
    $debugFlag = TRUE;

  // option t
  } else if ('t' == $option[0]) {
    $time_intervall = $option[1];
  }
}

/***** INIT *****/

// include necessary files
include "util.inc.php";
include INC_DIR . "config.inc.php";
include INC_DIR . "db.inc.php";
include INC_DIR . "basic.lib.php";

// show header
multiip_showHeader();

// connect to databases
$db_login = db_connectToLoginDB();

/***** GET GOING *****/


// ** IP **/
// get distinct ip's
$string = "ip";
multiip_showBetween($string);
$ips = multiip_getDistinct_($string);

// check each ip
foreach ($ips as $ip) {
  $users = multiip_check_($ip, $string);
  if (sizeof($users) > 1) {
    multiip_log("%s: (%s)", $ip, implode(',', $users));
  }
}

/**Passwords**/
//get distinct passwords
$string = "password";
multiip_showBetween($string);
$passwords = multiip_getDistinct_($string);

//check each password
foreach ($passwords as $pass) {
  $users = multiip_check_($pass, $string);
  if(sizeof($users) > 1) {
    multiip_log("%s: (%s)", $pass, implode(',', $users));
  }
}
/**PollID**/
//get distinct pollIDs
$string = "pollID";
multiip_showBetween($string);
$pollIDs = multiip_getDistinct_($string);

//check each pollID
foreach ($pollIDs as $poll) {
  $users = multiip_check_($poll, $string);
  if(sizeof($users) > 1) {
    multiip_log("%s: (%s)", $poll, implode(', ', $users));
  }
}

// ***** FUNCTIONS ***** *******************************************************

/**
 * Shows usage
 */
function multiip_usage() {
  echo
    "Usage: php login_multi_ip.php [-d] [-h] [-t time_interval]\n".
    "  -d                Debug\n".
    "  -h                This help\n".
    "  -t time_interval  Only consider ips of the last time_interval hours\n";
}

/**
 * Logging function with printf syntax
 */
function multiip_log($format /* , .. */) {

  // get args
  $args = func_get_args();

  // get format string
  $format = array_shift($args);

  // do something
  echo vsprintf($format, $args) . "\n";
}

/**
 * Shows header
 */
function multiip_showHeader() {
  multiip_log('------------------------------------------------------------');
  multiip_log('- FINDING MULTIS -----------------------------------------');
  multiip_log('- from %s', date('r'));
  multiip_log('------------------------------------------------------------');
}
/**
 * Show Between
 */
function multiip_showBetween($string){
  multiip_log('');
  multiip_log('------------------------------------------------------------');
  multiip_log('-- searching %s --', $string);
  multiip_log('------------------------------------------------------------');
  multiip_log('');
  
}


function multiip_getDistinctIPs() {

  // prepare query
  global $time_intervall, $db_login;
  $sql = $db_login->prepare("SELECT ip FROM LoginLog 
                             WHERE success = 1 
                             AND stamp > NOW() - INTERVAL :time_intervall HOUR 
                             GROUP BY ip 
                             HAVING COUNT(*) > 1");
  $sql->bindValue('time_intervall', $time_intervall, PDO::PARAM_INT);

  // on error
  if (!$sql->execute()) {
    multiip_log('Could not retrieve ips from LoginLog');
    exit(1);
  }

  // collect records
  $ips = array();
  while ($row = $sql->fetch())
    $ips[] = $row['ip'];

  $sql->closeCursor();
    
  return $ips;
}

function multiip_checkIP($ip) {

  // prepare query
  global $time_intervall, $db_login;
  $sql = $db_login->prepare("SELECT user 
                             FROM LoginLog 
                             WHERE ip = :ip 
                             AND stamp > NOW() - INTERVAL :time_intervall HOUR 
                             AND success = 1 
                             GROUP BY user");
  $sql->bindValue('ip', $ip, PDO::PARAM_STR);
  $sql->bindValue('time_intervall', $time_intervall, PDO::PARAM_INT);

  // on error
  if (!$sql->execute()) {
    multiip_log('Could not check ip from LoginLog');
    exit(1);
  }
  
  // collect records
  $users = array();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    $users[] = $row['user'];
  }
  $sql->closeCursor();

  return $users;
}

function multiip_getDistinct_($string) {

  // prepare query
  global $time_intervall, $db_login;
  $sql = $db_login->prepare("SELECT $string 
                             FROM LoginLog 
                             WHERE success = 1 
                             AND stamp > NOW() - INTERVAL :time_intervall HOUR 
                             GROUP BY {$string} HAVING COUNT(*) > 1");
  $sql->bindValue('time_intervall', $time_intervall, PDO::PARAM_INT);
  
  // on error
  if (!$sql->execute()) {
    multiip_log('Could not retrieve %ss from LoginLog', $string);
    exit(1);
  }

  // collect records
  $result = array();
  while ($row = $sql->fetch()) {
    $result[] = $row[$string];
  }

  return $result;
}

function multiip_check_($search, $string) {

  // prepare query
  global $time_intervall, $db_login;
  $sql = $db_login->prepare("SELECT user 
                             FROM LoginLog 
                             WHERE {$string} = :search 
                             AND stamp > NOW() - INTERVAL :time_intervall HOUR 
                             AND success = 1 
                             GROUP BY user");
  $sql->bindValue('search', $search, PDO::PARAM_STR);
  $sql->bindValue('time_intervall', $time_intervall, PDO::PARAM_INT);
  
  // on error
  if (!$sql->execute()) {
    multiip_log('Could not check %s from LoginLog', $string);
    exit(1);
  }

  // collect records
  $users = array();
  while ($row = $sql->fetch())
    $users[] = $row['user'];

  return $users;
}

?>