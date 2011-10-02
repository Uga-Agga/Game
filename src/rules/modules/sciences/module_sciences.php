<?
function &sciences_getSelector(){

  global $params, $scienceTypeList;

  $sciences = array();
  foreach ($scienceTypeList AS $key => $value)
    if (!$value->nodocumentation){
      $temp = array('value' => $value->scienceID, 'description'  => lib_shorten_html($value->name, 20));
      if (isset($params->sciencesID) && $params->sciencesID == $value->scienceID) $temp['selected'] = 'selected="selected"';
      $sciences[] = $temp;
    }
  usort($sciences, "descriptionCompare");
  return $sciences;
}

function &sciences_getContent(){
 global $params, $scienceTypeList, $resourceTypeList, $unitTypeList;

  $id = $params->sciencesID;

  if (!isset($scienceTypeList[$id]) || $scienceTypeList[$id]->nodocumentation){
    $science = $scienceTypeList[0];
  } else {
    $science = $scienceTypeList[$id];
  }

  $resourceCost = array();
  foreach ($science->resourceProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($resourceCost, array('dbFieldName' => $resourceTypeList[$key]->dbFieldName,
                                      'name'        => $resourceTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $unitCost = array();
  foreach ($science->unitProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($unitCost, array('dbFieldName' => $unitTypeList[$key]->dbFieldName,
                                  'name'        => $unitTypeList[$key]->name,
                                  'amount'      => formula_parseToReadable($value)));
    }
  }

  $externalCost = array();
  foreach ($science->externalProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($externalCost, array('dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
                                      'name'        => $defenseSystemTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $buildingCost = array();
  foreach ($science->buildingProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($externalCost, array('dbFieldName' => $buildingTypeList[$key]->dbFieldName,
                                      'name'        => $buildingTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $template = @tmpl_open("templates/science.ihtml");

  tmpl_set($template, array('name'           => $science->name,
                            'description'    => $science->description,
                            'maximum'        => formula_parseToReadable($science->maxLevel),
                            'productiontime' => formula_parseToReadable($science->productionTimeFunction),
                            'dbFieldName'    => $science->dbFieldName,
                            'RESOURCECOST'   => $resourceCost,
                            'DEPENDENCIES'   => rules_checkDependencies($science)));

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