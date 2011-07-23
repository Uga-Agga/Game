<?php
/*
 * takeover.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('formula_parser.inc.php');

################################################################################


/**
 * This function delegates the task at issue to the respective function.
 */

function takeover_main($caveID, $ownCaves) {

  // initialize return value
  $result = '';

  // get current task
  $task = request_var('task', "");

  switch ($task) {

    // show main page
    default:
      $result = takeover_show($caveID, $ownCaves);
      break;

    // show change confirmation page
    case 'confirm_change':
      $result = takeover_confirmChange($caveID, $ownCaves);
      break;

    // change cave page
    case 'change':
      $result = takeover_change($caveID, $ownCaves);
      break;

    // show withdrawal confirmation page
    case 'confirm_withdrawal':
      $result = takeover_confirmWithdrawal($caveID, $ownCaves);
      break;

    // withdrawal page
    case 'withdrawal':
      $result = takeover_withdrawal($caveID, $ownCaves);
      break;
  }

  return $result;
}


################################################################################


/**
 * This function shows the general information page
 */

function takeover_show($caveID, $ownCaves, $feedback = NULL) {
  global $resourceTypeList,
         $TAKEOVERMAXPOPULARITYPOINTS, $TAKEOVERMINRESOURCEVALUE;

  // get params
  $playerID = $_SESSION['player']->playerID;
  $maxcaves = $_SESSION['player']->takeover_max_caves;

  // open template
  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'takeover.ihtml');

  // show feedback
  if ($feedback !== NULL)
    tmpl_set($template, '/MESSAGE/message', $feedback);

  // don't show page, if maxcaves reached
  if (sizeof($ownCaves) >= $maxcaves) {
    tmpl_set($template, '/MESSAGE/message', sprintf(_('Sie haben bereits die maximale Anzahl von %d H&ouml;hlen erreicht.'), $maxcaves));

  // prepare page
  } else {

    // collect resource ratings
    $ratings = array();
    foreach ($resourceTypeList AS $resource)
      if ($resource->takeoverValue)
        $ratings[] = array('dbFieldName' => $resource->dbFieldName,
                           'name'        => $resource->name,
                           'value'       => $resource->takeoverValue);

    // fill page
    tmpl_set($template, '/TAKEOVER',
             array('beliebtheit'   => $TAKEOVERMAXPOPULARITYPOINTS,
                   'mindestgebot'  => $TAKEOVERMINRESOURCEVALUE,
                   'maxcaves'      => $maxcaves,
                   'targetXCoord'  => request_var('targetXCoord', 0),
                   'targetYCoord'  => request_var('targetYCoord', 0),
                   'RESOURCEVALUE' => $ratings));

    // get bidding
    $bidding = takeover_getBidding();
    if ($bidding) {
      tmpl_set($template, '/TAKEOVER/CHOSEN', $bidding);
      tmpl_set($template, '/TAKEOVER', array('currentXCoord' => $bidding['xCoord'],
                                             'currentYCoord' => $bidding['yCoord']));
    }
  }
  return tmpl_parse($template);
}


################################################################################


/**
 * This function shows a page where one can confirm the change of one's cave
 */

function takeover_confirmChange($caveID, $ownCaves) {
  global $config;

  // get params
  $xCoord        = request_var('xCoord', 0);
  $yCoord        = request_var('yCoord', 0);
  $currentXCoord = request_var('currentXCoord', 0);
  $currentYCoord = request_var('currentYCoord', 0);

  // only one ordinate
  if ($xCoord == 0 || $yCoord == 0)
    return takeover_show($caveID, $ownCaves, _('Zum Wechseln mu&szlig;t du sowohl die x- als auch die y-Koordinate angeben.'));

  // already bidding on this cave
  else if ($currentXCoord == $xCoord && $currentYCoord == $yCoord)
    return takeover_show($caveID, $ownCaves, _('Du bietest bereits f&uuml;r diese H&ouml;hle.'));

  // open template
  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'takeover_change.ihtml');

  // generate check value
  $_SESSION['check'] = uniqid('change');

  // now bidding on another one
  tmpl_set($template, array('xCoord' => $xCoord, 'yCoord' => $yCoord,
                            'check' => $_SESSION['check']));

  return tmpl_parse($template);
}


################################################################################


/**
 * This function changes the cave.
 */

function takeover_change($caveID, $ownCaves) {

  // get check
  $check = request_var('check', 0);

  // get coordinates
  $xCoord = request_var('xCoord', 0);
  $yCoord = request_var('yCoord', 0);

  // verify $check
  if ($check != $_SESSION['check'])
    return takeover_show($caveID, $ownCaves, _('Sie k&ouml;nnen nicht f&uuml;r diese H&ouml;hle bieten. W&auml;hlen sie eine freie H&ouml;hle.'));

  // not enough informations
  if ($xCoord == 0 || $yCoord == 0)
    return takeover_show($caveID, $ownCaves, _('Sie k&ouml;nnen nicht f&uuml;r diese H&ouml;hle bieten. W&auml;hlen sie eine freie H&ouml;hle.'));

  // cave change successfull
  if (changeCaveIfReasonable($xCoord, $yCoord))
    return takeover_show($caveID, $ownCaves,
                         sprintf(_('Sie bieten nun f&uuml;r die H&ouml;hle in (%d|%d).'), $xCoord, $yCoord));

  return takeover_show($caveID, $ownCaves, _('Sie k&ouml;nnen nicht f&uuml;r diese H&ouml;hle bieten. W&auml;hlen sie eine freie H&ouml;hle.'));
}


