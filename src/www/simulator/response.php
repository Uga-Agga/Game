<?php
{
  require_once("../game/include/game_rules.php");
  init_units();
  init_defenseSystems();
  init_resources();

  for ($i = 0; $i < sizeof($_POST['effectDefender']); $i++) {
    $argument_list .= ($n = $_POST['effectDefender'][$i]) ? " $n" : " 0" ;
    $argument_list .= ($n = $_POST['effectAttacker'][$i]) ? " $n" : " 0" ;
  }
  for ($i = 0; $i < sizeof($resourceTypeList); $i++) {
    $argument_list .= " 0";
  }
  for ($i = 0; $i < sizeof($unitTypeList); $i++) {
    $argument_list .= ($n = $_POST['unitDefender'][$i]) ? " $n" : " 0";
    $argument_list .= ($n = $_POST['unitAttacker'][$i]) ? " $n" : " 0";
  }
  for ($i = 0; $i < sizeof($defenseSystemTypeList); $i++) {
    $argument_list .= ($n = $_POST['defenseSystemDefender'][$i]) ? " $n" : " 0";
  }

  exec($cmd = "./simul ".escapeshellcmd($argument_list), $output);

  $template = @tmpl_open("response.ihtml");

  $c = sizeof($resourceTypeList); 

  for($i = 0; $i < sizeof($unitTypeList); $i++, $c+=2) {
    $unitTypeList[$i]->number_defender = $output[$c];
    $unitTypeList[$i]->number_attacker = $output[$c+1];
  }

  for($i = 0; $i < sizeof($defenseSystemTypeList); $i++, $c++) {
    $defenseSystemTypeList[$i]->number_defender = $output[$c];
  }

  usort($unitTypeList, "nameCompare");
  usort($defenseSystemTypeList, "nameCompare");

  foreach($unitTypeList AS $unit){
    if(!$unit->nodocumentation){  
    tmpl_iterate($template, '/UNIT');
    tmpl_set($template, 'UNIT', get_object_vars($unit));
    }
  }
  foreach($defenseSystemTypeList AS $defenseSystem){
    if(!$defenseSystem->nodocumentation){  
    tmpl_iterate($template, '/DEFENSESYSTEM');
    tmpl_set($template, 'DEFENSESYSTEM', get_object_vars($defenseSystem));
  }
  }

  $statsData = array(
    "Fernangriff",
    "Geb&auml;udeschaden",
    "Get&uuml;mmelschaden",
    "Gr&ouml;&szlig;e");

  for ($i=0; $i < sizeof($statsData); $i++) {
    
    tmpl_iterate($template, '/STATS');
    tmpl_set($template, "/STATS/alternate", 
	     ($count++ % 2 ? "alternate" : ""));
    tmpl_set($template, "/STATS/text", $statsData[$i]);
    tmpl_set($template, "/STATS/defender", $output[$c++]);
    tmpl_set($template, "/STATS/attacker", $output[$c++]);

  }
  
  echo tmpl_parse($template);
}

function nameCompare($a, $b){
  return strcmp($a->name, $b->name);
}

?>