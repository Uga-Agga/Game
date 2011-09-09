<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>
<xsl:strip-space elements="Name Description p"/>

<xsl:template match="Config">&lt;?php
// require_once('lib/GameObjects.php');
// require_once('lib/MovementClass.php');
<xsl:apply-templates/>?&gt;
</xsl:template>

<xsl:template match="Header">
$MAX_RESOURCE = <xsl:value-of select="count(//Resource)"/>;
$MAX_BUILDING = <xsl:value-of select="count(//Building)"/>;
$MAX_SCIENCE = <xsl:value-of select="count(//Science)"/>;
$MAX_UNIT = <xsl:value-of select="count(//Unit)"/>;
$MAX_DEFENSESYSTEM = <xsl:value-of select="count(//DefenseSystem)"/>;
$TAKEOVERMAXPOPULARITYPOINTS = <xsl:value-of select="TakeoverMaxPopularityPoints"/>;
$TAKEOVERMINRESOURCEVALUE = <xsl:value-of select="TakeoverMinResourceValue"/>;
$WATCHTOWERVISIONRANGE = "<xsl:apply-templates select="WatchTowerVisionRange"/>";
$EXPOSEINVISIBLE = "<xsl:apply-templates select="ExposeInvisible"/>";
$WONDERRESISTANCE = "<xsl:apply-templates select="WonderResistance"/>";
$FUELRESOURCEID = <xsl:value-of select="FuelResourceID"/>;
$MOVEMENTCOSTCONSTANT = "<xsl:apply-templates select="MovementCost"/>";
$MOVEMENTSPEEDCONSTANT = "<xsl:apply-templates select="MovementSpeed"/>";
</xsl:template>

<xsl:template match="p">&lt;p&gt;<xsl:apply-templates/>&lt;/p&gt;</xsl:template>
<xsl:template match="Description"><xsl:apply-templates/></xsl:template>

<!-- FIXME add language support -->
<xsl:template match="Languages"></xsl:template>

<!-- FIXME merge wonder support -->
<xsl:template match="trades"></xsl:template>
<xsl:template match="wonders"></xsl:template>
<xsl:template match="Weathers"></xsl:template>
<xsl:template match="incidentals"></xsl:template>
<xsl:template match="regimes"></xsl:template>


<!-- object -->
<xsl:template match="object">[<xsl:choose>
<xsl:when test="name(id(@id))='Resource'">R</xsl:when>
<xsl:when test="name(id(@id))='Building'">B</xsl:when>
<xsl:when test="name(id(@id))='Science'">S</xsl:when>
<xsl:when test="name(id(@id))='DefenseSystem'">D</xsl:when>
<xsl:when test="name(id(@id))='Unit'">U</xsl:when>
<xsl:when test="name(id(@id))='EffectType'">E</xsl:when>
</xsl:choose><xsl:value-of select="count(id(@id)/preceding-sibling::*)"/>.ACT]</xsl:template>

<!-- ***** RESOURCES ****************************************************** -->
<xsl:template match="ResourceTypes">
/********************** Resourcetypes *********************/
class Resource {

  var $resourceID;
  var $name;
  var $remark;
  var $dbFieldName;
  var $maxLevel;
  var $resProdFunction;

  var $ratingValue     = 0;
  var $takeoverValue   = 0;
  var $saveStorage     = 0;
  var $nodocumentation = 0;

  function Resource($resourceID, $name, $remark, $dbFieldName, $maxLevel, $resProdFunction){
    $this-&gt;resourceID      = $resourceID;
    $this-&gt;name            = $name;
    $this-&gt;remark          = $remark;
    $this-&gt;dbFieldName     = $dbFieldName;
    $this-&gt;maxLevel        = $maxLevel;
    $this-&gt;resProdFunction = $resProdFunction;
  }
}

function init_resources(){
  global $resourceTypeList;
<xsl:apply-templates select="Resource"/>
}
</xsl:template>

