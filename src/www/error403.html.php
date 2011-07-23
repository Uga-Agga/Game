<?php
  $message = trim(htmlentities(strip_tags($_GET['message']), ENT_QUOTES));
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>Zugriff verweigert</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta HTTP-EQUIV="Refresh" CONTENT="5; URL=<?php define('_VALID_UA', 1); require_once('config.inc.php'); require_once('include/config.inc.php'); echo LOGIN_PATH;?>">
    <script language="javascript" type="text/javascript">
      if (top.location != self.location) top.location = self.location;
    </script>
    <style type="text/css">
      <!--
      BODY {background-color: #CCCC99; font-family : sans-serif;}
      A {color: blue;}
      A:Hover {color: red;}
      H1 {font-variant: small-caps; background-color: black; color: white; padding: 4px; width: 100%;}
      .copyright {font-size: smaller;}
      -->
    </style>
  </head>
  <body>
    <h1>Zugriff verweigert</h1>
    <p>Fehler: <?php echo $message ? $message : "unbekannt."; ?></p>
    <p>
      Sie werden in wenigen Sekunden zum <a href="<?php echo LOGIN_PATH; ?>">Uga-Agga-Portal</a> weitergeleitet.
    </p>
    <p>
      Wenn Sie der Meinung sind, dass sie diese Fehlermeldung f&auml;lschlich
      erhalten haben, teilen sie uns dies im
      <a href="http://forum.uga-agga.de/">Forum</a> mit.
    </p>
    <p align="center" class="copyright">&copy; <a href="mailto:team@uga-agga.de">uga-agga team</a></p>
  </body>
</html>
