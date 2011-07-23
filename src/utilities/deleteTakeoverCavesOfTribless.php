<?php
/**
 * This script delets inactive player´s takeoverable caves
 */

include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

echo "---------------------------------------------------------------------\n";
echo "- deleteTakeoverableCavesOfTribless LOG FILE ------------------------\n";
echo "  vom " . date("r") . "\n";

/**
 * Connects to game DB
 *
 * @return  a DB link
 */
function connectToGameDB() {
		global $config;

		$db_game = new Db($config->DB_GAME_HOST,
						$config->DB_GAME_USER,
						$config->DB_GAME_PWD,
						$config->DB_GAME_NAME);

		if (!$db_game) {
				inactives_log('Failed to connect to login DB.');
				exit(1);
		}

		return $db_game;
}



function cave_giveUpCave($db, $caveID, $playerID){
		$query = "UPDATE Cave SET playerID = 0, takeoverable = 2, ".
				"protection_end = NOW()+0, secureCave = 0 ".
				"WHERE playerID = '$playerID' AND ".
				"caveID = '$caveID' AND ".
				"starting_position = 0";

		if (!$db->query($query)) return 0;
		if (!$db->affected_rows()) return 0;

		// delete all scheduled Events
		//   Event_movement - will only be deleted, when a new player gets that cave
		//   Event_artefact - can't be deleted, as it would result in serious errors
		//   Event_wonder   - FIX ME: don't know
		$db->query("DELETE FROM Event_defenseSystem WHERE caveID = '$caveID'");
		$db->query("DELETE FROM Event_expansion WHERE caveID = '$caveID'");
		$db->query("DELETE FROM Event_science WHERE caveID = '$caveID'");
		$db->query("DELETE FROM Event_unit WHERE caveID = '$caveID'");

		return 1;
}

function getTakeoverableCaves($db, $playerID){

		$query = "SELECT * FROM Cave ".
				"WHERE playerID = ". intval($playerID) . " ".
				"AND secureCave = '0'";

		$result = $db->query($query);
		$caves = array();
		if ($result){
				while($row = $result->nextRow(MYSQL_ASSOC))
						$caves[] = $row;
				return $caves;
		}
		return 0;
}

function getPlayersWithoutTribe($db){

		$query = "SELECT * FROM `Player`".
				"WHERE `tribe` LIKE '' ".
				"AND ( UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`tribeBlockEnd`) ) / 3600 >= ".LOSE_CAVE_AFTER_COULD_JOIN_TRIBE_HOURS;

		$result = $db->query($query);
		$players = array();
		if ($result){
				while($row = $result->nextRow(MYSQL_ASSOC))
						$players[] = $row;
				return $players;
		}
		return 0;
}

{
		global $config;
		$config = new Config();
		$db = 	connectToGameDB();
		srand ((double)microtime()*1000000);
		$players = getPlayersWithoutTribe($db);
		if ($players==0) {
				echo "ERROR: getPlayersWithoutTribe\n";
				exit;
		}
		foreach ($players as $player) {
				$caves = getTakeoverableCaves($db, $player[playerID]);
				if ($caves !=0) {
						$cavecount = count($caves);
						if ($cavecount > 0) {
								$randcave = $caves[rand(0,$cavecount-1)];
								cave_giveUpCave($db, $randcave[caveID], $player[playerID]);	
								echo "Delete From Player: ". $player[playerID].
										 " cave ". $randcave[caveID]. "\n";
						}
				} else {
						echo "ERROR: getTakeoverableCaves for " . $player[playerID]."\n" ;

				}
		}
}

?>