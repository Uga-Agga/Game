<?php
/*
 * wonder.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function wonder_getWonderContent($caveID, &$details) {
  global $wonderTypeList, $template;

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

  $action = request_var('action', '');
  switch ($action) {
/****************************************************************************************************
*
* "wundern" xD
*
****************************************************************************************************/
    case 'wonder':
      $wonderID = request_var('wonderID', -1);
      if ($wonderID != -1) {
        $caveName = request_var('CaveName', '');
        if (!empty($caveName)) {
          $caveData = getCaveByName(request_var('CaveName', ""));
          $xCoord = $caveData['xCoord'];
          $yCoord = $caveData['yCoord'];
        } else {
          $xCoord = request_var('xCoord', 0);
          $yCoord = request_var('yCoord', 0);
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
  foreach ($wonderTypeList as $id => $wonder) {
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