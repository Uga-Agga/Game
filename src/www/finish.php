<?php
/*
 * error.php -
 * Copyright (c) 2003  OGP Team
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
require_once("include/params.inc.php");
require_once("include/template.inc.php");

//messages
$messageText = array (
  'default' => _('Es ist ein Fehler aufgetreten. Bitte erneut einloggen um weiterspielen zu können.'),
  'db'      => _('Es konnte keine Verbindung zur Datenbank hergestellt werden!<br />Bitte wende dich an einen Administrator oder versuche es später erneut.'),
  'inaktiv' => sprintf(_('Sie waren für %s Minuten oder mehr inaktiv. Bitte loggen sie sich erneut ins Spiel ein um weiterspielen zu können.'), ((int)(SESSION_MAX_LIFETIME/60))),
  'logout'  => _('Du bist jetzt ausgeloggt und können den Browser schließen oder weitersurfen.<br /><br />Vielen Dank für das Spielen von Uga-Agga!'),
  'wrongSessionID' => _('Falsche oder ungültige SessionID.'),
);

// init request class
$request = new Request();

// load and open template
$template = new Template(UA_GAME_DIR . '/templates/de_DE/uga/');
$template->setFile('finish.tmpl');

$id = $request->getVar('id', '');
if (!empty($id) && isset($messageText[$id])) {
  $message = $messageText[$id];

  // Irgendwas zu tun bei bestimmten Meldungen?
  switch ($id) {
    case 'logout':
      @session_start();
      @session_destroy();
    break;
  }

} else {
  $message = $messageText['default'];
}


$template->addVars(array(
  'gfx'  => DEFAULT_GFX_PATH,
  'login_path' => LOGIN_PATH,
  'status_msg' => $message,
  'time' => date("d.m.Y H:i:s"),
));
$template->render();

?>