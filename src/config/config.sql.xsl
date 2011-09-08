<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>
<xsl:template match="Config">
#sql-dump that inserts required fields from the main config xml

#Artefact_class
#effects

ALTER TABLE `Artefact_class`
<xsl:apply-templates select="//EffectType"/>;


#Artefact_rituals
#resources
#buildings
#units
#defenses

ALTER TABLE `Artefact_rituals`
<xsl:apply-templates select="//Building|//DefenseSystem|//Resource|//Unit">
<xsl:sort select="name()"/>
<xsl:sort select="@id"/>
</xsl:apply-templates>;


#Cave
#resources
#resources_delta
#buildings
#defenses
#sciences
#units
#effects

ALTER TABLE `Cave`
<xsl:apply-templates select="//Building|//DefenseSystem|//Resource|//Science|//Unit">
<xsl:sort select="name()"/>
<xsl:sort select="@id"/>
<xsl:with-param name="delta" select="true()"/>
</xsl:apply-templates>,
<xsl:apply-templates select="//EffectType"/>;


#Cave_takeover
#resources

ALTER TABLE `Cave_takeover`
<xsl:apply-templates select="//Resource">
<xsl:sort select="name()"/>
<xsl:sort select="@id"/>
</xsl:apply-templates>;


#Event_movement
#units
#resources

ALTER TABLE `Event_movement`
<xsl:apply-templates select="//Resource|//Unit">
<xsl:sort select="name()"/>
<xsl:sort select="@id"/>
</xsl:apply-templates>;


#Event_wonderEnd
#resources
#buildings
#defenses
#sciences
#units
#effects

ALTER TABLE `Event_wonderEnd`
<xsl:apply-templates select="//Building|//DefenseSystem|//Resource|//Science|//Unit">
<xsl:sort select="name()"/>
<xsl:sort select="@id"/>
</xsl:apply-templates>,
<xsl:apply-templates select="//EffectType"/>;

#Event_weatherEnd

ALTER TABLE `Event_weatherEnd`
<xsl:apply-templates select="//EffectType"/>;


#Hero_new
#effects

ALTER TABLE `Hero_new`
<xsl:apply-templates select="//EffectType"/>;


#Hero_rituals
#resources
#buildings
#units
#defenses

ALTER TABLE `Hero_rituals`
<xsl:apply-templates select="//Building|//DefenseSystem|//Resource|//Unit">
<xsl:sort select="name()"/>
<xsl:sort select="@id"/>
</xsl:apply-templates>;


#Player
#sciences

ALTER TABLE `Player`
<xsl:apply-templates select="//Science|//Potion">
<xsl:sort select="name()"/>
<xsl:sort select="@id"/>
</xsl:apply-templates>;


#Questionnaire_presents
#resources
#defenses
#units

ALTER TABLE `Questionnaire_presents`
<xsl:apply-templates select="//DefenseSystem|//Resource|//Unit">
<xsl:sort select="name()"/>
<xsl:sort select="@id"/>
</xsl:apply-templates>;


#StatisticUnit
#units

ALTER TABLE `StatisticUnit`
<xsl:apply-templates select="//Unit">
<xsl:sort select="name()"/>
<xsl:sort select="@id"/>
</xsl:apply-templates>;

</xsl:template>

<xsl:template match="Resource">
<xsl:param name="delta"/>
ADD `<xsl:value-of select="@id"/>` INTEGER NOT NULL default '0'<xsl:if test="$delta">,
ADD `<xsl:value-of select="@id"/>_delta` INTEGER NOT NULL default '0'</xsl:if>
<xsl:if test="position()!=last()">,</xsl:if>
</xsl:template>

<xsl:template match="Building|DefenseSystem|Science|Unit|Potion">
ADD `<xsl:value-of select="@id"/>` INTEGER NOT NULL default '0'<xsl:if test="position()!=last()">,</xsl:if>
</xsl:template>

<xsl:template match="EffectType">
ADD `<xsl:value-of select="@id"/>` DECIMAL(8,3) NOT NULL DEFAULT '0.00'<xsl:if test="position()!=last()">,</xsl:if>
</xsl:template>


</xsl:stylesheet>
