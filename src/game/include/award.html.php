<?php
/*
 * award.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/** This function returns basic award details
 *
 *  @param tag       the current award's tag
 */
function award_getAwardDetail($tag) {
  global $db;

  $msgs = array();

  $sql = $db->prepare("SELECT * FROM ". AWARDS_TABLE ." WHERE tag = :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);

  if (($sql->rowCountSelect() == 0) || !$sql->execute()) {
    $msgs[] = sprintf(_('Dieser Orden existiert nicht: "%s".'), $tag);
    $row    = array();
  } else {
    $row = $sql->fetch(PDO::FETCH_ASSOC);
  }

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'award_detail.ihtml');

  if (sizeof($msgs)) {
    foreach ($msgs AS $msg) {
      tmpl_iterate($template, "MESSAGE");
      tmpl_set($template, "MESSAGE/message", $msg);
    }
  }

  if (sizeof($row)){
    tmpl_set($template, 'AWARD', $row);
  }

  return tmpl_parse($template);
}

?>