################################################################################


/**
 * This function shows a page where one can confirm one's withdrawal
 */

function takeover_confirmWithdrawal($caveID, $ownCaves) {;

  // open template
  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'takeover_withdrawal.ihtml');

  // generate check value
  $_SESSION['check'] = uniqid('withdrawal');
  tmpl_set($template, array('check' => $_SESSION['check']));

  return tmpl_parse($template);
}


################################################################################


/**
 * This function let the player withdraw his bidding.
 */

function takeover_withdrawal($caveID, $ownCaves) {
  global $db;

  // get check
  $check = request_var('check', 0);

  // verify $check
  if ($check != $_SESSION['check'])
    return takeover_show($caveID, $ownCaves, _('Sie konnten ihr Angebot nicht zur&uuml;ckziehen.'));

  // withdraw

  // prepare query
  $sql = $db->prepare("DELETE FROM ". CAVE_TAKEOVER_TABLE ." WHERE playerID = :playerID");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

  if (!$sql->execute())
    return takeover_show($caveID, $ownCaves, _('Sie konnten ihr Angebot nicht zur&uuml;ckziehen.'));

  return takeover_show($caveID, $ownCaves, _('Sie haben ihr Angebot zur&uuml;ckgezogen.'));
}


################################################################################
# HELP FUNCTIONS                                                               #
################################################################################


/**
 *
 */

function takeover_getBidding() {
  global $db, $resourceTypeList;

  // prepare query
  $sql = $db->prepare("SELECT * FROM ". CAVE_TAKEOVER_TABLE ." WHERE playerID = :playerID");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

  // return NULL on error or if recordSet is empty, as there is no bidding
  if (!$sql->execute())
    return NULL;

  // fetch row
  $row = $sql->fetch();
  if (!$row) return NULL;
  
  // fill return value
  $bidding = array('caveID'      => $row['caveID'],
                   'xCoord'      => $row['xCoord'],
                   'yCoord'      => $row['yCoord'],
                   'status'      => $row['status'],
                   'caveName'    => $row['name'],
                   'uh_caveName' => unhtmlentities($row['name']));

  // get own status
  $bidding += takeover_getStatusPic($row['status']);

  // get sent resources
  $sum = 0;
  $resources = array();
  foreach ($resourceTypeList as $resource) {
    $amount = $row[$resource->dbFieldName];
    if ($amount > 0) {
      $resources[] = array('name'  => $resource->name, 'value' => $amount);
      $sum += $amount * $resource->takeoverValue;
    }
  }

  // merge $resources with bidding
  if (sizeof($resources)) {
    $bidding['RESOURCE'] = $resources;
    $bidding['SUM'] = array('sum' => $sum);
  } else {
    $bidding['NONE'] = array('iterate' => '');
  }

  // get other bidders
  $bidders = array();
  $sql = $db->prepare("SELECT p.name, p.playerID, ct.status 
                       FROM ". CAVE_TAKEOVER_TABLE ." ct, Player p 
                       WHERE caveID = :caveID AND ct.playerID = p.playerID 
                       AND ct.playerID != :playerID");
  $sql->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

  if ($sql->execute())
    while($row = $sql->fetch()) {
      $temp  = array('playername' => $row['name'],
                     'playerID'   => $row['playerID']);
      $temp += takeover_getStatusPic($row['status']);
      $bidders[] = $temp;
    }

  // merge $bidders with bidding
  if (sizeof($bidders)) {
    $bidding['BIDDER'] = $bidders;
  } else {
    $bidding['NOONE'] = array('iterate' => '');
  }

  return $bidding;
}


################################################################################


/**
 *
 */

function takeover_getStatusPic($status) {
  return array('status-img' => 'star' . substr($status + 1000, 1), 'status-txt' => $status);
}


################################################################################


/**
 * check:
 * 1. this cave is a takeoverable cave
 * 2. neuen Eintrag in Cave_takeover (alten ueberschreiben)
 */

function changeCaveIfReasonable($xCoord, $yCoord) {
  global $db, $resourceTypeList;

  // prepare return value
  $result = FALSE;

  // ist diese Hoehle ueberhaupt frei? Welche caveID hat diese?
  $sql = $db->prepare("SELECT * FROM ". CAVE_TABLE ." WHERE playerID = 0 AND takeoverable = 1 
                 AND xCoord = :xCoord AND yCoord = :yCoord"); 
  $sql->bindValue('xCoord', $xCoord, PDO::PARAM_INT);
  $sql->bindValue('yCoord', $yCoord, PDO::PARAM_INT);
  if ($sql->execute()) {
    // this cave has no owner and may be taken over
    if ($row = $sql->fetch()) {

        // prepare statement
        $colNames = array();
        $values = array();
        foreach($resourceTypeList AS $resource) {
            $colNames[] = $resource->dbFieldName;
            $values[]   = "0";
        }
        $colNames = implode(",", $colNames);
        $values   = implode(", ", $values);

        $sql = sprintf("REPLACE INTO ". CAVE_TAKEOVER_TABLE ." ".
                         "(playerID, caveID, xCoord, yCoord, name, %s, status) ".
                         "VALUES (%d, %d, %d, %d, '%s', %s, 0)",
                         $colNames,
                         $_SESSION['player']->playerID, $row['caveID'],
                         $row['xCoord'], $row['yCoord'], $row['name'], $values);
        
        if ($db->query($sql))
          $result = TRUE;
    }
  }

  return $result;
}

?>