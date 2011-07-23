<?php
/*
 * tribeHistory.html.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribeHistory_getContent($tag) {
  global $no_resource_flag;

  $no_resource_flag = 1;

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'tribeHistory.ihtml');

  $history = relation_getTribeHistory($tag);

  $i = 0;
  foreach($history AS $key => $values) {
    tmpl_iterate($template, 'ROWS');

    if ($i++ % 2)
      tmpl_set($template, 'ROWS/ROW_ALTERNATE', $values);
    else
      tmpl_set($template, 'ROWS/ROW',           $values);
  }

  return tmpl_parse($template);
}

?>