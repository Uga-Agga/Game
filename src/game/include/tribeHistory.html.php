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
  global $template;

  // open template
  $template->setFile('tribeHistory.tmpl');
  $template->setShowRresource(false);

  $history = relation_getTribeHistory($tag);
  $template->addVar('tribe_history', $history);
  return;
  
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