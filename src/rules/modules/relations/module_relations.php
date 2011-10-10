<?php
global $cfg;
global $relationList;
require_once($cfg['cfgpath']."relation_list.php");

function relations_getMenu(){

  $result[] = array('link' => "?modus=relations", 'content' => "Beziehungen");
  return $result;
}

function relations_getContent(){

  global $relationList;

  $template = @tmpl_open("templates/relations.ihtml");
  $i=0;

  foreach($relationList AS $relationData){
    $relationData['iterator'] = $i++ % 2;
    tmpl_iterate($template, 'ROWS');
    $relationData['otherSideToName'] = ($relationData['otherSideTo'] && isset($relationList[$relationData['otherSideTo']]['name'])) ? $relationList[$relationData['otherSideTo']]['name'] : "";
    foreach($relationData['transitions'] AS $relationID => $v){
      $relationData['transitions'][$relationID]['name'] = $relationList[$relationID]['name'];
    }

    tmpl_set($template, 'ROWS/ROW', $relationData);
  }
  return tmpl_parse($template);
}
?>