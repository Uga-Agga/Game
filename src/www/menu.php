<?php
/*
 * menu.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

require_once("config.inc.php");

require_once("include/page.inc.php");
require_once("include/db.functions.php");
require_once("include/time.inc.php");
require_once("include/basic.lib.php");
require_once("include/config.inc.php");
require_once("include/MenuItem.class.php");

################################################################################

// init session and connect to database
page_start();

################################################################################

if ($params->POST->site == 'left' || $params->POST->site == 'right')
{
  $templateFile = ($params->POST->site == 'left') ? 'menuLeft.ihtml' : 'menuRight.ihtml';
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . $templateFile);

  $items = array();
  if ($params->POST->site == 'left')
  {
    $items[] = new InGameMenuItem(EASY_DIGEST,     _('Terminkalender'));
    $items[] = new InGameMenuItem(UNIT_MOVEMENT,   _('Bewegung'));
    $items[] = new InGameMenuItem(MESSAGES,        _('Nachrichten'));
    $items[] = new InGameMenuItem(ALL_CAVE_DETAIL, _('Alle Höhlen'));
    $items[] = new InGameMenuItem(RANKING,         _('Punktzahl'));
    $items[] = new InGameMenuItem(MAP,             _('Karte'));
    $items[] = new InGameMenuItem(WONDER,          _('Wunder'));
    $items[] = new InGameMenuItem(TAKEOVER,        _('Missionieren'));

/*  $this->items[] = new InGameMenuItem(UNIT_BUILDER, _('Einheiten'));
    $this->items[] = new InGameMenuItem(EXTERNAL_BUILDER, _('Verteidigung'));
    $this->items[] = new InGameMenuItem(IMPROVEMENT_DETAIL, _('Erweiterungen'));
    $this->items[] = new InGameMenuItem(SCIENCE, _('Entdeckungen'));
    $this->items[] = new InGameMenuItem(MERCHANT, _('Händler'));
    $this->items[] = new OffGameMenuItem(FORUM_PATH, 'forum', _('Zum Forum'));
    $this->items[] = new InGameMenuItem(SUGGESTIONS, _('Vorschläge'), 'Index');
    $this->items[] = new InGameMenuItem(LOGOUT, _('Logout'));*/
  }

  foreach ($items as $item)
    $data['ITEM_LEFT'][] = $item->getTmplData();

  // set data
  tmpl_set($template, $data);

  $output = tmpl_parse($template);
}
else
{
  // create model
  $model = new MenuModel();

  // create view
  $view = new MenuView();

  // collect data
  $data = array();

  // time
  $data['TIME'] = $model->getTime();

  // cave selector
  $data['SELECT'] = $model->getSelector();

  // fill menu icons
  $icons = $model->getIcons();
  foreach ($icons as $icon)
    $data['ICON'][] = $icon->getTmplData();

  // fill menu items
  $items = $model->getItems();
  foreach ($items as $item)
    $data['ITEM'][] = $item->getTmplData();

  // set data
  $view->setTmplData($data);

  // get view's output
  $output = $view->toString();
}
// replace gfx-path and output result
$gfx = $params->SESSION->nogfx ? DEFAULT_GFX_PATH : $params->SESSION->player->gfxpath;
echo str_replace ('%gfx%', $gfx, $output);

################################################################################

// close page
page_end();

################################################################################

/** This class is currently just a placeholder till I find my enlightenment..
 */

class MenuModel {

  var $time;
  var $timeContext;
  var $selector;
  var $icons;
  var $items;

  function MenuModel(){

    // prepare time
    $this->initTime();

    // prepare time
    $this->initSelector();

    // prepare menu icons
    $this->initIcons();

    // prepare menu
    $this->initItems();
  }

  function initTime(){
    // get ua_time
    $this->time = getUgaAggaTime(time());
    $this->time['monthName'] = getMonthName($this->time['month']);
  }

  function initSelector(){
    global $params;

    // get caveID
    $caveID = $params->SESSION->caveID;

    // get player's caves
    $caves = getCaves($params->SESSION->player->playerID);

    // prepare cave selector
    $this->selector = array();
    foreach($caves as $key => $cave)
      $this->selector[] = array('value'     => $key,
                                'selected'  => $caveID == $key ? "selected" : "",
                                'SELECTION' => array('iterate'  => ''),
                                'text'      => lib_shorten_html($cave['name'], 17));
  }

