<?xml version="1.0" encoding="UTF-8"?>
<!--
 config.c.xsl - game rules data types
 Copyright (c) 2004-2013  OGP Team

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License as
 published by the Free Software Foundation; either version 2 of
 the License, or (at your option) any later version.
 -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>
<!-- text elements -->
<xsl:strip-space elements="Name Description targetMessage sourceMessage p"/>
<!-- replace-string -->
<xsl:template name="replace-string">
<xsl:param name="text"/>
<xsl:param name="from"/>
<xsl:param name="to"/>
<xsl:choose>
<xsl:when test="contains($text, $from)">
  <xsl:variable name="before" select="substring-before($text, $from)"/>
  <xsl:variable name="after" select="substring-after($text, $from)"/>
  <xsl:value-of select="concat($before, $to)"/>
  <xsl:call-template name="replace-string">
    <xsl:with-param name="text" select="$after"/>
    <xsl:with-param name="from" select="$from"/>
    <xsl:with-param name="to" select="$to"/>
  </xsl:call-template>
</xsl:when><xsl:otherwise>
  <xsl:value-of select="$text"/>  
</xsl:otherwise>
</xsl:choose>
</xsl:template>

<!-- Config -->
<xsl:template match="Config">
#include "game_rules.h"
#include "wonder_rules.h"

#define INFINITY	(1.0f / 0.0f)

static const struct Resource resource_type_list[];
static const struct Building building_type_list[];
static const struct Science science_type_list[];
static const struct DefenseSystem defenseSystem_type_list[];
static const struct Unit unit_type_list[];
static const struct Effect effect_type_list[];

<xsl:apply-templates select="trades"/>
<xsl:apply-templates select="Languages"/>
<xsl:apply-templates select="ResourceTypes"/>
<xsl:apply-templates select="BuildingTypes"/>
<xsl:apply-templates select="ScienceTypes"/>
<xsl:apply-templates select="DefenseSystemTypes"/>
<xsl:apply-templates select="UnitTypes"/>
<xsl:apply-templates select="EffectTypes"/>
<xsl:apply-templates select="wonders"/>
<xsl:apply-templates select="Weathers"/>
<xsl:apply-templates select="Terrains"/>
<xsl:apply-templates select="Regions"/>
<xsl:apply-templates select="Movements"/>
</xsl:template>

<!-- Name, Description -->
<xsl:template match="Name">
	  [LOCALE_<xsl:value-of select="@lang"/>] = "<xsl:apply-templates/>",
	</xsl:template>
<xsl:template match="Description">
	  [LOCALE_<xsl:value-of select="@lang"/>] = "<xsl:apply-templates/>",
	</xsl:template>
<xsl:template match="p">&lt;p&gt;<xsl:apply-templates/>&lt;/p&gt;</xsl:template>

<!-- object -->
<xsl:template match="object">[<xsl:value-of select="@id"/>]</xsl:template>

<!-- cost -->
<xsl:template name="cost">
<xsl:param name="type"/>
  {
    .type = (const struct GameObject *) (<xsl:value-of select="$type"/>),
    .cost = "<xsl:apply-templates/>"
  },
</xsl:template>

<!-- costs -->
<xsl:template name="costs">
<xsl:param name="name"/>
<xsl:if test="count(Cost)">
static const struct ProductionCost <xsl:value-of select="$name"/>[] = {
<xsl:for-each select="Cost[name(id(@id))='Resource']">
<xsl:call-template name="cost">
<xsl:with-param name="type"
	select="concat('resource_type_list + ', count(id(@id)/preceding-sibling::*))"/>
</xsl:call-template>
</xsl:for-each>
<xsl:for-each select="Cost[name(id(@id))='Building']">
<xsl:call-template name="cost">
<xsl:with-param name="type"
	select="concat('building_type_list + ', count(id(@id)/preceding-sibling::*))"/>
</xsl:call-template>
</xsl:for-each>
<xsl:for-each select="Cost[name(id(@id))='DefenseSystem']">
<xsl:call-template name="cost">
<xsl:with-param name="type"
	select="concat('defenseSystem_type_list + ', count(id(@id)/preceding-sibling::*))"/>
</xsl:call-template>
</xsl:for-each>
<xsl:for-each select="Cost[name(id(@id))='Unit']">
<xsl:call-template name="cost">
<xsl:with-param name="type"
	select="concat('unit_type_list + ', count(id(@id)/preceding-sibling::*))"/>
</xsl:call-template>
</xsl:for-each>
};
</xsl:if>
</xsl:template>

<!-- get_costs -->
<xsl:template name="get_costs">
<xsl:param name="name"/>
<xsl:variable name="num_costs" select="count(Cost)"/>
<xsl:if test="$num_costs">
      .costs = <xsl:value-of select="$name"/>,
      .num_costs = <xsl:value-of select="$num_costs"/>,
</xsl:if>
</xsl:template>

<!-- requirement -->
<xsl:template name="requirement">
<xsl:param name="type"/>
  {
    .type = (const struct GameObject *) (<xsl:value-of select="$type"/>),
<xsl:choose>
<xsl:when test="@min">
    .minimum = <xsl:value-of select="@min"/>,
</xsl:when><xsl:otherwise>
    .minimum = -INFINITY,
</xsl:otherwise>
</xsl:choose>
<xsl:choose>
<xsl:when test="@max">
    .maximum = <xsl:value-of select="@max"/>
</xsl:when><xsl:otherwise>
    .maximum = INFINITY
</xsl:otherwise>
</xsl:choose>
  },
