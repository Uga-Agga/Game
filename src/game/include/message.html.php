<?php
/*
 * message.html.php -
 * Copyright (c) 2004  OGP Team
 * Copyright (c) 2011-2013 David Unger <unger-dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('modules/Contacts/model/Contacts.php');

///////////////////////////////////////////////////////////////////////////////
// MESSAGES                                                                  //
///////////////////////////////////////////////////////////////////////////////
function messages_getMessages($caveID, $deletebox, $box) {
  global $template;

  // open template
  $template->setFile('messageList.tmpl');

  // init messages class
  $messagesClass = new Messages;

  // Nachrichten löschen
  $deleted = $marked_as_read = 0;

  // alte status msg?
  if ($template->getVar('status_msg')) {
    $statusMsg = $template->getVar('status_msg');
  }

  $action = Request::getVar('action', '');
  $messageID = Request::getVar('messageID', 0);

  // checkboxes checked
  if ((is_array($deletebox) && Request::isPost('button')) || (Request::getVar('action', '') && Request::getVar('messageID', 0) != 0)) {
    if (Request::getVar('action', '') && Request::getVar('messageID', 0) != 0) {
      $deletebox = array($messageID);
      $switch = Request::getVar('action', '');
    } else {
      $switch = Request::getVar('button', '');
    }

    if (!sizeof($deletebox)) {
      $statusMsg = array('type' => 'error', 'message' => _('Du mußt mindestens eine Nachricht auswählen.'));
      $switch = '';
    }

    switch ($switch) {
      // mail and delete
      case 'mark_mail':
        $mailCount = $messagesClass->mailAndDeleteMessages($deletebox);
        $statusMsg = array('type' => 'success', 'message' => sprintf(_('%d Nachricht(en) per E-Mail verschickt erfolgreich gelöscht.'), $mailCount));
      break;

      // just delete
      case 'mark_delete':
        $deleteCount = $messagesClass->deleteMessages($deletebox);
        $statusMsg = array('type' => 'success', 'message' => sprintf(_('%d Nachricht(en) erfolgreich gelöscht.'), $deleteCount));
      break;

      // mark as read
      case 'mark_read':
        $readCount = $messagesClass->markAsRead($deletebox);
        $statusMsg = array('type' => 'success', 'message' => sprintf(_('%d Nachricht(en) als gelesen markiert.'), $readCount));
      break;

      // recover messages
      case 'mark_recover':
        $recoverCount = $messagesClass->recoverMessages($deletebox);
        $statusMsg = array('type' => 'success', 'message' => sprintf(_('%d Nachricht(en) wurden wiederhergestellt.'), $recoverCount));
      break;
    }
  }

  // delete all
  if (Request::isPost('delete_all')) {
    $deleted = $messagesClass->deleteAllMessages($box, Request::getVar('messageClass', -2));
    $statusMsg = array('type' => 'success', 'message' => sprintf(_('%d Nachricht(en) erfolgreich gelöscht.'), $deleted));
    unset($_REQUEST['messageClass'], $_POST['messageClass'], $_GET['messageClass']);
  }

  // verschiedene Boxes werden hier behandelt... //
  $boxes = array(
    BOX_INCOMING => array('boxname' => _('Posteingang'), 'from_to' => _('Absender')),
    BOX_OUTGOING => array('boxname' => _('Postausgang'), 'from_to' => _('Empfänger')),
    BOX_TRASH    => array('boxname' => _('Papierkorb'), 'from_to' => _('Absender')),
  );

  $classes = array();
  foreach ($messagesClass->MessageClass as $id => $text) {
    $messageClass = (Request::isGet('messageClass', true) || Request::isPost('messageClass', true)) ? Request::getVar('messageClass', 0) : 0;
    if ($id != 1001) {
      $selected = ($messageClass == $id) ? 'selected="selected"' : '';
      $classes[] = array('id' => $id, 'text' => $text, 'selected' => $selected);
    }

    //für jede Nachrichtenart wird eine eigene Box angelegt
    array_push($boxes, array($text => array('boxname' => _($text), 'von_an' => '')));
  }

  /////////////////////////////////////////////////

  // calculate offset
  $offset = Request::getVar('offset', 0);
  $messageClass = (Request::isGet('messageClass', true) || Request::isPost('messageClass', true)) ? Request::getVar('messageClass', 0) : -2;
  switch ($box){
    default:
    case BOX_INCOMING:
      $message_count = $messagesClass->getIncomingMessagesCount($messageClass);
      break;
    case BOX_OUTGOING:
      $message_count = $messagesClass->getOutgoingMessagesCount($messageClass);
      break;
    case BOX_TRASH:
      $message_count = $messagesClass->getTrashMessagesCount($messageClass);
      break;
  }

  // offset "normalisieren"
  if ($offset < 0) {
    $offset = 0;
  }

  if ($offset > $message_count - 1) {
    $offset = $message_count;
  }

  // Nachrichten einlesen und ausgeben
  $messages = array();
  switch ($box) {
    default:
    case BOX_INCOMING:
      $messages = $messagesClass->getIncomingMessages($offset, MSG_PAGE_COUNT, $messageClass);
      break;
    case BOX_OUTGOING:
      $messages = $messagesClass->getOutgoingMessages($offset, MSG_PAGE_COUNT, $messageClass);
      break;
    case BOX_TRASH:
      $messages = $messagesClass->getTrashMessages($offset, MSG_PAGE_COUNT, $messageClass);
      break;
  }

  // vor-zurück Knopf
  $message_prev = $message_next = array();
  if ($offset - MSG_PAGE_COUNT >= 0) {
    $message_prev = array(
      'offset' => $offset - MSG_PAGE_COUNT,
      'box'    => $box,
      'modus'  => MESSAGES_LIST,
      'message_class' => $messageClass
    );
  }

  if ($offset + MSG_PAGE_COUNT <= $message_count - 1) {
    $message_next = array(
      'offset' => $offset + MSG_PAGE_COUNT,
      'box'    => $box,
      'modus'  => MESSAGES_LIST,
      'message_class' => $messageClass
    );
  }

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'from_to'            => $boxes[$box]['from_to'],
    'messages'           => $messages,
    'message_box'        => $box,
    'message_classes'    => $classes,
    'message_class_id'   => isset($messageClass) ? $messageClass : 0,
    'message_class_name' => isset($messagesClass->MessageClass[$messageClass]) ? $messagesClass->MessageClass[$messageClass] : '',
    'message_min'        => ($message_count == 0) ? 0 : $offset + 1,
    'message_max'        => min($offset + MSG_PAGE_COUNT, $message_count),
    'message_count'      => $message_count,
    'message_prev'       => $message_prev,
    'message_next'       => $message_next,
    'status_msg'         => (isset($statusMsg)) ? $statusMsg : '',
    'trash'              => ($box == BOX_TRASH) ? true : false,
  ));
}

///////////////////////////////////////////////////////////////////////////////
// MESSAGESDETAIL                                                            //
///////////////////////////////////////////////////////////////////////////////

function messages_showMessage($caveID, &$myCaves, $messageID, $box) {
  global $template;

  if (empty($messageID)) {
    $template->throwError('Fehler beim Anzeigen der nachricht. ID wurde nicht übergeben!');
    return;
  }

  // init messages class
  $messagesClass = new Messages;

  // open template
  $template->setFile('messageDetail.tmpl');
  $template->setShowResource(false);

  $message = $messagesClass->getMessageDetail($messageID);
  if (empty($message)) {
    $template->throwError('Es wurde keine Nachricht gefunden.');
    return;
  }

  $antworten = $contacts = $loeschen = array();
  if ($message['sender'] != "System" && $box == BOX_INCOMING) {
    $template->addVars(array(
      'reply' => array(
        array('arg' => "box",       'value' => BOX_INCOMING),
        array('arg' => "subject",   'value' => $messagesClass->createSubject($message['subject'])),
        array('arg' => "recipient", 'value' => $message['sender'])
      )
    ));
    //$contacts = array('contact' => $message['sender']);
  }

  if ($message['nachrichtenart'] != 1001 && $box != BOX_TRASH) {
    $template->addVars(array(
      'delete' => array(
        'name' => 'Löschen',
        'item' => array(
          array('arg' => "box", 'value' => $box),
          array('arg' => "deletebox[" . $messageID . "]", 'value' => $messageID),
          array('arg' => "mark_action_value", 'value' => 'delete'),
        )
      )
    ));
  } else if ($box == BOX_TRASH) {
    $template->addVars(array(
      'recover' => array(
        'name' => 'Wiederherstellen',
        'item' => array(
          array('arg' => "box", 'value' => $box),
          array('arg' => "deletebox[" . $messageID . "]", 'value' => $messageID),
          array('arg' => "mark_action_value", 'value' => 'recover'),
        )
      )
    ));
  }

  // get next and privious messageID
  $messageClass = Request::getVar('filter', -2);

  $messageIdList = array();
  switch ($box) {
    default:
    case BOX_INCOMING:
      $messageIdList = $messagesClass->getIncomingIdList($messageClass);
      break;
    case BOX_OUTGOING:
      $messageIdList = $messagesClass->getOutgoingIdList($messageClass);
      break;
    case BOX_TRASH:
      $messageIdList = $messagesClass->getTrashIdList($messageClass);
      break;
  }

  if (array_key_exists(array_search($messageID, $messageIdList)+1, $messageIdList)) {
    $template->addVar('next_msg_id', $messageIdList[array_search($messageID, $messageIdList)+1]);
  }

  if (array_key_exists(array_search($messageID, $messageIdList)-1, $messageIdList)) {
    $template->addVar('previous_msg_id', $messageIdList[array_search($messageID, $messageIdList)-1]);
  }

  if (!empty($message['messageXML']) && in_array($message['nachrichtenart'], array(2, 6, 7, 9, 11, 20, 28, 29))) {
    $messageXml = @simplexml_load_string($message['messageXML']);
    $template->addVars(array(
      'message_report' => $message['nachrichtenart'],
      'message_xml'    => $messageXml,
      'list_cave_id'   => array_keys($myCaves),
    ));
  }

/****************************************************************************************************
*
* Übergeben ans Template
*
****************************************************************************************************/
  $template->addVars(array(
    'box'           => $box,
    'message'       => $message,
    'message_class' => $messageClass,
    'message_id'    => $messageID,
    'message_img'   => (isset(Config::$messageImage[$message['nachrichtenart']])) ? Config::$messageImage[$message['nachrichtenart']] : '',
    'xml_button'    => (empty($message['messageXML'])) ? false : true,
  ));
}

