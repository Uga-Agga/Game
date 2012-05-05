<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>

<!-- select wonders -->
<xsl:template match="Config">
  <xsl:apply-templates select="wonders"/>
  <xsl:apply-templates select="WonderCategories"/>
  <xsl:apply-templates select="Weathers"/>
</xsl:template>

<!-- object -->
<xsl:template match="object">[<xsl:choose>
<xsl:when test="name(id(@id))='Resource'">R</xsl:when>
<xsl:when test="name(id(@id))='Building'">B</xsl:when>
<xsl:when test="name(id(@id))='Science'">S</xsl:when>
<xsl:when test="name(id(@id))='DefenseSystem'">D</xsl:when>
<xsl:when test="name(id(@id))='Unit'">U</xsl:when>
<xsl:when test="name(id(@id))='EffectType'">E</xsl:when>
</xsl:choose><xsl:value-of select="count(id(@id)/preceding-sibling::*)"/>.ACT]</xsl:template>

<!-- ***** WEATHERS ******************************************************* -->
<xsl:template match="Weathers">&lt;?php
/********************** Weathertypes *********************/
class Weather {
  var $weatherID;
  var $name;
  var $impactList;
  function Weather() {
    $this-&gt;weatherID        = 0;
    $this-&gt;name            = "";

    $this-&gt;impactList    = array();
  }
}
function init_Weathers(){
  <xsl:apply-templates select="Weather"/>
}
?&gt;
</xsl:template>

<!-- ***** Wonder Category ************************************************ -->
<xsl:template match="WonderCategories">&lt;?php
/********************** Wonder Category *****************/
class WonderCategory {
  var $id;
  var $sortID;
  var $name;

  function WonderCategory() {
     $this-&gt;id             = "";
     $this-&gt;sortID         = "";
     $this-&gt;name           = "";
  }
}

function init_WonderCategories() {
  <xsl:apply-templates select="WonderCategory"/>
}
?&gt;
</xsl:template>

<xsl:template match="WonderCategories/WonderCategory">
  $tmp = new WonderCategory();
  $tmp-&gt;id            = "<xsl:apply-templates select="@id"/>";
  $tmp-&gt;sortID        = <xsl:value-of select="count(preceding-sibling::*)"/>;
  $tmp-&gt;name          = "<xsl:apply-templates select="@name"/>";

  $GLOBALS['wonderCategoryTypeList']["<xsl:apply-templates select="@id"/>"] = $tmp;

</xsl:template>

<!-- ***** Wonder ********************************************************* -->
<xsl:template match="Weathers/Weather">
  // <xsl:value-of select="Name"/>
  $tmp = new Weather();

  $tmp-&gt;weatherID       = <xsl:value-of select="count(preceding-sibling::*)"/>;
  $tmp-&gt;name            = "<xsl:value-of select="Name"/>";

  $tmp-&gt;impactList = array(<xsl:apply-templates select="we_impacts/we_impact"/>);

  $GLOBALS['weatherTypeList'][<xsl:value-of select="count(preceding-sibling::*)"/>] = $tmp;

</xsl:template>
<!-- ***** WONDERS ******************************************************** -->
<xsl:template match="wonders">&lt;?php
/*
 * wonder.rules.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011-2012  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/********************** Wondertypes *********************/
class Wonder {
  var $wonderID;
  var $wonderCategory;
  var $name;
  var $description;
  var $remark;
  var $offensiveness;
  var $target;
  var $chance;
  var $nodocumentation;
  var $groupID;
  var $isTribeWonder;
  
  var $impactList;

  var $resourceProductionCost;
  var $unitProductionCost;
  var $buildingProductionCost;

  var $buildingDepList;
  var $maxBuildingDepList;

  var $defenseSystemDepList;
  var $maxDefenseSystemDepList;

  var $resourceDepList;
  var $maxResourceDepList;

  var $scienceDepList;
  var $maxScienceDepList;

  var $unitDepList;
  var $maxUnitDepList;

  var $effectDepList;
  var $maxEffectDepList;