</xsl:template>

<!-- requirements -->
<xsl:template name="requirements">
<xsl:param name="name"/>
<xsl:if test="count(Requirement|EffectReq)">
static const struct Requirement <xsl:value-of select="$name"/>[] = {
<xsl:for-each select="Requirement[name(id(@id))='Resource']">
<xsl:call-template name="requirement">
<xsl:with-param name="type"
	select="concat('resource_type_list + ', count(id(@id)/preceding-sibling::*))"/>
</xsl:call-template>
</xsl:for-each>
<xsl:for-each select="Requirement[name(id(@id))='Building']">
<xsl:call-template name="requirement">
<xsl:with-param name="type"
	select="concat('building_type_list + ', count(id(@id)/preceding-sibling::*))"/>
</xsl:call-template>
</xsl:for-each>
<xsl:for-each select="Requirement[name(id(@id))='Science']">
<xsl:call-template name="requirement">
<xsl:with-param name="type"
	select="concat('science_type_list + ', count(id(@id)/preceding-sibling::*))"/>
</xsl:call-template>
</xsl:for-each>
<xsl:for-each select="Requirement[name(id(@id))='DefenseSystem']">
<xsl:call-template name="requirement">
<xsl:with-param name="type"
	select="concat('defenseSystem_type_list + ', count(id(@id)/preceding-sibling::*))"/>
</xsl:call-template>
</xsl:for-each>
<xsl:for-each select="Requirement[name(id(@id))='Unit']">
<xsl:call-template name="requirement">
<xsl:with-param name="type"
	select="concat('unit_type_list + ', count(id(@id)/preceding-sibling::*))"/>
</xsl:call-template>
</xsl:for-each>
<xsl:for-each select="EffectReq[name(id(@id))='EffectType']">
<xsl:call-template name="requirement">
<xsl:with-param name="type"
	select="concat('effect_type_list + ', count(id(@id)/preceding-sibling::*))"/>
</xsl:call-template>
</xsl:for-each>
};
</xsl:if>
</xsl:template>

<!-- get_requirements -->
<xsl:template name="get_requirements">
<xsl:param name="name"/>
<xsl:variable name="num_reqs" select="count(Requirement|EffectReq)"/>
<xsl:if test="$num_reqs">
      .requirements = <xsl:value-of select="$name"/>,
      .num_requirements = <xsl:value-of select="$num_reqs"/>
</xsl:if>
</xsl:template>

<xsl:template match="Languages">
/********************** language list *********************/

const struct Language language[] = {
<xsl:for-each select="Language">
  {
    .locale = "<xsl:value-of select="@locale"/>",
    .name   = "<xsl:value-of select="text()"/>"
  },
</xsl:for-each>
};
</xsl:template>

<xsl:template match="ResourceTypes">
/********************** resource types *********************/

static const struct Resource resource_type_list[] = {
<xsl:for-each select="Resource">
  { /* <xsl:value-of select="Name"/> */
    {
      { .class = RESOURCE_CLASS },

      .object_id   = <xsl:value-of select="position()-1"/>,
      .name        = {<xsl:apply-templates select="Name"/>},
      .description = {<xsl:apply-templates select="Description"/>},
      .dbFieldName = "<xsl:value-of select="@id"/>",
      .maxLevel    = "<xsl:apply-templates select="MaxStorage"/>",
      .hidden      = <xsl:value-of select="@hidden"/>
    },

    .ratingValue   = <xsl:value-of select="RatingValue"/>,
    .takeoverValue = <xsl:value-of select="TakeoverValue"/>,
    .production    = "<xsl:apply-templates select="Production"/>",

    .stealRatio    = <xsl:value-of select="StealRatio"/>,
    .destroyRatio  = <xsl:value-of select="DestroyRatio"/>,
    .safeStorage   = "<xsl:apply-templates select="SafeStorage"/>",
  },
</xsl:for-each>
};

const struct class resource_class;

const struct GameObject *resource_type[] = {
<xsl:for-each select="Resource">
  (const struct GameObject *) (resource_type_list + <xsl:value-of select="position()-1"/>),</xsl:for-each>
};
</xsl:template>

<xsl:template match="BuildingTypes">
/********************** building types *********************/

<xsl:for-each select="Building">
<xsl:call-template name="costs">
<xsl:with-param name="name" select="concat('building_cost_', position()-1)"/>
</xsl:call-template>
<xsl:call-template name="requirements">
<xsl:with-param name="name" select="concat('building_req_', position()-1)"/>
</xsl:call-template>
</xsl:for-each>

