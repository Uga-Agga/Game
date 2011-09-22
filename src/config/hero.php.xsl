<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>

<xsl:template match="Heroes">&lt;?php
global $heroTypesList;
global $heroSkillTypeList;


function init_heroTypes () {
  global $heroTypesList;
  
  <xsl:apply-templates select="HeroTypes/HeroType"/>
}

function init_heroSkills() {
  global $heroSkillTypeList;
  
  <xsl:apply-templates select="HeroSkills/HeroSkill"/>

}
?&gt;</xsl:template>


<xsl:template match="HeroType">
  
  $heroTypesList['<xsl:value-of select="count(preceding-sibling::*)"/>'] =  array(
                     'name' =&gt; '<xsl:value-of select="Name"/>',
                     'description' =&gt; "<xsl:apply-templates select="Description"/>",
                     'id' =&gt; '<xsl:value-of select="@id"/>',
                     'heroTypeID' =&gt; <xsl:value-of select="count(preceding-sibling::*)"/>,
                     'lvl' =&gt; '<xsl:value-of select="lvl"/>', 
                     'force' =&gt; '<xsl:value-of select="force"/>', 
                     'regHP' =&gt; '<xsl:value-of select="regHP"/>', 
                     'maxHpLvl' =&gt; '<xsl:value-of select="maxHP"/>',
                     'effects' =&gt; array(<xsl:apply-templates select="effects/effect"/>));
  
</xsl:template>

<xsl:template match="HeroSkill">

  $heroSkillTypeList['<xsl:value-of select="@id"/>'] = array(
                     'name' =&gt; '<xsl:value-of select="Name"/>',
                     'description' =&gt; "<xsl:apply-templates select="Description"/>",
                     'id' =&gt; '<xsl:value-of select="@id"/>',
                     'costTP' =&gt; '<xsl:apply-templates select="CostTP"/>',
                     'requiredLevel' =&gt; '<xsl:apply-templates select="RequiredLevel"/>');
                     
</xsl:template>

<xsl:template match="effect">
'<xsl:value-of select="@id"/>' =&gt; array('absolute' =&gt; <xsl:value-of select="@absolute"/>,
                                         'relative' =&gt; <xsl:value-of select="@relative"/>,
                                         'maxDelta' =&gt; <xsl:value-of select="@maxDelta"/>,
                                         'type' =&gt; '<xsl:value-of select="@type"/>')
                                          <xsl:if test="position()!=last()">,
                                          </xsl:if></xsl:template>

<xsl:template match="Description"><xsl:apply-templates/></xsl:template>
<xsl:template match="p">&lt;p&gt;<xsl:value-of select="normalize-space()"/>&lt;/p&gt;</xsl:template>

</xsl:stylesheet>