<?php
/*
 * config.inc.php - config file for the rules module
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define('_VALID_UA', 1);

define('UA_GAME_DIR', '/srv/git/Game/src/game/include/');
define('GFX_PATH', 'http://gfx.uga-agga.de');

$includes   = array();
$includes[] = UA_GAME_DIR;
$includes[] = ini_get('include_path');

// don't forget to add current include_path
ini_set('include_path', implode(PATH_SEPARATOR, $includes));

define('DEBUG', true);
define('TEMPLATE_CACHE', false);
define('TEMPLATE_RELOAD', false);

?>