  function initIcons(){
    $this->icons = array();
    $this->icons[] = new InGameMenuItem(CAVE_DETAIL, _('Diese Höhle'));
    $this->icons[] = new InGameMenuItem(ARTEFACT_LIST, _('Artefaktliste'));
    $this->icons[] = new InGameMenuItem(TRIBE, _('Mein Stamm'));
    $this->icons[] = new InGameMenuItem(EFFECTWONDER_DETAIL, _('Aktive Effekte und Wunder'));
    $this->icons[] = new InGameMenuItem(WEATHER_REPORT, _('Wetterbericht'));
    $this->icons[] = new InGameMenuItem(USER_PROFILE, _('Profil'));
    $this->icons[] = new InGameMenuItem(QUESTIONNAIRE, _('Fragebogen'));
    $this->icons[] = new InGameMenuItem(CONTACTS, _('Adressbuch'));
    $this->icons[] = new InGameMenuItem(CAVE_BOOKMARKS, _('Höhlenliste'), 'Show');
    $this->icons[] = new InGameMenuItem(DONATIONS, _('Spenden'), 'Index');
    $this->icons[] = new OffGameMenuItem(HELP_PATH, 'help', _('Hilfe'));
    $this->icons[] = new InGameMenuItem(DYK, _('Infos Rund um Uga-Agga'));
    $this->icons[] = new InGameMenuItem(NEWS, _('News'));
    $this->icons[] = new OffGameMenuItem(TOOLS_PATH, 'Tools', _('Uga-Agga Tools'));
    $this->icons[] = new InGameMenuItem(STATISTIC, _('Statistik'));
  }

  function initItems(){
    $this->items = array();
    $this->items[] = new InGameMenuItem(EASY_DIGEST, _('Terminkalender'));
    $this->items[] = new InGameMenuItem(ALL_CAVE_DETAIL, _('Alle Höhlen'));
    $this->items[] = new InGameMenuItem(MESSAGES, _('Nachrichten'));
    $this->items[] = new InGameMenuItem(MAP, _('Karte'));
    $this->items[] = new InGameMenuItem(UNIT_MOVEMENT, _('Bewegung'));
    $this->items[] = new InGameMenuItem(UNIT_BUILDER, _('Einheiten'));
    $this->items[] = new InGameMenuItem(EXTERNAL_BUILDER, _('Verteidigung'));
    $this->items[] = new InGameMenuItem(IMPROVEMENT_DETAIL, _('Erweiterungen'));
    $this->items[] = new InGameMenuItem(SCIENCE, _('Entdeckungen'));
    $this->items[] = new InGameMenuItem(WONDER, _('Wunder'));
    $this->items[] = new InGameMenuItem(MERCHANT, _('Händler'));
    $this->items[] = new InGameMenuItem(TAKEOVER, _('Missionieren'));
    $this->items[] = new InGameMenuItem(RANKING, _('Punktzahl'));
    $this->items[] = new OffGameMenuItem(FORUM_PATH, 'forum', _('Zum Forum'));
    $this->items[] = new InGameMenuItem(SUGGESTIONS, _('Vorschläge'), 'Index');
    $this->items[] = new InGameMenuItem(LOGOUT, _('Logout'));
  }

  function getTime(){
    return $this->time;
  }

  function getTimeContext(){
    return $this->timeContext;
  }

  function getSelector(){
    return $this->selector;
  }

  function getIcons(){
    return $this->icons;
  }

  function getItems(){
    return $this->items;
  }
}


################################################################################



/** This class is currently just a placeholder till I find my enlightenment..
 */

class MenuView {

  var $template;

  var $tmplData;

  function MenuView(){
    global $params;
    $this->template = tmpl_open($params->SESSION->player->getTemplatePath() .
                                'menu.ihtml');
  }

  function setTmplData($data){
    tmpl_set($this->template, $data);
  }

  function toString(){
    return tmpl_parse($this->template);
  }
}

?>