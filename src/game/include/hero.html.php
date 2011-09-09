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
//init Potions
init_potions();

/** This function returns basic hero details
 *
 *  @param caveID       the current caveID
 *  @param meineHöhlen  all the data of all your caves
 */



function hero_getHeroDetail($caveID,  $ownCaves){

  // get configuration settings
  global $config;

  // get db link
  global $db;
  global $potionTypeList, $heroTypeList, $heroSkillTypeList, $resourceTypeList;
  global $template;
  // open template
  $template->setFile('hero.tmpl');

  // get current playerID by user
  $playerID = $_SESSION['player']->playerID;
  $player = getPlayerByID($playerID);

  $newhero = false;

  $messageText = array(
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
  $action = request_var('ID', '');
  switch ($action) {
    case '1':
      $messageID = createNewHero('1',$playerID,$caveID);
      break;
    case '2':
      $messageID = createNewHero('2',$playerID,$caveID);
      break;
    case '3':
      $messageID = createNewHero('3',$playerID,$caveID);
      break;
  }
  $hero = getHeroByPlayer($playerID);
  
  if($hero!= null) {

    $disabled = 'disabled=disabled';
    $hero['force']=0.05*$hero['forceLvl'];
    $hero['expLeft']=100+3*pow($hero['lvl']+1, 4)-$hero['exp'];
    $hero['regHP']=5*pow($hero['regHpLvl'],2);

    $eventHero=getEventHero($playerID);
    
    if ($hero['HP'] == 0 || $hero['isAlive'] == false) {
      if ($eventHero === true) {
        $disabled = _('');
      }
      $hero['location'] = _('tot');
      $hero['path'] = _('hero_death.gif');
    }
    elseif($hero['caveID'] == 0){
      $hero['location'] = _('in Bewegung');
    }
    else{
      $hero['location'] = $ownCaves[$hero['caveID']]['name']. ' in (' .$ownCaves[$hero['caveID']]['xCoord']. '/'. $ownCaves[$hero['caveID']]['yCoord'] .')';
    }

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

    if($hero['HP']<=0.2*$hero['maxHP']){
      $hero['HPbar']='error';
    }
    else{
      $hero['HPbar']='success';
    }

    $action = request_var('action', '');
    switch ($action) {
      case 'Wiederbeleben':
        if ($eventHero === true){
          $messageID = createRitual($caveID, $playerID, $resource, $hero);
          if ($messageID === 2) {
            $disabled = 'disabled=disabled';
          }
          break;
        }
        $messageID = -2;
        break;

      case 'cancelOrder':
        if ($eventHero === false){
          $sql = $db->prepare("DELETE FROM " . EVENT_HERO_TABLE . "
                       WHERE playerID = :playerID ");
          $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
          $sql->execute();
          $disabled = '';
          $messageID = -4;
        }
        break;

      case 'skill':
        if ($hero['tpFree']>=1){
          $skill = request_var('skill', '');
          switch ($skill) {
            case 'force':
              //typ='force';
              if ($hero['forceLvl']<10){
                $hero['force']=0.05*($hero['forceLvl']+1);
                skillForce($playerID);
                $hero['forceLvl']++;
                $hero['tpFree']--;
                $messageID=1;
                break;
              }
              $messageID=-5;
              break;
            case 'maxHP':
              //typ='maxHP';
              if ($hero['maxHpLvl']<10){
                $hero['maxHP']=floor(100+25*pow($hero['maxHpLvl'], 3)/2);
                skillMaxHp($playerID,$hero['maxHP']);
                $hero['maxHpLvl']++;
                $hero['tpFree']--;
                $messageID=1;
                break;
              }
              $messageID=-5;
              break;
            case 'regHP':
              //typ='regHP';
              if ($hero['regHpLvl']<10){
                $hero['regHP']=5*pow($hero['regHpLvl']+1,2);
                skillRegHp($playerID);
                $hero['regHpLvl']++;
                $hero['tpFree']--;
                $messageID=1;
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
      $newhero = array(
      array( 'heroTypID' => '1',
            'heroTypName' => 'Heerführer',
        'heroTypDescription' => 'Als Meister der Kriegskunst hilft dieser Held euch die Schlagkraft eurer Truppe zu erhöhen.'),
      array( 'heroTypID' => '2',
            'heroTypName' => 'Verteidiger',
        'heroTypDescription' => 'Der Verteidiger ist ein kundiger Konstrukteur und hilft euch eure Höhle wiederstandsfähiger gegen den Einfall fremder Truppen zu machen'),
      array( 'heroTypID' => '3',
            'heroTypName' => 'Baumeister',
        'heroTypDescription' => 'Wer trachtet nicht danach seine Erträge zu erhöhen um so seine Höhle erblühen zu lassen? Der Baumeister lehrt eure Arbeiter noch effektiver ans Werk zu gehen.'),
      );
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
    ));
  }
  
  if ($potions) {
    $template->addVars(array(
      'potions'               => $potions,
    ));
  }
}

/* **** HERO HELP-FUNCTIONS ***** ********************************************/

function getHeroByPlayer($playerID) {
  global $db;

  // set database query with playerID
  $sql = $db->prepare("SELECT *
                       FROM ". HERO_TABLE ." 
                         WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID);

  // if not successful
  if (!$sql->execute() || !($result = $sql->fetch(PDO::FETCH_ASSOC))) {
    $sql->closeCursor();
    return null;
  }
  //otherwise

  if (empty($result))
  return null;

  // otherwise
  $sql->closeCursor();
  $hero = array(
      'heroID'       => $result['heroID'],
      'playerID'     => $result['playerID'],
      'name'         => $result['name'],
      'heroTypeID'    => $result['heroTypeID'],
      'lvl'          => $result['lvl'],
      'exp'          => $result['exp'],
      'caveID'       => $result['caveID'],
      'isAlive'      => $result['isAlive'],
      'tpFree'       => $result['tpFree'],
      'HP'           => $result['healPoints'],
      'maxHP'        => $result['maxHealPoints'],
      'forceLvl'     => $result['forceLvl'],
      'maxHpLvl'     => $result['maxHpLvl'],
      'regHpLvl'     => $result['regHpLvl'],
      'path'         => _('hero_imperator.gif'),
      'location'     => _('tot')
  );
  if($hero['heroTypeID']==2){
    $hero['path']=_('hero_defender.gif');
  }
  if($hero['heroTypeID']==3){
    $hero['path']=_('hero_constructor.gif');
  }
  return $hero;

}
function getEventHero($playerID) {
  global $db;

  // set database query with playerID
  $sql = $db->prepare("SELECT *
                       FROM ". EVENT_HERO_TABLE ." 
                         WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID);

  // if not successful
  if (!$sql->execute() || !$sql->fetch(PDO::FETCH_ASSOC)){
    $sql->closeCursor();
    return true;
  }
  // otherwise
  $sql->closeCursor();
  return false;

}
function getHeroQueue($playerID) {
  global $db;

  // set database query with playerID
  $sql = $db->prepare("SELECT *
                       FROM ". EVENT_HERO_TABLE ." 
                         WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID);

  // if not successful
  if (!$sql->execute() || !($result=$sql->fetch(PDO::FETCH_ASSOC))){
    $sql->closeCursor();
    return null;
  }
  // otherwise
  $sql->closeCursor();
  return $result;

}
function skillForce($playerID) {
  global $db;

  // set database query with playerID
  $sql = $db->prepare("UPDATE ". HERO_TABLE ."
                       SET forceLvl = forceLvl + 1,
             tpFree = tpFree - 1
                         WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->execute();
}
function skillMaxHp($playerID,$maxHP) {
  global $db;

  // set database query with playerID
  $sql = $db->prepare("UPDATE ". HERO_TABLE ."
                       SET maxHpLvl = maxHpLvl + 1,
             tpFree = tpFree - 1,
             maxHealPoints = :maxHP
                         WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->bindValue('maxHP', $maxHP, PDO::PARAM_INT);
  $sql->execute();
}
function skillRegHp($playerID) {
  global $db;

  // set database query with playerID
  $sql = $db->prepare("UPDATE ". HERO_TABLE ."
                       SET regHpLvl = regHpLvl + 1,
             tpFree = tpFree - 1
                         WHERE playerID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  $sql->execute();

}
function getRitualByLvl($lvl) {
  global $db;

  // set database query with playerID
  $sql = $db->prepare("SELECT *
                       FROM ". HERO_RITUAL_TABLE ." 
                         WHERE ritualID  = :lvl");
  $sql->bindValue('lvl', $lvl, PDO::PARAM_INT);

  // if not successful
  if (!$sql->execute() || !($ritual=$sql->fetch(PDO::FETCH_ASSOC))){
    $sql->closeCursor();
    return null;
  }
  // otherwise
  $sql->closeCursor();
  return $ritual;

}

function createRitual($caveID,$playerID,$ritual,$hero){
  global $db;

  $cave = getCaveSecure($caveID, $playerID);

  if ($ritual['population']['value']<= $cave['population'] &&
  $ritual['food']['value']<= $cave['food'] &&
  $ritual['wood']['value']<= $cave['wood'] &&
  $ritual['stone']['value']<= $cave['stone'] &&
  $ritual['metal']['value']<= $cave['metal'] &&
  $ritual['sulfur']['value']<= $cave['sulfur'])
  {

    $sql = $db->prepare("UPDATE ". CAVE_TABLE ."
                       SET population = population - :pop,
             food = food - :food,
             wood = wood - :wood,
             stone = stone - :stone,
             metal = metal - :metal,
             sulfur = sulfur - :sulfur
                         WHERE (playerID = :playerID) AND (caveID = :caveID)");
    $sql->bindValue('pop', $ritual['population']['value'], PDO::PARAM_INT);
    $sql->bindValue('food', $ritual['food']['value'], PDO::PARAM_INT);
    $sql->bindValue('wood', $ritual['wood']['value'], PDO::PARAM_INT);
    $sql->bindValue('stone', $ritual['stone']['value'], PDO::PARAM_INT);
    $sql->bindValue('metal', $ritual['metal']['value'], PDO::PARAM_INT);
    $sql->bindValue('sulfur', $ritual['sulfur']['value'], PDO::PARAM_INT);
    $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

    if (!$sql->execute()) {
      $sql->closeCursor();
      return -7;
    }
    $now = time();
    $sql = $db->prepare("INSERT INTO ". EVENT_HERO_TABLE ." (caveID, playerID, heroID,
                        start, end, blocked) 
                     VALUES (:caveID, :playerID, :heroID, :start, :end, :blocked)");      
    $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    $sql->bindValue('heroID', $hero['heroID'], PDO::PARAM_INT);
    $sql->bindValue('start', time_toDatetime($now), PDO::PARAM_STR);
    $sql->bindValue('end', time_toDatetime($now + $ritual['duration']), PDO::PARAM_STR);
    $sql->bindValue('blocked', 0, PDO::PARAM_INT);
    if ($sql->execute()) {
      $sql->closeCursor();

      $sql =  $db->prepare("UPDATE " . HERO_TABLE . " SET
                isAlive = -1, caveID = :caveID WHERE heroID = :heroID");
      $sql->bindValue('heroID', $hero['heroID'], PDO::PARAM_INT);
      $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);

      if (!$sql->execute()) {
        return -3;
      }

      return 2;
    }
  }
  return -3;
}
function createNewHero($typ, $playerID, $caveID) {
  global $db;

  $hero = getHeroByPlayer($playerID);

  if($hero == null) {
    $player = getPlayerByID($playerID);

    $sql = $db->prepare("INSERT INTO ". HERO_TABLE ."
                    (caveID, playerID, heroTypeID, name, exp,
             healPoints, maxHealPoints, isAlive,
             melee_damage_factor, melee_hp_factor, food_factor) 
                     VALUES (
             :caveID, :playerID, :typ, :name, :exp, 
             :healPoints, :maxHealPoints, :isAlive,
             :melee_damage_factor, :melee_hp_factor, :food_factor )");      
    $sql->bindValue('caveID', $caveID, PDO::PARAM_INT);
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    $sql->bindValue('typ', $typ, PDO::PARAM_INT);
    $sql->bindValue('name', $player['name'], PDO::PARAM_INT);
    $sql->bindValue('exp', 100, PDO::PARAM_INT);
    $sql->bindValue('healPoints', 0, PDO::PARAM_INT);
    $sql->bindValue('maxHealPoints', 100, PDO::PARAM_INT);
    $sql->bindValue('isAlive', 0, PDO::PARAM_INT);
    if ($typ == '1'){
      $sql->bindValue('melee_damage_factor', strval(0.05), PDO::PARAM_STR);
      $sql->bindValue('melee_hp_factor', strval(0.0), PDO::PARAM_STR);
      $sql->bindValue('food_factor', strval(0.0), PDO::PARAM_STR);
    }
    if ($typ == '2'){
      $sql->bindValue('melee_damage_factor', strval(0.0), PDO::PARAM_STR);
      $sql->bindValue('melee_hp_factor', strval(0.05), PDO::PARAM_STR);
      $sql->bindValue('food_factor', strval(0.0), PDO::PARAM_STR);
    }
    if ($typ == '3'){
      $sql->bindValue('melee_damage_factor', strval(0.0), PDO::PARAM_STR);
      $sql->bindValue('melee_hp_factor', strval(0.0), PDO::PARAM_STR);
      $sql->bindValue('food_factor', strval(0.05), PDO::PARAM_STR);
    }

    if (!$sql->execute()) {
      $sql->closeCursor();
      return -6;
    }

    return 3;
  }
  return -6;
}

function hero_removeHeroFromCave ($heroID) {
  global $db;

  $sql = $db->prepare("UPDATE " . CAVE_TABLE ." SET
             hero = 0
             WHERE hero = :heroID");
  $sql->bindValue('heroID', $heroID, PDO::PARAM_INT);

  if (!$sql->execute())
    return false;

  return true;
}

function hero_usePotion ($potionID, $value) {
  global $db, $potionTypeList;
  
  $playerID = $_SESSION['player']->playerID;
  
  $playerData = getPlayerByID($playerID);
  $hero = getHeroByPlayer($playerID);
  
  $potion = $potionTypeList[$potionID];
  
  if (!$potion)
    return -8;
  
  if ($potion->needed_level > $hero['lvl'])
    return -10;
    
  if ($playerData[$potion->dbFieldName] < $value)
    return -9; 
  
  // remove potions
  $sql = $db->prepare("UPDATE " . PLAYER_TABLE ."
                       SET " . $potion->dbFieldName . " = " . $potion->dbFieldName . " - :value
                       WHERE playerID = :playerID");
  $sql->bindValue('value', $value, PDO::PARAM_INT);
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  
  $sql_setback = $db->prepare("UPDATE " . PLAYER_TABLE ."
                       SET " . $potion->dbFieldName . " = " . $potion->dbFieldName . " + :value
                       WHERE playerID = :playerID");
  $sql_setback->bindValue('value', $value, PDO::PARAM_INT);
  $sql_setback->bindValue('playerID', $playerID, PDO::PARAM_INT);
  
  if (!$sql->execute()) {
    return -8;
  }
    
  
  // apply potion effects
  $newHealPoints = $hero['HP'];
  for ($i = 0; $i< $value; $i ++) {
    $newHealPoints += floor($hero['maxHP'] * $potion->hp_prozentual_increase/100) + 
                   $potion->hp_increase;
  }
  if ($hero['maxHP'] < $newHealPoints)
    $newHealPoints = $hero['maxHP'];
  var_dump($hero);
  if ($potion->tp_setBack == false) {
    $sql = $db->prepare("UPDATE " .HERO_TABLE ."
                         SET healPoints = :newHealPoints
                         WHERE playerID = :playerID");
    $sql->bindValue('newHealPoints', $newHealPoints, PDO::PARAM_INT);
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    
    if (!$sql->execute()) {
      $sql_setback->execute();
      return -8;
    }
  } else {
    $sql = $db->prepare("UPDATE " . HERO_TABLE ."
                         SET maxHpLvl = 0, forceLvl = 0, regHpLvl = 0
                         WHERE playerID = :playerID");
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
    
    if (!$sql->execute()) {
      $sql_setback->execute();
      return -8;
    }
    return 6;
  }

  return 5;
}
?>