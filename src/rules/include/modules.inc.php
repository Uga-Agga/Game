<?php
/*
 * modules.inc.php - configure the rules modules
 * Copyright (c) 2003  OGP-Team
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

$module_cfg['default_module'] = "misc";

$module_cfg['modules']['misc']['modus']        = "misc";
$module_cfg['modules']['misc']['custom_title'] = "Übersichten";
$module_cfg['modules']['misc']['active']       = 1;
$module_cfg['modules']['misc']['view']         = 1;
$module_cfg['modules']['misc']['showSelector'] = 1;
$module_cfg['modules']['misc']['weight']       = 1100;

$module_cfg['modules']['resources']['modus']        = "resources";
$module_cfg['modules']['resources']['custom_title'] = "Resourcen";
$module_cfg['modules']['resources']['active']       = 1;
$module_cfg['modules']['resources']['view']         = 1;
$module_cfg['modules']['resources']['showSelector'] = 1;
$module_cfg['modules']['resources']['weight']       = 1000;

$module_cfg['modules']['buildings']['modus']        = "buildings";
$module_cfg['modules']['buildings']['custom_title'] = "Erweiterungen";
$module_cfg['modules']['buildings']['active']       = 1;
$module_cfg['modules']['buildings']['view']         = 1;
$module_cfg['modules']['buildings']['showSelector'] = 1;
$module_cfg['modules']['buildings']['weight']       = 900;

$module_cfg['modules']['units']['modus']        = "units";
$module_cfg['modules']['units']['custom_title'] = "Einheiten";
$module_cfg['modules']['units']['active']       = 1;
$module_cfg['modules']['units']['view']         = 1;
$module_cfg['modules']['units']['showSelector'] = 1;
$module_cfg['modules']['units']['weight']       = 800;

$module_cfg['modules']['sciences']['modus']        = "sciences";
$module_cfg['modules']['sciences']['custom_title'] = "Entdeckungen";
$module_cfg['modules']['sciences']['active']       = 1;
$module_cfg['modules']['sciences']['view']         = 1;
$module_cfg['modules']['sciences']['showSelector'] = 1;
$module_cfg['modules']['sciences']['weight']       = 700;


$module_cfg['modules']['wonders']['modus']        = "wonders";
$module_cfg['modules']['wonders']['custom_title'] = "Wunder";
$module_cfg['modules']['wonders']['active']       = 1;
$module_cfg['modules']['wonders']['view']         = 1;
$module_cfg['modules']['wonders']['showSelector'] = 1;
$module_cfg['modules']['wonders']['weight']       = 600;

$module_cfg['modules']['defenses']['modus']        = "defenses";
$module_cfg['modules']['defenses']['custom_title'] = "Verteidigung";
$module_cfg['modules']['defenses']['active']       = 1;
$module_cfg['modules']['defenses']['view']         = 1;
$module_cfg['modules']['defenses']['showSelector'] = 1;
$module_cfg['modules']['defenses']['weight']       = 500;


$module_cfg['modules']['relations']['modus']        = "relations";
$module_cfg['modules']['relations']['custom_title'] = "Beziehungstypen";
$module_cfg['modules']['relations']['active']       = 1;
$module_cfg['modules']['relations']['view']         = 1;
$module_cfg['modules']['relations']['showSelector'] = 1;
$module_cfg['modules']['relations']['weight']       = 400;

$module_cfg['modules']['governments']['modus']        = "governments";
$module_cfg['modules']['governments']['custom_title'] = "Regierungen";
$module_cfg['modules']['governments']['active']       = 1;
$module_cfg['modules']['governments']['view']         = 1;
$module_cfg['modules']['governments']['showSelector'] = 1;
$module_cfg['modules']['governments']['weight']       = 300;

?>