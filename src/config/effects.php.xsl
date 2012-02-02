<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>
<xsl:strip-space elements="*"/>

<xsl:template match="Config"><xsl:apply-templates select="EffectTypes" /></xsl:template>

<xsl:template match="EffectTypes">&lt;?php
/*
 * effects.list.php -
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
define("MAX_EFFECT", <xsl:value-of select="count(EffectType)"/>);

/********************** Effecttypes *********************/
class Effects {

  var $effectID;
  var $name;
  var $dbFieldName;
  var $description;
  var $isResourceEffect;

  function Effects($effectID, $name, $dbFieldName, $description, $isResourceEffect){
    $this-&gt;effectID    = $effectID;
    $this-&gt;name        = $name;
    $this-&gt;dbFieldName = $dbFieldName;
    $this-&gt;description = $description;
    $this-&gt;isResourceEffect = $isResourceEffect;
  }
}

function init_Effects() {
  $GLOBALS['effectTypeList'] = array();
<xsl:for-each select="EffectType">
<xsl:variable name="id" select="position()-1"/>
  /* ***** <xsl:value-of select="Name"/> ***** */
  $tmp = new Effects('<xsl:value-of select="$id"/>', '<xsl:value-of select="Name"/>', '<xsl:value-of select="@id"/>', '<xsl:apply-templates select="Description[@lang='de_DE']"/>', '<xsl:value-of select="@isResourceEffect"/>');
  $GLOBALS['effectTypeList'][<xsl:value-of select="$id"/>] = $tmp;
</xsl:for-each>
}
?&gt;
</xsl:template>

<xsl:template match="Description"><xsl:apply-templates/></xsl:template>
<xsl:template match="p">&lt;p&gt;<xsl:apply-templates/>&lt;/p&gt;</xsl:template>
</xsl:stylesheet>