<xsl:template match="Resource">
  $tmp = new Resource(<xsl:value-of select="count(preceding-sibling::*)"/>, '<xsl:value-of select="Name"/>', "<xsl:apply-templates select="Remark[@lang='de_DE']"/>", '<xsl:apply-templates select="@id"/>', '<xsl:apply-templates select="MaxStorage"/>',
                      '<xsl:apply-templates select="Production"/>');
  <xsl:if test="SafeStorage!='0'">$tmp-&gt;saveStorage = '<xsl:apply-templates select="SafeStorage"/>';
  </xsl:if>
  <xsl:if test="RatingValue!=0">$tmp-&gt;ratingValue = <xsl:apply-templates select="RatingValue"/>;
  </xsl:if>
  <xsl:if test="TakeoverValue!=0">$tmp-&gt;takeoverValue = <xsl:apply-templates select="TakeoverValue"/>;
  </xsl:if>
  <xsl:if test="@hidden!=0">$tmp-&gt;nodocumentation = <xsl:apply-templates select="@hidden"/>;
  </xsl:if>
  $resourceTypeList[<xsl:value-of select="count(preceding-sibling::*)"/>] = $tmp;
</xsl:template>

<!-- ***** BUILDINGS ****************************************************** -->
<xsl:template match="BuildingTypes">
/********************** Buildingtypes *********************/
class Building {
  var $buildingID;
  var $name;
  var $description;
  var $remark;
  var $dbFieldName;
  var $position;
  var $maxLevel;
  var $productionTimeFunction;

  var $ratingValue             = 0;
  var $resourceProductionCost  = array();
  var $unitProductionCost      = array();
  var $buildingProductionCost  = array();
  var $defenseProductionCost   = array();
  var $buildingDepList         = array();
  var $maxBuildingDepList      = array();
  var $defenseSystemDepList    = array();
  var $maxDefenseSystemDepList = array();
  var $resourceDepList         = array();
  var $maxResourceDepList      = array();
  var $scienceDepList          = array();
  var $maxScienceDepList       = array();
  var $unitDepList             = array();
  var $maxUnitDepList          = array();
  var $nodocumentation         = 0;

  function Building($buildingID, $name, $description, $remark, $dbFieldName, $position, $maxLevel, $productionTimeFunction, $ratingValue){
    $this-&gt;buildingID             = $buildingID;
    $this-&gt;name                   = $name;
    $this-&gt;description            = $description;
    $this-&gt;remark                 = $remark;
    $this-&gt;dbFieldName            = $dbFieldName;
    $this-&gt;position               = $position;
    $this-&gt;maxLevel               = $maxLevel ;
    $this-&gt;productionTimeFunction = $productionTimeFunction;
    $this-&gt;ratingValue            = $ratingValue;
  }
}

function init_buildings(){

  global $buildingTypeList;
  <xsl:apply-templates select="Building"/>
}
</xsl:template>