static const struct Building building_type_list[] = {
<xsl:for-each select="Building">
  { /* <xsl:value-of select="Name"/> */
    {
      {
	{ .class = BUILDING_CLASS },

	.object_id   = <xsl:value-of select="position()-1"/>,
	.name        = {<xsl:apply-templates select="Name"/>},
	.description = {<xsl:apply-templates select="Description"/>},
	.dbFieldName = "<xsl:value-of select="@id"/>",
	.maxLevel    = "<xsl:apply-templates select="MaxDevelopmentLevel"/>",
	.hidden      = <xsl:value-of select="@hidden"/>
      },

<xsl:if test="Position">
      .position    = <xsl:value-of select="Position"/>,
</xsl:if>
      .ratingValue = <xsl:value-of select="RatingValue"/>,

      .productionTime = "<xsl:apply-templates select="ProductionTime"/>",
      <xsl:call-template name="get_costs">
      <xsl:with-param name="name" select="concat('building_cost_', position()-1)"/>
      </xsl:call-template>

      <xsl:call-template name="get_requirements">
      <xsl:with-param name="name" select="concat('building_req_', position()-1)"/>
      </xsl:call-template>
    }
  },
</xsl:for-each>
};

const struct class building_class;

const struct GameObject *building_type[] = {
<xsl:for-each select="Building">
  (const struct GameObject *) (building_type_list + <xsl:value-of select="position()-1"/>),</xsl:for-each>
};
</xsl:template>

<xsl:template match="ScienceTypes">
/********************** science types *********************/

<xsl:for-each select="Science">
<xsl:call-template name="costs">
<xsl:with-param name="name" select="concat('science_cost_', position()-1)"/>
</xsl:call-template>
<xsl:call-template name="requirements">
<xsl:with-param name="name" select="concat('science_req_', position()-1)"/>
</xsl:call-template>
</xsl:for-each>

static const struct Science science_type_list[] = {
<xsl:for-each select="Science">
  { /* <xsl:value-of select="Name"/> */
    {
      {
	{ .class = SCIENCE_CLASS },

	.object_id   = <xsl:value-of select="position()-1"/>,
	.name        = {<xsl:apply-templates select="Name"/>},
	.description = {<xsl:apply-templates select="Description"/>},
	.dbFieldName = "<xsl:value-of select="@id"/>",
	.maxLevel    = "<xsl:apply-templates select="MaxDevelopmentLevel"/>",
	.hidden      = <xsl:value-of select="@hidden"/>
      },

<xsl:if test="Position">
      .position    = <xsl:value-of select="Position"/>,
</xsl:if>
      .ratingValue = 0,

      .productionTime = "<xsl:apply-templates select="ProductionTime"/>",
      <xsl:call-template name="get_costs">
      <xsl:with-param name="name" select="concat('science_cost_', position()-1)"/>
      </xsl:call-template>

      <xsl:call-template name="get_requirements">
      <xsl:with-param name="name" select="concat('science_req_', position()-1)"/>
      </xsl:call-template>
    }
  },
</xsl:for-each>
};

const struct class science_class;

const struct GameObject *science_type[] = {
<xsl:for-each select="Science">
  (const struct GameObject *) (science_type_list + <xsl:value-of select="position()-1"/>),</xsl:for-each>
};
</xsl:template>

<xsl:template match="DefenseSystemTypes">
/********************** defense system types *********************/

<xsl:for-each select="DefenseSystem">
<xsl:call-template name="costs">
<xsl:with-param name="name" select="concat('defenseSystem_cost_', position()-1)"/>
</xsl:call-template>
<xsl:call-template name="requirements">
<xsl:with-param name="name" select="concat('defenseSystem_req_', position()-1)"/>
</xsl:call-template>
</xsl:for-each>

static const struct DefenseSystem defenseSystem_type_list[] = {
<xsl:for-each select="DefenseSystem">
  { /* <xsl:value-of select="Name"/> */
    {
      {
	{
	  { .class = DEFENSE_SYSTEM_CLASS },

	  .object_id   = <xsl:value-of select="position()-1"/>,
	  .name        = {<xsl:apply-templates select="Name"/>},
	  .description = {<xsl:apply-templates select="Description"/>},
	  .dbFieldName = "<xsl:value-of select="@id"/>",
	  .maxLevel    = "<xsl:apply-templates select="MaxDevelopmentLevel"/>",
	  .hidden      = <xsl:value-of select="@hidden"/>
	},

<xsl:if test="Position">
	.position    = <xsl:value-of select="Position"/>,
</xsl:if>
	.ratingValue = <xsl:value-of select="round((9 * RangedDamage + 5 * MeleeDamage + 150 * StructuralDamageResistance div Size + 4 * Size + 52.5) div 6)"/>,

	.productionTime = "<xsl:apply-templates select="ProductionTime"/>",
	<xsl:call-template name="get_costs">
	<xsl:with-param name="name" select="concat('defenseSystem_cost_', position()-1)"/>
	</xsl:call-template>

	<xsl:call-template name="get_requirements">
	<xsl:with-param name="name" select="concat('defenseSystem_req_', position()-1)"/>
	</xsl:call-template>
      },
<!--
      .meleeDamage            = <xsl:value-of select="MeleeDamage"/>,
      .meleeDamageResistance  = <xsl:value-of select="MeleeDamageResistance"/>,
      .rangedDamage           = <xsl:value-of select="RangedDamage"/>,
      .rangedDamageResistance = <xsl:value-of select="RangedDamageResistance"/>,
      .structuralDamage       = <xsl:value-of select="StructuralDamage"/>,
      .structuralDamageResistance = <xsl:value-of select="StructuralDamageResistance"/>,
      .size                   = <xsl:value-of select="Size"/>,
 -->
      .attackRange = <xsl:value-of select="RangedDamage"/>,
      .attackAreal = 0,
      .attackRate  = <xsl:value-of select="MeleeDamage"/>,
      .defenseRate = <xsl:value-of select="StructuralDamageResistance"/>,
      .hitPoints   = <xsl:value-of select="Size"/>,
      .rangedDamageResistance = 0,
      <xsl:if test="CriticalDamageProbability">
        .criticalDamageProbability = <xsl:value-of select="CriticalDamageProbability"/>,
      </xsl:if>
      <xsl:if test="HeavyDamageProbability">
        .heavyDamageProbability = <xsl:value-of select="HeavyDamageProbability"/>,
      </xsl:if>

<xsl:if test="AntiSpyChance">
      .antiSpyChance = <xsl:value-of select="AntiSpyChance"/>
</xsl:if>
    },
<xsl:if test="WarPoints">
    .warpoints = <xsl:value-of select="WarPoints"/>
</xsl:if>
  },
</xsl:for-each>
};

