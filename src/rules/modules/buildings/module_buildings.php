<?
function &buildings_getSelector(){

  global $params, $buildingTypeList;

  $buildings = array();
  foreach ($buildingTypeList AS $key => $value)
    if (!$value->nodocumentation){
      $temp = array('value' => $value->buildingID, 'description' => lib_shorten_html($value->name, 20));
      if (isset($params->buildingsID) && $params->buildingsID == $value->buildingID)
        $temp['selected'] = 'selected="selected"';
      $buildings[] = $temp;
    }
  usort($buildings, "descriptionCompare");
  return $buildings;
}

function &buildings_getContent(){

 global $params, $buildingTypeList, $resourceTypeList, $unitTypeList;

  $id = $params->buildingsID;

  if (!isset($buildingTypeList[$id]) || $buildingTypeList[$id]->nodocumentation){
    $building = $buildingTypeList[0];
  } else {
    $building = $buildingTypeList[$id];
  }

  $resourceCost = array();
  foreach ($building->resourceProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($resourceCost, array('dbFieldName' => $resourceTypeList[$key]->dbFieldName,
                                      'name'        => $resourceTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $unitCost = array();
  foreach ($building->unitProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($unitCost, array('dbFieldName' => $unitTypeList[$key]->dbFieldName,
                                  'name'        => $unitTypeList[$key]->name,
                                  'amount'      => formula_parseToReadable($value)));
    }
  }

  $externalCost = array();
  foreach ($building->externalProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($externalCost, array('dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
                                      'name'        => $defenseSystemTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $buildingCost = array();
  foreach ($building->buildingProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($externalCost, array('dbFieldName' => $buildingTypeList[$key]->dbFieldName,
                                      'name'        => $buildingTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $template = @tmpl_open("templates/building.ihtml");

  tmpl_set($template, array('name'           => $building->name,
                            'description'    => $building->description,
                            'maximum'        => formula_parseToReadable($building->maxLevel),
                            'productiontime' => formula_parseToReadable($building->productionTimeFunction),
                            'dbFieldName'    => $building->dbFieldName,
                            'RESOURCECOST'   => $resourceCost,
                            'DEPENDENCIES'   => rules_checkDependencies($building)));
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
