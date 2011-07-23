<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>
<xsl:strip-space elements="*"/>

<xsl:template match="tribeSystem">&lt;?php
<xsl:apply-templates select="leaderDeterminations"/>
<xsl:apply-templates select="governments"/>
?&gt;</xsl:template>

<xsl:template match="tribeSystem/leaderDeterminations"><xsl:apply-templates select="leaderDetermination"/></xsl:template>

<xsl:template match="tribeSystem/leaderDeterminations/leaderDetermination">
/* ***** <xsl:apply-templates select="name"/> ***** */
$leaderDeterminationList[<xsl:value-of select="@leaderDeterminationID"/>]['leaderDeterminationID']  = "<xsl:value-of select="@leaderDeterminationID"/>";
$leaderDeterminationList[<xsl:value-of select="@leaderDeterminationID"/>]['name']        = "<xsl:apply-templates select="name"/>";
$leaderDeterminationList[<xsl:value-of select="@leaderDeterminationID"/>]['description'] = "<xsl:apply-templates select="description"/>";
</xsl:template>

<xsl:template match="tribeSystem/governments"><xsl:apply-templates select="government"/></xsl:template>

<xsl:template match="tribeSystem/governments/government">
/* ***** <xsl:apply-templates select="name"/> ***** */
$governmentList[<xsl:value-of select="@governmentID"/>]['governmentID']  = "<xsl:value-of select="@governmentID"/>";
$governmentList[<xsl:value-of select="@governmentID"/>]['name']        = "<xsl:apply-templates select="name"/>";
$governmentList[<xsl:value-of select="@governmentID"/>]['resref']      = "<xsl:apply-templates select="resref"/>";
$governmentList[<xsl:value-of select="@governmentID"/>]['leaderDeterminationID']      = "<xsl:apply-templates select="leaderDeterminationID"/>";
$governmentList[<xsl:value-of select="@governmentID"/>]['description'] = "<xsl:apply-templates select="description"/>";
$governmentList[<xsl:value-of select="@governmentID"/>]['effects']     = array(<xsl:apply-templates select="effects"/>);
</xsl:template>

<xsl:template match="name"><xsl:value-of select="."/></xsl:template>
<xsl:template match="resref"><xsl:value-of select="."/></xsl:template>
<xsl:template match="leaderDeterminationID"><xsl:value-of select="."/></xsl:template>
<xsl:template match="description"><xsl:apply-templates/></xsl:template>
<xsl:template match="p">&lt;p&gt;<xsl:value-of select="normalize-space()"/>&lt;/p&gt;</xsl:template>


<xsl:template match="effects"><xsl:apply-templates select="effect"/></xsl:template>

<xsl:template match="effect">array('effectID' => <xsl:value-of select="@effectID"/>,
                                   'value'    => <xsl:value-of select="@value"/>,
                                   'lore'     => "<xsl:value-of select="normalize-space()"/>")<xsl:if test="position()!=last()">,
                                        </xsl:if>
</xsl:template>
</xsl:stylesheet>