  function Wonder() {
    $this-&gt;wonderID        = 0;
    $this-&gt;wonderCategory  = "";
    $this-&gt;name            = "";
    $this-&gt;description     = "";
    $this-&gt;remark          = "";
    $this-&gt;offensiveness   = "";
    $this-&gt;target          = "";
    $this-&gt;chance          = "";
    $this-&gt;nodocumentation = 0;
    $this-&gt;groupID         = 0;
    $this-&gt;isTribeWonder   = 0;
    
    $this-&gt;impactList    = array();

    $this-&gt;resourceProductionCost  = array();
    $this-&gt;unitProductionCost = array();

    $this-&gt;buildingDepList    = array();
    $this-&gt;maxBuildingDepList = array();

    $this-&gt;defenseSystemDepList    = array();
    $this-&gt;maxDefenseSystemDepList = array();

    $this-&gt;resourceDepList    = array();
    $this-&gt;maxResourceDepList = array();

    $this-&gt;scienceDepList    = array();
    $this-&gt;maxScienceDepList = array();

    $this-&gt;unitDepList    = array();
    $this-&gt;maxUnitDepList = array();

    $this-&gt;effectDepList    = array();
    $this-&gt;maxEffectDepList = array();

  }
}

function init_Wonders(){
  <xsl:apply-templates select="wonder"/>
}
?&gt;
</xsl:template>

<!-- ***** Text elements ************************************************** -->
<xsl:strip-space elements="Name Description targetMessage sourceMessage p"/>
<xsl:template match="p">&lt;p&gt;<xsl:apply-templates/>&lt;/p&gt;</xsl:template>
<xsl:template match="Description"><xsl:apply-templates/></xsl:template>

<!-- ***** Wonder ********************************************************* -->
<xsl:template match="wonders/wonder">
  // <xsl:value-of select="Name"/>
  $tmp = new Wonder();

  $tmp-&gt;wonderID        = <xsl:value-of select="count(preceding-sibling::*)"/>;
  $tmp-&gt;wonderCategory  = "<xsl:value-of select="@WonderCategory"/>";
  $tmp-&gt;name            = "<xsl:value-of select="Name"/>";
  $tmp-&gt;description     = "<xsl:apply-templates select="Description[@lang='de_DE']"/>";
  $tmp-&gt;remark          = "<xsl:apply-templates select="Remark[@lang='de_DE']"/>";
<xsl:choose>
<xsl:when test="@offensive='1'">
  $tmp-&gt;offensiveness   = 'offensive';
</xsl:when><xsl:otherwise>
  $tmp-&gt;offensiveness   = 'defensive';
</xsl:otherwise>
</xsl:choose>
  $tmp-&gt;target          = "<xsl:apply-templates select="@target"/>";
  $tmp-&gt;chance          = "<xsl:apply-templates select="chance"/>";
  $tmp-&gt;nodocumentation = <xsl:apply-templates select="@hidden"/>;
  $tmp-&gt;groupID         = <xsl:apply-templates select="@groupID"/>;
  $tmp-&gt;isTribeWonder   = <xsl:apply-templates select="@isTribeWonder"/>;
  
  $tmp-&gt;impactList = array(<xsl:apply-templates select="impacts/impact"/>);

  $tmp-&gt;resourceProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Resource']"/>);
  $tmp-&gt;unitProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Unit']"/>);
  $tmp-&gt;buildingProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Building']"/>);


  $tmp-&gt;buildingDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Building']" mode="min"/>);
  $tmp-&gt;maxBuildingDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Building']" mode="max"/>);

  $tmp-&gt;defenseSystemDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='DefenseSystem']" mode="min"/>);
  $tmp-&gt;maxDefenseSystemDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='DefenseSystem']" mode="max"/>);

  $tmp-&gt;resourceDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Resource']" mode="min"/>);
  $tmp-&gt;maxResourceDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Resource']" mode="max"/>);

  $tmp-&gt;scienceDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Science']" mode="min"/>);
  $tmp-&gt;maxScienceDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Science']" mode="max"/>);

  $tmp-&gt;unitDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Unit']" mode="min"/>);
  $tmp-&gt;maxUnitDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Unit']" mode="max"/>);

  $tmp-&gt;effectDepList    = array(<xsl:apply-templates select="EffectReq[name(id(@id))='EffectType']" mode="min"/>);
  $tmp-&gt;maxEffectDepList = array(<xsl:apply-templates select="EffectReq[name(id(@id))='EffectType']" mode="max"/>);

  $GLOBALS['wonderTypeList'][<xsl:value-of select="count(preceding-sibling::*)"/>] = $tmp;

</xsl:template>

