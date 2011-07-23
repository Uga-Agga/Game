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

/** This function returns basic hero details
 *
 *  @param caveID       the current caveID
 *  @param meineHöhlen  all the data of all your caves
 */
function hero_getHeroDetail($caveID, $meineHoehlen){

	// get configuration settings
	global $config;
	// get parameters from the page request
	global $params;
	// get db link
	global $db;
	global $resourceTypeList;

	// hide the top resource bar
	global $no_resource_flag;
	$no_resource_flag = true;

	// get current playerID by user
	$playerID = $params->SESSION->player->playerID;

	// get data from Helden-table by current user-playerID
	$result = getHeroByPlayer($playerID);

	// variables for bonuspoints
	$bonuspoints = $result['bonusPunkte'];
	// for bodypower
	$bodypower = $result['koerperKraft'];
	// and if hero is in tournament
	$hero_in_turnier = isHeroInTurnier($playerID);
	$hero_by_monster = isHeroInDuell($playerID);
	// set messages
	$boni_message = "Sie k&ouml;nnen ".$bonuspoints." Bonuspunkte verteilen.";
	$flucht_message = "Setze die Fluchtgrenze";
	$turnier_msng = "Schicke Helden zu einem der zur Zeit angebotenen Turniere";
	$monster_msng = "Schicke Helden zu einem Monsterduell ...";

	// Fluchtgrenze-parameter abfangen
	if (isset($params->POST->set_grenze)) {
		$flucht = $params->POST->flucht;
		if ($flucht < 0)
			$flucht_message = "Fluchtgrenze kann nicht negativ sein, versuchen Sie sie neu zu setzen";
		else
			if ($flucht > $bodypower)
				$flucht_message = "F&uuml;r die Fluchtgrenze sind nur Werte 0..K erlaubt. ".
									"F&uuml;r Ihren Helden gilt K == ".$bodypower;
			else
				if (setRunAwayPoint($playerID, $flucht) == 1) $result['fluchtGrenze'] = $flucht;
	}

	// Held zum Monsterduell parameter abfangen
	if (isset($params->POST->monster)) {
		$xCoord = $params->POST->xCoord;
		$yCoord = $params->POST->yCoord;
		$cName  = $params->POST->cName;

		$cNameExists = false;
		if ((empty($xCoord) || empty($yCoord)) AND !empty($cName)) {
			$cave = getCaveByName($cName);
			if (sizeof($cave) != 0) {
				$xCoord = $cave['xCoord'];
				$yCoord = $cave['yCoord'];
				$cNameExists = true;
			}
		}

		if ((empty($xCoord) || empty($yCoord)) AND empty($cName))
			$monster_msng = "Sie sollten die Koordinaten oder Name der Zielh&ouml;hle setzen,".
								" denn erst dann geht Held zum Monsterkampf ...";
		else if ((empty($xCoord) || empty($yCoord)) AND !empty($cName) AND !$cNameExists)
			$monster_msng = "Es existiert keine H&ouml;hle mit dem Namen '". $cName ."' ! <br>".
								"Gebe richtigen Namen der H&ouml;hle oder Koordinaten an und schicke ".
								"den Helden zum Monsterduell ...";
		else if ($xCoord <= 0 || $yCoord <= 0)
			$monster_msng = "Die Koordinaten der H&ouml;hlen in UGA-AGGA-Land k&ouml;nnen nicht negativ ".
								"und Null sein :-)<br>Setze richtige (positive :) Koordinaten ".
								"und schicke Helden zum Monsterduell ...";
		else {
			$monster_cave = letHeroFight($playerID, $xCoord, $yCoord);
			if ($monster_cave == -2)
				$monster_msng = "Held konnet nicht (wieso auch immer) zu einem Monsterduell geschickt werden".
									"<br>Versuche erneut die Zielkoordinaten einzugeben und".
									"schicke den Helden zum Monsterduell ...";
			else if ($monster_cave == -1)
				$monster_msng = "Es existiert keine H&ouml;hle mit Koordinaten ".$xCoord."/".$yCoord.
									"<br>Setze existierende Koordinaten und schicke Helden zum Monsterduell ...";
			else if ($monster_cave == 0) {
				$monster_msng = "Held ist bei einem Monsterduell";
				$hero_by_monster = true;
			}
		}

		$choose_turnier = true;
	}

	// Held zum Turnier parameter abfangen
	if (isset($params->POST->turnier)) {
		$turnierID = $params->POST->turniere;
		$meingebot = 0;
		if (isset($params->POST->meinGebot))
			$meingebot = $params->POST->meinGebot;

		if ($turnierID < 0)
			$turnier_msng = "W&auml;hle zuerst <b>einen Turnier</b> und gebe danach noch das Gebot ein";
		else if ($meingebot < 0)
			$turnier_msng = "Das Gebot kann nicht negativ sein. W&auml;hle ein Turnier und gebe ".
									"das richtige Gebot ein";
		else if ($meingebot == 0)
			$turnier_msng = "Sie sollten schon was f&uuml;r die Teilnahme bieten";
		else {
			$play = letHeroPlay($caveID, $playerID, $turnierID, $meingebot);
			if ($play == -1)
				$turnier_msng = "Held konnte nicht zu dem Turnier geschickt werden";
			else
				if ($play == -2)
					$turnier_msng = "Sie haben nicht gen&uuml;gend Rohstoffe um f&uuml;r diesen Turnier zu bieten";
				else if ($play == 1) {
						$turnier_msng = "Held ist beim Turnier";
						$hero_in_turnier = true;
					}
		}
		$choose_turnier = true;
	}

	// Held zurueck vom Turnier
	if (isset($params->POST->abmelden)) {
		$hero_in_turnier = letHeroGoHome($playerID);

		if (!$hero_in_turnier)
			$turnier_msng = "<b>Ihr Held ist wieder da</b><br>Schicke Helden zu einem der zur Zeit angebotenen Turniere";

		$choose_turnier = true;
	}

	// Held zurueck vom Monsterduell
	if (isset($params->POST->umkehren)) {
		$hero_by_monster = letHeroTurnBack($playerID);

		if (!$hero_in_turnier)
			$monster_msng = "<b>Ihr Held ist wieder da</b><br>Schicke Helden zu einem Monsterduell";

		$choose_turnier = true;
	}

	// bonus-parameter abfangen, boni vergeben
	if (isset($params->POST->set_boni)) {
		$a_bonus = 0 + $params->POST->A_bonus;
		$v_bonus = 0 + $params->POST->V_bonus;
		$m_bonus = 0 + $params->POST->M_bonus;
		$allSetPoints = $a_bonus + $v_bonus + $m_bonus;

		if (($a_bonus < 0) || ($v_bonus < 0) || ($m_bonus < 0))
			$boni_message = "Negative Werte sind nicht erlaubt. Sie k&ouml;nnen immer noch ".$bonuspoints.
										" Bonuspunkte verteilen";
		else
			if (($bonuspoints - $allSetPoints) < 0)
				$boni_message = "Zuviele Bonuspunkte verteilt. Sie k&ouml;nnen nur ".$bonuspoints.
										" Bonuspunkte verteilen";
			else {
				$boni = array(	'angriffsWert'		=> intval($a_bonus),
								'verteidigungsWert'	=> intval($v_bonus),
								'mentalKraft'		=> intval($m_bonus));
				if (setBonusPoints($playerID, $boni, $allSetPoints) == 1) {
					$result['bonusPunkte'] = $bonuspoints = $bonuspoints - $allSetPoints;
					$result['angriffsWert'] = $result['angriffsWert'] + $a_bonus;
					$result['verteidigungsWert'] = $result['verteidigungsWert'] + $v_bonus;
					$result['mentalKraft'] = $result['mentalKraft'] + $m_bonus;
					$boni_message = "Sie k&ouml;nnen ".$bonuspoints." Bonuspunkte verteilen.";
				}
			}
	}

/* **** CREATE HERO PAGE ***** *********************************************/

	// get the template
	$template = tmpl_open($params->SESSION->player->getTemplatePath() . 'hero_detail.ihtml');

	// user has a hero
	if ($result) {
	// and hero wants to go to tournament
	if (isset($params->POST->choose_turnier))
		$choose_turnier = true;
	// turnier
	if ($choose_turnier)
	{
	$no_resource_flag = false;
		if ($hero_in_turnier) {
			if (isset($params->POST->abmelden))
				$turnier_msng = "Zur&uuml;ckholen hat nicht geklappt (wieso auch immer)<br><b>Held ist beim Turnier</b>";
			else
				$turnier_msng = "<b>Held ist beim Turnier</b>";
			tmpl_set($template, 'HERO/MESSAGE/message', $turnier_msng);

			tmpl_set($template, "HERO/ZUMTURNIER/BUTTON/button_value", "Held vom Turnier abmelden ".
											"(Ihr Gebot wird dabei verlorengehen)");
			$hidden = array(
				array('name'=>'modus',	'value'=>HERO_DETAIL),
				array('name'=>'abmelden','value'=>'true'));
			tmpl_set($template, 'HERO/ZUMTURNIER/BUTTON/PARAMS', $hidden);
		}
		else if ($hero_by_monster) {
			if (isset($params->POST->umkehren))
				$turnier_msng = "Held umkehren hat nicht geklappt (wieso auch immer)<br><b>Held ist im Monsterduell</b>";
			else
				$turnier_msng = "<b>Held ist in einem Monsterduell</b>";
			tmpl_set($template, 'HERO/MESSAGE/message', $turnier_msng);

			tmpl_set($template, "HERO/ZUMTURNIER/BUTTON/button_value", "Held vom Monsterduell zur&uuml;ckholen");
			$hidden = array(
				array('name'=>'modus',	'value'=>HERO_DETAIL),
				array('name'=>'umkehren','value'=>'true'));
			tmpl_set($template, 'HERO/ZUMTURNIER/BUTTON/PARAMS', $hidden);
		}
		else {
			$turniere = array();
			$turniere = getAllTurniers();
			if ($turniere) {
				tmpl_set($template, "HERO/ZUMTURNIER/message", $turnier_msng);

				tmpl_set($template, "HERO/ZUMTURNIER/SELECTION/SELECT/id", "-1");
				tmpl_set($template, "HERO/ZUMTURNIER/SELECTION/SELECT/selected", "selected");
				tmpl_set($template, "HERO/ZUMTURNIER/SELECTION/SELECT/turnier_name",
												"- - - W&auml;hle einen Turnier - - -");

				foreach ($turniere AS $value){
					tmpl_iterate($template, "HERO/ZUMTURNIER/SELECTION/SELECT");
					tmpl_set($template, "HERO/ZUMTURNIER/SELECTION/SELECT/id", $value['turnierID']);
					tmpl_set($template, "HERO/ZUMTURNIER/SELECTION/SELECT/turnier_name",
											"Turnier \"".$value['turnierName']."\" ( mit Gebot in " .
											$resourceTypeList[$value['art']]->name .
											getStartPointForTournament($value['turnierID']) . " )");
				}
				tmpl_set($template, "HERO/ZUMTURNIER/SELECTION/value", 0);
				tmpl_set($template, "HERO/ZUMTURNIER/BUTTON/button_value", "Held zum Turnier");
				$hidden = array(
					array('name'=>'modus',	'value'=>HERO_DETAIL),
					array('name'=>'turnier','value'=>'true'));
				tmpl_set($template, 'HERO/ZUMTURNIER/BUTTON/PARAMS', $hidden);
			}
			else {
				tmpl_set($template, "HERO/ZUMTURNIER/message", "Es werden zur Zeit keine Turniere angeboten ...");
			}
	// Monstern
			tmpl_set($template, "HERO/MONSTER/message", $monster_msng);
			tmpl_set($template, "HERO/MONSTER/MONSTERFIELD/button_value", "Los geht's");
			$hidden = array(
				array('name'=>'modus',	'value'=>HERO_DETAIL),
				array('name'=>'monster','value'=>'true'));
			tmpl_set($template, 'HERO/MONSTER/MONSTERFIELD/PARAMS', $hidden);
		}
	}
	else
	// show all herodata
	{
		// set names for columns
		$detail_for_hero = array(	'level'				=> "Lvl : ",
									'erfahrungsWert'	=> "Exp : ",
									'angriffsWert'		=> "<b>A :</b> ",
									'verteidigungsWert'	=> "<b>V :</b> ",
									'mentalKraft'		=> "<b>M :</b> ",
									'koerperKraft'		=> "<b>K :</b> ");
		// set hero detail
		tmpl_set($template, "HERO/HERO_DETAIL/name", "<u><b>".$result['name']." :</b></u>");

		foreach($detail_for_hero as $key => $value) {
			tmpl_iterate($template, "HERO/HERO_DETAIL/DETAIL");
			tmpl_set($template, "HERO/HERO_DETAIL/DETAIL/name", $detail_for_hero[$key]);
			tmpl_set($template, "HERO/HERO_DETAIL/DETAIL/value", $result[$key].",");
		}

		// if hero was lazy
		if ($result['leichteSiege'] > 9)
			tmpl_set($template, "HERO/MESSAGE/message", "<i>Ihr Held gewinnt keine Erfahrung.</i>");
		// or very lezy
		else if ($result['leichteSiege'] >= 3)
			tmpl_set($template, "HERO/MESSAGE/message","<i>Ihr Held gewinnt nur die H&auml;lfte der Erfahrung.</i>");

		// wenn bonuspunkte vorhanden
		if ($bonuspoints > 0) {
			tmpl_set($template, "HERO/BONUSPOINTS/message", $boni_message);

			$cols_for_bonus = array('angriffsWert'		=> "A",
									'verteidigungsWert'	=> "V",
									'mentalKraft'		=> "M");
			foreach($cols_for_bonus as $key => $value) {
				tmpl_iterate($template, "HERO/BONUSPOINTS/VERTEILE");
				tmpl_set($template, "HERO/BONUSPOINTS/VERTEILE/vor_input", "<b>".$cols_for_bonus[$key]."</b> : ");
				tmpl_set($template, "HERO/BONUSPOINTS/VERTEILE/name", $cols_for_bonus[$key]."_bonus");
			}
			$hidden = array(
				array('name'=>'modus',		'value'=>HERO_DETAIL),
				array('name'=>'set_boni',	'value'=>'true'));
			tmpl_set($template, 'HERO/BONUSPOINTS/PARAMS', $hidden);
		}

		// fluchtgrenze
		tmpl_set($template, "HERO/FLUCHT/message", $flucht_message);
		tmpl_set($template, 'HERO/FLUCHT/value', $result['fluchtGrenze']);

		$hidden = array(
			array('name'=>'modus',		'value'=>HERO_DETAIL),
			array('name'=>'set_grenze',	'value'=>'true'));
		tmpl_set($template, 'HERO/FLUCHT/PARAMS', $hidden);

		// set names for rows for treasure
		$body_array = array('schatzKopf'	=> "Kopf",
							'schatzHals'	=> "Hals",
							'schatzRing'	=> "Finger",
							'schatzRuestung'=> "R&uuml;stung",
							'schatzSchild'	=> "Schild",
							'schatzWaffe'	=> "Waffe");
		$schaetze_attribute = array();
		// Set all artifacts
		foreach($body_array as $key => $value) {
			tmpl_iterate($template, "HERO/SCHAETZE/SCHATZ");
			tmpl_set($template, "HERO/SCHAETZE/SCHATZ/body_part", $body_array[$key]);

			// if user has this type of artifact
			if ($result[$key]) {
				// get artifact detail
				$schaetze = getSchatzBySchatzID($result[$key]);

				// set it into template
				tmpl_set($template, "HERO/SCHAETZE/SCHATZ/artefact", $schaetze['name']);
				tmpl_set($template, "HERO/SCHAETZE/SCHATZ/artefact_value", " (".$schaetze['eigenschaften'].")");

				// get all artifact attributes one by one
				$schaetze_attribute = array_merge($schaetze_attribute, explode(" ", $schaetze['eigenschaften']));
			}
			// if no artifact of this type
			else
				tmpl_set($template, "HERO/SCHAETZE/SCHATZ/artefact", "kein Schatz angelegt");
		}

		// turnierbutton
		if ($hero_in_turnier)
			tmpl_set($template, 'HERO/TURNIERBUTTON/MESSAGE/message', "<b>Held ist in einem Turnier</b>");
		if ($hero_by_monster)
			tmpl_set($template, 'HERO/TURNIERBUTTON/MESSAGE/message', "<b>Held ist in einem Monsterduell</b>");
		$hidden = array(
			array('name'=>'modus',	'value'=>HERO_DETAIL),
			array('name'=>'choose_turnier','value'=>'true'));
		tmpl_set($template, 'HERO/TURNIERBUTTON/PARAMS', $hidden);
	}
	} // close "if ($result)"
	// user has no hero
	else {
		tmpl_set($template, "HERO/MESSAGE/message", "Sie haben keinen Helden.");

		// buy new hero hier (function is not exist at moment, only view)
		$hidden = array(
			array('name'=>'modus',	'value'=>HERO_DETAIL),
			array('name'=>'new_hero','value'=>'true'));
		tmpl_set($template, 'HERO/BUY/PARAMS', $hidden);
	}

	// return the parsed template
	return tmpl_parse($template);
}

