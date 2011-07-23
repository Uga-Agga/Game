<?php 
global $config, $scienceTypeList;

define("INC_DIR", "/var/www/game/include/");

if ($_SERVER['argc'] != 1) {
  echo "Usage: ".$_SERVER['argv'][0]."\n";
  exit (1);
}

echo "REPAIR START SCIENCES...\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."formula_parser.inc.php";

$config = new Config();

if (!($db = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
  echo "UNIT STATS: Failed to connect to game db.\n";
  exit(1);
}

$query = 
  "SELECT * FROM StartValue";

if (!($result = $db->query($query))) {
  echo "Could not get start values.\n"; exit(1);
}

$allValues = array();
while($row = $result->nextRow(MYSQL_ASSOC)) {
  $allValues[$row['dbFieldName']] = $row['value'];
}

$scienceSet = array();
foreach($scienceTypeList AS $id => $science) {
  if (($tmp = $allValues[$science->dbFieldName]) > 0) {
    $scienceSet[] = "{$science->dbFieldName} = '{$tmp}'";
  }
}

$set = implode($scienceSet, ", ");
echo "Set string: $set \n";

$query = 
  "SELECT playerID, caveID ".
  "FROM Cave ".
  "WHERE playerID != 0 ".
  "AND yCoord > 120 ".
  "AND starting_position > 0 ".
  "AND building_trainingcenter >= 3 ";   // only those with option on

if (!($result = $db->query($query))) {
  echo "Could not get playerIDs.\n"; exit(1);
}

while($player = $result->nextRow(MYSQL_ASSOC)) {
  echo "Updating player {$player['playerID']}.\n";

  $query = 
     "UPDATE Player ".
     "SET $set ".
     "WHERE playerID = '{$player['playerID']}'";
     
  if (! $db->query($query)) {
    echo "Could not update player entry:\n $query\n";
    exit(1);
  }

  $query =
    "UPDATE Cave ".
    "SET $set ".
    "WHERE caveID = '{$player['caveID']}'";
   
  if (! $db->query($query)) {
    echo "Could not update cave entry:\n $query\n";
    exit(1);
  }

}

?>