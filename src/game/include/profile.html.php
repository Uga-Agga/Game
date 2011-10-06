<?php
/*
 * profile.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################

/**
 * This function delegates the task at issue to the respective function.
 */

function profile_main() {
  global $config, $template;

  // connect to login db
  if (!($db_login = DbConnect($config->DB_LOGIN_HOST, $config->DB_LOGIN_USER, $config->DB_LOGIN_PWD, $config->DB_LOGIN_NAME))) {
    $template->throwError('Datenbankverbindungsfehler. Bitte wende dich an einen Administrator.');
    return;
  }

  $action = request_var('action', '');
  switch ($action) {
    // change cave page
    case 'change':
      // proccess form data
      $message = profile_update($db_login);

      // update player's data
      page_refreshUserData();
    break;

    // change cave page
    case 'delete':
      if (isset($_POST['cancelOrderConfirm'])) {
        if (profile_processDeleteAccount($db_login, $_SESSION['player']->playerID)) {
          session_destroy();

          $message = array('type' => 'success', 'message' => _('Ihr Account wurde zur Löschung vorgemerkt. Sie sind jetzt ausgeloggt und können das Fenster schließen.'));
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

  // open template
  $template->setFile('profile.tmpl');

  // get login data
  $playerData = profile_getPlayerData($db_login);
  if (!$playerData) {
    $template->throwError('Datenbankfehler. Bitte wende dich an einen Administrator');
    return;
  }

  // show message
  if (isset($message) && !empty($message)) {
    $template->addVar('status_msg', $message);
  }

  // show the profile's data
  profile_fillUserData($template, $playerData);
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
  print_r($sql->errorInfo());
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

  return array("game" => $game, "login" => $login);
}


################################################################################


/** This function gets the players data out of the game and login
 *  database.
 */
function profile_fillUserData($template, $playerData) {
  global $config, $template;

  $profileData = array();

  ////////////// user data //////////////////////
  $p = new ProfileDataGroup(_('Benutzerdaten'));
  $p->add(new ProfileElementInfo(_('Name'), $playerData['game']['name']));
  $p->add(new ProfileElementInfo(_('Geschlecht'), $playerData['game']['sex']));
  $p->add(new ProfileElementInfo(_('Email'), $playerData['game']['email']));
  $p->add(new ProfileElementInput(_('Email 2'), $playerData['game']['email2'], 'data', 'email2', 30, 90));
  $p->add(new ProfileElementInput(_('Herkunft'), $playerData['game']['origin'], 'data', 'origin', 30, 30));
  $p->add(new ProfileElementInput(_('ICQ#'), $playerData['game']['icq'], 'data', 'icq', 15, 15));
  $p->add(new ProfileElementInput(_('Avatar URL <br /><small>(max. Breite: '.MAX_AVATAR_WIDTH.', max. Höhe: '.MAX_AVATAR_HEIGHT .')</small>'), $playerData['game']['avatar'], 'data', 'avatar', 60, 200));
  $p->add(new ProfileElementMemo(_('Beschreibung'), $playerData['game']['description'], 'data', 'description', 25, 8));
  $profileData[] = $p->getTmplData();

  ////////////// L10N //////////////////////
  $uaLanguageNames = LanguageNames::getLanguageNames();
  
  $p = new ProfileDataGroup(_('Lokalisierung'));
  $slct = new ProfileElementSelection(_('Sprache'), 'data', 'language');
  foreach ($uaLanguageNames as $key => $text) {
    $slct->add(new ProfileSelector($key, $text, $key == $_SESSION['player']->language));
  }
  $p->add($slct);
  $profileData[] = $p->getTmplData();

  ////////////// template //////////////////////
  $p = new ProfileDataGroup(_('Template auswählen'));
  $slct = new ProfileElementSelection(_('Template auswählen'), 'data', 'template');
  foreach ($config->template_paths as $key => $text) {
    $slct->add(new ProfileSelector($key, $text, $key == $_SESSION['player']->template));
  }
  $p->add($slct);$profileData[] = $p->getTmplData();

  ////////////// gfxpath //////////////////////
  $p = new ProfileDataGroup(_('Grafikpack'));
  $p->add(new ProfileElementInput(sprintf(_('Pfad zum Grafikpack<br />(default:%s)'), DEFAULT_GFX_PATH), $playerData['game']['gfxpath'], 'data', 'gfxpath', 60, 200));
  $profileData[] = $p->getTmplData();

  ////////////// password //////////////////////
  $p = new ProfileDataGroup(_('Passwort-Änderung'));
  $p->add(new ProfileElementPassword(_('Neues Passwort'),  '', 'password', 'password1', 15, 15));
  $p->add(new ProfileElementPassword(_('Neues Passwort - Wiederholung'), '', 'password', 'password2', 15, 15));
  $profileData[] = $p->getTmplData();

  $template->addVar('profile_data', $profileData);
}


################################################################################


/** This function sets the changed data specified by the user.
 */
function profile_update($db_login) {
  global $db;

  $playerID = $_SESSION['player']->playerID;
  $data     = request_var('data', array('' => ''));
  $password = request_var('password', array('' => ''));

  $data['description'] = $_POST['data']['description'];

  // validate language code
  $uaLanguageNames = LanguageNames::getLanguageNames();
  if (!array_key_exists($data['language'], $uaLanguageNames)) {
    unset($data['language']);
  }

  // check if avatar is a image
  if (array_key_exists('avatar', $data)) {
    if (($data['avatar'] !== '') && !getimagesize($data['avatar'])) {
      $data['avatar'] = '';
      return array('type' => 'error', 'message' => ('Ungültiges Bild oder URL beim Avatar! Wird zurückgesetzt!'));
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
                         avatar = :avatar
                       WHERE playerID = :playerID");
  $sql->bindValue('origin', $data['origin'], PDO::PARAM_STR);
  $sql->bindValue('icq', $data['icq'], PDO::PARAM_INT);
  $sql->bindValue('description', $data['description'], PDO::PARAM_STR);
  $sql->bindValue('template', $data['template'], PDO::PARAM_INT);
  $sql->bindValue('language', $data['language'], PDO::PARAM_STR);
  $sql->bindValue('gfxpath', $data['gfxpath'], PDO::PARAM_STR);
  $sql->bindValue('email2', $data['email2'], PDO::PARAM_STR);
  $sql->bindValue('avatar', $data['avatar'], PDO::PARAM_STR);
  $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);
  if (!$sql->execute()) {
    return array('type' => 'error', 'message' => _('Die Daten konnten gar nicht oder zumindest nicht vollständig aktualisiert werden.'));
  }

  // ***** now update the password, if it is set **** **************************
  if (strlen($password['password1'])) {
    // typo?
    if (strcmp($password['password1'], $password['password2']) != 0) {
      return array('type' => 'error', 'message' => _('Das Passwort stimmt nicht mit der Wiederholung überein.'));
    }

    // password too short?
    if(!preg_match('/^\w{6,}$/', unhtmlentities($password['password1']))) {
      return array('type' => 'error', 'message' => _('Das Passwort muss mindestens 6 Zeichen lang sein!'));
    }

    // set password
    $sql = $db_login->prepare("UPDATE Login SET password = :password WHERE LoginID = :loginID");
    $sql->bindValue('password', $password['password1'], PDO::PARAM_STR); 
    $sql->bindValue('playerID', $playerID, PDO::PARAM_INT);

    if (!$sql->execute() || $sql->rowCount() == 0) {
      return array('type' => 'error', 'message' => _('Die Daten konnten gar nicht oder zumindest nicht vollständig aktualisiert werden.'));
    }
  }

  return array('type' => 'success', 'message' => _('Die Daten wurden erfolgreich aktualisiert.'));
}


################################################################################


class ProfileDataGroup {

  var $heading;
  var $elements;

  function ProfileDataGroup($heading) {
    
    $this->heading = $heading;
    $this->elements = array();
  }

  function add($element){
    $this->elements[] = $element;
  }

  function getTmplData(){
    $result = array('heading' => $this->heading);

    foreach ($this->elements as $element)
      $result[$element->getTmplContext()][] = $element->getTmplData();

    return $result;
  }
}


################################################################################


class ProfileElement {
  function getTmplContext(){
    return NULL;
  }
  function getTmplData(){
    return NULL;
  }
  function validate(){
    return true;
  }
}


################################################################################


class ProfileElementInfo extends ProfileElement {

  var $name;
  var $value;

  function ProfileElementInfo($name, $value) {
    $this->name  = $name;
    $this->value = $value;
  }

  function getTmplContext() {
    return 'entry_info';
  }

  function getTmplData(){
    return array('name' => $this->name, 'value' => $this->value);
  }
}


################################################################################


class ProfileElementInput extends ProfileElement {

  var $name;
  var $value;
  var $dataarray;
  var $dataentry;
  var $size;
  var $maxlength;

  function ProfileElementInput($name, $value, $dataarray, $dataentry, $size, $maxlength) {
    $this->name       = $name;
    $this->value      = $value;
    $this->dataarray  = $dataarray;
    $this->dataentry  = $dataentry;
    $this->size       = $size;
    $this->maxlength  = $maxlength;
  }

  function getTmplContext(){
    return 'entry_input';
  }

  function getTmplData(){
    return array('name'      => $this->name,
                 'value'     => $this->value,
                 'dataarray' => $this->dataarray,
                 'dataentry' => $this->dataentry,
                 'size'      => $this->size,
                 'maxlength' => $this->maxlength);
  }
}


################################################################################


class ProfileElementPassword extends ProfileElementInput {

  function ProfileElementPassword($name, $value, $dataarray, $dataentry, $size, $maxlength) {
    $this->name       = $name;
    $this->value      = $value;
    $this->dataarray  = $dataarray;
    $this->dataentry  = $dataentry;
    $this->size       = $size;
    $this->maxlength  = $maxlength;
  }

  function getTmplContext() {
    return 'entry_input';
  }
}


################################################################################


class ProfileElementMemo extends ProfileElement {

  var $name;
  var $value;
  var $dataarray;
  var $dataentry;
  var $cols;
  var $rows;

  function ProfileElementMemo($name, $value, $dataarray, $dataentry, $cols, $rows) {
    $this->name       = $name;
    $this->value      = $value;
    $this->dataarray  = $dataarray;
    $this->dataentry  = $dataentry;
    $this->cols       = $cols;
    $this->rows       = $rows;
  }

  function getTmplContext() {
    return 'entry_memo';
  }

  function getTmplData() {
    return array('name'      => $this->name,
                 'value'     => $this->value,
                 'dataarray' => $this->dataarray,
                 'dataentry' => $this->dataentry,
                 'cols'      => $this->cols,
                 'rows'      => $this->rows);
  }
}


################################################################################


class ProfileElementSelection extends ProfileElement {

  var $name;
  var $dataarray;
  var $dataentry;
  var $selectors;

  function ProfileElementSelection($name, $dataarray, $dataentry) {
    $this->name       = $name;
    $this->dataarray  = $dataarray;
    $this->dataentry  = $dataentry;
    //$this->selectors  = $selectors;
  }

  function add($selector) {
    $this->selectors[] = $selector;
  }

  function getTmplContext() {
    return 'entry_selection';
  }

  function getTmplData() {
    $result = array('name'      => $this->name,
                    'dataarray' => $this->dataarray,
                    'dataentry' => $this->dataentry);
    foreach ($this->selectors as $selector)
      $result[$selector->getTmplContext()][] = $selector->getTmplData();
    return $result;
  }
}


################################################################################


class ProfileSelector extends ProfileElement {

  var $text;
  var $key;
  var $selected;

  function ProfileSelector($key, $text, $selected = FALSE) {
    $this->key      = $key;
    $this->text     = $text;
    $this->selected = $selected;
  }

  function getTmplContext() {
    return 'selector';
  }

  function getTmplData() {
    $result = array('text' => $this->text, 'key' => $this->key);
    if ($this->selected) $result['selector'] = true;
    return $result;
  }
}


################################################################################


class ProfileCheckbox extends ProfileElement {

  var $name;
  var $value;
  var $dataarray;
  var $dataentry;
  var $checked;

  function ProfileCheckbox($name, $value, $dataarray, $dataentry, $checked) {
    $this->name       = $name;
    $this->value      = $value;
    $this->dataarray  = $dataarray;
    $this->dataentry  = $dataentry;
    $this->checked    = $checked;
  }

  function getTmplContext() {
    return 'entry_checkbox';
  }

  function getTmplData() {
    return array('name'     => $this->name,
                 'value'     => $this->value,
                 'dataarray' => $this->dataarray,
                 'dataentry' => $this->dataentry,
                 'checked'   => $this->checked);
  }
}

?>