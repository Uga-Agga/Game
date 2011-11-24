<?php
/*
 * tribeAdmin.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function doYouKnow_getContent() {
  global $db, $config;
  
  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'doYouKnow.ihtml');

  if (request_var('show', "") == "all") {
    $sql = $db->prepare("SELECT * FROM ". DO_YOU_KNOW_TABLE);
  } else {
    $sql = $db->prepare("SELECT * FROM ". DO_YOU_KNOW_TABLE ." ORDER BY RAND( ) LIMIT 0 , 1");
  }

  $sql->execute();
  while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
    tmpl_iterate($template, "ELEM");
    tmpl_set($template,array("ELEM/header" => $row['titel'], 
                           "ELEM/text" => str_replace("\n", "<br />", $row['content'])));
  }			   

  if (request_var('show', "") != "all")
    tmpl_iterate($template, "LINKLIST");


  return tmpl_parse($template);
}

?>