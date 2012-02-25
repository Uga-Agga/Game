<?php
/*
 * wonder.html.php - 
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function wonder_getWonderContent($caveID, &$details) {
  global $template;

  // open template
  $template->setFile('wonder.tmpl');

  // messages
  $messageText = array(
    -3 => array('type' => 'error', 'message' => _('Die angegebene Zielhöhle wurde nicht gefunden.')),
    -2 => array('type' => 'error', 'message' => _('Das Wunder kann nicht auf die angegbene Zielhöhle erwirkt werden.')),
    -1 => array('type' => 'error', 'message' => _('Es ist ein Fehler bei der Verarbeitung Ihrer Anfrage aufgetreten. Bitte wenden Sie sich an die Administratoren')),
     0 => array('type' => 'error', 'message' => _('Das Wunder kann nicht erwirkt werden. Es fehlen die notwendigen Voraussetzungen')),
     1 => array('type' => 'success', 'message' => _('Das Erflehen des Wunders scheint Erfolg zu haben.')),
     2 => array('type' => 'info', 'message' => _('Die Götter haben Ihr Flehen nicht erhört! Die eingesetzten Opfergaben sind natürlich dennoch verloren. Mehr Glück beim nächsten Mal!'))
  );

  $action = Request::getVar('action', '');
  switch ($action) {
/****************************************************************************************************
*
* "wundern" xD
*
****************************************************************************************************/
    case 'wonder':
      $wonderID = Request::getVar('wonderID', -1);
      $caveName = Request::getVar('CaveName', '');
      $xCoord = Request::getVar('xCoord', 0);
      $yCoord = Request::getVar('yCoord', 0);

      if ($wonderID != -1) {
        if (!empty($caveName)) {
          $caveData = getCaveByName($caveName);
          $xCoord = $caveData['xCoord'];
          $yCoord = $caveData['yCoord'];
        } else if ($xCoord == 0 && $yCoord == 0) {
          $messageID = -3;
          break;
        }
      } else {
        $messageID = -1;
        break;
      }

      $messageID = wonder_processOrder($_SESSION['player']->playerID, $wonderID, $caveID, $xCoord, $yCoord, $details);
      $details = getCaveSecure($caveID, $_SESSION['player']->playerID);
    break;
  }

  // Show the wonder table
  $wonders = $wondersUnqualified = array();
  foreach ($GLOBALS['wonderTypeList'] as $id => $wonder) {
    // exclude tribeWonders
    if ($wonder->isTribeWonder) {
      continue;
    }
    
    $result = rules_checkDependencies($wonder, $details);

/****************************************************************************************************
*
* Wunder die gebetet werden können.
*
****************************************************************************************************/
    if (($result === TRUE) && (!$wonder->nodocumentation)) {
      $wonders[$wonder->wonderID] = array(
        'dbFieldName' => $wonder->wonderID, // Dummy. Wird für die boxCost.tmpl gebraucht.
        'name'        => $wonder->name,
        'wonder_id'   => $wonder->wonderID,
        'description' => $wonder->description,
        'same'        => ($wonder->target == 'same') ? true : false
      );
      $wonders[$wonder->wonderID] = array_merge($wonders[$wonder->wonderID], parseCost($wonder, $details));

      // show the building link ?!
      if ($wonders[$wonder->wonderID]['notenough']) {
        $wonders[$wonder->wonderID]['no_build_msg'] = _('Zu wenig Rohstoffe');
      } else {
        $wonders[$wonder->wonderID]['build_link'] = true;
      }

/****************************************************************************************************
*
* Wunder die nicht gewundert werden können.
*
****************************************************************************************************/
    } else if ($result !== FALSE && !$wonder->nodocumentation) {
      $wondersUnqualified[$wonder->wonderID] = array(
        'name'         => $wonder->name,
        'wonder_id'    => $wonder->wonderID,
        'description'  => $wonder->description,
        'dependencies' => $result
      );
    }
  }

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'cave_id'             => $caveID,
    'status_msg'          => (isset($messageID)) ? $messageText[$messageID] : '',
    'wonders'             => $wonders,
    'wonders_unqualified' => $wondersUnqualified,
  ));
  
}

?>