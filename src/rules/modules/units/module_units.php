<?
function &units_getSelector(){

  global $params, $unitTypeList;

  $units = array();
  foreach ($unitTypeList AS $key => $value)
    if (!$value->nodocumentation){
      $temp = array('value' => $value->unitID, 'description'  => lib_shorten_html($value->name, 20));
      if (isset($params->unitsID) && $params->unitsID == $value->unitID) $temp['selected'] = 'selected="selected"';
      $units[] = $temp;      
    }
  usort($units, "descriptionCompare");
  return $units;
}

function &units_getContent(){

  global $params, $unitTypeList, $resourceTypeList;

  $id = $params->unitsID;

  if (!isset($unitTypeList[$id]) || $unitTypeList[$id]->nodocumentation){
    $unit = $unitTypeList[0];
  } else {
    $unit = $unitTypeList[$id];
  }

  $resourceCost = array();
  foreach ($unit->resourceProductionCost as $key => $value){
    if ($value != "" && $value != 0){
      array_push($resourceCost, array('dbFieldName' => $resourceTypeList[$key]->dbFieldName,
                                      'name'        => $resourceTypeList[$key]->name,
                                      'amount'      => $value));
    }
  }

  $unitCost = array();
  foreach ($unit->unitProductionCost as $key => $value){
    if ($value != "" && $value != 0){
      array_push($unitCost, array('dbFieldName' => $unitTypeList[$key]->dbFieldName,
                                  'name'        => $unitTypeList[$key]->name,
                                  'amount'      => formula_parseToReadable($value)));
    }
  }

  $externalCost = array();
  foreach ($unit->externalProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($externalCost, array('dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
                                      'name'        => $defenseSystemTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $buildingCost = array();
  foreach ($unit->buildingProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($externalCost, array('dbFieldName' => $buildingTypeList[$key]->dbFieldName,
                                      'name'        => $buildingTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $template = @tmpl_open("templates/unit.ihtml");

  tmpl_set($template, array('name'           => $unit->name,
                            'description'    => $unit->description,
                            'productiontime' => formula_parseToReadable($unit->productionTimeFunction),
                            'rangeAttack'    => $unit->attackRange,
                            'arealAttack'    => $unit->attackAreal,
                            'attackRate'     => $unit->attackRate,
                            'meleeDefenseRate'  => $unit->defenseRate,
                            'rangedDefenseRate' => $unit->rangedDamageResistance,
                            'size'           => $unit->hitPoints,
                            'warpoints'      => $unit->warpoints,
                            'antiSpyChance'  => $unit->antiSpyChance,
                            'spyChance'      => $unit->spyChance,
                            'spyValue'       => $unit->spyValue,
                            'spyQuality'     => $unit->spyQuality,
                            'dbFieldName'    => $unit->dbFieldName,
                            'movement_speed' => $unit->wayCost,
                            'movement_cost'  => $unit->foodCost,
       'normalDamageProbabilit' => 100 * (1-($unit->heavyDamageProbability + $unit->criticalDamageProbability)),
       'heavyDamageProbability' => 100 * ($unit->heavyDamageProbability),
       'criticalDamageProbability' => 100 * ($unit->criticalDamageProbability),
                            'RESOURCECOST'   => $resourceCost,
                            'DEPENDENCIES'   => rules_checkDependencies($unit)));

  if (sizeof($unitCost)){
    tmpl_iterate($template, 'MORECOSTS');
    tmpl_set($template, 'MORECOSTS/MORECOST', $unitCost);
  }

  if (sizeof($externalCost)){
    tmpl_iterate($template, 'MORECOSTS');
    tmpl_set($template, 'MORECOSTS/MORECOST', $externalCost);
  }
  if (sizeof($buildingCost)){
    tmpl_iterate($template, 'MORECOSTS');
    tmpl_set($template, 'MORECOSTS/MORECOST', $buildingCost);
  }

  return tmpl_parse($template);
}
?>
