<?php
/*
 * delete.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function profile_deleteAccount($playerID) {
  global $config, $db, $no_resource_flag;

  $no_resource_flag = 1;

  // try to connect to login db
  $db_login = db_connectToLoginDB();

  // proccess form data

  if (request_var('confirm', false)) { // the only necessary field
    $success = profile_processDeleteAccount($playerID, $db_login);

    $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'deleteResponse.ihtml');
    if ($success) {
      session_destroy();

      tmpl_set($template, 'message', _('Ihr Account wurde zur L&ouml;schung vorgemerkt. Sie sind jetzt ausgeloggt und k&ouml;nnen das Fenster schlie&szlig;en.'));
      tmpl_set($template, 'link', LOGIN_PATH);
    }
    else {
      tmpl_set($template, 'message', _('Das l&ouml;schen Ihres Accounts ist fehlgeschlagen. Bitte wenden Sie sich an das Support Team.'));
      tmpl_set($template, 'link', "ugastart.php");
    }

    return tmpl_parse($template);
  }

  // Show confirmation request

  $template = tmpl_open($_SESSION['player']->getTemplatePath() . 'dialog.ihtml');

  tmpl_set($template, 'message', _('M&ouml;chten Sie Ihren Account unwiderruflich l&ouml;schen? Ihre gesamten Spieldaten gehen verloren, ein neuerliches einloggen als dieser Spieler ist nicht m&ouml;glich. <br /> Allerdings steht Ihnen die Emailadresse anschlie&szlig;end f&uuml;r eine Neuanmeldung zur Verf&uuml;gung. <br /> Beachten Sie, da&szlig Ihre H&ouml;hle noch f&uuml;r einige Zeit nach der L&ouml;schung f&ouml;r andere Spieler sichtbar ist, da die L&ouml;schungen aus der Datenbank nur einmal am Tag vorgenommen werden.'));

  tmpl_set($template, 'BUTTON/formname', 'confirm');
  tmpl_set($template, 'BUTTON/text', _('Account l&ouml;schen'));
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', DELETE_ACCOUNT);
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'confirm');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', 1);

  tmpl_iterate($template, 'BUTTON');

  tmpl_set($template, 'BUTTON/formname', 'cancel');
  tmpl_set($template, 'BUTTON/text', _('Abbrechen'));
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', USER_PROFILE);

  return tmpl_parse($template);
}

/** This function deletes the account. The account isn't deleted directly,
 *  but marked with a specialtag. It'll be deleted by a special script,
 *  that runs on a given time...
 */
function profile_processDeleteAccount($playerID, $db_login) {
  $sql = $db_login->prepare("UPDATE Login SET deleted = 1, ".
           "email = CONCAT(email, '_del') ".
           "WHERE LoginID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  
  return $sql->execute();
}

?>