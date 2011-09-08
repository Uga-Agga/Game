<?php
/*
 * merchant.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once("game_rules.php");
//init Potions
init_potions();

function merchant_getMechantDetail($playerID, $caveID, &$details) {
  global $buildingTypeList, $resourceTypeList, $tradeCategoriesTypeList, $tradeTypeList, $unitTypeList;
  global $db, $template;
  
  // open template
  $template->setFile('merchant.tmpl');

  // messages
  $messageText = array (
    -2 => array('type' => 'info', 'message' =>"Der Händler schaut dich entgeistert an. \"Du warst doch gerade erst hier. Komm später nochmal wieder\""),
    -1 =>  array('type' => 'error', 'message' =>"Es ist ein Fehler bei der Verarbeitung Ihrer Anfrage aufgetreten. Bitte wenden Sie sich an die Administratoren."),
     0 =>  array('type' => 'info', 'message' =>"Der Händler schüttelt mit dem Kopf. \"Meine Ware hat ihren Preis und sie ist jeden Rohstoff wert! Der nächste Häuptling ist bestimmt bereit meinen Preis zu zahlen!\""),
     1 =>  array('type' => 'success', 'message' =>"Erfreut nimmt der Händler deine Bezahlung entgegen. \"Ich hoffe dir gefällt meine Ware. Empfehle mich bitte weiter!\"")
  );

  $action = request_var('action', '');
  switch ($action) {
/****************************************************************************************************
*
* bauen? naja. eher ordern ;)
*
****************************************************************************************************/
    case 'build':
      $tradeID = request_var('tradeID', -1);

      $messageID = merchant_processOrder($tradeID, $caveID, $details);
      $details = getCaveSecure($caveID, $_SESSION['player']->playerID);
    break;
  }

/****************************************************************************************************
*
* Anzeigen der kaufbaren Sachen
*
****************************************************************************************************/
  foreach ($tradeCategoriesTypeList as $j => $cat) {
    $trades[$j] = array(
      'id'   => $j,
      'name' => $tradeCategoriesTypeList[$j]->name
    );

    $count = 0;
    foreach ($tradeTypeList as $id => $trade) {
      if ($trade->nodocumentation) {
        continue;
      }

      if ($trade->category != $tradeCategoriesTypeList[$j]->id) {
        continue;
      }
   
      $less = false;
      $canbuy = true;
      $locktill = '';
      $sql = $db->prepare("SELECT LockTill < :LockTill as allowed, LockTill
                           FROM ". TRADELOCK_TABLE . "
                           WHERE PlayerID = :playerID
                             AND cat = :cat");
      $sql->bindValue('LockTill', date("Y-m-d H:i:s", time()), PDO::PARAM_STR);
      $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
      $sql->bindValue('cat', $cat->id, PDO::PARAM_STR);

      if ($sql->execute()) {
        $row = $sql->fetch(PDO::FETCH_ASSOC);

        if ($row['allowed'] == 0 && $row) {
          $canbuy = false;
          $locktill= time_fromDatetime($row['LockTill']);
        }
      }
      $sql->closeCursor();

      $trades[$j]['data'][$id] = array(
        'bgID'        => ($count++ % 2) + 1,
        'name'        => $trade->name,
        'trade_id'    => $id,
        'description' => $trade->description
      );

       $trades[$j]['data'][$id] = array_merge($trades[$j]['data'][$id], parseCost($trade, $details));

      // show the building link ?!
      if (!$canbuy)
        $trades[$j]['data'][$id]['no_build_msg'] = sprintf(_('Wieder im Angebot ab %s'), gmdate("d.m.Y H:i:s",$locktill));
      else if ($trades[$j]['data'][$id]['notenough']) {
        $trades[$j]['data'][$id]['no_build_msg'] = _('Zu wenig Rohstoffe');
      } else {
        $trades[$j]['data'][$id]['build_link'] = true;
      }
    }
  }

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'cave_id'    => $caveID,
    'status_msg' => (isset($messageID)) ? $messageText[$messageID] : '',
    'trades'     => $trades
  ));
}