<xsl:template match="Building">
  // <xsl:value-of select="Name"/>
  $tmp = new Building(<xsl:value-of select="count(preceding-sibling::*)"/>, '<xsl:value-of select="Name"/>',
                      "<xsl:apply-templates select="Description[@lang='de_DE']"/>",
                      "<xsl:apply-templates select="Remark[@lang='de_DE']"/>",
                      '<xsl:value-of select="@id"/>', <xsl:choose><xsl:when test="Position"><xsl:value-of select="Position"/></xsl:when><xsl:otherwise>0</xsl:otherwise></xsl:choose>, '<xsl:apply-templates select="MaxDevelopmentLevel"/>', '<xsl:apply-templates select="ProductionTime"/>', <xsl:value-of select="RatingValue"/>);
  <xsl:if test="count(Cost[name(id(@id))='Resource'])!=0">$tmp-&gt;resourceProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Resource']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='Unit'])!=0">
  $tmp-&gt;unitProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Unit']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='Building'])!=0">
  $tmp-&gt;buildingProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Building']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='DefenseSystem'])!=0">
  $tmp-&gt;defenseProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='DefenseSystem']"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Building'])!=0">
  $tmp-&gt;buildingDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Building']" mode="min"/>);
  $tmp-&gt;maxBuildingDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Building']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='DefenseSystem'])!=0">
  $tmp-&gt;defenseSystemDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='DefenseSystem']" mode="min"/>);
  $tmp-&gt;maxDefenseSystemDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='DefenseSystem']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Resource'])!=0">
  $tmp-&gt;resourceDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Resource']" mode="min"/>);
  $tmp-&gt;maxResourceDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Resource']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Science'])!=0">
  $tmp-&gt;scienceDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Science']" mode="min"/>);
  $tmp-&gt;maxScienceDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Science']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Unit'])!=0">
  $tmp-&gt;unitDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Unit']" mode="min"/>);
  $tmp-&gt;maxUnitDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Unit']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(EffectReq)!=0">
  $tmp-&gt;effectDepList    = array(<xsl:apply-templates select="EffectReq[name(id(@id))='EffectType']" mode="min"/>);
  $tmp-&gt;maxEffectDepList = array(<xsl:apply-templates select="EffectReq[name(id(@id))='EffectType']" mode="max"/>);
  </xsl:if>
  <xsl:if test="@hidden!=0">$tmp-&gt;nodocumentation = <xsl:apply-templates select="@hidden"/>;
  </xsl:if>
  $buildingTypeList[<xsl:value-of select="count(preceding-sibling::*)"/>] = $tmp;
</xsl:template>

<xsl:template match="ScienceTypes">
/********************** Sciencetypes *********************/
class Science {
  var $scienceID;
  var $name;
  var $description;
  var $dbFieldName;
  var $position;
  var $maxLevel;
  var $productionTimeFunction;

  var $resourceProductionCost  = array();
  var $unitProductionCost      = array();
  var $buildingProductionCost  = array();
  var $defenseProductionCost   = array();
  var $buildingDepList         = array();
  var $maxBuildingDepList      = array();
  var $defenseSystemDepList    = array();
  var $maxDefenseSystemDepList = array();
  var $resourceDepList         = array();
  var $maxResourceDepList      = array();
  var $scienceDepList          = array();
  var $maxScienceDepList       = array();
  var $unitDepList             = array();
  var $maxUnitDepList          = array();
  var $nodocumentation         = 0;

  function Science($scienceID, $name, $description, $dbFieldName, $position, $maxLevel, $productionTimeFunction){
    $this-&gt;scienceID              = $scienceID;
    $this-&gt;name                   = $name;
    $this-&gt;description            = $description;
    $this-&gt;dbFieldName            = $dbFieldName;
    $this-&gt;position               = $position;
    $this-&gt;maxLevel               = $maxLevel ;
    $this-&gt;productionTimeFunction = $productionTimeFunction;
  }
}

function init_sciences(){

  global $scienceTypeList;
  <xsl:apply-templates select="Science"/>
}
</xsl:template>

<xsl:template match="Config/ScienceTypes/Science">
  // <xsl:value-of select="Name"/>
  $tmp = new Science(<xsl:value-of select="count(preceding-sibling::*)"/>, '<xsl:value-of select="Name"/>',
                     "<xsl:apply-templates select="Description[@lang='de_DE']"/>",
                     '<xsl:value-of select="@id"/>', <xsl:choose><xsl:when test="Position"><xsl:value-of select="Position"/></xsl:when><xsl:otherwise>0</xsl:otherwise></xsl:choose>, '<xsl:apply-templates select="MaxDevelopmentLevel"/>', '<xsl:apply-templates select="ProductionTime"/>', 0);
  <xsl:if test="count(Cost[name(id(@id))='Resource'])!=0">$tmp-&gt;resourceProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Resource']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='Unit'])!=0">
  $tmp-&gt;unitProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Unit']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='Building'])!=0">
  $tmp-&gt;buildingProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Building']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='DefenseSystem'])!=0">
  $tmp-&gt;defenseProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='DefenseSystem']"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Building'])!=0">
  $tmp-&gt;buildingDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Building']" mode="min"/>);
  $tmp-&gt;maxBuildingDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Building']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='DefenseSystem'])!=0">
  $tmp-&gt;defenseSystemDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='DefenseSystem']" mode="min"/>);
  $tmp-&gt;maxDefenseSystemDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='DefenseSystem']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Resource'])!=0">
  $tmp-&gt;resourceDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Resource']" mode="min"/>);
  $tmp-&gt;maxResourceDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Resource']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Science'])!=0">
  $tmp-&gt;scienceDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Science']" mode="min"/>);
  $tmp-&gt;maxScienceDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Science']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Unit'])!=0">
  $tmp-&gt;unitDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Unit']" mode="min"/>);
  $tmp-&gt;maxUnitDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Unit']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(EffectReq)!=0">
  $tmp-&gt;effectDepList    = array(<xsl:apply-templates select="EffectReq[name(id(@id))='EffectType']" mode="min"/>);
  $tmp-&gt;maxEffectDepList = array(<xsl:apply-templates select="EffectReq[name(id(@id))='EffectType']" mode="max"/>);
  </xsl:if>
  <xsl:if test="@hidden!=0">$tmp-&gt;nodocumentation = <xsl:apply-templates select="@hidden"/>;
  </xsl:if>

  $scienceTypeList[<xsl:value-of select="count(preceding-sibling::*)"/>] = $tmp;