const struct class defense_system_class;

const struct GameObject *defense_system_type[] = {
<xsl:for-each select="DefenseSystem">
  (const struct GameObject *) (defenseSystem_type_list + <xsl:value-of select="position()-1"/>),</xsl:for-each>
};
</xsl:template>

<xsl:template match="UnitTypes">
/********************** unit types *********************/

<xsl:for-each select="Unit">
<xsl:call-template name="costs">
<xsl:with-param name="name" select="concat('unit_cost_', position()-1)"/>
</xsl:call-template>
<xsl:call-template name="requirements">
<xsl:with-param name="name" select="concat('unit_req_', position()-1)"/>
</xsl:call-template>
</xsl:for-each>

static const struct Unit unit_type_list[] = {
<xsl:for-each select="Unit">
  { /* <xsl:value-of select="Name"/> */
    {
      {
	{
	  { .class = UNIT_CLASS },

	  .object_id   = <xsl:value-of select="position()-1"/>,
	  .name        = {<xsl:apply-templates select="Name"/>},
	  .description = {<xsl:apply-templates select="Description"/>},
	  .dbFieldName = "<xsl:value-of select="@id"/>",
	  .hidden      = <xsl:value-of select="@hidden"/>
	},

<xsl:if test="Position">
	.position    = <xsl:value-of select="Position"/>,
</xsl:if>
	.ratingValue = <xsl:value-of select="round((9 * RangedDamage + 4 * StructuralDamage + 5 * MeleeDamage + 50 * (RangedDamageResistance + 2 * MeleeDamageResistance) div Size + 4 * Size + 52.5 div Velocity) * (3 - Visible) div 12)"/>,

	.productionTime = "<xsl:apply-templates select="ProductionTime"/>",
	<xsl:call-template name="get_costs">
	<xsl:with-param name="name" select="concat('unit_cost_', position()-1)"/>
	</xsl:call-template>

	<xsl:call-template name="get_requirements">
	<xsl:with-param name="name" select="concat('unit_req_', position()-1)"/>
	</xsl:call-template>
      },
<!--
      .meleeDamage            = <xsl:value-of select="MeleeDamage"/>,
      .meleeDamageResistance  = <xsl:value-of select="MeleeDamageResistance"/>,
      .rangedDamage           = <xsl:value-of select="RangedDamage"/>,
      .rangedDamageResistance = <xsl:value-of select="RangedDamageResistance"/>,
      .structuralDamage       = <xsl:value-of select="StructuralDamage"/>,
      .structuralDamageResistance = <xsl:value-of select="StructuralDamageResistance"/>,
      .size                   = <xsl:value-of select="Size"/>,
 -->
      .attackRange = <xsl:value-of select="RangedDamage"/>,
      .attackAreal = <xsl:value-of select="StructuralDamage"/>,
      .attackRate  = <xsl:value-of select="MeleeDamage"/>,
      .defenseRate = <xsl:value-of select="MeleeDamageResistance"/>,
      .hitPoints   = <xsl:value-of select="Size"/>,
      .rangedDamageResistance = <xsl:value-of select="RangedDamageResistance"/>,
      
      <xsl:if test="CriticalDamageProbability">
        .criticalDamageProbability = <xsl:value-of select="CriticalDamageProbability"/>,
      </xsl:if>
      <xsl:if test="HeavyDamageProbability">
        .heavyDamageProbability = <xsl:value-of select="HeavyDamageProbability"/>,
      </xsl:if>

<xsl:if test="AntiSpyChance">
      .antiSpyChance = <xsl:value-of select="AntiSpyChance"/>
</xsl:if>
    },

    .visible = <xsl:value-of select="Visible"/>,
<xsl:if test="WarPoints">
    .warpoints = <xsl:value-of select="WarPoints"/>,
</xsl:if>
<xsl:choose>
<xsl:when test="FuelUsage">
    .foodCost = <xsl:value-of select="FuelUsage"/>,
</xsl:when><xsl:otherwise>
    .foodCost = <xsl:value-of select="round((((RangedDamageResistance div 2)+ (MeleeDamageResistance div 2) + (Size)) div 40)*100) div 100"/>,
</xsl:otherwise>
</xsl:choose>
<xsl:choose>
<xsl:when test="Velocity">
    .wayCost = <xsl:value-of select="Velocity"/>,
</xsl:when><xsl:otherwise>
    .wayCost = 1,
</xsl:otherwise>
</xsl:choose>
<xsl:if test="count(Encumbrance)">
    .encumbranceList = {
<xsl:for-each select="Encumbrance">
      [<xsl:value-of select="count(id(@id)/preceding-sibling::*)"/>] = <xsl:value-of select="@value"/>,
</xsl:for-each>
    },
</xsl:if>

<xsl:if test="SpyValue">
    .spyValue   = <xsl:value-of select="SpyValue"/>,
</xsl:if>
<xsl:if test="SpyChance">
    .spyChance  = <xsl:value-of select="SpyChance"/>,
</xsl:if>
<xsl:if test="SpyQuality">
    .spyQuality = <xsl:value-of select="SpyQuality"/>
</xsl:if>
  },
</xsl:for-each>
};

