/*
 * uaconfig.c - ugaagga config php extension
 * Copyright (c) 2005  Elmar Ludwig, Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "uaconfig.h"
#include "game_rules.h"

/* numeric object types */
#define UA_RESOURCE      0
#define UA_BUILDING      1
#define UA_SCIENCE       2
#define UA_DEFENSESYSTEM 3
#define UA_UNIT          4
#define UA_EFFECT        5
#define UA_TERRAIN       6
#define UA_REGION        7
#define UA_MOVEMENT      8

ZEND_DECLARE_MODULE_GLOBALS(uaconfig)

/* {{{ uaconfig_functions[] */
function_entry uaconfig_functions[] = {
  PHP_FE(ua_get_locale, NULL)
  PHP_FE(ua_set_locale, NULL)

  PHP_FE(ua_get_length, NULL)

  PHP_FE(ua_object_name, NULL)
  PHP_FE(ua_object_description, NULL)
  PHP_FE(ua_object_dbfieldname, NULL)
  PHP_FE(ua_object_maxlevel, NULL)
  PHP_FE(ua_object_nodocumentation, NULL)

  PHP_FE(ua_expansion_position, NULL)
  PHP_FE(ua_expansion_ratingvalue, NULL)
  PHP_FE(ua_expansion_productiontimefunction, NULL)
  PHP_FE(ua_expansion_requirements, NULL)
  PHP_FE(ua_expansion_productioncost, NULL)

  PHP_FE(ua_resource_ratingvalue, NULL)
  PHP_FE(ua_resource_takeovervalue, NULL)
  PHP_FE(ua_resource_productionfunction, NULL)
  PHP_FE(ua_resource_safestorage, NULL)

  PHP_FE(ua_battle_attackrange, NULL)
  PHP_FE(ua_battle_attackareal, NULL)
  PHP_FE(ua_battle_attackrate, NULL)
  PHP_FE(ua_battle_defenserate, NULL)
  PHP_FE(ua_battle_hitpoints, NULL)
  PHP_FE(ua_battle_rangeddamageresistance, NULL)
  PHP_FE(ua_battle_antispychance, NULL)

  PHP_FE(ua_unit_visible, NULL)
  PHP_FE(ua_unit_foodcost, NULL)
  PHP_FE(ua_unit_waycost, NULL)
  PHP_FE(ua_unit_spyvalue, NULL)
  PHP_FE(ua_unit_spychance, NULL)
  PHP_FE(ua_unit_spyquality, NULL)
  PHP_FE(ua_unit_encumbrancelist, NULL)

  PHP_FE(ua_terrain_takeoverbycombat, NULL)
  PHP_FE(ua_terrain_barren, NULL)
  PHP_FE(ua_terrain_color, NULL)
  PHP_FE(ua_terrain_effects, NULL)

  PHP_FE(ua_region_startregion, NULL)
  PHP_FE(ua_region_takeoveractivatable, NULL)
  PHP_FE(ua_region_barren, NULL)
  PHP_FE(ua_region_effects, NULL)

  PHP_FE(ua_movement_action, NULL)
  PHP_FE(ua_movement_speed, NULL)
  PHP_FE(ua_movement_provisions, NULL)
  PHP_FE(ua_movement_conquering, NULL)
  PHP_FE(ua_movement_invisible, NULL)
  PHP_FE(ua_movement_requirements, NULL)

  { NULL, NULL, NULL }
};
/* }}} */


/* {{{ uaconfig_module_entry
 */
zend_module_entry uaconfig_module_entry = {
  STANDARD_MODULE_HEADER,
  "Uga-Agga Config",
  uaconfig_functions,
  PHP_MINIT(uaconfig),
  NULL,
  PHP_RINIT(uaconfig),
  NULL,
  PHP_MINFO(uaconfig),
  "0.0.4",
  STANDARD_MODULE_PROPERTIES
};
/* }}} */

/* implement standard "stub" routine to introduce ourselves to Zend */
ZEND_GET_MODULE(uaconfig)


/* {{{ globals and ini entries */
static void php_uaconfig_init_globals(zend_uaconfig_globals *uaconfig_globals)
{
}
/* }}} */


