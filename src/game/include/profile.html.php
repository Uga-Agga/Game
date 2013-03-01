<?php
/*
 * profile.html.php -
 * Copyright (c) 2003  OGP Team
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function profile_main() {
  global $template;

  // open template
  $template->setFile('profile.tmpl');

  // connect to login db
  if (!($db_login = DbConnect(Config::DB_LOGIN_HOST, Config::DB_LOGIN_USER, Config::DB_LOGIN_PWD, Config::DB_LOGIN_NAME))) {
    $template->throwError('Datenbankverbindungsfehler. Bitte wende dich an einen Administrator.');
    return;
  }

  $action = Request::getVar('action', '');
  switch ($action) {
/****************************************************************************************************
*
* Profil aktualisieren
*
****************************************************************************************************/
    case 'change':
      // proccess form data
      $message = profile_update($db_login);

      // update player's data
      page_refreshUserData();
    break;

/****************************************************************************************************
*
* Account "löschen"
*
****************************************************************************************************/
    case 'delete':
      if (Request::isPost('postConfirm')) {
        if (profile_processDeleteAccount($db_login, $_SESSION['player']->playerID)) {
          session_destroy();

          die(json_encode(array('mode' => 'finish', 'title' => 'Account gelöscht', 'msg' => _('Ihr Account wurde zur Löschung vorgemerkt. Sie sind jetzt ausgeloggt und können das Fenster schließen.'))));
        } else {
          $message = array('type' => 'error', 'message' => _('Das löschen Ihres Accounts ist fehlgeschlagen. Bitte wenden Sie sich an das Support Team.'));
        }
      } else {
        $template->addVars(array(
          'cancelOrder_box' => true,
          'confirm_action'  => 'delete',
          'confirm_id'      => $_SESSION['player']->playerID,
          'confirm_mode'    => USER_PROFILE,
          'confirm_msg'     => _('Möchtest du deinen Account wirklich löschen?'),
        ));
      }
    break;
  }

  // get login data
  $playerData = profile_getPlayerData($db_login);
  if (!$playerData) {
    $template->throwError('Datenbankfehler. Bitte wende dich an einen Administrator');
    return;
  }

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'status_msg' => (isset($message) && !empty($message)) ? $message : '',
    'player'     => $playerData['game'],
    'language'   => LanguageNames::getLanguageNames(),
    'template'   => Config::$template_paths
  ));
}

/** This function deletes the account. The account isn't deleted directly,
 *  but marked with a specialtag. It'll be deleted by a special script,
 *  that runs on a given time...
 */
