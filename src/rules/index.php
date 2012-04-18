<?php
/*
 * index.php -
 * Copyright (c) 2003  OGP-Team
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/***** include required files **************************************/
require_once('config.inc.php');
require_once('include/template.inc.php');
require_once('include/params.inc.php');
require_once('include/basic.inc.php');
require_once('include/parser.inc.php');
require_once('include/modules.inc.php');

/***** INITIALIZE GLOBALS ******************************************/

/***** GET TEMPLATE *************************************************/
$template = new template();

/***** GET MODUS ***************************************************/
$modus = request_var('modus', '');
if (!isset($module_cfg['modules'][$modus])) {
  $modus = $module_cfg['default_module'];
}

/***** LOAD ACTIVE MODULES *****************************************/
$active_modules = lib_getActiveModules();

/***** GET CONTENT *************************************************/
$modus_function = $modus . "_getContent";
if (function_exists($modus_function)) {
  $content = $modus_function();
} else {
  die('Unbekannter Modus');
}

/***** GET MENU *************************************************/
$menu = array();
foreach ($active_modules AS $module){
  $menu_function = $module['modus'] . "_getMenu";
  if (function_exists("$menu_function"))
    $menu = array_merge($menu, $menu_function());
}
$template->addVar('left_menu', $menu);

/***** GET SELECTORS ***********************************************/
$selectors = array();
foreach ($active_modules AS $module){
  $selector_function = $module['modus'] . "_getSelector";
  if (function_exists("$selector_function"))
    $selectors[] = array(
      'modus' => $module['modus'],
      'item' => $selector_function()
    );
}

if (sizeof($selectors)) {
  $template->addVar('selectors', $selectors);
}

/***** FILL TEMPLATE ***********************************************/
$template->addVars(array(
  'gfx' => GFX_PATH,
));

$template->render();

?>
