/*
 * uaconfig.h - ugaagga config php extension
 * Copyright (c) 2005  Elmar Ludwig, Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _UACONFIG_H_
#define _UACONFIG_H_

#include <php.h>
#include <php_ini.h>
#include <SAPI.h>
#include <ext/standard/info.h>

extern zend_module_entry uaconfig_module_entry;
#define phpext_uaconfig_ptr &uaconfig_module_entry

#ifdef PHP_WIN32
#define PHP_UACONFIG_API __declspec(dllexport)
#else
#define PHP_UACONFIG_API
#endif

PHP_MINIT_FUNCTION(uaconfig);
PHP_MINFO_FUNCTION(uaconfig);
PHP_RINIT_FUNCTION(uaconfig);

#ifdef ZTS
#include "TSRM.h"
#endif

ZEND_BEGIN_MODULE_GLOBALS(uaconfig)
  int locale_id;
ZEND_END_MODULE_GLOBALS(uaconfig)

#ifdef ZTS
#define UACONFIG_G(v) TSRMG(uaconfig_globals_id, zend_uaconfig_globals *, v)
#else
#define UACONFIG_G(v) (uaconfig_globals.v)
#endif

static PHP_FUNCTION(ua_get_locale);
static PHP_FUNCTION(ua_set_locale);

static PHP_FUNCTION(ua_get_length);

static PHP_FUNCTION(ua_object_name);
static PHP_FUNCTION(ua_object_description);
static PHP_FUNCTION(ua_object_dbfieldname);
static PHP_FUNCTION(ua_object_maxlevel);
static PHP_FUNCTION(ua_object_nodocumentation);

static PHP_FUNCTION(ua_expansion_position);
static PHP_FUNCTION(ua_expansion_ratingvalue);
static PHP_FUNCTION(ua_expansion_productiontimefunction);
static PHP_FUNCTION(ua_expansion_requirements);
static PHP_FUNCTION(ua_expansion_productioncost);

static PHP_FUNCTION(ua_resource_ratingvalue);
static PHP_FUNCTION(ua_resource_takeovervalue);
static PHP_FUNCTION(ua_resource_productionfunction);
static PHP_FUNCTION(ua_resource_safestorage);

static PHP_FUNCTION(ua_battle_attackrange);
static PHP_FUNCTION(ua_battle_attackareal);
static PHP_FUNCTION(ua_battle_attackrate);
static PHP_FUNCTION(ua_battle_defenserate);
static PHP_FUNCTION(ua_battle_hitpoints);
static PHP_FUNCTION(ua_battle_rangeddamageresistance);
static PHP_FUNCTION(ua_battle_antispychance);

static PHP_FUNCTION(ua_unit_visible);
static PHP_FUNCTION(ua_unit_foodcost);
static PHP_FUNCTION(ua_unit_waycost);
static PHP_FUNCTION(ua_unit_spyvalue);
static PHP_FUNCTION(ua_unit_spychance);
static PHP_FUNCTION(ua_unit_spyquality);
static PHP_FUNCTION(ua_unit_encumbrancelist);

static PHP_FUNCTION(ua_terrain_takeoverbycombat);
static PHP_FUNCTION(ua_terrain_barren);
static PHP_FUNCTION(ua_terrain_color);
static PHP_FUNCTION(ua_terrain_effects);

static PHP_FUNCTION(ua_region_startregion);
static PHP_FUNCTION(ua_region_takeoveractivatable);
static PHP_FUNCTION(ua_region_barren);
static PHP_FUNCTION(ua_region_effects);

static PHP_FUNCTION(ua_movement_action);
static PHP_FUNCTION(ua_movement_speed);
static PHP_FUNCTION(ua_movement_provisions);
static PHP_FUNCTION(ua_movement_conquering);
static PHP_FUNCTION(ua_movement_invisible);
static PHP_FUNCTION(ua_movement_requirements);

#endif /* _UACONFIG_H_ */