function merchant_processOrder($tradeID, $caveID, $caveData) {
  global $tradeCategoriesTypeList, $tradeTypeList, $db, $potionTypeList;

  $sql = $db->prepare("SELECT LockTill < :LockTill as allowed
                       FROM " . TRADELOCK_TABLE ."
                       WHERE PlayerID= :playerID 
                        AND cat = :cat");
  $sql->bindValue('LockTill', date("Y-m-d H:i:s", time()), PDO::PARAM_STR);
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('cat', $tradeTypeList[$tradeID]->category, PDO::PARAM_STR);

  if ($sql->execute()) {
    $row =  $sql->fetch(PDO::FETCH_ASSOC);
    if ($row["allowed"]== 0 && $row) {
     return -2;
   }
  }

  $sql = $db->prepare("DELETE FROM ". TRADELOCK_TABLE ."
                       WHERE  PlayerID= :playerID
                        AND cat = :cat");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('cat', $tradeTypeList[$tradeID]->category, PDO::PARAM_STR);
  
  if (!$sql->execute()) {
    return -1;
  }

  // take production costs from cave
  if (!processProductionCost($tradeTypeList[$tradeID], $caveID, $caveData)) {
    return 0;
  }

  $now = time() + $_SESSION['player']->getTimeCorrection();
  
  if ($tradeTypeList[$tradeID]->category == "potion") {
    foreach ($tradeTypeList[$tradeID]->impactList[0]['potions'] AS $potionID => $potion) {
      
      if (!$potionTypeList[$potionID])
        return -1;
      
      $sql = $db->prepare("UPDATE " . PLAYER_TABLE . "
                           SET " . $potionTypeList[$potionID]->dbFieldName . " =  " . $potionTypeList[$potionID]->dbFieldName . " + :absolute 
                           WHERE playerID = :playerID");
      $sql->bindValue('absolute', $potion['absolute'], PDO::PARAM_INT);
      $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
      
      if (!$sql->execute()) {
        processProductionCostSetBack($tradeTypeList[$tradeID], $caveID, $caveData);
        return -1;
      }
    }
    
  } else {
    // create a random factor between -0.3 and +0.3
    $delayRandFactor = (rand(0,getrandmax()) / getrandmax()) * 0.6 - 0.3;
  
    // now calculate the delayDelta depending on the first impact's delay
    $delayDelta = $tradeTypeList[$tradeID]->impactList[0]['delay'] * $delayRandFactor;
  
    foreach($tradeTypeList[$tradeID]->impactList AS $impactID => $impact) {
      $delay = (int)(($delayDelta + $impact['delay']) * WONDER_TIME_BASE_FACTOR);
      $sql = $db->prepare("INSERT INTO ". EVENT_TRADE_TABLE ." 
                           (targetID, tradeID, impactID, start, end)
                            VALUES (:targetID, :tradeID, :impactID, :start, :end)");
      $sql->bindValue('targetID', $caveID, PDO::PARAM_INT);
      $sql->bindValue('tradeID', $tradeID-1, PDO::PARAM_INT);
      $sql->bindValue('impactID', $impactID, PDO::PARAM_INT);
      $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
      $sql->bindValue('end', time_toDatetime($now + $delay), PDO::PARAM_STR);
  
      if (!$sql->execute()) {
        processProductionCostSetBack($tradeTypeList[$tradeID], $caveID, $caveData);
        return -1;
      }
    }
  }

  $lock = $now + $tradeCategoriesTypeList[$tradeTypeList[$tradeID]->category]->secondsbetween ;

  $sql = $db->prepare("INSERT INTO ". TRADELOCK_TABLE ." 
                       (PlayerID, cat, LockTill)
                        VALUES (:playerID, :cat, :LockTill)");
  $sql->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  $sql->bindValue('cat', $tradeTypeList[$tradeID]->category, PDO::PARAM_STR);
  $sql->bindValue('LockTill', time_toDatetime($lock), PDO::PARAM_STR);

  if (!$sql->execute()) {
    return -1;
  }

  /*
  $targetMessage =
    "Der Besitzer der H&ouml;hle in {$caveData['xCoord']}/{$caveData['yCoord']} ".
    "hat auf Ihre H&ouml;hle in $coordX/$coordY ein Wunder gewirkt.";

  messages_sendSystemMessage($targetData['playerID'], 9,
           "Wunder!",
           $targetMessage, $db);
  */
  return 1;
}

?>