<!--IMPACTS-->
<xsl:template match="impact"><xsl:value-of select="position()-1"/> =>
    array('delay'    => <xsl:value-of select="@delay"/>,
          'duration' => <xsl:value-of select="@duration"/>,
          'steal' => <xsl:value-of select="@steal"/>,
          'deactivateTearDown' => <xsl:value-of select="@deactivateTearDown"/>,
          'targetMessage' => <xsl:apply-templates select="targetMessage"/>,
          'sourceMessage' => <xsl:apply-templates select="sourceMessage"/>,
          'effects'   => array(<xsl:apply-templates select="effects/effect" />),
<xsl:if test="effects/@all>0">    'effectsAll'=> <xsl:value-of select="effects/@all" />,</xsl:if>
          'resources' => array(<xsl:apply-templates select="resources/resource" />),
<xsl:if test="resources/@all>0">    'resourcesAll'=> <xsl:value-of select="resources/@all" />,</xsl:if>
          'units'     => array(<xsl:apply-templates select="units/unit" />),
<xsl:if test="units/@all>0">    'unitsAll'=> <xsl:value-of select="units/@all" />,</xsl:if>
          'buildings' => array(<xsl:apply-templates select="buildings/building" />),
<xsl:if test="buildings/@all>0">    'buildingsAll'=> <xsl:value-of select="buildings/@all" />,</xsl:if>
          'sciences'  => array(<xsl:apply-templates select="sciences/science" />),
<xsl:if test="sciences/@all>0">   'sciencesAll'=> <xsl:value-of select="sciences/@all" />,</xsl:if>
          'defenseSystems' => array(<xsl:apply-templates select="defenseSystems/defenseSystem" />),
<xsl:if test="defenseSystems/@all>0">   'defenseSystemsAll'=> <xsl:value-of select="defenseSystems/@all" />,</xsl:if>
         )<xsl:if test="position()!=last()">,
                          </xsl:if>
</xsl:template>

<!--WETTER IMPACTS-->
<xsl:template match="we_impact"><xsl:value-of select="position()-1"/> =>
    array('delay'    => <xsl:value-of select="@delay"/>,
          'duration' => <xsl:value-of select="@duration"/>,
          'steal' => <xsl:value-of select="@steal"/>,
          'effects'   => array(<xsl:apply-templates select="effects/effect" />),
<xsl:if test="effects/@all>0">    'effectsAll'=> <xsl:value-of select="effects/@all" />,</xsl:if>
         )<xsl:if test="position()!=last()">,
                          </xsl:if>
</xsl:template>


<!--Target+SourceMessage-->
<xsl:template match="targetMessage">array('type' => "<xsl:value-of select="@messageType"/>",
                                 'message' => "<xsl:apply-templates />")</xsl:template>
<xsl:template match="sourceMessage">array('type' => "<xsl:value-of select="@messageType"/>",
                                 'message' => "<xsl:apply-templates />")</xsl:template>

<!--Effects-->
<xsl:template match="effect|resource|unit|building|science|defenseSystem"><xsl:value-of select="count(id(@id)/preceding-sibling::*)"/> =>
                              array('absolute' => <xsl:value-of select="@absolute"/>,
                                    'relative' => "<xsl:value-of select="@relative"/>",
                                    'maxDelta' => "<xsl:value-of select="@maxDelta"/>",
                                    'type'     => "<xsl:value-of select="@type"/>")<xsl:if test="position()!=last()">,
                           </xsl:if>
</xsl:template>

<!--ProductionCosts-->
<xsl:template match="Cost"><xsl:value-of select="count(id(@id)/preceding-sibling::*)"/> => '<xsl:value-of select="."/>'<xsl:if test="position()!=last()">, </xsl:if>
</xsl:template>

<!-- Requirement -->
<xsl:template match="Requirement|EffectReq" mode="min">
<xsl:value-of select="count(id(@id)/preceding-sibling::*)"/> => <xsl:choose><xsl:when test="@min"><xsl:value-of select="@min"/></xsl:when><xsl:otherwise>0</xsl:otherwise></xsl:choose>,
</xsl:template>

<xsl:template match="Requirement|EffectReq" mode="max">
<xsl:value-of select="count(id(@id)/preceding-sibling::*)"/> => <xsl:choose><xsl:when test="@max"><xsl:value-of select="@max"/></xsl:when><xsl:otherwise>-1</xsl:otherwise></xsl:choose>,
</xsl:template>

</xsl:stylesheet>