///////////////////////////////////////////////////////////////////////////////
// NEW_MESSAGE                                                               //
///////////////////////////////////////////////////////////////////////////////

function messages_newMessage($caveID) {
  global $template;

  // get contacts model
  $contacts_model = new Contacts_Model();
  $contacts = $contacts_model->getContacts();

  // open template
  $template->setFile('messageDialogue.tmpl');
  $template->setShowResource(false);

  $template->addVars(array(
    'box'        => Request::getVar('box', BOX_INCOMING),
    'sender'     => $_SESSION['player']->name,
    'recipient'  => unhtmlentities(Request::getVar('recipient', '')),
    'subject'    => Request::getVar('subject', '', true),
    'contacts'   => $contacts,
    'hidden'     => array(
      array('arg' => "box",    'value' => Request::getVar('box', BOX_INCOMING)),
      array('arg' => "caveID", 'value' => $caveID),
      array('arg' => "modus",  'value' => NEW_MESSAGE_RESPONSE)
    ),
  ));
}

///////////////////////////////////////////////////////////////////////////////
// NEW_MESSAGE_RESPONSE                                                      //
///////////////////////////////////////////////////////////////////////////////

function messages_sendMessage($caveID) {
  global $template;

  // init messages class
  $messagesClass = new Messages;

  $zeichen = 16384;

  $subject = (Request::isPost('subject', true)) ? Request::getVar('subject', '', true) : _('Kein Betreff');
  $nachricht = (Request::isPost('nachricht', true)) ? Request::getVar('nachricht', '', true) : '';

  // **** get recipient ****
  $contactID = Request::getVar('contactID', 0);

  // get recipient from contactlist
  $recipient = "";
  if ($contactID > 0) {
    // get contacts model
    $contacts_model = new Contacts_Model();
    $contact = $contacts_model->getContact($contactID);
    $recipient = $contact['contactname'];

  // get recipient from textfield
  } else {
    $recipient = Request::getVar('recipient', '');
  }

  // open template
  $template->setFile('messageDialogue.tmpl');
  $template->setShowResource(false);

  if ((strlen($nachricht) > $zeichen) || empty($nachricht)) {
    $message = array('type' => 'error', 'message' => sprintf(_('Fehler! Nachricht konnte nicht verschickt werden! Stellen Sie sicher, dass die Nachricht nicht länger als %d Zeichen oder leer ist.'), $zeichen));

    $template->addVars(array(
      'box'        => Request::getVar('box', BOX_INCOMING),
      'status_msg' => $message,
      'sender'     => $_SESSION['player']->name,
      'recipient' => $recipient,
      'subject'    => $subject,
      'nachricht'  => $nachricht,
      'hidden'     => array(
        array('arg' => "box",    'value' => Request::getVar('box', BOX_INCOMING)),
        array('arg' => "caveID", 'value' => $caveID),
        array('arg' => "modus",  'value' => NEW_MESSAGE_RESPONSE)
      ),
    ));

    return;
  }

  if ($messagesClass->insertMessageIntoDB($recipient, $subject, $nachricht)) {
    $template->addVar('status_msg', array('type' => 'success', 'message' => _('Ihre Nachricht wurde verschickt!')));
    messages_getMessages($caveID, 0, BOX_INCOMING);
    return;
  } else {
    $message = array('type' => 'error', 'message' => _('Fehler! Nachricht konnte nicht verschickt werden! Stellen Sie sicher, dass es den angegebenen Empfänger gibt.'));

    $template->addVars(array(
      'box'        => Request::getVar('box', BOX_INCOMING),
      'status_msg' => $message,
      'sender'     => $_SESSION['player']->name,
      'recipient'  => $recipient,
      'subject'    => $subject,
      'nachricht'  => $nachricht,
      'hidden'     => array(
        array('arg' => "box",    'value' => Request::getVar('box', BOX_INCOMING)),
        array('arg' => "caveID", 'value' => $caveID),
        array('arg' => "modus",  'value' => NEW_MESSAGE_RESPONSE)
      ),
    ));
  }
}

?>