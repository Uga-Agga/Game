<?php
/*
 * ranking.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."game_rules.php";

$config = new Config();
$db     = DbConnect();

init_buildings();
init_defenseSystems();
init_resources();
init_sciences();
init_units();

global $buildingTypeList,
       $defenseSystemTypeList,
       $resourceTypeList,
       $scienceTypeList,
       $unitTypeList;

///////////////////////////// constant values //////////////////////////////

// playerID => ranking_points
$constant_values = array (
);

echo "-----------------------------------------------------------------------\n";
echo "- RANKING LOG FILE ----------------------------------------------------\n";
echo "  vom " . date("r") . "\n";

// Ranking nach 2-Schritte-Prozess:

// 1. Teilbereiche ranken
// 2. Durchschnitt bilden +++NEU+++ Der Durchschnitt wird nun fuer drei Tage
//    aufbewahrt und daraus ein 3-Tage-Mittel bestimmt

// Die Teilbereiche fuer Schritt 1 lauten:

//  a.) Summe der Groessen der milit. Einheiten + Verteidigungsanlagen
//  ausgeschieden b.) Summe der Hoehlen
//  c.) Summe aller vorhandenen Rohstoffe
//  d.) Summe aller Gebaeude in allen Hoehlen
//  e.) Summe aller Entdeckungen
//  f.) Summe aller Artefakte

// ----------------------------------------------------------------------------
// Schritt (0.a.) alte Werte loeschen
$sql = $db->prepare("SELECT r.playerID
                     FROM " . RANKING_TABLE . " r
                       LEFT JOIN " . PLAYER_TABLE . " p
                         ON p.playerID = r.playerID
                     WHERE ISNULL(p.name)
                       OR p.tribe LIKE :god_ally
                       OR p.tribe LIKE :quest_ally");
$sql->bindValue('god_ally', GOD_ALLY, PDO::PARAM_STR);
$sql->bindValue('quest_ally', QUEST_ALLY, PDO::PARAM_STR);
if (!$sql->execute()) {
  echo "Fehler beim Auslesen geloeschter Spieler in Schritt (0.a.i)\n";
  return -17;
}

$deleted_players = array();
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  array_push($deleted_players, $row['playerID']);
}
$sql->closeCursor();
echo "Folgende SpielerIDs wurden aus der Ranking Tabelle geloescht:<br>\n";

$sqlDelete = $db->prepare("DELETE FROM " . RANKING_TABLE . " WHERE playerID = :playerID");
for ($i = 0; $i < sizeof($deleted_players); ++$i) {
  echo "playerID = " . $deleted_players[$i] . "<br>\n";

  $sqlDelete->bindValue('playerID', $deleted_players[$i], PDO::PARAM_INT);
  if (!$sqlDelete->execute()) {
    echo "Fehler beim loeschen der alten Werte.\n";
    return -1;
  }
}

// ----------------------------------------------------------------------------
// Schritt (0.b.) neue Werte eintragen

$query = "INSERT IGNORE INTO " . RANKING_TABLE . " (playerID, name, religion)
          SELECT p.playerID, p.name,
            CASE WHEN p.".DB_UGA_FIELDNAME." > p.".DB_AGGA_FIELDNAME." THEN 'uga'
              WHEN p.".DB_AGGA_FIELDNAME." > p.".DB_UGA_FIELDNAME." THEN 'agga'
              WHEN p.".DB_ENZIO_FIELDNAME." > p.".DB_UGA_FIELDNAME." THEN 'enzio'
                ELSE 'none' END AS religion
           FROM " . PLAYER_TABLE . " p
           WHERE p.tribe NOT LIKE '".GOD_ALLY."' AND p.tribe NOT LIKE '".QUEST_ALLY."'";
if (!$db->query($query)) {
  echo "Fehler beim Anlegen der neuen Werte.\n";
  return -2;
}

// ----------------------------------------------------------------------------
// Schritt (0.c.) Banned Liste erstellen, von Spielern, die nicht ins Ranking sollen

$sql = $db->prepare("SELECT playerID, name
                     FROM " . PLAYER_TABLE . "
                     WHERE tribe LIKE :god_ally
                       OR tribe LIKE :quest_ally");
$sql->bindValue('god_ally', GOD_ALLY, PDO::PARAM_STR);
$sql->bindValue('quest_ally', QUEST_ALLY, PDO::PARAM_STR);

if (!$sql->execute()) {
  echo "Fehler beim Anlegen der banned Liste. (0.c.)\n";
  return -17;
}

echo "Folgende SpielerIDs sind vom Ranking gebanned:<br>\n";
$banned_players = array();
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  array_push($banned_players, $row['playerID']);
  echo "ID : " . $row['playerID'] . " Name : " . $row['name'] . "<br>\n";
}
$sql->closeCursor();

// ----------------------------------------------------------------------------
// Schritt (0.d.) Religion und Ruhm erneuern

$sql = $db->prepare("SELECT p.playerID, 
                       CASE WHEN ".DB_UGA_FIELDNAME." > ".DB_AGGA_FIELDNAME." THEN 'uga'
                         WHEN ".DB_AGGA_FIELDNAME." > ".DB_UGA_FIELDNAME." THEN 'agga'
                         WHEN p.".DB_ENZIO_FIELDNAME." > p.".DB_UGA_FIELDNAME." THEN 'enzio'
                           ELSE 'none' END AS religion
                     FROM " . PLAYER_TABLE . " p
                     WHERE p.tribe NOT LIKE :god_ally
                       AND p.tribe NOT LIKE :quest_ally");
$sql->bindValue('god_ally', GOD_ALLY, PDO::PARAM_STR);
$sql->bindValue('quest_ally', QUEST_ALLY, PDO::PARAM_STR);
if (!$sql->execute()) {
  echo "Fehler beim Auslesen der Religion. (0.d.)\n";
  return -1;
}
$rows = $sql->fetchAll(PDO::FETCH_ASSOC);
$sql->closeCursor();

$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . " SET religion = :religion WHERE playerID = :playerID");
foreach ($rows as $row) {
  $sqlUpdate->bindValue('religion', $row['religion'], PDO::PARAM_INT);
  $sqlUpdate->bindValue('playerID', $row['playerID'], PDO::PARAM_INT);
  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Eintragen der Religion und des Ruhmes. (0.d.)\n";
    return -1;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.a.) Summe der Groessen der milit. Einheiten + Verteidigungsanlagen

// Funktion zur Bewertung der Staerke einer milit. Einheit foer das Ranking
function unit_rating ($unit) {
  $attackAreal = (isset($unit->attackAreal)) ? $unit->attackAreal * 0.2 : 0;
  return round(($unit->attackRange * 1.3 + $attackAreal +
    $unit->attackRate + $unit->defenseRate +
    $unit->hitPoints) / 3);
}

$unitColNames = array();
for ($i = 0; $i < sizeof($unitTypeList); ++$i) {
  array_push($unitColNames, unit_rating($unitTypeList[$i]) . " * " . $unitTypeList[$i]->dbFieldName);
}
$unitColNames  = implode(" + ", $unitColNames);

$defenseColNames = array();
for ($i = 0; $i < sizeof($defenseSystemTypeList); ++$i) {
  array_push($defenseColNames, unit_rating($defenseSystemTypeList[$i]) . " * " . $defenseSystemTypeList[$i]->dbFieldName);
}
$defenseColNames = implode(" + ", $defenseColNames);

$military = array();

// zuerst Einheiten aus der Tabelle 'Cave' einfuegen
$sql = $db->prepare("SELECT playerID, SUM(" . $unitColNames . " + " . $defenseColNames . ") AS military
                     FROM " . CAVE_TABLE . "
                     GROUP BY playerID
                     HAVING playerID != 0");
if (!$sql->execute()) {
  echo "Fehler beim Auslesen in Schritt (1.a.i)\n";
  return -3;
}

while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  if (!isset($military[$row['playerID']])) {
    $military[$row['playerID']] = $row['military'];
  } else {
    $military[$row['playerID']] += $row['military'];
  }
}
$sql->closeCursor();

// dann Einheiten aus der Tabelle 'Event_Movement' dazu addieren
$movingUnitColNames = array();
for ($i = 0; $i < sizeof($unitTypeList); ++$i) {
  array_push($movingUnitColNames, unit_rating($unitTypeList[$i]) . " * m." . $unitTypeList[$i]->dbFieldName);
}
$movingUnitColNames  = implode(" + ", $movingUnitColNames);

$sql = $db->prepare("SELECT c.playerID, m.caveID, SUM(" . $movingUnitColNames . ") AS military
                     FROM " . EVENT_MOVEMENT_TABLE . " m
                       LEFT JOIN " . CAVE_TABLE . " c ON c.caveID = m.caveID
                     GROUP BY m.caveID
                     HAVING caveID != 0");
if (!$sql->execute()) {
  echo "Fehler beim Auslesen in Schritt (1.a.ii)\n";
  return -4;
}

while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($military[$row['playerID']])) {
    $military[$row['playerID']] = $row['military'];
  } else {
    $military[$row['playerID']] += $row['military'];
  }
}
$sql->closeCursor();

// military ranking

// first delete banned players from ranking
$military = unsetBanned($military, $banned_players);

$maxval = max($military) / 10000;
if (!$maxval)
  $maxval = 1;

$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . "
                           SET military = :military,
                             military_rank = :military_rank
                           WHERE playerID = :playerID");
foreach ($military as $playerID => $military) {
  $sqlUpdate->bindValue('military', $military, PDO::PARAM_INT);
  $sqlUpdate->bindValue('military_rank', floor($military/$maxval), PDO::PARAM_INT);
  $sqlUpdate->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.a.iii)\n";
    return -5;
  }
}

// ----------------------------------------------------------------------------
// (1.b.) Summe der Hoehlen


$query = "UPDATE " . RANKING_TABLE . " SET caves = 0";
if (!$db->query($query)) {
  echo "Fehler beim Hoehlenzaehlen (1.b.)\n";
  return -1;
}

$sql = $db->prepare("SELECT playerID, Count(*) AS anzahl
                     FROM " . CAVE_TABLE . "
                     WHERE playerID != 0
                     GROUP BY playerID");
if (!$sql->execute()) {
  echo "Fehler beim Hoehlenzaehlen in Schritt (1.b.i)\n";
  return -6;
}

$caves = array();
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  $caves[$row['playerID']] = $row['anzahl'];
}

$maxval = max($caves) / 10000;
$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . " SET caves = :countCaves WHERE playerID = :playerID");
foreach ($caves as $playerID => $countCaves) {
  $sqlUpdate->bindValue('countCaves', $countCaves, PDO::PARAM_INT);
  $sqlUpdate->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.b.ii)\n";
    return -7;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.c.) Summe aller vorhandenen Rohstoffe
// FIXME: Die Rohstoffe moessen gewichtet werden!
$resourcesColNames = array();
for ($i = 0; $i < sizeof($resourceTypeList); ++$i) {
  array_push($resourcesColNames, $resourceTypeList[$i]->takeoverValue . " * " . $resourceTypeList[$i]->dbFieldName);
}
$resourcesColNames  = implode(" + ", $resourcesColNames);

$sql = $db->prepare("SELECT playerID, SUM(" . $resourcesColNames . ") as resources
                     FROM " . CAVE_TABLE . "
                     GROUP BY playerID
                     HAVING playerID != 0");
if (!$sql->execute()) {
  echo "Fehler beim Rohstoffe zaehlen in Schritt (1.c.i)\n";
  return -8;
}

$resources = array();
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  $resources[$row['playerID']] = $row['resources'];
}
$sql->closeCursor();

// first delete banned players from ranking
$resources = unsetBanned($resources, $banned_players);

$maxval = max($resources) / 10000;
$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . "
                           SET resources = :resources,
                             resources_rank = :resources_rank
                           WHERE playerID = :playerID");
foreach ($resources as $playerID => $resources) {
  $sqlUpdate->bindValue('resources', $resources, PDO::PARAM_INT);
  $sqlUpdate->bindValue('resources_rank', floor($resources/$maxval), PDO::PARAM_INT);
  $sqlUpdate->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.c.ii)\n";
    return -9;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.d.) Summe aller Gebaeude in allen Hoehlen

$buildingsColNames = array();
for ($i = 0; $i < sizeof($buildingTypeList); ++$i) {
  array_push($buildingsColNames, $buildingTypeList[$i]->ratingValue . " * " . $buildingTypeList[$i]->dbFieldName);
}
$buildingsColNames  = implode(" + ", $buildingsColNames);

$sql = $db->prepare("SELECT playerID, SUM(" . $buildingsColNames . ") as buildings
                     FROM " . CAVE_TABLE . "
                     GROUP BY playerID
                     HAVING playerID != 0");
if (!$sql->execute()) {
  echo "Fehler beim Gebaeude zaehlen in Schritt (1.d.i)\n";
  return -10;
}
/// HIER BIN ICH ///
$buildings = array();
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  $buildings[$row['playerID']] = $row['buildings'];
}
$sql->closeCursor();

// first delete banned players from ranking
$buildings = unsetBanned($buildings, $banned_players);

$maxval = max($buildings) / 10000;

$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . "
                           SET buildings = :buildings,
                             buildings_rank = :buildings_rank
                           WHERE playerID = :playerID");
foreach ($buildings as $playerID => $buildings) {
  $sqlUpdate->bindValue('buildings', $buildings, PDO::PARAM_INT);
  $sqlUpdate->bindValue('buildings_rank', floor($buildings/$maxval), PDO::PARAM_INT);
  $sqlUpdate->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.d.ii)\n";
    return -11;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.e.) Summe aller Entdeckungen

$sciencesColNames = array();
for ($i = 0; $i < sizeof($scienceTypeList); ++$i) {
  array_push($sciencesColNames, $scienceTypeList[$i]->dbFieldName);
}
$sciencesColNames  = implode(" + ", $sciencesColNames);

$sql = $db->prepare("SELECT playerID, (" . $sciencesColNames . ") AS sciences
                     FROM " . PLAYER_TABLE . "
                     ORDER BY sciences");
if (!$sql->execute()) {
  echo "Fehler beim Wissenschaftszaehlen in Schritt (1.e.i)\n";
  return -12;
}

$sciences = array();
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  $sciences[$row['playerID']] = $row['sciences'];
}
$sql->closeCursor();

// first delete banned players from ranking
$sciences = unsetBanned($sciences, $banned_players);

$maxval = max($sciences) / 10000;
$maxval = (!$maxval) ? 1 : $maxval;

$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . "
                           SET sciences = :sciences,
                             sciences_rank = :sciences_rank
                           WHERE playerID = :playerID");
foreach ($sciences as $playerID => $sciences) {
  $sqlUpdate->bindValue('sciences', $sciences, PDO::PARAM_INT);
  $sqlUpdate->bindValue('sciences_rank', floor($sciences/$maxval), PDO::PARAM_INT);
  $sqlUpdate->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.e.ii)\n";
    return -13;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.f.) Summe aller Artfakte
$sql = $db->prepare("SELECT playerID, SUM(artefacts) AS artefacts
                     FROM " . CAVE_TABLE . "
                     WHERE playerID != 0
                     GROUP BY playerID
                     ORDER BY artefacts");
if (!$sql->execute()) {
  echo "Fehler beim Artefaktzaehlen in Schritt (1.f.i)\n";
  return -1;
}

$artefacts = array();
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  $artefacts[$row['playerID']] = $row['artefacts'];
}
$sql->closeCursor();

// first delete banned players from ranking
$artefacts = unsetBanned($artefacts, $banned_players);

$maxval = max($artefacts) / 10000;
$maxval = (!$maxval) ? 1 : $maxval;

$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . "
                           SET artefacts = :artefacts,
                             artefacts_rank = :artefacts_rank
                           WHERE playerID = :playerID");
foreach ($artefacts as $playerID => $artefacts) {
  $sqlUpdate->bindValue('artefacts', $artefacts, PDO::PARAM_INT);
  $sqlUpdate->bindValue('artefacts_rank', floor($artefacts/$maxval), PDO::PARAM_INT);
  $sqlUpdate->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.f.ii)\n";
    return -1;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.g.) Clanpunkte oebertragen 
$sql = $db->prepare("SELECT MAX(playerAverage) AS max
                     FROM " . RANKING_TRIBE_TABLE);
if (!$sql->execute() || !($row = $sql->fetch(PDO::FETCH_ASSOC))) {
  echo "Fehler beim Finden der maximalen Stammespunkte in Schritt (1.g)\n" .$query . "\n";
  return -12;
}
$sql->closeCursor();

$max = $row['max'] ? $row['max'] : 1;
$factor = 10000 / $max;

$sql = $db->prepare("SELECT p.playerID, t.playerAverage AS tribePoints
                     FROM " . PLAYER_TABLE . " p
                       LEFT JOIN " . RANKING_TRIBE_TABLE . " t ON t.tribe LIKE p.tribe
                     WHERE t.tribe IS NOT NULL");
if (!$sql->execute()) {
  echo "Fehler beim Finden der Stammespunkte in Schritt (1.g)\n" .$query . "\n";
  return -12;
}

$tribePoints = array();
while($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  $tribePoints[$row['playerID']] = $row['tribePoints'] * $factor ;
}
$sql->closeCursor();

$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . "
                           SET tribePoints = :tribePoints
                           WHERE playerID = :playerID");
foreach ($tribePoints as $playerID => $value) {
  $sqlUpdate->bindValue('tribePoints', $value, PDO::PARAM_INT);
  $sqlUpdate->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.g)\n";
    return -13;
  }
}

// ----------------------------------------------------------------------------
// Schritt (2.a.) Durchschnitt bilden
$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . " r
                             INNER JOIN " . PLAYER_TABLE . " p ON r.playerID = p.playerID
                           SET r.fame= GREATEST(0, p.warpoints_pos - p.warpoints_neg)");
if (!$sqlUpdate->execute()) {
  echo "Fehler beim Einfuegen der Knueppelpunkte in das Spielerrank (2.a.i)\n";
  return -14;
}

$sql = $db->prepare("SELECT max(fame) as max
                     FROM " . RANKING_TABLE);
if (!$sql->execute() || !($row = $sql->fetch(PDO::FETCH_ASSOC))) {
  echo "Fehler beim Finden der maximalen KP in Schritt (2.a.i)\n" .$query . "\n";
  return -14;
}
$sql->closeCursor();

$max = $row['max'] ? $row['max'] : 1;

$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . " r
                           SET average_2 = average_1,
                             average_1 = average_0,
                             playerPoints = SIGN(caves)*(military_rank + 3*buildings_rank + 5*sciences_rank + (ROUND(10000 * fame / GREATEST(1,(:max)))) ) / 10,
                             average_0 = playerPoints,
                             average = (average_0 + average_1 + average_2)/3");
$sqlUpdate->bindValue('max', $max, PDO::PARAM_INT);
if (!$sqlUpdate->execute()) {
  echo "Fehler beim Einfuegen der durchschnittlichen Punktzahl (2.a.ii)\n";
  return -14;
}


// ----------------------------------------------------------------------------
// Schritt (2.a.2) Constant ranking values
$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . "
                           SET average = :average
                           WHERE playerID = :playerID");
foreach ($constant_values AS $playerID => $value) {
  $sqlUpdate->bindValue('average', $value, PDO::PARAM_INT);
  $sqlUpdate->bindValue('playerID', $playerID, PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Setzen der konstanten Punktzahl in (2.a.2)\n";
  }
  echo "PlayerID $playerID: Feste Punktzahl $value";
}


// ----------------------------------------------------------------------------
// Schritt (2.b.) Rang eintragen
$sql = $db->prepare("SELECT playerID
                     FROM " . RANKING_TABLE . "
                     ORDER BY average DESC, playerPoints DESC");
if (!$sql->execute()) {
  echo "Fehler beim Einfuegen des Rangs in Schritt (2.b.i)\n";
  return -15;
}
$rows = $sql->fetchAll(PDO::FETCH_ASSOC);
$sql->closeCursor();

$count = 1;
$sqlUpdate = $db->prepare("UPDATE " . RANKING_TABLE . "
                           SET rank = :rank
                           WHERE playerID = :playerID");
foreach ($rows as $row) {
  $sqlUpdate->bindValue('rank', $count++, PDO::PARAM_INT);
  $sqlUpdate->bindValue('playerID', $row['playerID'], PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Einfuegen des Rangs in Schritt (2.b.ii)\n";
    return -16;
  }
}

// ***** FUNCTIONS *****
function unsetBanned($haystack, $banned) {
  for ($banned_count = 0; $banned_count < sizeof($banned); ++$banned_count) {
    unset($haystack[$banned[$banned_count]]);
  }
  return $haystack;
}

// -----------------------------------------------------------------------------
// TRIBE RANKING: STEP 1: Group tribes and accumulate average

/*
if (!$db->query("DELETE FROM RankingTribe")) {
  echo "Error deleting old tribe ranks.";
  return -17;
}*/
$query =
  "REPLACE INTO " . RANKING_TRIBE_TABLE . " (tribe, rank, points_rank, warpoints, glory, members, caves, playerAverage, war_won, war_lost)
   SELECT
     t.tag,
     rt.rank,
     rt.points_rank,
     GREATEST( 0, t.`warpoints_pos` - t.`warpoints_neg` )  as warpoints,
     rt.points_rank + ROUND(50*IFNULL(GREATEST( 0, t.`warpoints_pos` - t.`warpoints_neg` ) / (SELECT max(GREATEST( 0, `warpoints_pos` - `warpoints_neg` )) FROM Tribe),0)) as  glory,
     COUNT(r.playerID) as members,
     SUM(r.caves) as caves,
     SUM(r.average) / COUNT(r.playerID),
     t.war_won,
     t.war_lost
   FROM Tribe t
   LEFT JOIN Player p ON p.tribe LIKE t.tag
   LEFT JOIN Ranking r ON r.playerID = p.playerID
   LEFT JOIN RankingTribe rt ON p.tribe like rt.tribe
   WHERE r.playerID IS NOT NULL
   GROUP BY t.tag";
if (!$db->query($query)) {
  echo "Error accumulating tribe points.\n";
  return -18;
}

// ----------------------------------------------------------------------------
// TRIBE RANKING: STEP 2: Calculate ranks
$sql = $db->prepare("SELECT rankingID
                     FROM " . RANKING_TRIBE_TABLE . "
                     ORDER BY glory DESC, -1*(1 + playerAverage)");
if (!$sql->execute()) {
  echo "Fehler beim Einfuegen des Rangs in Schritt (Tribe Ranking 2)\n";
  return -15;
}
$rows = $sql->fetchAll(PDO::FETCH_ASSOC);
$sql->closeCursor();

$count = 1;
$sqlUpdate = $db->prepare("UPDATE " . RANKING_TRIBE_TABLE . "
                           SET rank = :rank
                           WHERE rankingID = :rankingID");
foreach ($rows as $row) {
  $sqlUpdate->bindValue('rank', $count++, PDO::PARAM_INT);
  $sqlUpdate->bindValue('rankingID', $row['rankingID'], PDO::PARAM_INT);

  if (!$sqlUpdate->execute()) {
    echo "Fehler beim Einfuegen des Rangs in Schritt (Tribe Ranking 2)\n";
    return -16;
  }
}
$sql->closeCursor();

?>