/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION(uaconfig)
{

  ZEND_INIT_MODULE_GLOBALS(uaconfig, php_uaconfig_init_globals, NULL);

  REGISTER_LONG_CONSTANT("MAX_RESOURCE",      MAX_RESOURCE,      CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("MAX_BUILDING",      MAX_BUILDING,      CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("MAX_SCIENCE",       MAX_SCIENCE,       CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("MAX_DEFENSESYSTEM", MAX_DEFENSESYSTEM, CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("MAX_UNIT",          MAX_UNIT,          CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("MAX_EFFECT",        MAX_EFFECT,        CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("MAX_TERRAIN",       MAX_TERRAIN,       CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("MAX_REGION",        MAX_REGION,        CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("MAX_MOVEMENT",      MAX_MOVEMENT,      CONST_CS | CONST_PERSISTENT);

  REGISTER_LONG_CONSTANT("UA_RESOURCE",      UA_RESOURCE,      CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("UA_BUILDING",      UA_BUILDING,      CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("UA_SCIENCE",       UA_SCIENCE,       CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("UA_DEFENSESYSTEM", UA_DEFENSESYSTEM, CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("UA_UNIT",          UA_UNIT,          CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("UA_EFFECT",        UA_EFFECT,        CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("UA_TERRAIN",       UA_TERRAIN,       CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("UA_REGION",        UA_REGION,        CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("UA_MOVEMENT",      UA_MOVEMENT,      CONST_CS | CONST_PERSISTENT);

  REGISTER_LONG_CONSTANT("ID_RESOURCE_FUEL", ID_RESOURCE_FUEL, CONST_CS | CONST_PERSISTENT);
  REGISTER_STRING_CONSTANT("MOVEMENT_COST",  MOVEMENT_COST,    CONST_CS | CONST_PERSISTENT);
  REGISTER_STRING_CONSTANT("MOVEMENT_SPEED", MOVEMENT_SPEED,   CONST_CS | CONST_PERSISTENT);

  REGISTER_LONG_CONSTANT("TAKEOVER_MIN_VALUE",  TAKEOVER_MIN_VALUE,  CONST_CS | CONST_PERSISTENT);
  REGISTER_LONG_CONSTANT("TAKEOVER_MAX_POINTS", TAKEOVER_MAX_POINTS, CONST_CS | CONST_PERSISTENT);

  REGISTER_STRING_CONSTANT("EXPOSE_INVISIBLE",  EXPOSE_INVISIBLE,  CONST_CS | CONST_PERSISTENT);
  REGISTER_STRING_CONSTANT("WATCH_TOWER_RANGE", WATCH_TOWER_RANGE, CONST_CS | CONST_PERSISTENT);
  REGISTER_STRING_CONSTANT("WONDER_RESISTANCE", WONDER_RESISTANCE, CONST_CS | CONST_PERSISTENT);

  return SUCCESS;
}
/* }}} */


/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION(uaconfig)
{
  php_info_print_table_start();
  php_info_print_table_header(2, "Uga-Agga Config support", "enabled");
  php_info_print_table_row(2, "Engine version", uaconfig_module_entry.version);
  php_info_print_table_row(2, "WWW", "http://www.uga-agga.de/");
  php_info_print_table_end();
}
/* }}} */


/* {{{ PHP_MINFO_FUNCTION */
PHP_RINIT_FUNCTION(uaconfig)
{
    UACONFIG_G(locale_id) = 0;

    return SUCCESS;
}
/* }}} */


static const struct GameConfig {
  const struct GameObject **config;
  int length;
} config[] = {
  {resource_type,       MAX_RESOURCE},
  {building_type,       MAX_BUILDING},
  {science_type,        MAX_SCIENCE},
  {defense_system_type, MAX_DEFENSESYSTEM},
  {unit_type,           MAX_UNIT},
  {effect_type,         MAX_EFFECT},
  {terrain_type,        MAX_TERRAIN},
  {region_type,         MAX_REGION},
  {movement_type,       MAX_MOVEMENT}
};


static const struct GameObject *game_object(long type, long id) {

  if (type < 0 || type >= sizeof config / sizeof config[0])
    zend_error(E_ERROR, "illegal config type: %ld\n", type);

  if (id < 0 || id >= config[type].length)
    zend_error(E_ERROR, "illegal type id: %ld\n", id);

  return config[type].config[id];
}


/*
 * Get locale id of specified locale name.
 */
static int get_locale_id(const char *locale) {
  int index;

  if (locale)
    for (index = 0; index < MAX_LOCALE; ++index)
      if (strcmp(locale, language[index].locale) == 0)
        return index;

  /* unknown locale */
  return 0;
}


/* {{{ proto void ua_set_locale(string locale)
   */
static ZEND_FUNCTION(ua_set_locale) {

  const char *name;
  int len;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &name, &len) == FAILURE)
    return;
  UACONFIG_G(locale_id) = get_locale_id(name);
}


#define RETURN_STRING_1(str)  RETURN_STRING(str ? (char *) str : "", 1)


/* {{{ proto void ua_set_locale(string locale)
   */
static ZEND_FUNCTION(ua_get_locale) {
  RETURN_STRING_1(language[UACONFIG_G(locale_id)].locale);
}

static ZEND_FUNCTION(ua_get_length) {
  
  long type;
  
  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &type) == FAILURE)
    return;
    
  if (type < 0 || type >= sizeof config / sizeof config[0])
    zend_error(E_ERROR, "illegal config type: %ld\n", type);
    
  RETURN_LONG(config[type].length);
}

#define UA_FUNCTION(name, class, ret_type, field) \
  static ZEND_FUNCTION(name) { \
    long type, id; \
    class *object; \
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ll", &type, &id) == FAILURE) \
      return; \
    object = (class *) game_object(type, id); \
    RETURN_ ## ret_type(object->field); \
  }

UA_FUNCTION(ua_object_name, struct GameObject, STRING_1, name[UACONFIG_G(locale_id)])
UA_FUNCTION(ua_object_description, struct GameObject, STRING_1, description[UACONFIG_G(locale_id)])
UA_FUNCTION(ua_object_dbfieldname, struct GameObject, STRING_1, dbFieldName)
UA_FUNCTION(ua_object_maxlevel, struct GameObject, STRING_1, maxLevel)
UA_FUNCTION(ua_object_nodocumentation, struct GameObject, LONG, hidden)

UA_FUNCTION(ua_expansion_position, struct Expansion, LONG, position)
UA_FUNCTION(ua_expansion_ratingvalue, struct Expansion, LONG, ratingValue)
UA_FUNCTION(ua_expansion_productiontimefunction, struct Expansion, STRING_1, productionTime)

UA_FUNCTION(ua_resource_ratingvalue, struct Resource, DOUBLE, ratingValue)
UA_FUNCTION(ua_resource_takeovervalue, struct Resource, DOUBLE, takeoverValue)
UA_FUNCTION(ua_resource_productionfunction, struct Resource, STRING_1, production)
UA_FUNCTION(ua_resource_safestorage, struct Resource, STRING_1, safeStorage)

UA_FUNCTION(ua_battle_attackrange, struct BattleUnit, LONG, attackRange)
UA_FUNCTION(ua_battle_attackareal, struct BattleUnit, LONG, attackAreal)
UA_FUNCTION(ua_battle_attackrate, struct BattleUnit, LONG, attackRate)
UA_FUNCTION(ua_battle_defenserate, struct BattleUnit, LONG, defenseRate)
UA_FUNCTION(ua_battle_hitpoints, struct BattleUnit, LONG, hitPoints)
UA_FUNCTION(ua_battle_rangeddamageresistance, struct BattleUnit, LONG, rangedDamageResistance)
UA_FUNCTION(ua_battle_antispychance, struct BattleUnit, DOUBLE, antiSpyChance)

UA_FUNCTION(ua_unit_visible, struct Unit, LONG, visible)
UA_FUNCTION(ua_unit_foodcost, struct Unit, DOUBLE, foodCost)
UA_FUNCTION(ua_unit_waycost, struct Unit, DOUBLE, wayCost)
UA_FUNCTION(ua_unit_spyvalue, struct Unit, LONG, spyValue)
UA_FUNCTION(ua_unit_spychance, struct Unit, DOUBLE, spyChance)
UA_FUNCTION(ua_unit_spyquality, struct Unit, DOUBLE, spyQuality)

UA_FUNCTION(ua_terrain_takeoverbycombat, struct Terrain, LONG, takeoverByCombat)
UA_FUNCTION(ua_terrain_barren, struct Terrain, LONG, barren)

UA_FUNCTION(ua_region_startregion, struct Region, LONG, startRegion)
UA_FUNCTION(ua_region_takeoveractivatable, struct Region, LONG, takeoverActivatable)
UA_FUNCTION(ua_region_barren, struct Region, LONG, barren)

UA_FUNCTION(ua_movement_action, struct Movement, LONG, action)
UA_FUNCTION(ua_movement_speed, struct Movement, DOUBLE, speed)
UA_FUNCTION(ua_movement_provisions, struct Movement, DOUBLE, provisions)
UA_FUNCTION(ua_movement_conquering, struct Movement, LONG, conquering)
UA_FUNCTION(ua_movement_invisible, struct Movement, LONG, invisible)

static ZEND_FUNCTION(ua_expansion_requirements) {

  long  type, id;
  int   i;
  zval *obj;

  struct Expansion   *object;
  struct Requirement *rqmt;

  /* get type and id */
  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ll", &type, &id) == FAILURE)
    return;

  /* get object */
  object = (struct Expansion *) game_object(type, id);

  /* we'll return an array of objects */
  array_init(return_value);

  /* iterate the requirements */
  for (i = 0; i < object->num_requirements; ++i) {

    /* create a new object */
    MAKE_STD_ZVAL(obj);
    object_init(obj);

    rqmt = (struct Requirement *) &object->requirements[i];

    /* add type info */
    if (MEMBER_OF(rqmt->type, RESOURCE_CLASS))
      add_property_long(obj, "type", UA_RESOURCE);
    else if (MEMBER_OF(rqmt->type, BUILDING_CLASS))
      add_property_long(obj, "type", UA_BUILDING);
    else if (MEMBER_OF(rqmt->type, SCIENCE_CLASS))
      add_property_long(obj, "type", UA_SCIENCE);
    else if (MEMBER_OF(rqmt->type, DEFENSE_SYSTEM_CLASS))
      add_property_long(obj, "type", UA_DEFENSESYSTEM);
    else if (MEMBER_OF(rqmt->type, UNIT_CLASS))
      add_property_long(obj, "type", UA_UNIT);
    else if (MEMBER_OF(rqmt->type, EFFECT_CLASS))
      add_property_long(obj, "type", UA_EFFECT);
    else
      zend_error(E_ERROR, "requirement of illegal type: %d\n", i);

    add_property_long(obj, "id", rqmt->type->object_id);
    add_property_double(obj, "minimum", rqmt->minimum);
    add_property_double(obj, "maximum", rqmt->maximum);

    add_index_zval(return_value, i, obj);
  }
}

static ZEND_FUNCTION(ua_expansion_productioncost) {

  long  type, id;
  int   i;
  zval *obj;

  struct Expansion      *object;
  struct ProductionCost *cost;

  /* get type and id */
  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ll", &type, &id) == FAILURE)
    return;

  /* get object */
  object = (struct Expansion *) game_object(type, id);

  /* we'll return an array of objects */
  array_init(return_value);

  /* iterate the costs */
  for (i = 0; i < object->num_costs; ++i) {

    /* create a new object */
    MAKE_STD_ZVAL(obj);
    object_init(obj);

    cost = (struct ProductionCost *) &object->costs[i];

    /* add type info */
    if (MEMBER_OF(cost->type, RESOURCE_CLASS))
      add_property_long(obj, "type", UA_RESOURCE);
    else if (MEMBER_OF(cost->type, BUILDING_CLASS))
      add_property_long(obj, "type", UA_BUILDING);
    else if (MEMBER_OF(cost->type, DEFENSE_SYSTEM_CLASS))
      add_property_long(obj, "type", UA_DEFENSESYSTEM);
    else if (MEMBER_OF(cost->type, UNIT_CLASS))
      add_property_long(obj, "type", UA_UNIT);
    else
      zend_error(E_ERROR, "cost of illegal type: %d\n", i);

    add_property_long(obj, "id", cost->type->object_id);
    add_property_string(obj, "cost", (char *) cost->cost, 1);

    add_index_zval(return_value, i, obj);
  }
}

static ZEND_FUNCTION(ua_unit_encumbrancelist) {

  long id;
  int  i;

  struct Unit *object;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &id) == FAILURE)
    return;

  object = (struct Unit *) game_object(UA_UNIT, id);

  array_init(return_value);

  for (i = 0; i < MAX_RESOURCE; ++i)
    if (object->encumbranceList[i])
      add_index_long(return_value, i, object->encumbranceList[i]);
}

static ZEND_FUNCTION(ua_terrain_color) {

  long id;
  struct Terrain *object;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &id) == FAILURE)
    return;

  if (id < 0 || id >= MAX_TERRAIN)
    zend_error(E_ERROR, "illegal type id: %ld\n", id);

  object = (struct Terrain *) game_object(UA_TERRAIN, id);

  array_init(return_value);

  add_assoc_long(return_value, "r", object->color[0]);
  add_assoc_long(return_value, "g", object->color[1]);
  add_assoc_long(return_value, "b", object->color[2]);
}

static ZEND_FUNCTION(ua_terrain_effects) {

  long id, i;
  struct Terrain *object;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &id) == FAILURE)
    return;

  if (id < 0 || id >= MAX_TERRAIN)
    zend_error(E_ERROR, "illegal type id: %ld\n", id);

  object = (struct Terrain *) game_object(UA_TERRAIN, id);

  array_init(return_value);

  for (i = 0; i < MAX_EFFECT; ++i)
    if (object->effects[i])
      add_index_double(return_value, i, object->effects[i]);
}

static ZEND_FUNCTION(ua_region_effects) {

  long id, i;
  struct Region *object;

  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &id) == FAILURE)
    return;

  if (id < 0 || id >= MAX_REGION)
    zend_error(E_ERROR, "illegal type id: %ld\n", id);

  object = (struct Region *) game_object(UA_REGION, id);

  array_init(return_value);

  for (i = 0; i < MAX_EFFECT; ++i)
    if (object->effects[i])
      add_index_double(return_value, i, object->effects[i]);
}

static ZEND_FUNCTION(ua_movement_requirements) {

  long  id;
  int   i;
  zval *obj;

  struct Movement    *object;
  struct Requirement *rqmt;

  /* get id */
  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &id) == FAILURE)
    return;

  /* get object */
  object = (struct Movement *) game_object(UA_MOVEMENT, id);

  /* we'll return an array of objects */
  array_init(return_value);

  /* iterate the requirements */
  for (i = 0; i < object->num_requirements; ++i) {

    /* create a new object */
    MAKE_STD_ZVAL(obj);
    object_init(obj);

    rqmt = (struct Requirement *) &object->requirements[i];

    /* add type info */
    if (MEMBER_OF(rqmt->type, RESOURCE_CLASS))
      add_property_long(obj, "type", UA_RESOURCE);
    else if (MEMBER_OF(rqmt->type, BUILDING_CLASS))
      add_property_long(obj, "type", UA_BUILDING);
    else if (MEMBER_OF(rqmt->type, SCIENCE_CLASS))
      add_property_long(obj, "type", UA_SCIENCE);
    else if (MEMBER_OF(rqmt->type, DEFENSE_SYSTEM_CLASS))
      add_property_long(obj, "type", UA_DEFENSESYSTEM);
    else if (MEMBER_OF(rqmt->type, UNIT_CLASS))
      add_property_long(obj, "type", UA_UNIT);
    else if (MEMBER_OF(rqmt->type, EFFECT_CLASS))
      add_property_long(obj, "type", UA_EFFECT);
    else
      zend_error(E_ERROR, "requirement of illegal type: %d\n", i);

    add_property_long(obj, "id", rqmt->type->object_id);
    add_property_double(obj, "minimum", rqmt->minimum);
    add_property_double(obj, "maximum", rqmt->maximum);

    add_index_zval(return_value, i, obj);
  }
}
