<?php
/*
 * tribeRelationList.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribeRelationList_getContent($tribe) {
  global $db, $no_resource_flag, $config, $relationList;

  $no_resource_flag = 1;

  $template =
    tmpl_open($_SESSION['player']->getTemplatePath() . 'tribeRelationList.ihtml');

  tmpl_set($template, 'tribe', $tribe);

  $relations = relation_getRelationsForTribe($tribe);

  if (!$relations) page_dberror();

  foreach($relations['own'] AS $target => $relationData) {
    tmpl_iterate($template, 'ROWS');

    $data = array (
      "tribe"        => $relationData['tribe_target'],
      "relationTo"   => $relationList[$relationData['relationType']]['name'],
      "relationFrom" => ($relations['other'][$target] ?
         $relationList[$relations['other'][$target]['relationType']]['name'] :
         $relationList[0]['name']),
      "link"         => "main.php?modus=" . TRIBE_DETAIL .
                        "&tribe=" . $relationData['tribe_target']);
    $relations['other'][$target] = 0;         // mark this relation

    if ($i++ % 2)
      tmpl_set($template, 'ROWS/ROW_ALTERNATE', $data);
    else
      tmpl_set($template, 'ROWS/ROW',           $data);
  }

  foreach($relations['other'] AS $target => $relationData) {
    if (! $relationData) {      // already printed out this relation
      continue;
    }

    tmpl_iterate($template, 'ROWS');

    $data = array (
      "tribe"        => $relationData['tribe'],
      "relationFrom" => $relationList[$relationData['relationType']]['name'],
      "relationTo"   => $relationList[0]['name'],
      "link"         => "main.php?modus=" . TRIBE_DETAIL .
                        "&tribe=" . $relationData['tribe']);

    if ($i++ % 2)
      tmpl_set($template, 'ROWS/ROW_ALTERNATE', $data);
    else
      tmpl_set($template, 'ROWS/ROW',           $data);
  }

  return tmpl_parse($template);
}

?>