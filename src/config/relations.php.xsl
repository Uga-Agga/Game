<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>
<xsl:strip-space elements="*"/>

<xsl:template match="relations">&lt;?php
/*
 * relations.list.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');
<xsl:apply-templates select="relation"/>?&gt;</xsl:template>

<xsl:template match="relations/relation">
/* ***** <xsl:apply-templates select="name"/> ***** */
$GLOBALS['relationList'][<xsl:value-of select="@relationID"/>] = array(
  'relationID' => <xsl:value-of select="@relationID"/>,
  'name' => "<xsl:apply-templates select="name"/>",
  'targetSizeDiffDown' => "<xsl:apply-templates select="@targetSizeDiffDown"/>",
  'targetSizeDiffUp' => "<xsl:apply-templates select="@targetSizeDiffUp"/>",
  'minTimeForForceSurrenderHours' => "<xsl:apply-templates select="@minTimeForForceSurrenderHours"/>",
  'maxTimeForForceSurrenderHours' => "<xsl:apply-templates select="@maxTimeForForceSurrenderHours"/>",
  'startRelativeWarPointsForForceSurrender' => "<xsl:apply-templates select="startRelativeWarPointsForForceSurrender"/>",
  'dontLeaveTribe' => "<xsl:apply-templates select="@dontLeaveTribe"/>",
  'storeTargetMembers' => "<xsl:apply-templates select="@storeTargetMembers"/>",
  'attackerReceivesFame' => "<xsl:apply-templates select="@attackerReceivesFame"/>",
  'defenderReceivesFame' => "<xsl:apply-templates select="@defenderReceivesFame"/>",
  'fameUpdate' => "<xsl:apply-templates select="@fameUpdate"/>",
  'description' => "<xsl:apply-templates select="description"/>",
  'transitions' => array(<xsl:apply-templates select="transitions"/>),
  <xsl:if test="count(historyMessage)>0">
  'historyMessage' => "<xsl:apply-templates select="historyMessage"/>",
  </xsl:if>
  'otherSideTo' => <xsl:if test="count(otherSideTo)=0">-1</xsl:if><xsl:apply-templates select="otherSideTo"/>,
  'onDeletionSwitchTo' => <xsl:if test="count(onDeletionSwitchTo)=0">-1</xsl:if><xsl:apply-templates select="onDeletionSwitchTo"/>,
  'attackerMultiplicator' => <xsl:apply-templates select="attackerMultiplicator"/>,
  'defenderMultiplicator' => <xsl:apply-templates select="defenderMultiplicator"/>,
  'isNoRelation' => "<xsl:apply-templates select="@isNoRelation"/>",
  'isUltimatum' => "<xsl:apply-templates select="@isUltimatum"/>",
  'isWar' => "<xsl:apply-templates select="@isWar"/>",
  'isWarLost' => "<xsl:apply-templates select="@isWarLost"/>",
  'isWarWon' => "<xsl:apply-templates select="@isWarWon"/>",
  'isNonaggressionPact' => "<xsl:apply-templates select="@isNonaggressionPact"/>",
  'isAlly' => "<xsl:apply-templates select="@isAlly"/>",
  'isPrepareForWar' => "<xsl:apply-templates select="@isPrepareForWar"/>",
  'isWarAlly' => "<xsl:apply-templates select="@isWarAlly"/>"
);
</xsl:template>

<xsl:template match="name"><xsl:value-of select="."/></xsl:template>
<xsl:template match="description"><xsl:apply-templates/></xsl:template>
<xsl:template match="otherSideTo"><xsl:apply-templates/></xsl:template>
<xsl:template match="attackerMultiplicator"><xsl:apply-templates/></xsl:template>
<xsl:template match="defenderMultiplicator"><xsl:apply-templates/></xsl:template>
<xsl:template match="p">&lt;p&gt;<xsl:value-of select="normalize-space()"/>&lt;/p&gt;</xsl:template>

<xsl:template match="transitions"><xsl:apply-templates select="transition"/></xsl:template>

<xsl:template match="transition">
  <xsl:value-of select="@relationID"/> => array(
    'relationID' => <xsl:value-of select="@relationID"/>,
    'time'    => <xsl:value-of select="@time"/>)
    <xsl:if test="position()!=last()">,</xsl:if>
</xsl:template>
</xsl:stylesheet>





