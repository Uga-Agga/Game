<?php 
die();
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
global $config;




$config = new Config();

if (!($db_game =
      new Db($config->DB_GAME_HOST, $config->DB_GAME_USER,
             $config->DB_GAME_PWD, $config->DB_GAME_NAME))) {
  exit(1);
}
$db_game->query("UPDATE `RankingTribe` SET " . 
  "`points_rank` = round((cast( `points_rank` AS SIGNED ) + ( 1500 - CAST( `points_rank` AS SIGNED ) ) * 0.025 ))");

?>
