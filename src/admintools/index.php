<?php
/*
 * index.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

/***** include required files **************************************/
require_once("config.inc.php");
require_once("db.inc.php");
require_once("dbconfig.inc.php");
require_once("lib.inc.php");
require_once("modules_config.inc.php");
require_once("params.inc.php");

global $cfg, $db_cfg, $module_cfg;

/***** INITIALIZE GLOBALS ******************************************/
// get link to DB 'game'
$db_game = new DB($db_cfg['DB_GAME']['HOST'],
                  $db_cfg['DB_GAME']['USER'],
                  $db_cfg['DB_GAME']['PWD'],
                  $db_cfg['DB_GAME']['NAME']);
if (!$db_game) die("Wir sind derzeit nicht erreichbar.");

// get link to DB 'login'
$db_login = new DB($db_cfg['DB_LOGIN']['HOST'],
                   $db_cfg['DB_LOGIN']['USER'],
                   $db_cfg['DB_LOGIN']['PWD'],
                   $db_cfg['DB_LOGIN']['NAME']);
if (!$db_login) die("Wir sind derzeit nicht erreichbar.");

// get cleaned POST and GET parameters
$params = new Params();

/***** LOAD ACTIVE MODULES *****************************************/
$active_modules =& lib_getActiveModules();

/***** GET ACTIVE MODUS ********************************************/
$module = lib_checkModus($params->modus, $active_modules);

/***** GET CONTENT *************************************************/
$content = $module->getContent($params->modus);

/***** GET MENU *************************************************/
$menu = lib_getMenu($active_modules);

/***** FILL TEMPLATE ***********************************************/
$template = tmpl_open("templates/framework.ihtml");
tmpl_set($template, array('content'  => str_replace('%gfx%', $cfg['gfxpath'], $content),
                          'MENU'     => $menu,
                          'SELECTOR' => $selectors));

echo tmpl_parse($template);
?>