const struct class unit_class;

const struct GameObject *unit_type[] = {
<xsl:for-each select="Unit">
  (const struct GameObject *) (unit_type_list + <xsl:value-of select="position()-1"/>),</xsl:for-each>
};
</xsl:template>

<xsl:template match="EffectTypes">
/********************** effect types *********************/

static const struct Effect effect_type_list[] = {
<xsl:for-each select="EffectType">
  { /* <xsl:value-of select="Name"/> */
    {
      { .class = EFFECT_CLASS },

      .object_id   = <xsl:value-of select="position()-1"/>,
      .name        = {<xsl:apply-templates select="Name"/>},
<xsl:if test="count(Description)">
      .description = {<xsl:apply-templates select="Description"/>},
</xsl:if>
      .dbFieldName = "<xsl:value-of select="@id"/>",
      .hidden      = <xsl:value-of select="@hidden"/>,
    },
    .isResourceEffect = <xsl:value-of select="@isResourceEffect"/>
  },
</xsl:for-each>
};

const struct class effect_class;

const struct GameObject *effect_type[] = {
<xsl:for-each select="EffectType">
  (const struct GameObject *) (effect_type_list + <xsl:value-of select="position()-1"/>),</xsl:for-each>
};
</xsl:template>

<!-- impact effect -->
<xsl:template name="impact_effect">
  [<xsl:value-of select="count(id(@id)/preceding-sibling::*)"/>] = {
    .absolute = <xsl:value-of select="@absolute"/>,
    .relative = <xsl:value-of select="@relative"/>,
    .maxDelta = <xsl:value-of select="@maxDelta"/>,
    .type     = WONDER_RANDOM_<xsl:value-of select="@type"/>
  },
</xsl:template>


<xsl:template match="wonders">
/********************** wonder types *********************/

