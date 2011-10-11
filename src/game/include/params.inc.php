<?php
/*
 * params.inc.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function cleanSafeBracket($string) {
  $string = preg_replace("/(<)/", "_THIS_MUST_BE_LOWER_THEN_", $string);
  $string = preg_replace("/(>)/", "_THIS_MUST_BE_GREATER_THEN_", $string);
  $string = clean($string);
  $string = preg_replace("/(_THIS_MUST_BE_LOWER_THEN_)/", "&lt;", $string);
  $string = preg_replace("/_THIS_MUST_BE_GREATER_THEN_/", "&gt;", $string);

  return $string;
}

function clean($string){
  return trim(htmlentities(strip_tags($string), ENT_QUOTES));
}

/**
* set_var
*
* Set variable, used by {@link request_var the request_var function}
*
* @access private
*/
function set_var(&$result, $var, $type, $multibyte = false)
{
  settype($var, $type);
  $result = $var;

  if ($type == 'string')
  {
    $result = trim(htmlspecialchars(str_replace(array("\r\n", "\r", "\0"), array("\n", "\n", ''), $result), ENT_COMPAT));

    /*if (!empty($result))
    {
      // Make sure multibyte characters are wellformed
      if ($multibyte)
      {
        if (!preg_match('/^./u', $result))
        {
          $result = '';
        }
      }
      else
      {
        // no multibyte, allow only ASCII (0-127)
        $result = preg_replace('/[\x80-\xFF]/', '?', $result);
      }
    }*/

    $result = (true) ? stripslashes($result) : $result;
  }
}

/**
* request_var
*
* Used to get passed variable
*/
function request_var($var_name, $default, $multibyte = false, $cookie = false)
{
  if (!$cookie && !isset($_COOKIE[$var_name]))
  {
    if (!isset($_GET[$var_name]) && !isset($_POST[$var_name]))
    {
      return (is_array($default)) ? array() : $default;
    }
    $_REQUEST[$var_name] = isset($_POST[$var_name]) ? $_POST[$var_name] : $_GET[$var_name];
  }

  $super_global = ($cookie) ? '_COOKIE' : '_REQUEST';
  if (!isset($GLOBALS[$super_global][$var_name]) || is_array($GLOBALS[$super_global][$var_name]) != is_array($default))
  {
    return (is_array($default)) ? array() : $default;
  }

  $var = $GLOBALS[$super_global][$var_name];
  if (!is_array($default))
  {
    $type = gettype($default);
  }
  else
  {
    list($key_type, $type) = each($default);
    $type = gettype($type);
    $key_type = gettype($key_type);
    if ($type == 'array')
    {
      reset($default);
      $default = current($default);
      list($sub_key_type, $sub_type) = each($default);
      $sub_type = gettype($sub_type);
      $sub_type = ($sub_type == 'array') ? 'NULL' : $sub_type;
      $sub_key_type = gettype($sub_key_type);
    }
  }

  // arrays should remain arrays...
  if (is_array($var))
  {
    $_var = $var;
    $var = array();

    foreach ($_var as $k => $v)
    {
      set_var($k, $k, $key_type);
      if ($type == 'array' && is_array($v))
      {
        foreach ($v as $_k => $_v)
        {
          if (is_array($_v))
          {
            $_v = null;
          }
          set_var($_k, $_k, $sub_key_type, $multibyte);
          set_var($var[$k][$_k], $_v, $sub_type, $multibyte);
        }
      }
      else
      {
        if ($type == 'array' || is_array($v))
        {
          $v = null;
        }
        set_var($var[$k], $v, $type, $multibyte);
      }
    }
  }
  else
  {
    set_var($var, $var, $type, $multibyte);
  }

  return $var;
}

?>