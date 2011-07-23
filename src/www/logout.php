<?php
/*
 * logout.php - 
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

require_once("config.inc.php");
require_once("include/config.inc.php");

// start session
@session_start();

// destroy session data
@session_destroy();
?>
<!doctype html public "-//W3C//DTD HTML 3.2 //EN">
<html>
<head>
  <title>UGA AGGA</title>
  <script language="javascript" type="text/javascript">
    if (top.location != self.location) top.location = self.location;
  </script>
</head>
<body bgcolor="#FFFFCC" text="#000000" ONLOAD="if (window != window.top) { top.location.href=location.href }">
<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td>
      <p align="center">Weiter zu <a href="<?php echo LOGIN_PATH ?>">Uga-Agga</a></p>
      <p align="center">Sie sind jetzt ausgeloggt und k&ouml;nnen den Browser schlie&szlig;en oder weitersurfen.</p>
      <p align="center">Vielen Dank f&uuml;r das Spielen von Uga-Agga!</p>
    </td>
  </tr>
</table>
</body>
</html>