</xsl:template>

<xsl:template match="UnitTypes">
/********************** Unittypes *********************/
class Unit {
  var $unitID;
  var $name;
  var $description;
  var $dbFieldName;
  var $position;
  var $ranking;
  var $productionTimeFunction;
  var $encumbranceList;
  var $visible;

  var $attackRange;
  var $attackAreal;
  var $attackRate;
  var $defenseRate;
  var $hitPoints;

  var $rangedDamageResistance;
  
  var $heavyDamageProbability = 0;
  var $criticalDamageProbability = 0;

  <!--
  var meleeDamage;
  var meleeDamageResistance;
  var rangedDamage;
  var rangedDamageResistance;
  var structuralDamage;
  var size;
  -->

  var $spyChance     = 0;
  var $spyValue      = 0;
  var $antiSpyChance = 0;
  var $spyQuality    = 0;

  var $foodCost = 1;
  var $wayCost = 1;
  var $resourceProductionCost  = array();
  var $unitProductionCost      = array();
  var $buildingProductionCost  = array();
  var $defenseProductionCost  = array();
  var $buildingDepList         = array();
  var $maxBuildingDepList      = array();
  var $defenseSystemDepList    = array();
  var $maxDefenseSystemDepList = array();
  var $resourceDepList         = array();
  var $maxResourceDepList      = array();
  var $scienceDepList          = array();
  var $maxScienceDepList       = array();
  var $unitDepList             = array();
  var $maxUnitDepList          = array();
  var $fuelResourceID          = 0;
  var $fuelFactor              = 0;
  var $nodocumentation         = 0;
  var $warpoints               = 0;

  function Unit($unitID, $name, $description, $dbFieldName, $position, $ranking, $productionTimeFunction,
                $attackRange, $attackAreal, $attackRate, $defenseRate, $rangedDamageResistance, $hitPoints,
                $encumbranceList, $visible){
  $this-&gt;unitID                 = $unitID;
  $this-&gt;name                   = $name;
  $this-&gt;description            = $description;
  $this-&gt;dbFieldName            = $dbFieldName;
  $this-&gt;position               = $position;
  $this-&gt;ranking                = $ranking;
  $this-&gt;productionTimeFunction = $productionTimeFunction;

  $this-&gt;attackRange            = $attackRange;
  $this-&gt;attackAreal            = $attackAreal;
  $this-&gt;attackRate             = $attackRate;
  $this-&gt;defenseRate            = $defenseRate;
  $this-&gt;rangedDamageResistance = $rangedDamageResistance;
  $this-&gt;hitPoints              = $hitPoints;
  $this-&gt;encumbranceList        = $encumbranceList;
  $this-&gt;visible                = $visible;
  }
}

function init_units(){

  global $unitTypeList;
  <xsl:apply-templates select="Unit"/>
}
</xsl:template>