<xsl:for-each select="wonder">
<xsl:variable name="wid" select="position()-1"/>
<xsl:for-each select="impacts/impact">
<xsl:variable name="uid" select="concat($wid,'_',position()-1)"/>
<!-- effect arrays for the impacts -->
<xsl:if test="count(resources/resource)">
static const struct ImpactEffect resources_<xsl:value-of select="$uid"/>[MAX_RESOURCE] = {
<xsl:for-each select="resources/resource">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>

<xsl:if test="count(buildings/building)">
static const struct ImpactEffect buildings_<xsl:value-of select="$uid"/>[MAX_BUILDING] = {
<xsl:for-each select="buildings/building">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>

<xsl:if test="count(sciences/science)">
static const struct ImpactEffect sciences_<xsl:value-of select="$uid"/>[MAX_SCIENCE] = {
<xsl:for-each select="sciences/science">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>

<xsl:if test="count(defenseSystems/defenseSystem)">
static const struct ImpactEffect defenseSystems_<xsl:value-of select="$uid"/>[MAX_DEFENSESYSTEM] = {
<xsl:for-each select="defenseSystems/defenseSystem">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>

<xsl:if test="count(units/unit)">
static const struct ImpactEffect units_<xsl:value-of select="$uid"/>[MAX_UNIT] = {
<xsl:for-each select="units/unit">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>

<xsl:if test="count(effects/effect)">
static const struct ImpactEffect effects_<xsl:value-of select="$uid"/>[MAX_EFFECT] = {
<xsl:for-each select="effects/effect">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>
</xsl:for-each>

<xsl:if test="count(impacts/impact)">
static const struct WonderImpact impacts_<xsl:value-of select="$wid"/>[] = {
<xsl:for-each select="impacts/impact">
<xsl:variable name="uid" select="concat($wid,'_',position()-1)"/>
  {
    .duration = <xsl:value-of select="@duration"/>,
    .delay    = <xsl:value-of select="@delay"/>,
    .steal    = <xsl:value-of select="@steal"/>,

    .deactivateTearDown = <xsl:value-of select="@deactivateTearDown"/>,

    .targetMessage = "<xsl:apply-templates select="targetMessage"/>",
    .targetMessageType = WONDER_MESSAGE_<xsl:value-of select="targetMessage/@messageType"/>,

    .sourceMessage = "<xsl:apply-templates select="sourceMessage"/>",
    .sourceMessageType = WONDER_MESSAGE_<xsl:value-of select="sourceMessage/@messageType"/>,

<xsl:if test="count(resources/resource)">
    .resources = resources_<xsl:value-of select="$uid"/>,
    .resourcesAll = <xsl:value-of select="resources/@all"/>,
</xsl:if>
<xsl:if test="count(buildings/building)">
    .buildings = buildings_<xsl:value-of select="$uid"/>,
    .buildingsAll = <xsl:value-of select="buildings/@all"/>,
</xsl:if>
<xsl:if test="count(sciences/science)">
    .sciences = sciences_<xsl:value-of select="$uid"/>,
    .sciencesAll = <xsl:value-of select="sciences/@all"/>,
</xsl:if>
<xsl:if test="count(defenseSystems/defenseSystem)">
    .defenseSystems = defenseSystems_<xsl:value-of select="$uid"/>,
    .defenseSystemsAll = <xsl:value-of select="defenseSystems/@all"/>,
</xsl:if>
<xsl:if test="count(units/unit)">
    .units = units_<xsl:value-of select="$uid"/>,
    .unitsAll = <xsl:value-of select="units/@all"/>,
</xsl:if>
<xsl:if test="count(effects/effect)">
    .effects = effects_<xsl:value-of select="$uid"/>,
    .effectsAll = <xsl:value-of select="effects/@all"/>
</xsl:if>
  },
</xsl:for-each>
};
</xsl:if>
</xsl:for-each>

<xsl:for-each select="wonder">
<xsl:call-template name="costs">
<xsl:with-param name="name" select="concat('wonder_cost_', position()-1)"/>
</xsl:call-template>
<xsl:call-template name="requirements">
<xsl:with-param name="name" select="concat('wonder_req_', position()-1)"/>
</xsl:call-template>
</xsl:for-each>

static const struct Wonder wonder_type_list[] = {
<xsl:for-each select="wonder">
  { /* <xsl:value-of select="Name"/> */
    {
      { .class = WONDER_CLASS },

      .object_id   = <xsl:value-of select="position()-1"/>,
      .name        = {<xsl:apply-templates select="Name"/>},
      .description = {<xsl:apply-templates select="Description"/>},
      .hidden      = <xsl:value-of select="@hidden"/>
    },

    .groupid = <xsl:apply-templates select="@groupID"/>, 
    .isTribeCaveWonder = <xsl:value-of select="@isTribeCaveWonder"/>,
    .target = WONDER_TARGET_<xsl:value-of select="@target"/>,
    .chance = "<xsl:apply-templates select="chance"/>",
<xsl:if test="count(impacts/impact)">
    .impacts = impacts_<xsl:value-of select="position()-1"/>,
    .num_impacts = <xsl:value-of select="count(impacts/impact)"/>,
</xsl:if>
    <xsl:call-template name="get_costs">
    <xsl:with-param name="name" select="concat('wonder_cost_', position()-1)"/>
    </xsl:call-template>

    <xsl:call-template name="get_requirements">
    <xsl:with-param name="name" select="concat('wonder_req_', position()-1)"/>
    </xsl:call-template>
  },
</xsl:for-each>
};

const struct class wonder_class;

const struct GameObject *wonder_type[] = {
<xsl:for-each select="wonder">
  (const struct GameObject *) (wonder_type_list + <xsl:value-of select="position()-1"/>),</xsl:for-each>
};
</xsl:template>

<xsl:template match="Weathers">
/********************** weather types *********************/

<xsl:for-each select="Weather">
<xsl:variable name="wid" select="position()-1"/>
<xsl:for-each select="we_impacts/we_impact">
<xsl:variable name="uid" select="concat($wid,'_',position()-1)"/>
<!-- effect arrays for the impacts -->
<xsl:if test="count(effects/effect)">
static const struct ImpactEffect we_effects_<xsl:value-of select="$uid"/>[MAX_EFFECT] = {
<xsl:for-each select="effects/effect">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>
</xsl:for-each>

<xsl:if test="count(we_impacts/we_impact)">
static const struct WeatherImpact we_impacts_<xsl:value-of select="$wid"/>[] = {
<xsl:for-each select="we_impacts/we_impact">
<xsl:variable name="uid" select="concat($wid,'_',position()-1)"/>
  {
    .duration = <xsl:value-of select="@duration"/>,
    .delay    = <xsl:value-of select="@delay"/>,
    .steal    = <xsl:value-of select="@steal"/>,

<xsl:if test="count(effects/effect)">
    .effects = we_effects_<xsl:value-of select="$uid"/>,
    .effectsAll = <xsl:value-of select="effects/@all"/>
</xsl:if>
  },
</xsl:for-each>
};
</xsl:if>
</xsl:for-each>

static const struct Weather weather_type_list[] = {
<xsl:for-each select="Weather">
  { /* <xsl:value-of select="Name"/> */
    {
      { .class = WEATHER_CLASS },

      .object_id   = <xsl:value-of select="position()-1"/>,
      .name        = {<xsl:apply-templates select="Name"/>},
    },
<xsl:if test="count(we_impacts/we_impact)">
    .impacts = we_impacts_<xsl:value-of select="position()-1"/>,
    .num_impacts = <xsl:value-of select="count(impacts/impact)"/>,
</xsl:if>
  },
</xsl:for-each>
};

const struct class weather_class;

const struct GameObject *weather_type[] = {
<xsl:for-each select="Weather">
  (const struct GameObject *) (weather_type_list + <xsl:value-of select="position()-1"/>),</xsl:for-each>
};
</xsl:template>
<xsl:template match="Terrains">
/********************** terrain types *********************/

static const struct Terrain terrain_type_list[] = {
<xsl:for-each select="Terrain">
  [<xsl:value-of select="@id"/>] = { /* <xsl:value-of select="Name"/> */
    {
      { .class = TERRAIN_CLASS },

      .object_id   = <xsl:value-of select="@id"/>,
      .name        = {<xsl:apply-templates select="Name"/>},
      .description = {<xsl:apply-templates select="Description"/>},
    },

    .takeoverByCombat = <xsl:value-of select="@takeoverByCombat"/>,
    .barren  = <xsl:value-of select="@barren"/>,
    .color   = {
      <xsl:value-of select="concat(Color/@r,', ',Color/@g,', ',Color/@b)"/>
    },
    .tribeRegion = <xsl:value-of select="@tribeRegion"/>,
 <xsl:choose>
 <xsl:when test="TribeCaveWonder/@id">
    .tribeCaveWonderId = <xsl:value-of select="TribeCaveWonder/@id"/>,
</xsl:when><xsl:otherwise>
    .tribeCaveWonderId = 0,
</xsl:otherwise>
</xsl:choose>
<xsl:if test="count(Effect)">
    .effects = {
<xsl:for-each select="Effect">
      [<xsl:value-of select="count(id(@id)/preceding-sibling::*)"/>] = <xsl:value-of select="."/>,
</xsl:for-each>
    }
</xsl:if>
  },
</xsl:for-each>
};

const struct class terrain_class;
const struct GameObject *terrain_type[] = {
<xsl:for-each select="Terrain">
  [<xsl:value-of select="@id"/>] = (const struct GameObject *) (terrain_type_list + <xsl:value-of select="@id"/>),</xsl:for-each>
};
</xsl:template>

<!-- regions -->
<xsl:template match="Regions">

/********************** region types *********************/

static const struct Region region_type_list[] = {
<xsl:for-each select="Region">
  [<xsl:value-of select="@id"/>] = { /* <xsl:value-of select="Name"/> */
    {
      { .class = REGION_CLASS },

      .object_id   = <xsl:value-of select="@id"/>,
      .name        = {<xsl:apply-templates select="Name"/>},
      .description = {<xsl:apply-templates select="Description"/>},
    },
    .startRegion = <xsl:value-of select="@startRegion"/>,
    .takeoverActivatable = <xsl:value-of select="@takeoverActivatable"/>,
    .barren  = <xsl:value-of select="@barren"/>,
<xsl:if test="count(Effect)">
    .effects = {
<xsl:for-each select="Effect">
      [<xsl:value-of select="count(id(@id)/preceding-sibling::*)"/>] = <xsl:value-of select="."/>,
</xsl:for-each>
    }
</xsl:if>
  },
</xsl:for-each>
};

const struct class region_class;
const struct GameObject *region_type[] = {
<xsl:for-each select="Region">
  [<xsl:value-of select="@id"/>] = (const struct GameObject *) (region_type_list + <xsl:value-of select="@id"/>),</xsl:for-each>
};
</xsl:template>

<!-- movements -->
<xsl:template match="Movements">

/********************** Movement types *********************/

<xsl:for-each select="Movement">
<xsl:call-template name="requirements">
<xsl:with-param name="name" select="concat('movement_req_', @id)"/>
</xsl:call-template>
</xsl:for-each>

static const struct Movement movement_type_list[] = {
<xsl:for-each select="Movement">
  [<xsl:value-of select="@id"/>] = { /* <xsl:value-of select="Name"/> */
    {
      { .class = MOVEMENT_CLASS },

      .object_id   = <xsl:value-of select="@id"/>,
      .name        = {<xsl:apply-templates select="Name"/>},
      .description = {<xsl:apply-templates select="Description"/>},
    },
    .action = <xsl:call-template name="actions">
                <xsl:with-param name="action" select="@action"/>
              </xsl:call-template>,
    .speed = <xsl:value-of select="@speed"/>,
    .provisions = <xsl:value-of select="@provisions"/>,
    .conquering = <xsl:value-of select="@conquering"/>,
    .invisible = <xsl:value-of select="@invisible"/>,
    <xsl:call-template name="get_requirements">
    <xsl:with-param name="name" select="concat('movement_req_', @id)"/>
    </xsl:call-template>
  },
</xsl:for-each>
};

const struct class movement_class;
const struct GameObject *movement_type[] = {
<xsl:for-each select="Movement">
  [<xsl:value-of select="@id"/>] = (const struct GameObject *) (movement_type_list + <xsl:value-of select="@id"/>),</xsl:for-each>
};
</xsl:template>

<xsl:template name="actions">
  <xsl:param name="action"/>
  <xsl:choose>
    <xsl:when test="$action='attack'">0</xsl:when>
    <xsl:when test="$action='conquer'">1</xsl:when>
    <xsl:when test="$action='move'">2</xsl:when>
    <xsl:when test="$action='send'">3</xsl:when>
    <xsl:when test="$action='spy'">4</xsl:when>
    <xsl:otherwise>-1</xsl:otherwise>
  </xsl:choose>
</xsl:template>


<xsl:template match="trades">
/********************** trade types *********************/

<xsl:for-each select="trade">
<xsl:variable name="wid" select="position()-1"/>
<xsl:for-each select="tradeimpacts/tradeimpact">
<xsl:variable name="uid" select="concat($wid,'_',position()-1)"/>
<!-- effect arrays for the impacts -->
<xsl:if test="count(resources/resource)">
static const struct ImpactEffect trade_resources_<xsl:value-of select="$uid"/>[MAX_RESOURCE] = {
<xsl:for-each select="resources/resource">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>

<xsl:if test="count(buildings/building)">
static const struct ImpactEffect trade_buildings_<xsl:value-of select="$uid"/>[MAX_BUILDING] = {
<xsl:for-each select="buildings/building">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>

<xsl:if test="count(sciences/science)">
static const struct ImpactEffect trade_sciences_<xsl:value-of select="$uid"/>[MAX_SCIENCE] = {
<xsl:for-each select="sciences/science">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>

<xsl:if test="count(defenseSystems/defenseSystem)">
static const struct ImpactEffect trade_defenseSystems_<xsl:value-of select="$uid"/>[MAX_DEFENSESYSTEM] = {
<xsl:for-each select="defenseSystems/defenseSystem">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>

<xsl:if test="count(units/unit)">
static const struct ImpactEffect trade_units_<xsl:value-of select="$uid"/>[MAX_UNIT] = {
<xsl:for-each select="units/unit">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>

<xsl:if test="count(effects/effect)">
static const struct ImpactEffect trade_effects_<xsl:value-of select="$uid"/>[MAX_EFFECT] = {
<xsl:for-each select="effects/effect">
<xsl:call-template name="impact_effect"/>
</xsl:for-each>
};
</xsl:if>
</xsl:for-each>

<xsl:if test="count(tradeimpacts/tradeimpact)">
static const struct WonderImpact trade_impacts_<xsl:value-of select="$wid"/>[] = {
<xsl:for-each select="tradeimpacts/tradeimpact">
<xsl:variable name="uid" select="concat($wid,'_',position()-1)"/>
  {
    .duration = <xsl:value-of select="@duration"/>,
    .delay    = <xsl:value-of select="@delay"/>,
    .steal    = 0,

    .deactivateTearDown = <xsl:value-of select="@deactivateTearDown"/>,

    .targetMessage = "<xsl:apply-templates select="targetMessage"/>",
    .targetMessageType = WONDER_MESSAGE_<xsl:value-of select="targetMessage/@messageType"/>,

    .sourceMessage = "",
    .sourceMessageType = WONDER_MESSAGE_none,

<xsl:if test="count(resources/resource)">
    .resources = trade_resources_<xsl:value-of select="$uid"/>,
    .resourcesAll = <xsl:value-of select="resources/@all"/>,
</xsl:if>
<xsl:if test="count(buildings/building)">
    .buildings = trade_buildings_<xsl:value-of select="$uid"/>,
    .buildingsAll = <xsl:value-of select="buildings/@all"/>,
</xsl:if>
<xsl:if test="count(sciences/science)">
    .sciences = trade_sciences_<xsl:value-of select="$uid"/>,
    .sciencesAll = <xsl:value-of select="sciences/@all"/>,
</xsl:if>
<xsl:if test="count(defenseSystems/defenseSystem)">
    .defenseSystems = trade_defenseSystems_<xsl:value-of select="$uid"/>,
    .defenseSystemsAll = <xsl:value-of select="defenseSystems/@all"/>,
</xsl:if>
<xsl:if test="count(units/unit)">
    .units = trade_units_<xsl:value-of select="$uid"/>,
    .unitsAll = <xsl:value-of select="units/@all"/>,
</xsl:if>
<xsl:if test="count(effects/effect)">
    .effects = trade_effects_<xsl:value-of select="$uid"/>,
    .effectsAll = <xsl:value-of select="effects/@all"/>
</xsl:if>
  },
</xsl:for-each>
};
</xsl:if>
</xsl:for-each>

<xsl:for-each select="trade">
<xsl:call-template name="costs">
<xsl:with-param name="name" select="concat('trade_cost_', position()-1)"/>
</xsl:call-template>
<xsl:call-template name="requirements">
<xsl:with-param name="name" select="concat('trade_req_', position()-1)"/>
</xsl:call-template>
</xsl:for-each>

static const struct Wonder trade_type_list[] = {
<xsl:for-each select="trade">
  { /* <xsl:value-of select="Name"/> */
    {
      { .class = WONDER_CLASS },

      .object_id   = <xsl:value-of select="position()-1"/>,
      .name        = {<xsl:apply-templates select="Name"/>},
      .description = {<xsl:apply-templates select="Description"/>},
      .hidden      = <xsl:value-of select="@hidden"/>
    },

    .groupid = <xsl:value-of select="count(id(@categoryId)/preceding-sibling::*)"/>,

<xsl:if test="count(tradeimpacts/tradeimpact)">
    .impacts = trade_impacts_<xsl:value-of select="position()-1"/>,
    .num_impacts = <xsl:value-of select="count(impacts/impact)"/>,
</xsl:if>
    <xsl:call-template name="get_costs">
    <xsl:with-param name="name" select="concat('trade_cost_', position()-1)"/>
    </xsl:call-template>
  },
</xsl:for-each>
};

const struct class trade_class;

const struct GameObject *trade_type[] = {
<xsl:for-each select="trade">
  (const struct GameObject *) (trade_type_list + <xsl:value-of select="position()-1"/>),</xsl:for-each>
};
</xsl:template>

</xsl:stylesheet>



