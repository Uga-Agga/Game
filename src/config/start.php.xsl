<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>

<xsl:template match="StartValues">&lt;?php
/*
 * startvalues.php -
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
define('STARTVALUES_AVERAGE_MULTIPLIER', <xsl:value-of select="@averageMultiplier"/>);
$start_values = array(<xsl:apply-templates select="StartValue"/>);
?&gt;</xsl:template>

<xsl:template match="StartValue">
'<xsl:value-of select="@id"/>' => <xsl:value-of select="@max"/>,
</xsl:template>
</xsl:stylesheet>
