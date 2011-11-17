<?php
/*
 * hero.html.php - basic hero system
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/* **** HERO FUNCTIONS ***** *************************************************/
/** ensure this file is being included by a parent file */

defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once("hero.rules.php");
//init Potions
init_potions();
//init HeroTypes
init_heroTypes();
//init HeroSkills
init_heroSkills();

/** This function returns basic hero details
 *
 *  @param caveID       the current caveID
 *  @param meineHöhlen  all the data of all your caves
 */
function hero_getHeroDetail($caveID, &$ownCaves) {
  global $config, $db,$template ;
  global $potionTypeList, $heroTypesList, $heroSkillTypeList, $resourceTypeList;

  // open template
  $template->setFile('hero.tmpl');

  // get current playerID by user
  $playerID = $_SESSION['player']->playerID;
  $player = getPlayerByID($playerID);

  $newhero = false;

  $messageText = array(
    -16 => array('type' => 'error', 'message' => _('Fehler beim Eintragen der Erfahrungspunkte nach der Opferung!')),
    -15 => array('type' => 'error', 'message' => _('Fehler beim Abziehen der geopferten Rohstoffe!')),
    -14 => array('type' => 'error', 'message' => _('Nicht genug Rohstoffe zum Opfern vorhanden!')),
    -13 => array('type' => 'error', 'message' => _('Fehler beim Holen der Opferwerte!')),
    -12 => array('type' => 'error', 'message' => _('Fehler beim Erhöhen des Levels!')),
    -11 => array('type' => 'error', 'message' => _('Nicht genug Talentpunkte vorhanden!')),
    -10 => array('type' => 'error', 'message' => _('Ihr Held ist noch nicht erfahren genug, diesen Trank zu nutzen!')),
    -9 => array('type' => 'error', 'message' => _('Nicht genug Tränke vorhanden!')),
    -8 => array('type' => 'error', 'message' => _('Fehler beim Anwenden des Trankes!')),
    -7 => array('type' => 'error', 'message' => _('Fehler beim Schreiben in die Datenbank.')),
    -6 => array('type' => 'error', 'message' => _('Der Held existiert bereits.')),
    -5 => array('type' => 'error', 'message' => _('Maximallevel des Skills erreicht.')),
    -4 => array('type' => 'notice', 'message' => _('Fehler beim Abbrechen der Wiederbelebung.')),
    -3 => array('type' => 'error', 'message' => _('Nicht genug Rohstoffe zum Wiederbeleben.')),
    -2 => array('type' => 'error', 'message' => _('Der Held wird bereits wiederbelebt.')),
    -1 => array('type' => 'error', 'message' => _('Dafür sind nicht genug Talentpunkte vorhanden.')),
    0 => array('type' => 'error', 'message' => _('Euch steht noch kein Held zur Verfügung.')),
    1 => array('type' => 'success', 'message' => _('Euer Held hat eine neue Fähigkeit erlernt.')),
    2 => array('type' => 'notice', 'message' => _('Die Wiederbelebung eures Helden hat begonnen.')),
    3 => array('type' => 'success', 'message' => _('Euer Held wurde erstellt.')),
    4 => array('type' => 'notice', 'message' => _('Wählt mit Bedacht, dies lässt sich womöglich nicht mehr rückgängig machen.')), 
    5 => array('type' => 'success', 'message' => _('Der Trank hat seine Wirkung entfaltet. Die Lebenspunkte wurden erhöht.')), 
    6 => array('type' => 'success', 'message' => _('Der Trank des Vergessens hat Wirkung gezeigt. Der Held ist nun wieder unerfahren.')),
    7 => array('type' => 'success', 'message' => _('Euer Held hat das nächste Level erreicht!')),
    8 => array('type' => 'success', 'message' => _('Eurem Helden wurden expValue Erfahrungspunkte gutgeschrieben.')),
    9 => array('type' => 'success', 'message' => _('Die Wiederbelebung wurde erfolgreich abgebrochen.'))
  );

  // create new hero
  $action = request_var('action', '');
  $newHeroID = request_var('id', '');
  if ($action =="createHero") {
    if (isset($heroTypesList[$newHeroID])) {
      $messageID = createNewHero($heroTypesList[$newHeroID]['heroTypeID'], $playerID, $caveID);
    }
  }

  $hero = getHeroByPlayer($playerID);

  if($hero!= null) {
    $showLevelUp = false;

    $ritual = getRitualByLvl($hero['lvl']);
    $resource['duration'] = $ritual['duration'];
    $cave = getCaveSecure($caveID, $playerID);

    foreach ($resourceTypeList as $key) {
      $dbFieldName = $key->dbFieldName;
      $enough = ($ritual[$dbFieldName]<=$cave[$dbFieldName]);
      $tmp = array(
        'enough'      => $enough,
        'value'       => $ritual["$dbFieldName"],
        'missing'     => $ritual["$dbFieldName"]-$cave["$dbFieldName"],
        'dbFieldName' => $dbFieldName,
        'name'        => $key->name,
      );
      $resource[$key->dbFieldName]= $tmp;
    }

    $action = request_var('action', '');
    switch ($action) {
      case 'reincarnate':
        if (checkEventHeroExists($playerID)) {
          $messageID = -2;
        } else {
          $messageID = createRitual($caveID, $playerID, $resource, $hero, $ownCaves);
        }
      break;

      case 'cancelOrder':
        if (checkEventHeroExists($playerID)) {
          $messageID = hero_cancelOrder();
        }
      break;

      case 'skill':
        if ($hero['tpFree'] >= 1) {
          $skill = request_var('skill', '');
          switch ($skill) {
            case 'force':
              //typ='force';
              if ($hero['forceLvl']<10) {
                if (skillForce($playerID, $hero)) {
                  $messageID = 1;
                }
                break;
              }

              $messageID = -5;
            break;

            case 'maxHP':
              //typ='maxHP';
              if ($hero['maxHpLvl']<10) {
                if (skillMaxHp($playerID, $hero)) {
                  $messageID = 1;
                } else {
                  $messageID = -5;
                }
                break;
              }

              $messageID = -5;
            break;

            case 'regHP':
              //typ='regHP';
              if ($hero['regHpLvl'] < 10) {
                if (skillRegHp($playerID, $hero)) {
                  $messageID = 1;
                } else {
                  $messageID = -5;
                }
                break;
              }

              $messageID = -5;
            break;
          }
        }
        break;

        case 'lvlUp':
          $messageID = hero_levelUp($hero);
        break;

        case 'immolateResources':
          $resourceID = request_var('resourceID', -1);
          $value = request_var('value', 0);

          $resultArray = hero_immolateResources($resourceID, $value, $caveID, $ownCaves);
          $messageID = $resultArray['messageID'];

          // set exp value in message
          if ($resultArray['value']>0) {
            $messageText[$messageID]['message'] = str_replace('expValue', $resultArray['value'], $messageText[$messageID]['message']);
          }
        break;

        case 'usePotion':
          $potionID = request_var('potionID', -1);
          $value = request_var('value', 0);

          if ($potionID == -1) {
            $messageID = -8; 
            break;
          }

          if ($value < 0) {
            $messageID = -8; 
            break;
          }

          $messageID = hero_usePotion($potionID, $value);
        break;
    }

    $queue=getHeroQueue($playerID);

    $player = getPlayerByID($playerID);
    $potions = array();
    foreach ($potionTypeList AS $potionID => $potion) {
      if ($player[$potion->dbFieldName] > 0) {
        $potion->value = $player[$potion->dbFieldName];
        $potions[] = $potion;
      }
    }
  } else {
    $player = getPlayerByID($playerID);
    if ($player['heroism'] >= 1){
      $messageID = 4;
      $newhero = true;
    }
    else{
      $messageID = 0;
    }
  }
  /****************************************************************************************************
   *
   * Übergeben ans Template
   *
   ****************************************************************************************************/
  $template->addVars(array(
    'status_msg' => (isset($messageID)) ? $messageText[$messageID] : '',
  ));

  if (isset($queue) && $queue) {
    $template->addVars(array(
      'quene_show'   => true,
      'quene_finish' => time_formatDatetime($queue['end']),
    ));
  }

  if ($hero) {
    $hero = getHeroByPlayer($playerID);

    if ($hero['expLeft'] <= 0) {
      $showLevelUp = true;
    }


    if ($hero['healPoints'] <= 0.2 * $hero['maxHealPoints']) {
      $hero['HPbar']='error';
    } else {
      $hero['HPbar']='success';
    }

    $template->addVars(array(
      'hero'             => $hero,
      'showLevelUp'      => (isset ($showLevelUp)) ? $showLevelUp : '',
      'delay'            => time_formatDuration($ritual['duration']),
      'ritual'           => $ritual,
      'resource'         => $resource,
      'resourceTypeList' => $resourceTypeList,
    ));
  }
  
  if ($newhero) {
    $template->addVars(array(
        'newhero'               => $newhero,
        'heroTypesList'             => $heroTypesList
    ));
  }
  
  if (isset($potions) && $potions) {
    $template->addVar('potions', $potions);
  }

  if ($heroSkillTypeList) {
    $template->addVar('skills', $heroSkillTypeList);
  }
}

?>