<xsl:template match="Config/UnitTypes/Unit">
  // <xsl:value-of select="Name"/>
  $tmp = new Unit(<xsl:value-of select="count(preceding-sibling::*)"/>, '<xsl:value-of select="Name"/>',
                  "<xsl:apply-templates select="Description[@lang='de_DE']"/>",
                  '<xsl:value-of select="@id"/>', <xsl:choose><xsl:when test="Position"><xsl:value-of select="Position"/></xsl:when><xsl:otherwise>0</xsl:otherwise></xsl:choose>, <xsl:value-of select="round((((RangedDamage div 10)*15)+((StructuralDamage div 15)*10)+((MeleeDamage div 15)*12.5)+(((RangedDamageResistance+2*MeleeDamageResistance) div (3*Size))*25)+((Size div 15)*10)+((0.5 div Velocity)*17.5))*((1 div (Visible+1))+0.5))"/>, '<xsl:apply-templates select="ProductionTime"/>', <xsl:value-of select="RangedDamage"/>, <xsl:value-of select="StructuralDamage"/>, <xsl:value-of select="MeleeDamage"/>, <xsl:value-of select="MeleeDamageResistance"/>, <xsl:value-of select="RangedDamageResistance"/>, <xsl:value-of select="Size"/>, array(<xsl:apply-templates select="Encumbrance"/>), <xsl:value-of select="Visible"/>);

  <xsl:choose>
  <xsl:when test="count(FuelUsage)">$tmp-&gt;foodCost = <xsl:value-of select="FuelUsage"/>;
  </xsl:when>
  <xsl:otherwise>$tmp-&gt;foodCost = <xsl:value-of select="round((((RangedDamageResistance div 2)+ (MeleeDamageResistance div 2) + (Size)) div 40)*100) div 100"/>;
  </xsl:otherwise>
  </xsl:choose>
  <xsl:choose>
  <xsl:when test="count(Velocity)">$tmp-&gt;wayCost  = <xsl:value-of select="Velocity"/>;</xsl:when>
  <xsl:otherwise>$tmp-&gt;wayCost  = 1;</xsl:otherwise>
  </xsl:choose>
  <xsl:if test="count(HeavyDamageProbability)!=0">$tmp-&gt;heavyDamageProbability = <xsl:value-of select="HeavyDamageProbability"/>;
  </xsl:if>
  <xsl:if test="count(CriticalDamageProbability)!=0">$tmp-&gt;criticalDamageProbability = <xsl:value-of select="CriticalDamageProbability"/>;
  </xsl:if>
  <xsl:if test="count(WarPoints)!=0">$tmp-&gt;warpoints = <xsl:value-of select="WarPoints"/>;
  </xsl:if>

  <xsl:if test="count(SpyValue)!=0">$tmp-&gt;spyValue = <xsl:value-of select="SpyValue"/>;
  </xsl:if>
  <xsl:if test="count(SpyChance)!=0">$tmp-&gt;spyChance = <xsl:value-of select="SpyChance"/>;
  </xsl:if>
  <xsl:if test="count(AntiSpyChance)!=0">$tmp-&gt;antiSpyChance = <xsl:value-of select="AntiSpyChance"/>;
  </xsl:if>
  <xsl:if test="count(SpyQuality)!=0">$tmp-&gt;spyQuality = <xsl:value-of select="SpyQuality"/>;
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='Resource'])!=0">$tmp-&gt;resourceProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Resource']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='Unit'])!=0">
  $tmp-&gt;unitProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Unit']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='Building'])!=0">
  $tmp-&gt;buildingProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Building']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='DefenseSystem'])!=0">
  $tmp-&gt;defenseProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='DefenseSystem']"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Building'])!=0">
  $tmp-&gt;buildingDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Building']" mode="min"/>);
  $tmp-&gt;maxBuildingDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Building']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='DefenseSystem'])!=0">
  $tmp-&gt;defenseSystemDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='DefenseSystem']" mode="min"/>);
  $tmp-&gt;maxDefenseSystemDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='DefenseSystem']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Resource'])!=0">
  $tmp-&gt;resourceDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Resource']" mode="min"/>);
  $tmp-&gt;maxResourceDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Resource']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Science'])!=0">
  $tmp-&gt;scienceDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Science']" mode="min"/>);
  $tmp-&gt;maxScienceDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Science']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Unit'])!=0">
  $tmp-&gt;unitDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Unit']" mode="min"/>);
  $tmp-&gt;maxUnitDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Unit']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(EffectReq)!=0">
  $tmp-&gt;effectDepList    = array(<xsl:apply-templates select="EffectReq[name(id(@id))='EffectType']" mode="min"/>);
  $tmp-&gt;maxEffectDepList = array(<xsl:apply-templates select="EffectReq[name(id(@id))='EffectType']" mode="max"/>);
  </xsl:if>
  <xsl:if test="@hidden!=0">$tmp-&gt;nodocumentation = <xsl:apply-templates select="@hidden"/>;
  </xsl:if>
  $unitTypeList[<xsl:value-of select="count(preceding-sibling::*)"/>] = $tmp;
