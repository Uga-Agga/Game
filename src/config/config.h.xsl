<?xml version="1.0" encoding="UTF-8"?>
<!--
 config.h.xsl - game rules data types
 Copyright (c) 2004  OGP Team

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License as
 published by the Free Software Foundation; either version 2 of
 the License, or (at your option) any later version.
 -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>
<!-- object -->
<xsl:template match="object">[<xsl:value-of select="@id"/>]</xsl:template>

<!-- Config -->
<xsl:template match="Config">
#ifndef _GAME_RULES_H_
#define _GAME_RULES_H_

#include "object.h"

#define MAX_RESOURCE <xsl:value-of select="count(ResourceTypes/*)"/>
#define MAX_BUILDING <xsl:value-of select="count(BuildingTypes/*)"/>
#define MAX_SCIENCE <xsl:value-of select="count(ScienceTypes/*)"/>
#define MAX_DEFENSESYSTEM <xsl:value-of select="count(DefenseSystemTypes/*)"/>
#define MAX_UNIT <xsl:value-of select="count(UnitTypes/*)-1"/>
#define MAX_EFFECT <xsl:value-of select="count(EffectTypes/*)"/>
#define MAX_WONDER <xsl:value-of select="count(wonders/*)"/>
#define MAX_WEATHER <xsl:value-of select="count(Weathers/*)"/>
#define MAX_INCIDENTAL <xsl:value-of select="count(incidentals/*)"/>
#define MAX_REGIME <xsl:value-of select="count(regimes/*)"/>

#define MAX_TERRAIN <xsl:for-each select="//Terrain/@id">
<xsl:sort data-type="number" order="descending"/>
<xsl:if test="position()=1"><xsl:value-of select=".+1"/></xsl:if>
</xsl:for-each>
#define MAX_REGION <xsl:for-each select="//Region/@id">
<xsl:sort data-type="number" order="descending"/>
<xsl:if test="position()=1"><xsl:value-of select=".+1"/></xsl:if>
</xsl:for-each>
#define MAX_MOVEMENT <xsl:for-each select="//Movement/@id">
<xsl:sort data-type="number" order="descending"/>
<xsl:if test="position()=1"><xsl:value-of select=".+1"/></xsl:if>
</xsl:for-each>

#define ID_RESOURCE_FUEL <xsl:value-of select="Header/FuelResourceID"/>
#define MOVEMENT_COST "<xsl:apply-templates select="Header/MovementCost"/>"
#define MOVEMENT_SPEED "<xsl:apply-templates select="Header/MovementSpeed"/>"

#define TAKEOVER_MIN_VALUE <xsl:value-of select="Header/TakeoverMinResourceValue"/>
#define TAKEOVER_MAX_POINTS <xsl:value-of select="Header/TakeoverMaxPopularityPoints"/>

#define EXPOSE_INVISIBLE "<xsl:apply-templates select="Header/ExposeInvisible"/>"
#define WATCH_TOWER_RANGE "<xsl:apply-templates select="Header/WatchTowerVisionRange"/>"
#define WONDER_RESISTANCE "<xsl:apply-templates select="Header/WonderResistance"/>"

#define RESOURCE_CLASS		((struct class *) &amp;resource_class)
#define BUILDING_CLASS		((struct class *) &amp;building_class)
#define SCIENCE_CLASS		((struct class *) &amp;science_class)
#define DEFENSE_SYSTEM_CLASS	((struct class *) &amp;defense_system_class)
#define UNIT_CLASS		((struct class *) &amp;unit_class)
#define EFFECT_CLASS		((struct class *) &amp;effect_class)
#define TERRAIN_CLASS		((struct class *) &amp;terrain_class)
#define REGION_CLASS		((struct class *) &amp;region_class)
#define MOVEMENT_CLASS		((struct class *) &amp;movement_class)

enum LocaleSpecifier
{<xsl:for-each select="Languages/Language">
    LOCALE_<xsl:value-of select="@locale"/>,</xsl:for-each>
    MAX_LOCALE
};

struct Language
{
    const char *locale;
    const char *name;
};

extern const struct Language language[];

struct ProductionCost
{
    const struct GameObject *type;
    const char *cost;
};

struct Requirement
{
    const struct GameObject *type;
    double minimum;
    double maximum;
};

struct GameObject	/* FIXME: change names */
{
    struct object base;

    int object_id;
    const char *name[MAX_LOCALE];
    const char *description[MAX_LOCALE];
    const char *dbFieldName;
    const char *maxLevel;
    int hidden;
};

struct Expansion
{
    struct GameObject base;

    int position;
    int ratingValue;

    const char *productionTime;
    const struct ProductionCost *costs;
    int num_costs;

    const struct Requirement *requirements;
    int num_requirements;
};

struct Resource
{
    struct GameObject base;

    double ratingValue;
    double takeoverValue;
    const char *production;

    double stealRatio;	/* not implemented */
    double destroyRatio;	/* not implemented */
    const char *safeStorage;
};

struct BattleUnit
{
    struct Expansion base;

/*  int meleeDamage;
    int meleeDamageResistance;
    int rangedDamage;
    int rangedDamageResistance;
    int structuralDamage;
    int structuralDamageResistance;
    int size;			*/

    int attackRange;
    int attackAreal;
    int attackRate;
    int defenseRate;
    int hitPoints;
    int rangedDamageResistance;

    double criticalDamageProbability;
    double heavyDamageProbability;

    double antiSpyChance;
};

struct Building
{
    struct Expansion base;
};

struct Science
{
    struct Expansion base;
};

struct DefenseSystem
{
    struct BattleUnit base;
    int warpoints;
};

struct Unit
{
    struct BattleUnit base;

    int visible;
    int warpoints;
    
    double foodCost;
    double wayCost;
    int encumbranceList[MAX_RESOURCE];

    int spyValue;
    double spyChance;
    double spyQuality;
};

struct Effect
{
    struct GameObject base;
    int isResourceEffect;
};

struct Terrain
{
    struct GameObject base;
    
    int takeoverByCombat;
    int barren;
    unsigned char color[3];

    double effects[MAX_EFFECT];
};

struct Region
{
    struct GameObject base;

    int startRegion;
    int takeoverActivatable;
    int barren;

    double effects[MAX_EFFECT];
};

struct Movement
{
    struct GameObject base;

    int action;

    double speed;
    double provisions;

    int conquering;
    int invisible;

    const struct Requirement *requirements;
    int num_requirements;
};

extern const struct class resource_class;
extern const struct class building_class;
extern const struct class science_class;
extern const struct class defense_system_class;
extern const struct class unit_class;
extern const struct class effect_class;
extern const struct class terrain_class;
extern const struct class region_class;
extern const struct class movement_class;

extern const struct GameObject *resource_type[];
extern const struct GameObject *building_type[];
extern const struct GameObject *science_type[];
extern const struct GameObject *defense_system_type[];
extern const struct GameObject *unit_type[];
extern const struct GameObject *effect_type[];
extern const struct GameObject *terrain_type[];
extern const struct GameObject *region_type[];
extern const struct GameObject *movement_type[];

#endif /* _GAME_RULES_H_ */
</xsl:template>
</xsl:stylesheet>
