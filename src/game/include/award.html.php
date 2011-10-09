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
  global $db, $template;

  // open template
  $template->setFile('awardDetail.tmpl');

  $msgs = array();

  $sql = $db->prepare("SELECT * FROM ". AWARDS_TABLE ." WHERE tag = :tag");
  $sql->bindValue('tag', $tag, PDO::PARAM_STR);
  if (!$sql->execute()) {
    $template->addVar('no_award', true);
    return;
  }

  $award = $sql->fetch(PDO::FETCH_ASSOC);
  $template->addVar('award', $award);
}

?>