</xsl:template>

<xsl:template match="DefenseSystemTypes">
/********************** Defense Systems *********************/
class DefenseSystem {
  var $defenseSystemID;
  var $name;
  var $description;
  var $remark;
  var $dbFieldName;
  var $position;
  var $maxLevel;
  var $productionTimeFunction;

  var $attackRange;
  var $attackRate;
  var $defenseRate;
  var $hitPoints;

  var $warPoints = 0;
  var $heavyDamageProbability;
  var $criticalDamageProbability;



  <!--
  var meleeDamage;
  var rangedDamage;
  var structuralDamageResistance;
  var size;
  -->

  var $resourceProductionCost  = array();
  var $unitProductionCost      = array();
  var $buildingProductionCost  = array();
  var $defenseProductionCost  = array();
  var $buildingDepList         = array();
  var $maxBuildingDepList      = array();
  var $defenseSystemDepList    = array();
  var $maxDefenseSystemDepList = array();
  var $resourceDepList         = array();
  var $maxResourceDepList      = array();
  var $scienceDepList          = array();
  var $maxScienceDepList       = array();
  var $unitDepList             = array();
  var $maxUnitDepList          = array();

  var $antiSpyChance   = 0;
  var $nodocumentation = 0;

  function DefenseSystem($defenseSystemID, $name, $description, $remark, $dbFieldName, $position, $maxLevel,
                         $productionTimeFunction, $attackRange, $attackRate, $defenseRate, $hitPoints){

    $this-&gt;defenseSystemID        = $defenseSystemID;
    $this-&gt;name                   = $name;
    $this-&gt;description            = $description;
    $this-&gt;remark                 = $remark;
    $this-&gt;dbFieldName            = $dbFieldName;
    $this-&gt;position               = $position;
    $this-&gt;maxLevel               = $maxLevel;
    $this-&gt;productionTimeFunction = $productionTimeFunction;
    $this-&gt;attackRange            = $attackRange;
    $this-&gt;attackRate             = $attackRate;
    $this-&gt;defenseRate            = $defenseRate;
    $this-&gt;hitPoints              = $hitPoints;
  }
}

function init_defenseSystems(){

  global $defenseSystemTypeList;
 <xsl:apply-templates select="DefenseSystem"/>
}
</xsl:template>


