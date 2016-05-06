<?php
/*
 * news.html.php -
 * Copyright (c) 2016 David Unger <david@edv-unger.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function news_getContent() {
  global $db, $template;

  $template->setFile('news.tmpl');
  $template->setShowResource(false);
  
  $sql = $db->prepare("SELECT *, DATE_FORMAT(time, '%d.%m.%y %H:%i') as time
                       FROM " . NEWS_TABLE . "
                       ORDER BY time ASC");
  if (!$sql->execute()) return;
  
  $news = array();
  while($row = $sql->fetch(PDO::FETCH_ASSOC)){
    $news[] = $row;
  }
  $sql->closeCursor();

  $template->addVar('news', $news);
}