<?php
/*
 * Autoloader.php -
 * Copyright (c) 2012 David Unger <unger.dave@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** Set Namespace **/
namespace Lib;

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################
class Autoloader {
    /**
     * Registers Twig_Autoloader as an SPL autoloader.
     */
    static public function register()
    {
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(array(new self, 'autoload'));
    }

    /**
     * Handles autoloading of classes.
     *
     * @param  string  $class  A class name.
     */
    static public function autoload($class)
    {
      // Twig klassen ausschließen!
      if (0 === strpos($class, 'Twig')) {
          return;
      }
      
      $file = ltrim($class, '\\');  // Evntl. vorangegangenen Backslashes entfernen
      $file_array = explode('\\', $file);

      $file = implode(DIRECTORY_SEPARATOR, $file_array);
      $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $file . '.php';
      if (is_file($path)) {
        require $path;
      }
      
    }
}