<xsl:template match="Config/DefenseSystemTypes/DefenseSystem">
  // <xsl:value-of select="Name"/>
  // RankingWert <xsl:value-of select="round((RangedDamage*1.3+MeleeDamage+StructuralDamageResistance+Size) div 3)"/>
  $tmp = new DefenseSystem(<xsl:value-of select="count(preceding-sibling::*)"/>, '<xsl:value-of select="Name"/>',
                           "<xsl:apply-templates select="Description[@lang='de_DE']"/>",
                           "<xsl:apply-templates select="Remark[@lang='de_DE']"/>",
                           '<xsl:value-of select="@id"/>', <xsl:choose><xsl:when test="Position"><xsl:value-of select="Position"/></xsl:when><xsl:otherwise>0</xsl:otherwise></xsl:choose>, '<xsl:apply-templates select="MaxDevelopmentLevel"/>', '<xsl:apply-templates select="ProductionTime"/>', <xsl:value-of select="RangedDamage"/>, <xsl:value-of select="MeleeDamage"/>, <xsl:value-of select="StructuralDamageResistance"/>, <xsl:value-of select="Size"/>);
  <xsl:if test="count(AntiSpyChance)>0">$tmp-&gt;antiSpyChance  = <xsl:value-of select="AntiSpyChance"/>;
  </xsl:if>
  <xsl:if test="count(HeavyDamageProbability)!=0">$tmp-&gt;heavyDamageProbability = <xsl:value-of select="HeavyDamageProbability"/>;
  </xsl:if>
  <xsl:if test="count(CriticalDamageProbability)!=0">$tmp-&gt;criticalDamageProbability = <xsl:value-of select="CriticalDamageProbability"/>;
  </xsl:if>
  <xsl:if test="count(WarPoints)!=0">$tmp-&gt;warPoints = <xsl:value-of select="WarPoints"/>;
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='Resource'])!=0">$tmp-&gt;resourceProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Resource']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='Unit'])!=0">
  $tmp-&gt;unitProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Unit']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='Building'])!=0">
  $tmp-&gt;buildingProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='Building']"/>);
  </xsl:if>
  <xsl:if test="count(Cost[name(id(@id))='DefenseSystem'])!=0">
  $tmp-&gt;defenseProductionCost = array(<xsl:apply-templates select="Cost[name(id(@id))='DefenseSystem']"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Building'])!=0">
  $tmp-&gt;buildingDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Building']" mode="min"/>);
  $tmp-&gt;maxBuildingDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Building']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='DefenseSystem'])!=0">
  $tmp-&gt;defenseSystemDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='DefenseSystem']" mode="min"/>);
  $tmp-&gt;maxDefenseSystemDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='DefenseSystem']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Resource'])!=0">
  $tmp-&gt;resourceDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Resource']" mode="min"/>);
  $tmp-&gt;maxResourceDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Resource']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Science'])!=0">
  $tmp-&gt;scienceDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Science']" mode="min"/>);
  $tmp-&gt;maxScienceDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Science']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(Requirement[name(id(@id))='Unit'])!=0">
  $tmp-&gt;unitDepList    = array(<xsl:apply-templates select="Requirement[name(id(@id))='Unit']" mode="min"/>);
  $tmp-&gt;maxUnitDepList = array(<xsl:apply-templates select="Requirement[name(id(@id))='Unit']" mode="max"/>);
  </xsl:if>
  <xsl:if test="count(EffectReq)!=0">
  $tmp-&gt;effectDepList    = array(<xsl:apply-templates select="EffectReq[name(id(@id))='EffectType']" mode="min"/>);
  $tmp-&gt;maxEffectDepList = array(<xsl:apply-templates select="EffectReq[name(id(@id))='EffectType']" mode="max"/>);
  </xsl:if>
  <xsl:if test="@hidden!=0">$tmp-&gt;nodocumentation = <xsl:apply-templates select="@hidden"/>;
  </xsl:if>
  $defenseSystemTypeList[<xsl:value-of select="count(preceding-sibling::*)"/>] = $tmp;
</xsl:template>

<xsl:template match="Cost"><xsl:value-of select="count(id(@id)/preceding-sibling::*)"/> => '<xsl:apply-templates/>'<xsl:if test="position()!=last()">, </xsl:if>
</xsl:template>

<!-- Requirement -->
<xsl:template match="Requirement|EffectReq" mode="min"><xsl:value-of select="count(id(@id)/preceding-sibling::*)"/> => <xsl:value-of select="@min"/><xsl:if test="position()!=last()">, </xsl:if>
</xsl:template>

