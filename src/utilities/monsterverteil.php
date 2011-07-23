<?php 
global $config;
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

$config = new Config();

if (!($db = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
echo "fehler";
  exit(1);
}


if (!($r = $db->query("SELECT caveID FROM Cave where  monsterID != 0"))) {  
echo "fehler";
  exit(1);
}
while($row = $r->nextRow()){
  $monsterID = rand(1,195);
  $querry = "UPDATE Cave set monsterID = ".$monsterID." where caveID = ".$row['caveID']." ;"; 
  echo $querry."\n";
  $db->query($querry);
}

?>