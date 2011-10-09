<?php
/*
 * Module_News.php - 
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once("Module_Base.lib.php");
require_once("Menu.lib.php");
require_once("Menu_Item.lib.php");

class Module_News extends Module_Base {

  function Module_News(){
    $this->modi[] = 'news_create';
    $this->modi[] = 'news_show';
  }

  function getContent($modus){

    global $db_login, $params, $cfg;

    $content = "";
    switch ($modus){
      case 'news_create':
        $template = tmpl_open("modules/Module_News/templates/create.ihtml");



        // Form Submitted
        if (isset($params->creator)){

          $sql = "INSERT INTO `Portal_news` (`newsID`, `category`, `archive`, ".
                 "`author`, `date`, `title`, `content`) ".
                 "VALUES (0, '" . ($params->newsCategory) .
                 "', '" . "0" .
                 "', '" . ($params->newsAuthor) .
                 "', '" . ($params->newsDate) .
                 "', '" . ($params->newsTitle) .
                 "', '" . nl2br(lib_bb_code($params->newsContent)) . "')";

          if (!$db_login->query($sql)) die("Datenbankfehler beim Eintragen der News!");
          tmpl_set($template, "MESSAGE/message", "News eingetragen!");
        }
        // just show it
        else {
          foreach ($cfg['news']['categories'] as $category){
            tmpl_iterate($template, '/FORM/CATEGORY');
            tmpl_set($template, '/FORM/CATEGORY', array('text' => $category, 'value' => $category));
          }
          tmpl_set($template, '/FORM/date', date("d-m-Y"));
        }



        $content = tmpl_parse($template);
        break;

      case 'news_show':

        $template = tmpl_open("modules/Module_News/templates/show.ihtml");

        $sql = "SELECT * FROM Portal_news ORDER BY newsID DESC";
        $result = $db_login->query($sql);
        if (!$result || $result->isEmpty()){
          return "Error while retrieving news!";
        }

        $news = array();
        while ($row = $result->nextRow()) $news[] = $row;

        tmpl_set($template, 'NEWS', $news);
        $content = tmpl_parse($template);
        break;
    }
    return $content;
  }

  function getMenu(){
    $menu = new Menu($this->getName());
    $menu->addItem(new Menu_Item("?modus=news_create", "create"));
    $menu->addItem(new Menu_Item("?modus=news_show",   "show"));
    return $menu->getMenu();
  }

  function getName(){
    return "News";
  }
}
?>