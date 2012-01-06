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

################################################################################


/**
 * This function delegates the task at issue to the respective function.
 */
function takeover_main($caveID, $ownCaves) {
  global $template;

  // open template
  $template->setFile('takeover.tmpl');

  $feedback = '';
  $action = Request::getVar('action', '');
  switch ($action) {
    case 'withdrawal':
      if (Request::isPost('abort_withdrawal')) {
        break;
      }

      if (Request::isPost('confirm_withdrawal')) {
        $feedback = takeover_withdrawal();
        break;
      }

      // generate check value
      $_SESSION['withdrawal_check'] = uniqid('withdrawal');
      $template->addVar('withdrawal_check',  $_SESSION['withdrawal_check']);
    break;

    case 'change':
      if (Request::isPost('abort_change')) {
        break;
      }

      $bidding       = takeover_getBidding();
      $xCoord        = Request::getVar('xCoord', 0);
      $yCoord        = Request::getVar('yCoord', 0);
      $currentYCoord = (isset($bidding['yCoord'])) ? $bidding['yCoord'] : 0;
      $currentXCoord = (isset($bidding['xCoord'])) ? $bidding['xCoord'] : 0;

      // only one ordinate
      if ($xCoord == 0 || $yCoord == 0) {
        $feedback =  array('type' => 'error', 'message' => _('Zum Wechseln mußt du sowohl die x- als auch die y-Koordinate angeben.'));
        break;
      // already bidding on this cave
      } else if ($currentXCoord == $xCoord && $currentYCoord == $yCoord) {
        $feedback =  array('type' => 'error', 'message' => _('Du bietest bereits für diese Höhle.'));
        break;
      }

      if (Request::isPost('confirm_change')) {
        $feedback = takeover_change($xCoord, $yCoord);
        break;
      }

      // generate check value
      $_SESSION['change_check'] = uniqid('change_check');
      $template->addVar('change_check', $_SESSION['change_check']);
      $template->addVar('xCoord', $xCoord);
      $template->addVar('yCoord', $yCoord);
    break;
  }

  // get params
  $playerID = $_SESSION['player']->playerID;
  $maxcaves = $_SESSION['player']->takeover_max_caves;

  // show feedback
  if (!empty($feedback)) {
    $template->addVar('status_msg', $feedback);
  }

  // don't show page, if maxcaves reached
  if (sizeof($ownCaves) >= $maxcaves) {
    $template->addVars(array(
      'status_msg'  => array('type' => 'info', 'message' => sprintf(_('Sie haben bereits die maximale Anzahl von %d Höhlen erreicht.'), $maxcaves)),
      'show_page' => false,
    ));

  // prepare page
  } else {
    $template->addVar('show_page', true);

    // collect resource ratings
    $ratings = array();
    foreach ($GLOBALS['resourceTypeList'] AS $resource) {
      if ($resource->takeoverValue) {
        $ratings[] = array(
          'dbFieldName' => $resource->dbFieldName,
          'name'        => $resource->name,
          'value'       => $resource->takeoverValue
        );
      }
    }

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
    $template->addVars(array(
      'popularity'       => GameConstants::TAKEOVER_MAX_POPULARITY_POINTS,
      'maxcaves'         => $maxcaves,
      'target_x_coord'   => Request::getVar('targetXCoord', 0),
      'target_y_coord'   => Request::getVar('targetYCoord', 0),
      'resource_ratings' => $ratings
    ));

    // get bidding
    $bidding = takeover_getBidding(count($ownCaves));
    if ($bidding) {
      $template->addVars(array(
        'chosen'          => true,
        'current_x_coord' => $bidding['xCoord'],
        'current_y_Coord' => $bidding['yCoord'],
        'current_name'    => $bidding['caveName'],
        'bidding'         => $bidding,
      ));
    } else {
      $template->addVar('chosen', false);
    }
  }
}


################################################################################


/**
 * This function changes the cave.
 */

function takeover_change($xCoord, $yCoord) {
  // get check
  $change_check = Request::getVar('change_check', 0);

  // verify $check
  if ($change_check != $_SESSION['change_check']) {
    return array('type' => 'error', 'message' => _('Sie können nicht für diese Höhle bieten. Wählen sie eine freie Höhle.'));
  }

  // cave change successfull
  if (changeCaveIfReasonable($xCoord, $yCoord)) {
    return array('type' => 'success', 'message' => sprintf(_('Sie bieten nun für die Höhle in (%d|%d).'), $xCoord, $yCoord));
  }

  return array('type' => 'error', 'message' => _('Sie können nicht für diese Höhle bieten. Wählen sie eine freie Höhle.'));
}


