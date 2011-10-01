<?
function &defenses_getSelector(){

  global $params, $defenseSystemTypeList;

  $defenseSystems = array();
  foreach ($defenseSystemTypeList AS $key => $value)
    if (!$value->nodocumentation){
      $temp = array('value' => $value->defenseSystemID, 'description' => lib_shorten_html($value->name, 20));
      if (isset($params->defensesID) && $params->defensesID == $value->defenseSystemID)
        $temp['selected'] = 'selected="selected"';
      $defenseSystems[] = $temp;
    }
  usort($defenseSystems, "descriptionCompare");
  return $defenseSystems;
}

function &defenses_getContent(){

  global $params, $defenseSystemTypeList, $resourceTypeList, $unitTypeList;

  $id = $params->defensesID;

  if (!isset($defenseSystemTypeList[$id]) || $defenseSystemTypeList[$id]->nodocumentation){
    $defenseSystem = $defenseSystemTypeList[0];
  } else {
    $defenseSystem = $defenseSystemTypeList[$id];
  }

  $resourceCost = array();
  foreach ($defenseSystem->resourceProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($resourceCost, array('dbFieldName' => $resourceTypeList[$key]->dbFieldName,
                                      'name'        => $resourceTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $unitCost = array();
  foreach ($defenseSystem->unitProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($unitCost, array('dbFieldName' => $unitTypeList[$key]->dbFieldName,
                                  'name'        => $unitTypeList[$key]->name,
                                  'amount'      => formula_parseToReadable($value)));
    }
  }

  $externalCost = array();
  foreach ($defenseSystem->externalProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($externalCost, array('dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
                                      'name'        => $defenseSystemTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $buildingCost = array();
  foreach ($defenseSystem->buildingProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($externalCost, array('dbFieldName' => $buildingTypeList[$key]->dbFieldName,
                                      'name'        => $buildingTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $template = @tmpl_open("templates/defenseSystem.ihtml");

  tmpl_set($template, array('name'           => $defenseSystem->name,
                            'description'    => $defenseSystem->description,
                            'maximum'        => formula_parseToReadable($defenseSystem->maxLevel),
                            'productiontime' => formula_parseToReadable($defenseSystem->productionTimeFunction),
                            'rangeAttack'    => $defenseSystem->attackRange,
                            'attackRate'     => $defenseSystem->attackRate,
                            'defenseRate'    => $defenseSystem->defenseRate,
                            'size'           => $defenseSystem->hitPoints,
                            'antiSpyChance'  => $defenseSystem->antiSpyChance,
                            'dbFieldName'    => $defenseSystem->dbFieldName,
                            'warpoints'      => $defenseSystem->warPoints,
                            'RESOURCECOST'   => $resourceCost,
                            'DEPENDENCIES'   => rules_checkDependencies($defenseSystem)));
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