<xsl:template match="Requirement|EffectReq" mode="max"><xsl:value-of select="count(id(@id)/preceding-sibling::*)"/> => <xsl:choose><xsl:when test="@max"><xsl:value-of select="@max"/></xsl:when><xsl:otherwise>-1</xsl:otherwise></xsl:choose><xsl:if test="position()!=last()">, </xsl:if>
</xsl:template>

<xsl:template match="Encumbrance"><xsl:value-of select="count(id(@id)/preceding-sibling::*)"/> => '<xsl:value-of select="@value"/>'<xsl:if test="position()!=last()">, </xsl:if>
</xsl:template>

<!-- Hero -->
<xsl:template match="HeroType">/****hallo***/</xsl:template>
<xsl:template match="HeroSkills"></xsl:template>

<!-- Potions -->
<xsl:template match="Potions">
/*************** Tränke ******************/
class Potion {

  var $potionID;
  var $name = "";
  var $description = "";
  var $dbFieldName;
  var $hp_increase = 0;
  var $hp_prozentual_increase = 0;
  var $tp_setBack = 0;
  var $needed_level = 0;

  function Potion ($potionID, $name, $description, $dbFieldName, $hp_increase, $hp_prozentual_increase, $tp_setBack, $needed_level, $value) {
    $this-&gt;potionID                  = $potionID;
    $this-&gt;name                      = $name;
    $this-&gt;description               = $description;
    $this-&gt;dbFieldName               = $dbFieldName;
    $this-&gt;hp_increase               = $hp_increase;
    $this-&gt;hp_prozentual_increase    = $hp_prozentual_increase;
    $this-&gt;tp_setBack                = $tp_setBack;
    $this-&gt;needed_level              = $needed_level;
    $this-&gt;value                     = $value;
  }
}

function init_potions() {
  global $potionTypeList;
  <xsl:apply-templates select="Potion"/>
}
</xsl:template>


<xsl:template match="Potion">
  $tmp = new Potion (<xsl:value-of select="count(preceding-sibling::*)"/>, 
                     '<xsl:value-of select="Name"/>',
                     "<xsl:apply-templates select="Description[@lang='de_DE']"/>",
                     '<xsl:value-of select="@id"/>',
                     <xsl:value-of select="HP_increase"/>, 
                     <xsl:value-of select="HP_prozentual_increase"/>, 
                     <xsl:value-of select="TP_setback"/>, 
                     <xsl:value-of select="NeededLevel"/>,
                     0);
  $potionTypeList[<xsl:value-of select="count(preceding-sibling::*)"/>] = $tmp;
  
</xsl:template>


<!-- Terrains -->
<xsl:template match="Terrains">
/********************** Terrains *********************/
  define("MAX_TERRAINS", <xsl:value-of select="count(Terrain)"/>);
  global $terrainList;
  $terrainList = array();
  <xsl:apply-templates select="Terrain"/>
</xsl:template>

<xsl:template match="Terrain">
  // <xsl:value-of select="Name"/>
  $terrainList[<xsl:value-of select="@id"/>] = array('name' =&gt; '<xsl:value-of select="Name"/>',
                          'takeoverByCombat' =&gt; <xsl:value-of select="@takeoverByCombat"/>,
                          'barren' =&gt; <xsl:value-of select="@barren"/>,
                          'color' =&gt; array(<xsl:apply-templates select="Color"/>),
                          'effects' =&gt; array(<xsl:apply-templates select="Effect"/>));
</xsl:template>

<xsl:template match="Regions">
</xsl:template>

<xsl:template match="Movements">
</xsl:template>

<xsl:template match="Effect">
<xsl:value-of select="count(id(@id)/preceding-sibling::*)"/> =&gt; '<xsl:value-of select="."/>'<xsl:if test="position()!=last()">,
                                            </xsl:if></xsl:template>

<xsl:template match="Color">'r' =&gt; <xsl:value-of select="number(@r)"/>, 'g' =&gt; <xsl:value-of select="@g"/>, 'b' =&gt; <xsl:value-of select="@b"/>
</xsl:template>

<xsl:template match="EffectTypes"></xsl:template>

</xsl:stylesheet>
