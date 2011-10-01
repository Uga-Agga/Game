<?
global $cfg;
global $leaderDeterminationList, $governmentList;
require_once($cfg['cfgpath']."government.rules.php");

function &governments_getMenu(){

  $result[] = array('link' => "?modus=governments", 'content' => "Regierungen");
  return $result;
}

function &governments_getContent(){

  global $governmentList, $leaderDeterminationList;
  $i=0;
  $template = @tmpl_open("templates/governments.ihtml");

  foreach($leaderDeterminationList AS $leaderDeterminationData){
   	$leaderDeterminationData['iterator'] = $i++ % 2;
    tmpl_iterate($template, 'LEADERDETERMINATION');
    tmpl_set($template, 'LEADERDETERMINATION', $leaderDeterminationData);
  }
  $j=0;
  foreach($governmentList AS $governmentData){
  $governmentData['iterator'] = $j++ % 2;
    tmpl_iterate($template, 'GOVERNMENT');
    $governmentData['leaderDetermination'] = $leaderDeterminationList[$governmentData['leaderDeterminationID']]['name'];
    tmpl_set($template, 'GOVERNMENT', $governmentData);
  }
  return tmpl_parse($template);
}
?>
