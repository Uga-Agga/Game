<?
function &misc_getMenu(){

  $result[] = array('link' => "?modus=misc&amp;miscID=6", 'content' => "Rohstoffe");
  $result[] = array('link' => "?modus=misc&amp;miscID=1", 'content' => "Einheiten");
  $result[] = array('link' => "?modus=misc&amp;miscID=2", 'content' => "Verteidigungsanlagen");
  $result[] = array('link' => "?modus=misc&amp;miscID=5", 'content' => "Erweiterungen");
  $result[] = array('link' => "?modus=misc&amp;miscID=4", 'content' => "Wunder");
  $result[] = array('link' => "?modus=misc&amp;miscID=3", 'content' => "Traglasten");

  return $result;
}

function &misc_getContent(){

  global $params;

  $miscID = (isset($params->miscID)) ? $params->miscID : 1;
  switch ($miscID){
    case 1:
    default: $result = getUnitStats();
             break;

    case 2:  $result = getDefenseStats();
             break;
    case 3:  $result = getUnitsEncumbrance();
             break;
    case 4:  $result = getWondersStats();
             break;
    case 5:  $result = getBuildingsStats();
             break;
    case 6:  $result = getResourcesStats();
             break;
  }
  return $result;
}

function &getUnitStats(){

 global $unitTypeList;

  // get a copy of the unitTypeList
  $copy = $unitTypeList;
  // sort that copy by names
  usort($copy, "nameCompare");

  $i=0;
  $template = @tmpl_open("templates/unitstats.ihtml");
  foreach ($copy AS $value)
    if (!$value->nodocumentation){
      tmpl_iterate($template, 'UNIT');
      tmpl_set($template, 'UNIT',
               array('id'          => $value->unitID,
                     'name'        => $value->name,
                     'ranking'     => $value->ranking,
                     'attackRange' => $value->attackRange,
                     'attackAreal' => $value->attackAreal,
                     'attackRate'  => $value->attackRate,
                     'defenseRate' => $value->defenseRate,
                     'RDResist'    => $value->rangedDamageResistance,
                     'hitPoints'   => $value->hitPoints,
                     'warpoints'   => $value->warpoints,
                     'foodCost'    => $value->foodCost,
                     'wayCost'     => $value->wayCost,
                     'iterator'    => $i++ %2));
      if ($value->visible == 0){
        tmpl_iterate($template, 'UNIT/REMARK');
        tmpl_set($template, 'UNIT/REMARK/remark', "unsichtbar");
      }
    }
  return @tmpl_parse($template);
}

function &getDefenseStats(){

 global $defenseSystemTypeList;

  // get a copy of the defenseSystemTypeList
  $copy = $defenseSystemTypeList;
  // sort that copy by names
  usort($copy, "nameCompare");

  $i=0;
  $template = @tmpl_open("templates/defensestats.ihtml");

  foreach ($copy AS $value)
    if (!$value->nodocumentation){
      tmpl_iterate($template, 'DEFENSE');
      tmpl_set($template, 'DEFENSE',
               array('id'          => $value->defenseSystemID,
                     'name'        => $value->name,
                     'attackRange' => $value->attackRange,
                     'attackRate'  => $value->attackRate,
                     'warpoints'   => $value->warPoints,
                     'defenseRate' => $value->defenseRate,
                     'hitPoints'   => $value->hitPoints,
					 'remark' 	   => $value->remark,
                     'iterator'    => $i++ %2));
    }
  return tmpl_parse($template);
}

function &getUnitsEncumbrance(){

 global $resourceTypeList, $unitTypeList;

  // get a copy of the unitTypeList
  $copy = $unitTypeList;
  // sort that copy by unit names
  usort($copy, "nameCompare");

  $i=0;
  $template = @tmpl_open("templates/unitsencumbrance.ihtml");

  foreach ($resourceTypeList AS $resource){
    if (!$resource->nodocumentation) {
      tmpl_iterate($template, 'HEADER_RESOURCES');
      tmpl_set($template, 'HEADER_RESOURCES',
               array('name'        => $resource->name,
                     'dbFieldName' => $resource->dbFieldName));
    }
  }

  foreach ($copy AS $unit){
    if (!$unit->nodocumentation){

      $encumbrances = array();
      foreach ($resourceTypeList AS $resource){
        if (!$resource->nodocumentation)
          $encumbrances[] = array('value' => intval($unit->encumbranceList[$resource->resourceID]));
      }

      tmpl_iterate($template, 'UNIT');
      tmpl_set($template, 'UNIT',
               array('unitID'      => $unit->unitID,
                     'name'        => $unit->name,
                     'ENCUMBRANCE' => $encumbrances,
                     'iterator'    => $i++ %2));
    }
  }

  return tmpl_parse($template);
}

function &getWondersStats(){

 global $wonderTypeList, $cfg;

  require_once($cfg['cfgpath']."wonder.inc.php");

  $uaWonderTargetText = WonderTarget::getWonderTargets();

  // get a copy of the wonderTypeList
  $copy = $wonderTypeList;
  // sort that copy by names
  usort($copy, "nameCompare");

  $i = 0;
  $template = @tmpl_open("templates/wondersstats.ihtml");

  foreach ($copy AS $value)
    if (!$value->nodocumentation){
      tmpl_iterate($template, 'WONDERS');
      tmpl_set($template, 'WONDERS',
               array('id'            => $value->wonderID,
                     'name'          => $value->name,
                     'offensiveness' => lib_translate($value->offensiveness),
                     'chance'        => round(eval('return '.formula_parseBasic($value->chance).';'), 3),
                     'target'        => $uaWonderTargetText[$value->target],
					 'remark'		 => $value->remark,
                     'iterator'      => $i++ %2));
    }
  return tmpl_parse($template);
}

function &getBuildingsStats(){

 global $buildingTypeList, $cfg;

  // get a copy of the buildingTypeList
  $copy = $buildingTypeList;
  // sort that copy by names
  usort($copy, "nameCompare");

  $i=0;
  $template = @tmpl_open("templates/buildingstats.ihtml");

  foreach ($copy AS $value)
    if (!$value->nodocumentation){
      tmpl_iterate($template, 'BUILDINGS');
      tmpl_set($template, 'BUILDINGS',
               array('id'            => $value->buildingID,
                     'name'          => $value->name,
                     'points'        => $value->ratingValue,
                     'remark'		 => $value->remark,
                     'iterator'      => $i++ %2));
    }
  return tmpl_parse($template);
}

function &getResourcesStats(){

 global $resourceTypeList;

  // get a copy of the $resourceTypeList
  $copy = $resourceTypeList;
  // sort that copy by names
  usort($copy, "nameCompare");

  $i=0;
  $template = @tmpl_open("templates/resourcestats.ihtml");

  foreach ($copy AS $value)
    if (!$value->nodocumentation){
      tmpl_iterate($template, 'RESOURCES');
      tmpl_set($template, 'RESOURCES',
               array('id'            => $value->resourceID,
                     'name'          => $value->name,
					 'dbFieldName'	 => $value->dbFieldName,
					 'remark'		 => $value->remark,
                     'iterator'      => $i++ %2));
    }
  return tmpl_parse($template);
}
?>
