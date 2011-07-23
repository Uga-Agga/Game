<?php
{
  require_once("../game/include/game_rules.php");
  init_units();
  init_defenseSystems();

  $template = tmpl_open("simulator.ihtml");
  tmpl_set($template, "target", "response.php");

  // Show the effect fields

  $effectData = array(
    "Fernangriffschaden" => "range",
    "Geb&auml;udeschaden" => "areal",
    "Nahkampfschaden" => "melee",
    "Widerstand" => "defense",
    "Gr&ouml;&szlig;en" => "size"
    );
  
  $effect = 0;
  
  foreach($effectData AS $text => $name) {
    tmpl_iterate($template, '/EFFECT');
    tmpl_set($template, '/EFFECT/alternate', 
	     ($count++ % 2 ? "alternate" : ""));
    tmpl_set($template, '/EFFECT/text', $text."faktor");
    tmpl_set($template, '/EFFECT/name', $effect++);
  }
  
  usort($unitTypeList, "nameCompare");

  // Show the unit table
  foreach($unitTypeList AS $unit) {
    if(!$unit->nodocumentation){    
      tmpl_iterate($template, '/UNIT');
      tmpl_set($template, 'UNIT', array(
        'name'      => $unit->name,
        'unitID'    => $unit->unitID));
    }
  }

  usort($defenseSystemTypeList, "nameCompare");

  // Show the defenseSystem table
  foreach($defenseSystemTypeList AS $defenseSystem) {
    if(!$defenseSystem->nodocumentation){    
    
      tmpl_iterate($template, '/DEFENSESYSTEM');
      tmpl_set($template, 'DEFENSESYSTEM', array(
        'name'            => $defenseSystem->name,
        'defenseSystemID' => $defenseSystem->defenseSystemID));
    }  
  }
  
  echo tmpl_parse($template);
}

function nameCompare($a, $b){
  return strcmp($a->name, $b->name);
}

?>