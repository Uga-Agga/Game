<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>

<xsl:template match="Heroes">&lt;?php
/*
 * hero.rules.php -
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

function init_heroTypes () {
  <xsl:apply-templates select="HeroTypes/HeroType"/>
}

?&gt;</xsl:template>


<xsl:template match="HeroType">
  
  $GLOBALS['heroTypesList']['<xsl:value-of select="count(preceding-sibling::*)"/>'] =  array(
                     'name' =&gt; '<xsl:value-of select="Name"/>',
                     'description' =&gt; "<xsl:apply-templates select="Description"/>",
                     'id' =&gt; '<xsl:value-of select="@id"/>',
                     'heroTypeID' =&gt; <xsl:value-of select="count(preceding-sibling::*)"/>,
                     'lvl_formula' =&gt; '<xsl:value-of select="lvl"/>', 
                     'lvlUp_formula' =&gt; '<xsl:value-of select="lvlUp"/>',
                     'regHP_formula' =&gt; '<xsl:value-of select="regHP"/>', 
                     'maxHP_formula' =&gt; '<xsl:value-of select="maxHP"/>',
                     'ritual' =&gt; array('ritualCost' => array(<xsl:apply-templates select="ritual/Cost"/>),
                                          'duration' => '<xsl:value-of select="ritual/@duration"/>'));
  
</xsl:template>


<xsl:template match="Cost">
'<xsl:value-of select="@id"/>' =&gt; '<xsl:apply-templates/>'
<xsl:if test="position()!=last()">,</xsl:if>
</xsl:template>

<xsl:template match="Description"><xsl:apply-templates/></xsl:template>
<xsl:template match="p">&lt;p&gt;<xsl:value-of select="normalize-space()"/>&lt;/p&gt;</xsl:template>

</xsl:stylesheet>
