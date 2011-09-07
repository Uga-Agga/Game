<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>

<!-- select wonders -->
<xsl:template match="Config">
  <xsl:apply-templates select="trades"/>
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

<xsl:template match="trades">&lt;?php
/********************** Tradetypes *********************/
class Trade {
  var $tradeID;
  var $name;
  var $description;
  var $nodocumentation;
  var $category;
  
  var $impactList;

  var $resourceProductionCost;
  var $unitProductionCost;
  var $buildingProductionCost;

  function Trade() {
    $this-&gt;tradeID         = 0;
    $this-&gt;name            = "";
    $this-&gt;description     = "";
    $this-&gt;nodocumentation = 0;
    $this-&gt;category        = 0;
    $this-&gt;impactList             = array();
    $this-&gt;resourceProductionCost = array();
    $this-&gt;unitProductionCost     = array();
    $this-&gt;buildingProductionCost = array();
  }
}

$tradeTypeList = array();

function init_Trades(){
  global $tradeTypeList;
  <xsl:apply-templates select="trade"/>
}

class TradeCategories {
  var $id;
  var $name;
  var $secondsbetween;

  function TradeCategories() {
     $this-&gt;id             = "";
     $this-&gt;name           = "";
     $this-&gt;secondsbetween = 0;
  }
}

$tradeCategoriesTypeList = array();

function init_TradeCategories() {
  global $tradeCategoriesTypeList;
  <xsl:apply-templates select="tradecategories/tradecategory"/>
}

init_Trades();
init_TradeCategories();


?&gt;
</xsl:template>

<xsl:template match="trades/tradecategories/tradecategory">
  $tmp = new TradeCategories();
  $tmp-&gt;id            = "<xsl:apply-templates select="@id"/>";
  $tmp-&gt;name          = "<xsl:apply-templates select="@name"/>";
  $tmp-&gt;secondsbetween = <xsl:apply-templates select="@secondsbetween"/>;

  $tradeCategoriesTypeList["<xsl:apply-templates select="@id"/>"] = $tmp;

</xsl:template>



<!-- ***** Text elements ************************************************** -->
<xsl:strip-space elements="Name targetMessage Description p"/>
<xsl:template match="p">&lt;p&gt;<xsl:apply-templates/>&lt;/p&gt;</xsl:template>
<xsl:template match="trades/trade">

  // <xsl:value-of select="Name"/>
  $tmp = new Trade();

  $tmp-&gt;tradeID          = <xsl:value-of select="count(preceding-sibling::*)"/>;
  $tmp-&gt;name             = "<xsl:value-of select="Name"/>";
  $tmp-&gt;nodocumentation  = <xsl:apply-templates select="@hidden"/>;
  $tmp-&gt;category         = "<xsl:apply-templates select="@categoryId"/>";
  $tmp-&gt;description      = <xsl:apply-templates select="Description"/>; 
  $tmp-&gt;impactList             = array(<xsl:apply-templates select="tradeimpacts/tradeimpact"/>);
  $tmp-&gt;resourceProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Resource']"/>);
  $tmp-&gt;unitProductionCost     = array(<xsl:apply-templates select="Cost[name(id(@id))='Unit']"/>);
  $tmp-&gt;buildingProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Building']"/>);
  $tradeTypeList[<xsl:value-of select="count(preceding-sibling::*)"/>] = $tmp;

</xsl:template>

<!--IMPACTS-->
<xsl:template match="tradeimpact"><xsl:value-of select="position()-1"/> =>
    array('delay'    => <xsl:value-of select="@delay"/>,
          'duration' => 0<!--erstmal IMMER 0 <xsl:value-of select="@duration"/>-->,
          'deactivateTearDown' => <xsl:value-of select="@deactivateTearDown"/>,
          'targetMessage' => <xsl:apply-templates select="targetMessage"/>,
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
          'potions'  => array(<xsl:apply-templates select="potions/potion" />),
         )<xsl:if test="position()!=last()">,
                          </xsl:if>
</xsl:template>



<xsl:template match="Description">"<xsl:apply-templates />"</xsl:template>


<!--Target+SourceMessage-->
<xsl:template match="targetMessage">array('type' => "<xsl:value-of select="@messageType"/>",
                                 'message' => "<xsl:apply-templates />")</xsl:template>

<!--Effects-->
<xsl:template match="effect|resource|unit|building|science|defenseSystem|potion"><xsl:value-of select="count(id(@id)/preceding-sibling::*)"/> =>
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
