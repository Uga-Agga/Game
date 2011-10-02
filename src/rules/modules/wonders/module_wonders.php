<?
global $cfg;
require_once($cfg['cfgpath']."wonder.rules.php");
require_once($cfg['cfgpath']."wonder.inc.php");
init_wonders();

function &wonders_getSelector(){

  global $params, $wonderTypeList;

  $wonders = array();
  foreach ($wonderTypeList AS $key => $value)
    if (!$value->nodocumentation){
      $temp = array('value' => $value->wonderID, 'description'  => lib_shorten_html($value->name, 20));
      if (isset($params->wondersID) && $params->wondersID == $value->wonderID) $temp['selected'] = 'selected="selected"';
      $wonders[] = $temp;
    }
  usort($wonders, "descriptionCompare");
  return $wonders;
}

function &wonders_getContent(){
 global $params, $scienceTypeList, $resourceTypeList, $unitTypeList, $wonderTypeList;

  $id = $params->wondersID;

  if (!isset($wonderTypeList[$id]) || $wonderTypeList[$id]->nodocumentation){
    $wonder = $wonderTypeList[0];
  } else {
    $wonder = $wonderTypeList[$id];
  }

  $uaWonderTargetText = WonderTarget::getWonderTargets();

  $resourceCost = array();
  foreach ($wonder->resourceProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($resourceCost, array('dbFieldName' => $resourceTypeList[$key]->dbFieldName,
                                      'name'        => $resourceTypeList[$key]->name,
                                      'amount'      => formula_parseToReadable($value)));
    }
  }

  $unitCost = array();
  foreach ($wonder->unitProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      array_push($unitCost, array('dbFieldName' => $unitTypeList[$key]->dbFieldName,
                                  'name'        => $unitTypeList[$key]->name,
                                  'amount'      => formula_parseToReadable($value)));
    }
  }


  $template = @tmpl_open("templates/wonder.ihtml");


  tmpl_set($template, array('name'           => $wonder->name,
                            'offensiveness'  => lib_translate($wonder->offensiveness),
                            'description'    => $wonder->description,
                            'dbFieldName'    => $wonder->dbFieldName,
                            'chance'         => round(eval('return '.formula_parseBasic($wonder->chance).';'), 3),
                            'target'         => $uaWonderTargetText[$wonder->target],
                            'RESOURCECOST'   => $resourceCost,
                            'DEPENDENCIES'   => rules_checkDependencies($wonder)));

  if (sizeof($unitCost))
    tmpl_set($template, 'UNITCOSTS/UNITCOST', $unitCost);

  return tmpl_parse($template);
}
?>
