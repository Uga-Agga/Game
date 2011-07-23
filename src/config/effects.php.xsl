<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>
<xsl:strip-space elements="*"/>

<xsl:template match="Config"><xsl:apply-templates select="EffectTypes" /></xsl:template>

<xsl:template match="EffectTypes">&lt;?php
define("MAX_EFFECT", <xsl:value-of select="count(EffectType)"/>);

/********************** Effecttypes *********************/
class Effects {

  var $effectID;
  var $name;
  var $dbFieldName;
  var $description;

  function Effects($effectID, $name, $dbFieldName, $description){
    $this-&gt;effectID    = $effectID;
    $this-&gt;name        = $name;
    $this-&gt;dbFieldName = $dbFieldName;
    $this-&gt;description = $description;
  }
}

function init_Effects() {
  global $effectTypeList;

  $effectTypeList = array();
<xsl:for-each select="EffectType">
<xsl:variable name="id" select="position()-1"/>
  /* ***** <xsl:value-of select="Name"/> ***** */
  $tmp = new Effects('<xsl:value-of select="$id"/>', '<xsl:value-of select="Name"/>', '<xsl:value-of select="@id"/>', '<xsl:apply-templates select="Description[@lang='de_DE']"/>');
  $effectTypeList[<xsl:value-of select="$id"/>] = $tmp;
</xsl:for-each>
}
?&gt;
</xsl:template>

<xsl:template match="Description"><xsl:apply-templates/></xsl:template>
<xsl:template match="p">&lt;p&gt;<xsl:apply-templates/>&lt;/p&gt;</xsl:template>
</xsl:stylesheet>
