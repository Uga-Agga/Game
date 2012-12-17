<?php
/*
 * hero.html.php - basic hero system
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011-2012  Georg Pitterle
 * Copyright (c) 2011-2012  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/* **** HERO FUNCTIONS ***** *************************************************/
/** ensure this file is being included by a parent file */

defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/** This function returns basic hero details
 *
 *  @param caveID       the current caveID
 *  @param ownCaves  all the data of all your caves
 */
function hero_getHeroDetail($caveID, &$ownCaves) {
  global $db, $template;

  // open template
  $template->setFile('hero.tmpl');

  // get current playerID by user
  $playerID = $_SESSION['player']->playerID;
  $player = getPlayerByID($playerID);

  $newhero = false;
  
  $messageText = array(
    -24 => array('type' => 'error', 'message' => _('Es können nur Rohstoffe aus der aktuellen Höhle geopfert werden!')),
    -23 => array('type' => 'error', 'message' => _('Die Fähigkeit wurde schon erlernt!')),
    -22 => array('type' => 'error', 'message' => _('Dein Held hat den falschen Typ, um die Fähigkeit zu erlernen!')),
    -21 => array('type' => 'error', 'message' => _('Fehler beim Erlernen der Fähigkeit!')),
    -20 => array('type' => 'error', 'message' => _('Dein Held hat nicht das erforderliche Level!')),
    -19 => array('type' => 'error', 'message' => _('Fehler beim Eintragen des neuen Heldentyps!')),
    -18 => array('type' => 'error', 'message' => _('Euer Held ist tot!')),
    -17 => array('type' => 'error', 'message' => _('Euer Held ist gar nicht tot!')),
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
    9 => array('type' => 'success', 'message' => _('Die Wiederbelebung wurde erfolgreich abgebrochen.')), 
    10 => array('type' => 'success', 'message' => _('Heldentyp erfolgreich gewechselt!')), 
    11 => array('type' => 'success', 'message' => _('Dein Held hat eine neue Fähigkeit erlernt!')),
    );

  // create new hero
  $action = Request::getVar('action', '');
  $newHeroID = Request::getVar('id', '');
  if ($action =="createHero") {
    if (isset($GLOBALS['heroTypesList'][$newHeroID])) {
      $messageID = createNewHero($GLOBALS['heroTypesList'][$newHeroID]['heroTypeID'], $playerID, $caveID);
    }
  }

  $hero = getHeroByPlayer($playerID);
  
  $showTypesList = false;
  $changeType = false;
  if ($hero['heroTypeID'] == 1000) {
    $hero = null;
    $changeType = true;
    $showTypesList = true;
  }
  
  if ($hero != null) {
    $showLevelUp = false;

    $ritual = getRitual($hero);

    $resource['duration'] = $ritual['duration'];
    $cave = getCaveSecure($caveID, $playerID);

    foreach ($GLOBALS['resourceTypeList'] as $key) {
      $dbFieldName = $key->dbFieldName;

      if (!isset($ritual[$dbFieldName])){
        continue;
      }

      $enough = ($ritual[$dbFieldName] <= $cave[$dbFieldName]);
      $tmp = array(
        'enough'      => $enough,
        'value'       => $ritual[$dbFieldName],
        'missing'     => $ritual[$dbFieldName] - $cave[$dbFieldName],
        'dbFieldName' => $dbFieldName,
        'name'        => $key->name,
      );
      $resource[$key->dbFieldName] = $tmp;
    }

    $action = Request::getVar('action', '');
    switch ($action) {
      case 'reincarnate':
        if ($hero['isAlive'] == 1) {
          $messageID = -17;
          break;
        }

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
        if ($hero['isAlive'] != 1) {
          $messageID = -18;
          break;
        }

        if ($hero['tpFree'] >= 1) {
          $skill = Request::getVar('skill', '');
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

      case 'skill_ability':
        if ($hero['isAlive'] != 1) {
          $messageID = -18;
          break;
        }

        if ($hero['tpFree'] >= 1) {
          if ($skillID = Request::getVar('skillID', '')) {
            $messageID = hero_skillAbility($skillID, $hero);
          }
          
          
        }
      break;

      case 'lvlUp':
        if ($hero['isAlive'] != 1) {
          $messageID = -18;
          break;
        }

        $messageID = hero_levelUp($hero);
      break;

      case 'immolateResources':
        $value = Request::getVar('value', array('' => ''));

        $resultArray = hero_immolateResources($value, $caveID, $ownCaves);
        $messageID = $resultArray['messageID'];

        // set exp value in message
        if ($resultArray['value']>0) {
          $messageText[$messageID]['message'] = str_replace('expValue', $resultArray['value'], $messageText[$messageID]['message']);
        }
      break;

      case 'usePotion':
        if ($hero['isAlive'] != 1) {
          $messageID = -18;
          break;
        }

        $potionID = Request::getVar('potionID', -1);
        $value = Request::getVar('value', 0);

        if ($potionID == -1) {
          $messageID = -8; 
          break;
        }

        if ($value < 0) {
          $messageID = -8; 
          break;
        }

        $messageID = hero_usePotion($potionID, $value);
        if ($messageID == 6) {
          $hero = null;
          $showTypesList = true;
          $changeType = true;
        }
      break;
    }

    $queue=getHeroQueue($playerID);

    $player = getPlayerByID($playerID);
    $potions = array();
    foreach ($GLOBALS['potionTypeList'] AS $potionID => $potion) {
      if ($player[$potion->dbFieldName] > 0) {
        $potion->value = $player[$potion->dbFieldName];
        $potions[] = $potion;
      }
    }
  } elseif ($changeType) {
    if (Request::getVar('action', '') == 'changeType') {
      $messageID = hero_changeType(Request::getVar('typeID', -1));
      $showTypesList = false;
      $hero = getHeroByPlayer($playerID);
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

  if ($hero != null) {
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
      'resourceTypeList' => $GLOBALS['resourceTypeList'],
    ));
  }

  if ($newhero) {
    $template->addVars(array(
        'newhero'       => $newhero,
        'heroTypesList' => $GLOBALS['heroTypesList']
    ));
  }

  if ($showTypesList) {
    $template->addVars(array(
      'changeType' => $changeType,
      'heroTypesList' => $GLOBALS['heroTypesList']
    ));
  }

  $showImmolation = false;
  if ($ownCaves[$caveID]['hero']) {
    $showImmolation = true;
  }
  $template->addVar('showImmolation', $showImmolation);

  if (isset($potions) && $potions) {
    $template->addVar('potions', $potions);
  }

  if ($GLOBALS['heroSkillTypeList']) {
    $skills = array();
    foreach ($GLOBALS['heroSkillTypeList'] as $skillID => $skill) {
      
      // calculate skill effect
      foreach ($skill['effects'] as $effect_dbFieldName => $effect) {
        foreach($GLOBALS['effectTypeList'] as $eff) {
          if ($eff->dbFieldName == $effect_dbFieldName) {
            $name = $eff->name;
            break;
          }
        }

        $skill['effect_values'][] = $name . ": " . ($skill['skillFactor']*$hero['forceLvl']);
      }

    // filter skills by hero type
      foreach ($skill['requiredType'] as $rt) {
        if ($rt == $hero['id']) {
          $skills[] = $skill;
        }
      }
    }

    // check if send button is disabled
    foreach ($skills as $skillID => $skill) {
      if ($hero[$skill['dbFieldName']] || 
          $skill['costTP'] > $hero['tpFree'] || 
          $skill['requiredLevel'] > $hero['lvl']) {
        $skills[$skillID]['disableButton'] = true;
      }

      if ($hero[$skill['dbFieldName']]) {
        $skills[$skillID]['showEffects'] = true;
      }
    }

    $template->addVar('skills', $skills);
  }
}

?>