<?php
/*
 * weather.inc.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function weather_getReport() {
  global $weatherTypeList;
  
  init_Weathers();

  $regions = getRegions();

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'weather_report.ihtml');
    $tmp = true;
  foreach ($regions as $region) {
    $tmp = $tmp && ($region[weather] == -1);
  }

  if ($tmp) { // no weather so far
    tmpl_set($template, 'CONTENT/NOWEATHER', array('iterate' => ''));
    return tmpl_parse($template);
  }

  $alt = 0;
  foreach ($regions as $region) {
    $alt = ($alt + 1) % 2; // alternates between 0 and 1. Couldn't use regionID, they don't need to be adjacent
  	tmpl_iterate($template, 'CONTENT/WEATHER/ROW');
  	tmpl_set($template, 'CONTENT/WEATHER/ROW',
  	         array('region'    => $region['name'],
  	               'weather'   => $weatherTypeList[$region['weather']]->name,
  	               'alternate' => $alt));
  }

  return tmpl_parse($template);
}

?>