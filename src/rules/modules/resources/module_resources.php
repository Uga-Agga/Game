<?
function &resources_getSelector(){
  
  global $params, $resourceTypeList;
  
  $resources = array();
  foreach ($resourceTypeList AS $key => $value)
    if (!$value->nodocumentation){
      $temp = array('value'=> $value->resourceID, 'description' => lib_shorten_html($value->name, 20));
      if (isset($params->resourcesID) && $params->resourcesID == $value->resourceID) $temp['selected'] = 'selected="selected"';
      $resources[] = $temp;      
    }
  usort($resources, "descriptionCompare");
  return $resources;
}

function &resources_getContent(){

  global $params, $resourceTypeList;
  
  $id = $params->resourcesID;
  if (!isset($resourceTypeList[$id]) || $resourceTypeList[$id]->nodocumentation){
    $resource = $resourceTypeList[0];
  } else {
    $resource = $resourceTypeList[$id];
  }

  $template = @tmpl_open("templates/resource.ihtml");

  tmpl_set($template, array('name'         => $resource->name,
                            'description'  => $resource->description,
                            'production'   => formula_parseToReadable($resource->resProdFunction),
                            'max_storage'  => formula_parseToReadable($resource->maxLevel),
                            'dbFieldName'  => $resource->dbFieldName,
                            'DEPENDENCIES' => rules_checkDependencies($resource)));

  return tmpl_parse($template);
}
?>