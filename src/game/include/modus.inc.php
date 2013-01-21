<?php
/*
 * modus.inc.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

DEFINE('ARTEFACT_TABLE',                'Artefact');
DEFINE('ARTEFACT_CLASS_TABLE',          'Artefact_class');
DEFINE('ARTEFACT_MERGE_GENERAL_TABLE',  'Artefact_merge_general');
DEFINE('ARTEFACT_MERGE_SPECIAL_TABLE',  'Artefact_merge_special');
DEFINE('ARTEFACT_RITUALS_TABLE',        'Artefact_rituals');
DEFINE('AWARDS_TABLE',                  'Awards');
DEFINE('CAVE_TABLE',                    'Cave');
DEFINE('CAVE_BOOKMARKS_TABLE',          'CaveBookmarks');
DEFINE('CAVE_ORGINAL_NAME_TABLE',       'Cave_Orginalname');
DEFINE('CAVE_TAKEOVER_TABLE',           'Cave_takeover');
DEFINE('CHAT_QUEUE_TABLE',              'ChatQueue');
DEFINE('CHAT_ROOM_TABLE ',              'ChatRoom');
DEFINE('CHAT_USER_TABLE ',              'ChatUser');
DEFINE('CONTACTS_TABLE',                'Contacts');
DEFINE('DO_YOU_KNOW_TABLE',             'doYouKnow');
DEFINE('EVENT_ARTEFACT_TABLE',          'Event_artefact');
DEFINE('EVENT_DEFENSE_SYSTEM_TABLE',    'Event_defenseSystem');
DEFINE('EVENT_EXPANSION_TABLE',         'Event_expansion');
DEFINE('EVENT_MOVEMENT_TABLE',          'Event_movement');
DEFINE('EVENT_SCIENCE_TABLE',           'Event_science');
DEFINE('EVENT_TRADE_TABLE',             'Event_trade');
DEFINE('EVENT_TRADE_END_TABLE',         'Event_tradeEnd');
DEFINE('EVENT_UNIT_TABLE',              'Event_unit');
DEFINE('EVENT_WEATHER_TABLE',           'Event_weather');
DEFINE('EVENT_WEATHER_END_TABLE',       'Event_weatherEnd');
DEFINE('EVENT_WONDER_TABLE',            'Event_wonder');
DEFINE('EVENT_WONDER_END_TABLE',        'Event_wonderEnd');
DEFINE('HERO_TABLE',                    'Hero');
DEFINE('EVENT_HERO_TABLE',              'Event_hero');
DEFINE('HERO_RITUAL_TABLE',             'Hero_rituals');
DEFINE('HERO_MONSTER_TABLE',            'Hero_Monster');
DEFINE('HERO_TOURNAMENT_TABLE',         'Hero_tournament');
DEFINE('HERO_TREASURE_TABLE',           'Hero_treasure');
DEFINE('LOG_X_TABLE',                   'Log_');
DEFINE('LOG_0_TABLE',                   'Log_0');
DEFINE('LOG_1_TABLE',                   'Log_1');
DEFINE('LOG_2_TABLE',                   'Log_2');
DEFINE('LOG_3_TABLE',                   'Log_3');
DEFINE('LOG_4_TABLE',                   'Log_4');
DEFINE('LOG_5_TABLE',                   'Log_5');
DEFINE('LOG_6_TABLE',                   'Log_6');
DEFINE('MESSAGE_TABLE',                 'Message');
DEFINE('MONSTER_TABLE',                 'Monster');
DEFINE('OLD_TRIBES_TABLE',              'OldTribes');
DEFINE('PLAYER_TABLE',                  'Player');
DEFINE('PLAYER_HISTORY_TABLE',          'player_history');
DEFINE('QUESTIONNAIRE_ANSWERS_TABLE',   'Questionnaire_answers');
DEFINE('QUESTIONNAIRE_CHOISES_TABLE',   'Questionnaire_choices');
DEFINE('QUESTIONNAIRE_PRESENTS_TABLE',  'Questionnaire_presents');
DEFINE('QUESTIONNAIRE_QUESTIONS_TABLE', 'Questionnaire_questions');
DEFINE('RANKING_TABLE',                 'Ranking');
DEFINE('RANKING_TRIBE_TABLE',           'RankingTribe');
DEFINE('REGIONS_TABLE',                 'Regions');
DEFINE('RELATION_TABLE',                'Relation');
DEFINE('SESSION_TABLE',                 'Session');
DEFINE('START_VALUE_TABLE',             'StartValue');
DEFINE('STATISTIC_TABLE',               'Statistic');
DEFINE('STATISTIC_UNIT_TABLE',          'StatisticUnit');
DEFINE('SUGGESTIONS_TABLE',             'Suggestions');
DEFINE('TOURNAMENT_TABLE',              'Tournament');
DEFINE('TRADELOCK_TABLE',               'tradelock');
DEFINE('TREASURE_TABLE',                'Treasure');
DEFINE('TRIBE_TABLE',                   'Tribe');
DEFINE('TRIBE_ELECTION_TABLE',          'TribeElection');
DEFINE('TRIBE_HISTORY_TABLE',           'TribeHistory');
DEFINE('TRIBE_MESSAGE_TABLE',           'TribeMessage');
DEFINE('TRIBE_STORAGE_DONATIONS_TABLE', 'TribeStorageDonations');

DEFINE('LOGIN_TABLE',                   'Login');

DEFINE('ALL_CAVE_DETAIL',             'all_cave_detail');
DEFINE('ARTEFACT_DETAIL',             'artefact_detail');
DEFINE('ARTEFACT_LIST',               'artefact_list');
DEFINE('AWARD_DETAIL',                'award_detail');
DEFINE('CAVE_BOOKMARKS',              'CaveBookmarks');
DEFINE('CAVE_DETAIL',                 'cave_detail');
DEFINE('CONTACTS_BOOKMARKS',          'Contacts');
DEFINE('DEFENSE_BUILDER',             'defense_builder');
DEFINE('DEFENSE_DETAIL',              'defense_detail');
DEFINE('DELETE_ACCOUNT',              'delete_account');
DEFINE('DONATIONS',                   'donations');
DEFINE('EVENT_REPORTS',               'EventReports');
DEFINE('EASY_DIGEST',                 'easy_digest');
DEFINE('EFFECTWONDER_DETAIL',         'effectwonder_detail');
DEFINE('HERO_DETAIL',                 'hero_detail');
DEFINE('IMPROVEMENT_BUILDER',         'improvement_builder');
DEFINE('IMPROVEMENT_DETAIL',          'improvement_detail');
DEFINE('LOGOUT',                      'logout');
DEFINE('MAP',                         'map');
DEFINE('MAP_REGION',                  'map_region');
DEFINE('MAP_DETAIL',                  'map_detail');
DEFINE('MESSAGES_LIST',               'messages_list');
DEFINE('MESSAGE_READ',                'messages_read');
DEFINE('MESSAGE_NEW',                 'message_new');
DEFINE('NEW_MESSAGE_RESPONSE',        'new_message_response');
DEFINE('NO_CAVE_LEFT',                'no_cave_left');
DEFINE('NOT_MY_CAVE',                 'not_my_cave');
DEFINE('PLAYER_DETAIL',               'player_detail');
DEFINE('QUESTIONNAIRE',               'questionnaire');
DEFINE('QUESTIONNAIRE_PRESENTS',      'questionnaire_presents');
DEFINE('RANKING_PLAYER',              'ranking_player');
DEFINE('RANKING_TRIBE',               'ranking_tribe');
DEFINE('SCIENCE_BUILDER',             'science_builder');
DEFINE('SCIENCE_DETAIL',              'science_detail');
DEFINE('SUGGESTIONS',                 'suggestions');
DEFINE('TAKEOVER',                    'takeover');
DEFINE('TRIBE',                       'tribe');
DEFINE('TRIBE_DETAIL',                'tribe_detail');
DEFINE('UNIT_BUILDER',                'unit_builder');
DEFINE('UNIT_DETAIL',                 'unit_detail');
DEFINE('UNIT_MOVEMENT',               'unit_movement');
DEFINE('USER_PROFILE',                'user_profile');
DEFINE('VOTE'        ,                'vote');
DEFINE('WEATHER_REPORT',              'weather_report');
DEFINE('WONDER',                      'wonder');
DEFINE('WONDER_DETAIL',               'wonder_detail');
DEFINE('MERCHANT',                    'merchant');
DEFINE('NEWS',                        'news');
DEFINE('STATISTIC',                   'statistic');

$require_files = array();

$require_files[ALL_CAVE_DETAIL]             = array('cave.html.php', 'formula_parser.inc.php');
$require_files[ARTEFACT_DETAIL]             = array('artefact.html.php', 'artefact.inc.php');
$require_files[ARTEFACT_LIST]               = array('artefact.html.php', 'artefact.inc.php');
$require_files[AWARD_DETAIL]                = array('award.html.php');
$require_files[CAVE_BOOKMARKS]              = array('../modules/CaveBookmarks/CaveBookmarks.php');
$require_files[CAVE_DETAIL]                 = array('cave.html.php', 'tribes.inc.php', 'rules/relation.list.php', 'rules/hero.rules.php', 'hero.inc.php');
$require_files[CONTACTS_BOOKMARKS]          = array('../modules/Contacts/Contacts.php');
$require_files[DEFENSE_BUILDER]             = array('defense.html.php', 'defense.inc.php');
$require_files[DEFENSE_DETAIL]              = array('defenseDetail.html.php', 'defense.inc.php');
$require_files[DONATIONS]                   = array('donations.html.php');
$require_files[DELETE_ACCOUNT]              = array('delete.html.php');
$require_files[EASY_DIGEST]                 = array('artefact.inc.php', 'digest.html.php', 'digest.inc.php', 'movement.lib.php');
$require_files[EFFECTWONDER_DETAIL]         = array('rules/wonder.rules.php', 'effectWonderDetail.html.php', 'wonder.inc.php', 'rules/hero.rules.php', 'hero.inc.php');
$require_files[EVENT_REPORTS]               = array('../modules/EventReports/EventReports.php');
$require_files[HERO_DETAIL]                 = array('hero.html.php', 'rules/hero.rules.php', 'hero.inc.php');
$require_files[IMPROVEMENT_BUILDER]         = array('improvement.html.php', 'improvement.inc.php');
$require_files[IMPROVEMENT_DETAIL]          = array('improvementDetail.html.php', 'improvement.inc.php');
$require_files[LOGOUT]                      = array();
$require_files[MAP]                         = array('map.inc.php', 'map.html.php', 'tribes.inc.php');
$require_files[MAP_REGION]                  = array('map.inc.php', 'map.html.php', 'tribes.inc.php');
$require_files[MAP_DETAIL]                  = array('map.inc.php', 'map.html.php');
$require_files[MESSAGES_LIST]               = array('message.html.php', 'message.inc.php');
$require_files[MESSAGE_READ]                = array('message.html.php', 'message.inc.php');
$require_files[MESSAGE_NEW]                 = array('message.html.php', 'message.inc.php');
$require_files[NEW_MESSAGE_RESPONSE]        = array('message.html.php', 'message.inc.php');
$require_files[PLAYER_DETAIL]               = array('playerDetail.html.php', 'ranking.inc.php', 'messageParser.inc.php');
$require_files[QUESTIONNAIRE]               = array('questionnaire.html.php');
$require_files[QUESTIONNAIRE_PRESENTS]      = array('questionnaire.html.php', 'formula_parser.inc.php');
$require_files[RANKING_PLAYER]              = array('ranking.html.php', 'ranking.inc.php');
$require_files[RANKING_TRIBE]               = array('ranking.html.php', 'ranking.inc.php');
$require_files[SCIENCE_BUILDER]             = array('science.inc.php', 'science.html.php');
$require_files[SCIENCE_DETAIL]              = array('science.inc.php', 'scienceDetail.html.php');
$require_files[SUGGESTIONS]                 = array('suggestions.html.php');
$require_files[TAKEOVER]                    = array('formula_parser.inc.php', 'takeover.html.php', 'chat.inc.php');
$require_files[TRIBE]                       = array('tribe.html.php', 'tribes.inc.php', 'auth.inc.php', 'message.inc.php', 'rules/government.rules.php', 'rules/relation.list.php', 'rules/wonder.rules.php', 'wonder.inc.php', 'chat.inc.php');
$require_files[TRIBE_DETAIL]                = array('tribeDetail.html.php', 'tribes.inc.php', 'auth.inc.php', 'messageParser.inc.php', 'rules/relation.list.php');
$require_files[UNIT_BUILDER]                = array('unit.html.php', 'unit.inc.php');
$require_files[UNIT_DETAIL]                 = array('unitDetail.html.php', 'unit.inc.php');
$require_files[UNIT_MOVEMENT]               = array('unitMovement.html.php', 'unitMovement.inc.php', 'artefact.inc.php', 'digest.inc.php', 'movement.lib.php', 'tribes.inc.php', 'rules/relation.list.php', 'rules/hero.rules.php', 'hero.inc.php');
$require_files[USER_PROFILE]                = array('profile.html.php');
$require_files[VOTE]                        = array('vote.html.php');
$require_files[WEATHER_REPORT]              = array('weather.html.php', 'rules/wonder.rules.php', 'basic.lib.php');
$require_files[WONDER]                      = array('rules/wonder.rules.php', 'wonder.html.php', 'wonder.inc.php', 'message.inc.php', 'basic.lib.php');
$require_files[WONDER_DETAIL]               = array('rules/wonder.rules.php', 'wonderDetail.html.php', 'wonder.inc.php', 'rules/relation.list.php');
$require_files[MERCHANT]                    = array('formula_parser.inc.php', 'merchant.html.php', 'rules/trade.rules.php');
$require_files[NEWS]                        = array('rssFeedNews.html.php');
$require_files[STATISTIC]                   = array('statistic.html.php', 'rules/wonder.rules.php');

?>