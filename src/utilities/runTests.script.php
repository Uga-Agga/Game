<?php 
/*
 * runTest.script.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

global $config, $unitTypeList;

include "util.inc.php";

include INC_DIR."basic.lib.php";

echo "RUNNING TESTS...<br>\n";

echo "CONFIG.INC.PHP Including and instatiation<br>\n";
initMeasure();
include INC_DIR."config.inc.php";
$config = new Config();
resultMeasure(); echo "<br>\n";


echo "DB Including and instatiation<br>\n";
initMeasure();
include INC_DIR."db.inc.php";
if (!($db = DbConnect())) {
  echo "TESTS: Failed to connect to game db.<br>\n";
  exit(1);
}
resultMeasure(); echo "<br><br>";

{
echo "<br>\n################ Ranking ##################\n<br><br>\n";


include INC_DIR."ranking.inc.php";

echo "ranking_getReligiousDistribution()<br>\n";
initMeasure();
ranking_getReligiousDistribution();
echo "Erstes Mal: "; resultMeasure();
for ($i=0; $i < 9; $i++) {
  ranking_getReligiousDistribution();
}
resultMeasure(10); echo "<br>\n";
  
echo "ranking_getRowsByOffset() - 20 Einträge<br>\n";
define("RANKING_ROWS", 20);
initMeasure();
ranking_getRowsByOffset(0, 100);
echo "Erstes Mal: "; resultMeasure();
for ($i=0; $i < 9; $i++) {
  ranking_getRowsByOffset(0, 100);
}
resultMeasure(10); echo "<br>\n";

}




echo "<br>\n############### Game Rules ################\n<br><br>\n";

echo "FORMULA_PARSER.INC.PHP Including and instatiation of rules<br>\n";
initMeasure();
include INC_DIR."formula_parser.inc.php";
resultMeasure(); 
echo "<br>\n";

/////////////////// measuring functions ///////////////////////

function initMeasure() {
  global $db;
  
  stopwatch('start');

}

function resultMeasure($div = 1) {
  global $db;

  $proctime  = stopwatch();
  if (isset($db)) {
    $dbpercent = round($db->getQueryTime()/$proctime * 100, 2);
      
    echo "Zeit/$div: ".($proctime/$div)."s (".
         (100 - $dbpercent)."% PHP - ".
         ($dbpercent ? "$dbpercent" : "0")."% MySQL) <br>\n";
  }
}

function stopwatch($start = false) {
  static $starttime;

  list($usec, $sec) = explode(" ", microtime());
  $mt = ((float)$usec + (float)$sec);

  if (!empty($start))
    return ($starttime = $mt);
  else
    return $mt - $starttime;
}

?>