function profile_processDeleteAccount($db_login, $playerID) {
  $sql = $db_login->prepare("UPDATE Login
                             SET deleted = 1,
                               email = CONCAT(email, '_del')
                             WHERE LoginID = :playerID");
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  if (!$sql->execute() || $sql->rowCount() == 0) {
    return false;
  }

  return true;
}

/** This function gets the players data out of the game and login
 *  database.
 */
function profile_getPlayerData($db_login){
  global $db;

/****************************************************************************************************
*
* Profil aus der Game Datenbank auslesen
*
****************************************************************************************************/
  $sql_game = $db->prepare("SELECT * FROM ". PLAYER_TABLE ." WHERE playerID = :playerID");
  $sql_game->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  if (!$sql_game->execute()) {
    return NULL;
  }
  $game = $sql_game->fetch(PDO::FETCH_ASSOC);
  $sql_game->closeCursor();

/****************************************************************************************************
*
* Profil aus der Login Datenbank auslesen
*
****************************************************************************************************/
  $sql_login = $db_login->prepare("SELECT * FROM " . LOGIN_TABLE . " WHERE LoginID = :playerID");
  $sql_login->bindValue('playerID', $_SESSION['player']->playerID, PDO::PARAM_INT);
  if (!$sql_login->execute()) {
    return NULL;
  }
  $login = $sql_login->fetch(PDO::FETCH_ASSOC);
  $sql_login->closeCursor();

/****************************************************************************************************
*
* Sollte zwar NIE vorkommen aber mal lieber prüfen ob ein query leer ist.
*
****************************************************************************************************/
  if (empty($game) || empty($login)) {
    return NULL;
  }

  $game['avatar'] = @unserialize($game['avatar']);

  return array("game" => $game, "login" => $login);
}


/** This function sets the changed data specified by the user.
 */
function profile_update($db_login) {
  global $db;

  $playerID = $_SESSION['player']->playerID;
  $data = array(
    'avatar'       => Request::getVar('inputPlayerAvatar', ''),
    'description'  => Request::getVar('inputPlayerDescription', '', true),
    'email2'       => Request::getVar('inputPlayerEmail2', ''),
    'gfxpath'      => Request::getVar('inputPlayerGFX', ''),
    'icq'          => Request::getVar('inputPlayerICQ', ''),
    'language'     => Request::getVar('inputPlayerLang', ''),
    'origin'       => Request::getVar('inputPlayerOrigin', ''),
    'template'     => Request::getVar('inputPlayerTemplate', ''),
    'passwordNew'  => Request::getVar('inputPlayerPasswordNew', ''),
    'passwordRe'   => Request::getVar('inputPlayerPasswordRe', ''),
    'jabberPwdNew' => Request::getVar('inputJabberPasswordNew', ''),
    'jabberPwdRe'  => Request::getVar('inputJabberPasswordRe', ''),
  );

  // validate language code
  $uaLanguageNames = LanguageNames::getLanguageNames();
  if (!isset($uaLanguageNames[$data['language']])) {
    unset($data['language']);
  }

  // check if avatar is a image
  if (isset($data['avatar']) && !empty($data['avatar'])) {
    $avatarInfo = checkAvatar($data['avatar']);
    if (!$avatarInfo) {
      return array('type' => 'error', 'message' => ('Ungültiges Bild oder URL beim Avatar! Wird zurückgesetzt!'));
    } else {
      $data['avatar'] = $avatarInfo;
    }
  } else {
    $data['avatar'] = '';
  }

  if (filter_var($data['email2'], FILTER_VALIDATE_EMAIL) === false) {
    return array('type' => 'error', 'message' => ('Ungültiges E-Mail Adresse. Bitte nimm deine Eingaben erneut vor!'));
  }

  if (strcmp($data['jabberPwdNew'], $data['jabberPwdRe']) != 0) {
    return array('type' => 'error', 'message' => _('Das Jabber Passwort stimmt nicht mit der Wiederholung überein.'));
  }

  // password too short?
  if (empty($data['jabberPwdNew'])) {
    $data['jabberPwdNew'] = null;
  } else {
    if(!preg_match('/^\w{6,}$/', unhtmlentities($data['jabberPwdNew']))) {
      return array('type' => 'error', 'message' => _('Das Jabber Passwort muss mindestens 6 Zeichen lang sein!'));
    }
  }

  $sql = $db->prepare("UPDATE " . PLAYER_TABLE . "
                       SET origin = :origin,
                         icq = :icq,
                         avatar = :avatar,
                         description = :description,
                         template = :template,
                         language = :language,
                         gfxpath = :gfxpath,
                         email2 = :email2,
                         avatar = :avatar,
                         jabberPassword = :jabberPassword
                       WHERE playerID = :playerID");
  $sql->bindValue('origin', $data['origin'], PDO::PARAM_STR);
  $sql->bindValue('icq', $data['icq'], PDO::PARAM_INT);
  $sql->bindValue('description', $data['description'], PDO::PARAM_STR);
  $sql->bindValue('template', $data['template'], PDO::PARAM_INT);
  $sql->bindValue('language', $data['language'], PDO::PARAM_STR);
  $sql->bindValue('gfxpath', $data['gfxpath'], PDO::PARAM_STR);
  $sql->bindValue('email2', $data['email2'], PDO::PARAM_STR);
  $sql->bindValue('avatar', $data['avatar'], PDO::PARAM_STR);
  $sql->bindValue('jabberPassword', $data['jabberPwdNew'], PDO::PARAM_STR);
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    return array('type' => 'error', 'message' => _('Die Daten konnten gar nicht oder zumindest nicht vollständig aktualisiert werden.'));
  }

  // ***** now update the password, if it is set **** **************************
  if (strlen($data['passwordNew'])) {
    // typo?
    if (strcmp($data['passwordNew'], $data['passwordRe']) != 0) {
      return array('type' => 'error', 'message' => _('Das Spiel Passwort stimmt nicht mit der Wiederholung überein.'));
    }

    // password too short?
    if(!preg_match('/^\w{6,}$/', unhtmlentities($data['passwordNew']))) {
      return array('type' => 'error', 'message' => _('Das Spiel Passwort muss mindestens 6 Zeichen lang sein!'));
    }

    // set password
    $sql = $db_login->prepare("UPDATE Login SET password = :password WHERE LoginID = :loginID");
    $sql->bindValue('password', $data['passwordNew'], PDO::PARAM_STR);
    $sql->bindValue('loginID', $playerID, PDO::PARAM_INT);

    if (!$sql->execute() || $sql->rowCount() == 0) {
      return array('type' => 'error', 'message' => _('Die Daten konnten gar nicht oder zumindest nicht vollständig aktualisiert werden.'));
    }
  }

  return array('type' => 'success', 'message' => _('Die Daten wurden erfolgreich aktualisiert.'));
}

?>