<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text" encoding="UTF-8"/>

<xsl:template match="StartValues">&lt;?php
define('STARTVALUES_AVERAGE_MULTIPLIER', <xsl:value-of select="@averageMultiplier"/>);
$start_values = array(<xsl:apply-templates select="StartValue"/>);
?&gt;</xsl:template>

<xsl:template match="StartValue">
'<xsl:value-of select="@id"/>' => <xsl:value-of select="@max"/>,
</xsl:template>
</xsl:stylesheet>
