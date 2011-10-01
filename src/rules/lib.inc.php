<?
function descriptionCompare($a, $b){
  return strcmp($a['description'], $b['description']);
}

function nameCompare($a, $b){
  return strcmp($a->name, $b->name);
}

function lib_checkModus(){

  global $module_cfg, $params;

  if (isset($params->modus) && array_key_exists($params->modus, $module_cfg['modules']))
    $modus = $params->modus;

  // get default modus
  else
    $modus = $module_cfg['default_module'];

  return $modus;
}


function &lib_getActiveModules(){

  global $module_cfg;

  // filter active modules
  $modules = array_filter($module_cfg['modules'], create_function('$var', 'return $var["active"];'));

  // usort modules
  uasort($modules, create_function('$a, $b', 'if ($a["weight"] == $b["weight"]) return 0; return ($a["weight"] > $b["weight"]) ? -1 : 1;'));

  // require the modules
  array_walk($modules, create_function('$value, $key', 'require_once("./modules/$key/module_$key.php");'));

  return $modules;
}

function lib_shorten_html($string, $length){
  $temp = lib_unhtmlentities($string);
  if (strlen($temp) > $length)
    return htmlentities(substr($temp, 0, $length)) . "..";
  return $string;
}

function lib_unhtmlentities($string){
  static $trans_tbl;

  if (empty($trans_tbl)){
    $trans_tbl = get_html_translation_table (HTML_ENTITIES);
    $trans_tbl = array_flip ($trans_tbl);
  }
  return strtr ($string, $trans_tbl);
}

function lib_translate ($string){
  $glossar = array(
    'all'       => 'alle',
    'own'       => 'eigene',
    'other'     => 'fremde',
    'offensive' => 'offensiv',
    'defensive' => 'defensiv'
  );
  return $glossar["$string"];
}


?>