/* **** HERO HELP-FUNCTIONS ***** ********************************************/

function getHeroByPlayer($playerID) {
	global $db;

	// set database query with playerID
	$query = "SELECT * FROM Hero h WHERE h.playerID = " . intval($playerID);

	// get result bei query
	$result = $db->query($query);

	// if successful
	if ($result)
		return $result->nextRow(MYSQL_ASSOC);
	// otherwise
	else
		return null;
}
function getSchatzBySchatzID($schatzID) {
	global $db;

	// set database query with schatz_id
	$query = "SELECT name, eigenschaften FROM Treasure t WHERE t.schatz_id = " . intval($schatzID);

	// get result bei query
	$result = $db->query($query);

	// if successful
	if ($result)
		return $result->nextRow(MYSQL_ASSOC);
	// otherwise
	else
		return null;
}
function isHeroInTurnier($playerID) {
	global $db;

	// set database query with playerID
	$query = "SELECT playerID FROM Hero_tournament WHERE playerID = $playerID";
	// get result bei query
	$r = $db->query($query);

	// if successful
	if ($r->nextRow(MYSQL_ASSOC))
		return true;
	return false;
}
function letHeroPlay($caveID, $playerID, $turnierID, $meingebot) {
	global $db;
	global $resourceTypeList;

	$select = "SELECT * FROM Tournament t WHERE t.turnierID = $turnierID";

	if (!($r = $db->query($select)))
		return -1;

	$r = $r->nextRow(MYSQL_ASSOC);

	$update = "UPDATE Cave ";
	$updateRollback = "UPDATE Cave ";

	$set = "SET caveID = $caveID ";
	$setRollback = "SET caveID = $caveID ";

	$where = "WHERE caveID = $caveID ";
	$whereRollback = "WHERE caveID = $caveID ";

	$set .= ", " . $resourceTypeList[$r['art']]->dbFieldName . " = " . $resourceTypeList[$r['art']]->dbFieldName .
					" - $meingebot ";
	$setRollback .= ", " . $resourceTypeList[$r['art']]->dbFieldName . " = " .
					$resourceTypeList[$r['art']]->dbFieldName . " + $meingebot ";

	$where .= "AND " . $resourceTypeList[$r['art']]->dbFieldName . " >= $meingebot ";

	$update = $update . $set . $where;
	$updateRollback = $updateRollback . $setRollBack . $whereRollback;

	if (!$db->query($update) || $db->affected_rows() < 1)
		return -2;

	// set database-insert query for playerID, tournament and ...
	$insert = "INSERT INTO Hero_tournament (playerID, round, gebot, turnierID) ".
									"values ($playerID, 0, $meingebot, $turnierID)";
	// if successful
	if(!$db->query($insert)){
		$db->query($updateRollback);
		return -1;
	} else
		return 1;
}
function setBonusPoints($playerID, $boni, $summe) {
	global $db;

	// set database-update query
	$update = "UPDATE Hero ";
	$set = "SET playerID = $playerID ";
	$where = "WHERE playerID = $playerID ";
	// for all hero-skills
	foreach($boni as $key => $value) {
		$set = $set . ", " . $key . " = " . $key . " + " . $boni[$key];
	}
	$set = $set . ", bonusPunkte = bonusPunkte - $summe ";

	// parse query
	$update = $update.$set.$where;

	// if successful
	if ($db->query($update))
		return 1;
	else
		return -1;
}
function setRunAwayPoint($playerID, $flucht) {
	global $db;

	// set database-update query
	$update = "UPDATE Hero ";
	$set = "SET playerID = $playerID, fluchtGrenze = $flucht ";
	$where = "WHERE playerID = $playerID ";

	// parse query
	$update = $update.$set.$where;

	// if successful
	if ($db->query($update))
		return 1;
	else
		return -1;
}
function getAllTurniers() {
	global $db;

	// set database query for all tournaments
	$query = "SELECT * FROM Tournament t";

	// get result bei query
	$r = $db->query($query);

	if (!$r || $r->isEmpty())
		return array();

	$result = array();
	while($row = $r->nextRow(MYSQL_ASSOC))
		array_push($result, $row);
	return $result;
}
function getStartPointForTournament($turnierID) {
	global $db;

	$query = "SELECT starttime FROM Tournament t WHERE t.turnierID = $turnierID";

	$r = $db->query($query);

	if (!$r)
		return "";

	$r = $r->nextRow(MYSQL_ASSOC);

	if ($r['starttime'] == "")
		return "";
	else
		return ", Start: " . date("d.m.Y \u\m H:i", time_timestampToTime($r['starttime']));
}
function letHeroFight($playerID, $xCoord, $yCoord) {
	global $db;

	$query = "SELECT caveID FROM Cave c WHERE c.xCoord = $xCoord AND c.yCoord = $yCoord";

	$r = $db->query($query);

	if (!$r)
		return -2;
	else if ($db->affected_rows() < 1)
		return -1;
	else {
		$r = $r->nextRow(MYSQL_ASSOC);
		$caveID = $r['caveID'];

		$insert = "INSERT INTO Hero_Monster (playerID, caveID, starttime) ".
							   "values ($playerID, $caveID, NOW() + 0)";
		$r = $db->query($insert);

		if (!$r)
			return -2;
		else
			return 0;
	}
}
function isHeroInDuell($playerID) {
	global $db;

	$query = "SELECT playerID FROM Hero_Monster WHERE playerID = $playerID";

	$r = $db->query($query);

	if ($r->nextRow(MYSQL_ASSOC))
		return true;
	return false;
}
function letHeroTurnBack($playerID) {
	global $db;

	$query = "DELETE FROM Hero_Monster WHERE playerID = $playerID";

	if (!$db->query($query))
		return true;
	else
		return false;
}
function letHeroGoHome($playerID) {
	global $db;

	$query = "DELETE FROM Hero_tournament WHERE playerID = $playerID";

	if (!$db->query($query))
		return true;
	else
		return false;
}

?>