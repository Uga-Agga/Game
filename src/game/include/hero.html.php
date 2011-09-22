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

require_once("game_rules.php");
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

  // get configuration settings
  global $config;

  // get db link
  global $db;
  global $potionTypeList, $heroTypesList, $heroSkillTypeList, $resourceTypeList;
  global $template;
  // open template
  $template->setFile('hero.tmpl');
  
  // get current playerID by user
  $playerID = $_SESSION['player']->playerID;
  $player = getPlayerByID($playerID);

  $newhero = false;

  $messageText = array(
  -11 => array('type' => 'error', 'message' => _('Nicht genug Talentpunkte vorhanden!')),
  -10 => array('type' => 'error', 'message' => _('Ihr Held ist noch nicht erfahren genug, diesen Trank zu nutzen!')),
  -9 => array('type' => 'error', 'message' => _('Nicht genug Tränke vorhanden!')),
  -8 => array('type' => 'error', 'message' => _('Fehler beim Anwenden des Trankes!')),
  -7 => array('type' => 'error', 'message' => _('Fehler beim Schreiben in die Datenbank.')),
  -6 => array('type' => 'error', 'message' => _('Der Held existiert bereits.')),
  -5 => array('type' => 'error', 'message' => _('Maximallevel des Skills erreicht.')),
  -4 => array('type' => 'notice', 'message' => _('Die Wiederbelebung wurde erfolgreich abgebrochen.')),
  -3 => array('type' => 'error', 'message' => _('Nicht genug Rohstoffe zum Wiederbeleben.')),
  -2 => array('type' => 'error', 'message' => _('Der Held wird bereits wiederbelebt.')),
  -1 => array('type' => 'error', 'message' => _('Dafür sind nicht genug Talentpunkte vorhanden.')),
  0 => array('type' => 'error', 'message' => _('Euch steht noch kein Held zur Verfügung.')),
  1 => array('type' => 'success', 'message' => _('Euer Held hat eine neue Fähigkeit erlernt.')),
  2 => array('type' => 'notice', 'message' => _('Die Wiederbelebung eures Helden hat begonnen.')),
  3 => array('type' => 'success', 'message' => _('Euer Held wurde erstellt.')),
  4 => array('type' => 'notice', 'message' => _('Wählt mit Bedacht, dies lässt sich womöglich nicht mehr rückgängig machen.')), 
  5 => array('type' => 'success', 'message' => _('Der Trank hat seine Wirkung entfaltet. Die Lebenspunkte wurden erhöht.')), 
  6 => array('type' => 'success', 'message' => _('Der Trank des Vergessens hat Wirkung gezeigt. Der Held ist nun wieder unerfahren.'))
  );
  
  // create new hero
  $action = request_var('action', '');
  $newHeroID = request_var('ID', '');
  if ($action =="createHero") {
    foreach ($heroTypesList AS $typeName => $type) {
      if ($newHeroID == $type['heroTypeID']) {
        $messageID = createNewHero($heroTypesList[$typeName]['heroTypeID'], $playerID, $caveID);
        break;
      }
    }
    
  }

  $hero = getHeroByPlayer($playerID);
  
  if($hero!= null) {

    $disabled = 'disabled=disabled';
    
    $eventHero=getEventHero($playerID);
    
    $ritual = getRitualByLvl($hero['lvl']);
    $resource['duration'] = $ritual['duration'];
    $cave = getCaveSecure($caveID, $playerID);
    foreach ($resourceTypeList as $key){
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

    if($hero['healPoints'] <= 0.2 * $hero['maxHealPoints']) {
      $hero['HPbar']='error';
    }
    else{
      $hero['HPbar']='success';
    }

    $action = request_var('action', '');
    switch ($action) {
      case 'Wiederbeleben':
        if ($eventHero === true){
          $messageID = createRitual($caveID, $playerID, $resource, $hero, $ownCaves);
          if ($messageID === 2) {
            $disabled = 'disabled=disabled';
          }
          break;
        }
        $messageID = -2;
        break;

      case 'cancelOrder':
        if ($eventHero === false) {
          $sql = $db->prepare("DELETE FROM " . EVENT_HERO_TABLE . "
                       WHERE playerID = :playerID ");
          $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
          $sql->execute();
          $disabled = '';
          $messageID = -4;
        }
        break;

      case 'skill':
        if ($hero['tpFree']>=1) {
          $skill = request_var('skill', '');
          switch ($skill) {
            case 'force':
              //typ='force';
              if ($hero['forceLvl']<10) {
                $hero['force']=0.05*($hero['forceLvl']+1);
                if (skillForce($playerID)) {
                  $messageID=1;
                }
                break;
              }
              $messageID=-5;
              break;
            case 'maxHP':
              //typ='maxHP';
              if ($hero['maxHpLvl']<10) {
                $hero['maxHealPoints']=floor(100+25*pow($hero['maxHpLvl'], 3)/2);
                if (skillMaxHp($playerID,$hero['maxHealPoints'])) {
                  $messageID=1;
                } else {
                  $messageID = -5;
                }
                break;
              }
              $messageID=-5;
              break;
            case 'regHP':
              //typ='regHP';
              if ($hero['regHpLvl']<10) {
                $hero['regHealPoints']=5*pow($hero['regHpLvl']+1,2);
                if (skillRegHp($playerID)) {
                  $messageID=1;
                } else {
                  $messageID = -5;
                }
                break;
              }
              $messageID=-5;
              break;
          }
          break;
        }
        
        case 'usePotion':
          if (($potionID = request_var('potionID', -1)) == -1) {
            $messageID = -8; 
            break;
          }
          
          if (!$value = request_var('value', 0)) {
            $messageID = -8; 
            break;
          }
          
          $messageID = hero_usePotion($potionID, $value);
          break;
            
        $messageID=-1;
        break;
    }

    $queue=getHeroQueue($playerID);
    
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
    'status_msg'          => (isset($messageID)) ? $messageText[$messageID] : '',
  ));

  if ($queue) {
    $template->addVars(array(
      'quene_show'      => true,
      'quene_finish'    => time_formatDatetime($queue['end']),
    ));
  }

  if ($hero) {
    $hero = getHeroByPlayer($playerID);
    $template->addVars(array(
        'hero'               => $hero,
    'disabled'           => (isset($disabled)) ? $disabled : '',
    'delay'              => time_formatDuration($ritual['duration']),
    'ritual'             => $ritual,
    'resource'           => $resource,

    ));
  }
  if ($newhero) {
    $template->addVars(array(
        'newhero'               => $newhero,
        'heroTypesList'             => $heroTypesList
    ));
  }
  
  if ($potions) {
    $template->addVars(array(
      'potions'               => $potions,
    ));
  }
  
if ($heroSkillTypeList) {
    $template->addVars(array(
      'skills'               => $heroSkillTypeList,
    ));
  }
}

?>