<?php
/*
 * main.php -
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011-2012 David Unger
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
require_once("include/template.inc.php");
require_once("include/db.functions.php");
require_once("include/time.inc.php");
require_once("include/basic.lib.php");
require_once("include/vote.html.php");
require_once("modules/Messages/Messages.php");
require_once("include/formula_parser.inc.php");

date_default_timezone_set('Europe/Berlin'); // slange: added to fix warning in PHP5

page_start();

// session expired?
if (page_sessionExpired()) {
  header("Location: " . Config::GAME_END_URL . "?id=inaktiv");
  exit;
} else {
  $_SESSION['lastAction'] = time();
}

// session valid?
if (!page_sessionValidate()) {
  header("Location: " . Config::GAME_END_URL . "?id=wrongSessionID");
  exit;
}

// refresh user data
page_refreshUserData();

// load template
$template = new Template;

// get modus
$modus = page_getModus();

// get caves
$ownCaves = getCaves($_SESSION['player']->playerID);

// no caves left
if (!$ownCaves) {
  if (!in_array($modus, Config::$noCaveModusInclude)) {
    $modus = NO_CAVE_LEFT;
  }
} else {
  $caveID = Request::getVar('caveID', 0);

  // Keine neue Höhle ausgewählt.
  if ($caveID == 0) {
    // Bereits eine Höhle mal ausgewählt?
    if (isset($_SESSION['caveID']) && array_key_exists($_SESSION['caveID'], $ownCaves)) {
      $caveID = $_SESSION['caveID'];
    // erste Höhle nehmen
    } else {
      $temp = current($ownCaves);
      $caveID = $_SESSION['caveID'] = $temp['caveID'];
    }
  // Neue Höhle nehmen
  } else {
    // Höhle im eigenen besitz?
    if (array_key_exists($caveID, $ownCaves)) {
      $_SESSION['caveID'] = $caveID;
    // nein? erste Höhle nehmen!
    } else {
      $modus = NOT_MY_CAVE;

      $temp = current($ownCaves);
      $caveID = $_SESSION['caveID'] = $temp['caveID'];
    }
  }
}

// include required files
if (isset($require_files[$modus]) && is_array($require_files[$modus])) {
  foreach($require_files[$modus] as $file) {
    require_once('include/' . $file);
  }
}

// log request
page_logRequest($modus, $caveID);

// log ore
page_ore();

################################################################################

$requestKeys = array();
///////////////////////////////////////////////////////////////////////////////
$showads = false;
switch ($modus) {

  /////////////////////////////////////////////////////////////////////////////
  // UEBERSICHTEN                                                            //
  /////////////////////////////////////////////////////////////////////////////

  case NO_CAVE_LEFT:
    $template->throwError("Leider besitzen sie keine Höhle mehr.");
    break;

  case NOT_MY_CAVE:
    $template->throwError("Diese Höhle gehörte nicht ihnen!");
    break;

  case CAVE_DETAIL:
    getCaveDetailsContent($ownCaves[$caveID]);
    break;

  case ALL_CAVE_DETAIL:
    getAllCavesDetailsContent($ownCaves);
    break;

  case EASY_DIGEST:
    digest_getDigest($ownCaves);
    break;

  case EVENT_REPORTS:
    list($pagetitle, $content) = eventReports_main($caveID, $ownCaves);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // ARTEFAKTE                                                               //
  /////////////////////////////////////////////////////////////////////////////

  case ARTEFACT_DETAIL:
    artefact_getDetail($caveID, $ownCaves);
    $requestKeys = array('artefactID');
    break;
  case ARTEFACT_LIST:
    artefact_getList($caveID, $ownCaves);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // NACHRICHTEN                                                             //
  /////////////////////////////////////////////////////////////////////////////

  case MESSAGES_LIST:
    $deletebox = Request::getVar('deletebox', array('' => ''));
    $box = Request::getVar('box', 1);
    $box = (!empty($box)) ? $box : 1;

    messages_getMessages($caveID, $deletebox, $box);
    $requestKeys = array('box', 'messageClass');
    break;

  case MESSAGE_READ:
    $messageID = Request::getVar('messageID', 0);
    $box = Request::getVar('box', 1);

    messages_showMessage($caveID, $messageID, $box);
    $requestKeys = array('messageID', 'box', 'filter');
    break;

  case MESSAGE_NEW:
    messages_newMessage($caveID);
    break;

  case NEW_MESSAGE_RESPONSE:
    messages_sendMessage($caveID);
    break;

  case CONTACTS_BOOKMARKS:
    list($pagetitle, $content) = contactsbookmarks_main($caveID, $ownCaves);
    break;

  case CAVE_BOOKMARKS:
    list($pagetitle, $content) = cavebookmarks_main($caveID, $ownCaves);
    break;

  case DONATIONS:
    list($pagetitle, $content) = donations_main($caveID, $ownCaves);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // KARTEN                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case MAP:
    getCaveMapContent($caveID, $ownCaves);
    break;

  case MAP_REGION:
    getCaveMapRegionContent($caveID, $ownCaves);
    break;

  case MAP_DETAIL:
    $targetCaveID = Request::getVar('targetCaveID', 0);
    $method = Request::getVar('method', '');
    
    getCaveReport($caveID, $ownCaves, $targetCaveID, $method);
    $requestKeys = array('targetCaveID');
    break;

  /////////////////////////////////////////////////////////////////////////////
  // ERWEITERUNGEN                                                           //
  /////////////////////////////////////////////////////////////////////////////

  case IMPROVEMENT_BUILDER:
    improvement_getImprovementDetail($caveID, $ownCaves[$caveID]);
    break;

  case IMPROVEMENT_DETAIL:
    $buildingID = Request::getVar('buildingID', 0);
    $method = Request::getVar('method', '');

    improvement_getBuildingDetails($buildingID, $ownCaves[$caveID], $method);
    $requestKeys = array('buildingID');
    break;

  /////////////////////////////////////////////////////////////////////////////
  // WONDERS                                                                 //
  /////////////////////////////////////////////////////////////////////////////

  case WONDER:
    wonder_getWonderContent($caveID, $ownCaves[$caveID]);
    break;

  case WONDER_DETAIL:
    $wonderID = Request::getVar('wonderID', 0);
    $method = Request::getVar('method', '');

    wonder_getWonderDetailContent($wonderID, $ownCaves[$caveID], $method);
    $requestKeys = array('wonderID');
    break;

  /////////////////////////////////////////////////////////////////////////////
  // VERTEIDIGUNGSANLAGEN                                                    //
  /////////////////////////////////////////////////////////////////////////////

  case DEFENSE_BUILDER:
    defense_builder($caveID, $ownCaves[$caveID]);
    break;

  case DEFENSE_DETAIL:
    $defenseID = Request::getVar('defenseID', 0);
    $method = Request::getVar('method', '');

    defense_showProperties($defenseID, $ownCaves[$caveID], $method);
    $requestKeys = array('defense_id');
    break;

  /////////////////////////////////////////////////////////////////////////////
  // WISSENSCHAFT                                                            //
  /////////////////////////////////////////////////////////////////////////////

  case SCIENCE_BUILDER:
    science_getScienceDetail($caveID, $ownCaves[$caveID]);
    break;

  case SCIENCE_DETAIL:
    $scienceID = Request::getVar('scienceID', 0);
    $method = Request::getVar('method', '');

    science_getScienceDetails($scienceID, $ownCaves[$caveID], $method);
    $requestKeys = array('scienceID');
    break;

  /////////////////////////////////////////////////////////////////////////////
  // EINHEITEN                                                               //
  /////////////////////////////////////////////////////////////////////////////

  case UNIT_BUILDER:
    unit_getUnitDetail($caveID, $ownCaves[$caveID]);
    break;


  case UNIT_DETAIL:
    $unitID = Request::getVar('unitID', 0);
    $method = Request::getVar('method', '');

    unit_getUnitDetails($unitID, $ownCaves[$caveID], $method);
    $requestKeys = array('unitID');
    break;

  case UNIT_MOVEMENT:
    unit_Movement($caveID, $ownCaves);
    $requestKeys = array('targetXCoord', 'targetYCoord', 'targetCaveName');
    break;

  /////////////////////////////////////////////////////////////////////////////
  // MISSIONIEREN                                                            //
  /////////////////////////////////////////////////////////////////////////////

  case TAKEOVER:
    takeover_main($caveID, $ownCaves);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // PUNKTZAHLEN                                                             //
  /////////////////////////////////////////////////////////////////////////////

  case RANKING_PLAYER:
    $offset = Request::getVar('offset', '');
    $offset = ranking_checkOffset($_SESSION['player']->playerID, $offset);

    ranking_getContent($caveID, $offset);
    $requestKeys = array('offset');
    break;

  case RANKING_TRIBE:
    $offset = Request::getVar('offset', '');
    $offset  = rankingTribe_checkOffset($offset);

    rankingTribe_getContent($caveID, $offset);
    $requestKeys = array('offset');
    break;

  /////////////////////////////////////////////////////////////////////////////
  // TRIBES                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case TRIBE:
    tribe_getContent($_SESSION['player']->playerID, $_SESSION['player']->tribe);
    break;

  case TRIBE_ADMIN:
    tribeAdmin_getContent($_SESSION['player']->playerID, $_SESSION['player']->tribe);
    break;

  case TRIBE_RELATION_LIST:
    $tag = Request::getVar('tag', '');

    tribeRelationList_getContent($tag);
    $requestKeys = array('tag');
    break;

  case TRIBE_HISTORY:
    $tag = Request::getVar('tag', '');

    tribeHistory_getContent($tag);
    $requestKeys = array('tag');
    break;

  case TRIBE_DELETE:
    $confirm = Request::isPost('confirm');

    tribeDelete_getContent($_SESSION['player']->playerID, $_SESSION['player']->tribe, $confirm);
    break;

  case TRIBE_CHOOSE_LEADER:
    tribeChooseLeader_getContent($_SESSION['player']->playerID, $_SESSION['player']->tribe);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // FRAGEBÖGEN                                                              //
  /////////////////////////////////////////////////////////////////////////////

  case QUESTIONNAIRE:
    questionnaire_getQuestionnaire($caveID, $ownCaves);
    break;

  case QUESTIONNAIRE_PRESENTS:
    questionnaire_presents($caveID, $ownCaves);
    break;

  case SUGGESTIONS:
    list($pagetitle, $content) = suggestions_main($caveID, $ownCaves);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // HELDEN                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case HERO_DETAIL:
    hero_getHeroDetail($caveID, $ownCaves);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // AWARDS                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case AWARD_DETAIL:
    $award = Request::getVar('award', '');

    award_getAwardDetail($award);
    $requestKeys = array('award');
    break;

  /////////////////////////////////////////////////////////////////////////////
  // DIVERSES                                                                //
  /////////////////////////////////////////////////////////////////////////////

  case NEWS:
    rssFeedNews_getContent();
    break;

  case STATISTIC:
    statistic_getContent();
    break;

  case EFFECTWONDER_DETAIL:
    effect_getEffectWonderDetailContent($caveID, $ownCaves[$caveID]);
    break;

  case WEATHER_REPORT:
    weather_getReport();
    break;

  case USER_PROFILE:
    profile_main();
    break;

  case DELETE_ACCOUNT:
    profile_deleteAccount($_SESSION['player']->playerID);
    break;

  case PLAYER_DETAIL:
    $playerID = Request::getVar('playerID', 0);

    player_getContent($caveID, $playerID);
    $requestKeys = array('detailID');
    break;

  case TRIBE_DETAIL:
    $tribeID = Request::getVar('tribeID', 0);

    tribe_getContent($caveID, $tribeID);
    $requestKeys = array('tribeID');
    break;

  case TRIBE_PLAYER_LIST:
    $tribeID = Request::getVar('tribeID', 0);

    tribePlayerList_getContent($caveID, $tribeID);
    $requestKeys = array('tribeID');
    break;

  case DYK:
    doYouKnow_getContent();
  break;

  case MERCHANT:
    merchant_getMechantDetail($_SESSION['player']->playerID, $caveID, $ownCaves[$caveID]);
  break;

  case LOGOUT:
    header("Location: finish.php?id=logout");
    exit;
    break;

  /////////////////////////////////////////////////////////////////////////////
  /////////////////////////////////////////////////////////////////////////////
  /////////////////////////////////////////////////////////////////////////////

  default:
    $template->throwError("Modus " . $modus . "ist nicht verfügbar. CaveID :" . $caveID);
    break;
}

// init tutorial
$tutorial = new Tutorial;
$tutorialFinish = $tutorial->checkFinish($ownCaves[$caveID]);
if (!$tutorial->noTutorial) {
  if ($tutorialFinish && Request::isPost('nextTutorial')) {
    $tutorial->setFinish($ownCaves[$caveID]);
    $tutorialFinish = $tutorial->checkFinish($ownCaves[$caveID]);
  }

  $template->addVars(array(
    'tutorial_show'        => true,
    'tutorial_name'        => $tutorial->name,
    'tutorial_description' => $tutorial->description,
    'tutorial_finish_msg'  => $tutorial->finishMsg,
    'tutorial_finish'      => $tutorialFinish,
    'tutorial_open'        => ($tutorialFinish || Request::isPost('nextTutorial')) ? true : false,
  ));
} else {
  $template->addVar('tutorial_show', false);
}

// prepare resource bar
$resources = array();
if ($template->getShowRresource() && isset($resourceTypeList)) {
  foreach ($resourceTypeList as $resource) {
    $amount = floor($ownCaves[$caveID][$resource->dbFieldName]);
    if (!$resource->nodocumentation || $amount > 0) {
      $delta = $ownCaves[$caveID][$resource->dbFieldName . "_delta"];
      if ($delta > 0) $delta = "+" . $delta;
      $resources['resources'][] = array(
        'dbFieldName'  => $resource->dbFieldName,
        'name'         => $resource->name,
        'amount'       => $amount,
        'delta'        => $delta,
        'safe_storage' => round(eval('return ' . formula_parseToPHP("{$resource->saveStorage};", '$ownCaves[$caveID]'))),
        'max_level'    => round(eval('return ' . formula_parseToPHP("{$resource->maxLevel};", '$ownCaves[$caveID]')))
      );
    }
  }

  $template->addVars($resources);
}

// prepare new mail
$newMessageCount = messages_main($caveID, $ownCaves);

// set time
$UgaAggaTime = getUgaAggaTime(time());
$UgaAggaTime['month_name'] = getMonthName($UgaAggaTime['month']);

// init weather
init_Weathers();
$regions = getRegions();
$region = $regions[$ownCaves[$caveID]['regionID']];

// init vote
vote_main();

// init date for countdown
$now = new DateTime(); 

$terrainEffects = array();
foreach ($terrainList[$ownCaves[$caveID]['terrain']]['effects'] as $id => $value) {
  $terrainEffects[] = $effectTypeList[$id]->name . ' ' . $value;
}

// get queryString
$requestString = createRequestString($requestKeys);

// fill it
$template->addVars(array(
  'showads'           => ($showads) ? true : false,
  'cave_id'           => $caveID,
  'cave_name'         => $ownCaves[$caveID]['name'],
  'cave_x_coord'      => $ownCaves[$caveID]['xCoord'],
  'cave_y_coord'      => $ownCaves[$caveID]['yCoord'],
  'cave_terrain'      => $ownCaves[$caveID]['terrain'],
  'cave_terrain_desc' => $terrainList[$ownCaves[$caveID]['terrain']]['name'] . ' (' . implode(' | ', $terrainEffects) . ')',
  'time'              => date("d.m.Y H:i:s"),
  'bottom'            => vote_main(),
  'new_mail_link'     => (!empty($newMessageCount)) ? '_new' : '',
  'rules_path'        => RULES_PATH,
  'help_path'         => HELP_PATH,
  'player_fame'       => $_SESSION['player']->fame,
  'weather_id'        => $weatherTypeList[$region['weather']]->weatherID,
  'weather_name'      => $weatherTypeList[$region['weather']]->name,
  'gfx'               => ($_SESSION['nogfx']) ? DEFAULT_GFX_PATH : $_SESSION['player']->gfxpath,
  'show_hero_link'    => ($ownCaves[$caveID][HERO_DB_FIELD] > 0) ? true : false,
  'countdown_time'    => $now->format("M j, Y H:i:s O"),
  'query_string'      => $requestString,

  'ua_time_hour'            => $UgaAggaTime['hour'],
  'ua_time_day'             => $UgaAggaTime['day'],
  'ua_time_month'           => $UgaAggaTime['month'],
  'ua_time_year'            => $UgaAggaTime['year'],
  'ua_time_time_month_name' => $UgaAggaTime['month_name'],

  'artefact_list_link'      => ARTEFACT_LIST,
  'artefact_detail_link'    => ARTEFACT_DETAIL,
  'award_detail_link'       => AWARD_DETAIL,
  'cave_bookmarks_link'     => CAVE_BOOKMARKS,
  'cave_detail_link'        => CAVE_DETAIL,
  'contact_bookmarks_link'  => CONTACTS_BOOKMARKS,
  'defense_link'            => DEFENSE_BUILDER,
  'defense_detail_link'     => DEFENSE_DETAIL,
  'delete_account_link'     => DELETE_ACCOUNT,
  'donations_link'          => DONATIONS,
  'easy_digest_link'        => EASY_DIGEST,
  'effectwonder_detail_link' => EFFECTWONDER_DETAIL,
  'hero_link'               => HERO_DETAIL,
  'improvement_link'        => IMPROVEMENT_BUILDER,
  'improvement_detail_link' => IMPROVEMENT_DETAIL,
  'map_link'                => MAP,
  'map_detail_link'         => MAP_DETAIL,
//  'map_region_link'         => MAP_REGION_LINK,
  'merchant_link'           => MERCHANT,
  'messages_list_link'      => MESSAGES_LIST,
  'messages_new_link'       => MESSAGE_NEW,
  'messages_read_link'      => MESSAGE_READ,
  'news_link'               => NEWS,
  'player_detail_link'      => PLAYER_DETAIL,
  'questionaire_present_link' => QUESTIONNAIRE_PRESENTS,
  'questionaire_link'       => QUESTIONNAIRE,
  'user_profile_link'       => USER_PROFILE,
  'ranking_player_link'     => RANKING_PLAYER,
  'ranking_tribe_link'      => RANKING_TRIBE,
  'science_link'            => SCIENCE_BUILDER,
  'science_detail_link'     => SCIENCE_DETAIL,
  'suggestions_link'        => SUGGESTIONS,
  'takeover_link'           => TAKEOVER,
  'tribe_link'              => TRIBE,
  'tribe_admin_link'        => TRIBE_ADMIN,
  'tribe_choose_leader_link' => TRIBE_CHOOSE_LEADER,
  'tribe_detail_link'       => TRIBE_DETAIL,
  'tribe_history_link'      => TRIBE_HISTORY,
  'tribe_relation_link'     => TRIBE_RELATION_LIST,
  'tribe_player_list_link'  => TRIBE_PLAYER_LIST,
  'unit_link'               => UNIT_BUILDER,
  'unit_detail_link'        => UNIT_DETAIL,
  'unit_movement_link'      => UNIT_MOVEMENT,
  'wonder_link'             => WONDER,
  'wonder_detail_link'      => WONDER_DETAIL,
));

$caves = array();
if (sizeof($ownCaves)) {
  $caves['navigateCave'] = array();
  foreach ($ownCaves as $Cave) {
    $caves['navigateCave'][] = array(
      'caveID'            => $Cave['caveID'],
      'name'              => $Cave['name'],
      'x_coord'           => $Cave['xCoord'],
      'y_coord'           => $Cave['yCoord'],
      'class'             => ($caveID == $Cave['caveID']) ? 'bold' : '',
      'secure_cave'       => ($Cave['secureCave']) ? 'secureCave' : 'unsecureCave',
      'starting_position' => ($Cave['starting_position']) ? $Cave['starting_position'] : '',
      'active_name'       => $ownCaves[$caveID]['name'],
      'active_x_coord'    => $ownCaves[$caveID]['xCoord'],
      'active_y_coord'    => $ownCaves[$caveID]['yCoord'],
      'active'            => ($Cave['caveID'] == $caveID) ? true : false,
    );
  }

  $template->addVars($caves);
}

$template->render();

/*
// prepare next and previous cave
$keys = array_keys($ownCaves);
$pos =  array_search($caveID, $keys);
$prev = isset($keys[$pos - 1]) ? $keys[$pos - 1] : $keys[count($keys)-1];
$next = isset($keys[$pos + 1]) ? $keys[$pos + 1] : $keys[0];

if (!is_null($prev))
  tmpl_set($template, '/PREVCAVE', array('id' => $prev, 'name' => $ownCaves[$prev]['name']));

if (!is_null($next))
  tmpl_set($template, '/NEXTCAVE', array('id' => $next, 'name' => $ownCaves[$next]['name']));
*/

// close page
page_end();

?>