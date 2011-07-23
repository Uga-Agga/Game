<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>
<xsl:strip-space elements="*"/>

<xsl:template match="relations">&lt;?php<xsl:apply-templates select="relation"/>?&gt;</xsl:template>

<xsl:template match="relations/relation">
/* ***** <xsl:apply-templates select="name"/> ***** */
$relationList[<xsl:value-of select="@relationID"/>]['relationID']  = <xsl:value-of select="@relationID"/>;
$relationList[<xsl:value-of select="@relationID"/>]['name']        = "<xsl:apply-templates select="name"/>";
$relationList[<xsl:value-of select="@relationID"/>]['targetSizeDiffDown'] = "<xsl:apply-templates select="@targetSizeDiffDown"/>";
$relationList[<xsl:value-of select="@relationID"/>]['targetSizeDiffUp'] = "<xsl:apply-templates select="@targetSizeDiffUp"/>";
$relationList[<xsl:value-of select="@relationID"/>]['minTimeForForceSurrenderHours'] = "<xsl:apply-templates select="@minTimeForForceSurrenderHours"/>";
$relationList[<xsl:value-of select="@relationID"/>]['maxTimeForForceSurrenderHours'] = "<xsl:apply-templates select="@maxTimeForForceSurrenderHours"/>";
$relationList[<xsl:value-of select="@relationID"/>]['startRelativeWarPointsForForceSurrender'] = "<xsl:apply-templates select="startRelativeWarPointsForForceSurrender"/>";
$relationList[<xsl:value-of select="@relationID"/>]['dontLeaveTribe'] = "<xsl:apply-templates select="@dontLeaveTribe"/>";
$relationList[<xsl:value-of select="@relationID"/>]['storeTargetMembers'] = "<xsl:apply-templates select="@storeTargetMembers"/>";
$relationList[<xsl:value-of select="@relationID"/>]['attackerReceivesFame'] = "<xsl:apply-templates select="@attackerReceivesFame"/>";
$relationList[<xsl:value-of select="@relationID"/>]['defenderReceivesFame'] = "<xsl:apply-templates select="@defenderReceivesFame"/>";
$relationList[<xsl:value-of select="@relationID"/>]['fameUpdate'] = "<xsl:apply-templates select="@fameUpdate"/>";
$relationList[<xsl:value-of select="@relationID"/>]['description'] = "<xsl:apply-templates select="description"/>";
$relationList[<xsl:value-of select="@relationID"/>]['transitions'] = array(<xsl:apply-templates select="transitions"/>);
<xsl:if test="count(historyMessage)>0">$relationList[<xsl:value-of select="@relationID"/>]['historyMessage']="<xsl:apply-templates select="historyMessage"/>";</xsl:if>
$relationList[<xsl:value-of select="@relationID"/>]['otherSideTo'] = <xsl:if test="count(otherSideTo)=0">-1</xsl:if><xsl:apply-templates select="otherSideTo"/>;
$relationList[<xsl:value-of select="@relationID"/>]['onDeletionSwitchTo'] = <xsl:if test="count(onDeletionSwitchTo)=0">-1</xsl:if><xsl:apply-templates select="onDeletionSwitchTo"/>;
$relationList[<xsl:value-of select="@relationID"/>]['attackerMultiplicator']        = <xsl:apply-templates select="attackerMultiplicator"/>;
$relationList[<xsl:value-of select="@relationID"/>]['defenderMultiplicator']        = <xsl:apply-templates select="defenderMultiplicator"/>;
$relationList[<xsl:value-of select="@relationID"/>]['isNoRelation'] = "<xsl:apply-templates select="@isNoRelation"/>";
$relationList[<xsl:value-of select="@relationID"/>]['isUltimatum'] = "<xsl:apply-templates select="@isUltimatum"/>";
$relationList[<xsl:value-of select="@relationID"/>]['isWar'] = "<xsl:apply-templates select="@isWar"/>";
$relationList[<xsl:value-of select="@relationID"/>]['isWarLost'] = "<xsl:apply-templates select="@isWarLost"/>";
$relationList[<xsl:value-of select="@relationID"/>]['isWarWon'] = "<xsl:apply-templates select="@isWarWon"/>";
$relationList[<xsl:value-of select="@relationID"/>]['isNonaggressionPact'] = "<xsl:apply-templates select="@isNonaggressionPact"/>";
$relationList[<xsl:value-of select="@relationID"/>]['isAlly'] = "<xsl:apply-templates select="@isAlly"/>";
$relationList[<xsl:value-of select="@relationID"/>]['isPrepareForWar'] = "<xsl:apply-templates select="@isPrepareForWar"/>";
$relationList[<xsl:value-of select="@relationID"/>]['isWarAlly'] = "<xsl:apply-templates select="@isWarAlly"/>";
</xsl:template>

<xsl:template match="name"><xsl:value-of select="."/></xsl:template>
<xsl:template match="description"><xsl:apply-templates/></xsl:template>
<xsl:template match="otherSideTo"><xsl:apply-templates/></xsl:template>
<xsl:template match="attackerMultiplicator"><xsl:apply-templates/></xsl:template>
<xsl:template match="defenderMultiplicator"><xsl:apply-templates/></xsl:template>
<xsl:template match="p">&lt;p&gt;<xsl:value-of select="normalize-space()"/>&lt;/p&gt;</xsl:template>

<xsl:template match="transitions"><xsl:apply-templates select="transition"/></xsl:template>

<xsl:template match="transition"><xsl:value-of select="@relationID"/> => array('relationID' => <xsl:value-of select="@relationID"/>,
                                              'time'    => <xsl:value-of select="@time"/>)<xsl:if test="position()!=last()">,
                                        </xsl:if>
</xsl:template>
</xsl:stylesheet>