################################################################################


/**
 * This function let the player withdraw his bidding.
 */

function takeover_withdrawal() {
  global $db;

  // get check
  $withdrawal_check = Request::getVar('withdrawal_check', 0);

  // verify $check
  if (!isset($_SESSION['withdrawal_check']) || $withdrawal_check != $_SESSION['withdrawal_check']) {
    return array('type' => 'error', 'message' => _('Sie konnten ihr Angebot nicht zurückziehen.'));
  }

  // prepare query
  $sql = $db->prepare("DELETE FROM ". CAVE_TAKEOVER_TABLE ." WHERE playerID = :playerID");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

  if (!$sql->execute()) {
    return array('type' => 'error', 'message' => _('Sie konnten ihr Angebot nicht zurückziehen.'));
  }

  return array('type' => 'success', 'message' => _('Sie haben ihr Angebot zurückgezogen.'));
}


################################################################################
# HELP FUNCTIONS                                                               #
################################################################################


/**
 *
 */

function takeover_getBidding($caveCount=0) {
  global $db;

  // prepare query
  $sql = $db->prepare("SELECT * FROM ". CAVE_TAKEOVER_TABLE ." WHERE playerID = :playerID");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);

  // return NULL on error or if recordSet is empty, as there is no bidding
  if (!$sql->execute()) {
    return NULL;
  }

  // fetch row
  $row = $sql->fetch();
  if (!$row) return NULL;

  // fill return value
  $bidding = array(
    'caveID'      => $row['caveID'],
    'xCoord'      => $row['xCoord'],
    'yCoord'      => $row['yCoord'],
    'status'      => $row['status'],
    'caveName'    => $row['name'],
    'uh_caveName' => unhtmlentities($row['name'])
  );

  // get own status
  $bidding += takeover_getStatusPic($row['status']);

  // get sent resources
  $sum = 0;
  $resources = array();
  foreach ($GLOBALS['resourceTypeList'] as $resource) {
    $amount = $row[$resource->dbFieldName];
    if ($amount > 0) {
      $resources[] = array('name'  => $resource->name, 'value' => $amount);
      $sum += $amount * $resource->takeoverValue;
    }
  }

  // merge $resources with bidding
  if (sizeof($resources)) {
    $bidding['resource'] = $resources;
    $bidding['sum'] = $sum;
    $bidding['proportion'] = ($sum > 0 && $caveCount > 0) ? round(($sum / (200 * pow($caveCount, 2))), 3) : 0;
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
    $bidding['bidder'] = $bidders;
  }

  return $bidding;
}


################################################################################


/**
 *
 */

function takeover_getStatusPic($status) {
  return array('status_img' => 'star' . substr($status + 1000, 1), 'status_txt' => $status);
}


################################################################################


/**
 * check:
 * 1. this cave is a takeoverable cave
 * 2. neuen Eintrag in Cave_takeover (alten ueberschreiben)
 */

function changeCaveIfReasonable($xCoord, $yCoord) {
  global $db;

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
        foreach($GLOBALS['resourceTypeList'] AS $resource) {
            $colNames[] = $resource->dbFieldName;
            $values[]   = "0";
        }
        $colNames = implode(', ', $colNames);
        $values   = implode(', ', $values);

        $sql = $db->prepare("REPLACE INTO ". CAVE_TAKEOVER_TABLE ."
                             (playerID, caveID, xCoord, yCoord, name, " . $colNames . ", status)
                             VALUES (:playerID, :caveID, :xCoord, :yCoord, :name, " . $values . ", 0)");
        $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
        $sql->bindValue('caveID', $row['caveID'], PDO::PARAM_INT);
        $sql->bindValue('xCoord', $row['xCoord'], PDO::PARAM_INT);
        $sql->bindValue('yCoord', $row['yCoord'], PDO::PARAM_INT);
        $sql->bindValue('name', $row['name'], PDO::PARAM_STR);
        if ($sql->execute()) {
          $result = TRUE;
        }

    }
  }

  return $result;
}

?>