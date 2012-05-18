<?php
/*
 * Database.php -
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
class Database {
  public static $db;

  public static function connect() {
    /* Connect to an ODBC database using driver invocation */
    $dsn = 'mysql:dbname=' . Config::DB_NAME . ';host=' . Config::DB_HOST;

    try {
      $db = new ePDO($dsn, Config::DB_USER, Config::DB_PWD, array(ePDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'));
    } catch (PDOException $e) {
      return false;
    }

    // set right sql mode
    $db->query("SET sql_mode = 'NO_UNSIGNED_SUBTRACTION'");

    self::$db = $db;
  }
}

class ePDO extends \PDO {
  const PARAM_SET = -1;

  private $queryCount;
  private $queryTime;

  public function __construct($dsn, $user = NULL, $pwd = NULL, $options = NULL) {
    $this->queryCount = 0;
    $this->queryTime = 0;

    parent::__construct($dsn, $user, $pwd, $options);

    $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('\Lib\ePDOStatement', array($this)));
    $this->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    $this->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    //just for debugging
    if (SQL_DEBUG) {
      $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
    else {
      $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
    }
  }

  public function exec($sql) {
    $this->increaseQueryCount();
    $start = microtime();
    $data = parent::exec($sql);
    $this->incraseQueryTime($start, microtime());

    return $data;
  }

  public function query($sql) {
    $this->increaseQueryCount();
    $args = func_get_args();

    $start = microtime();
    try {
      $data = call_user_func_array(array($this, 'parent::query'), $args);
    } catch(\PDOException $e) {
      throw new UAException($e);
    }
    $this->incraseQueryTime($start, microtime());

    return $data;
  }

  public function getQueryCount() {
    return $this->queryCount;
  }

  public function getQueryTime() {
    return $this->queryTime;
  }

  public function increaseQueryCount() {
    $this->queryCount++;
  }

  public function incraseQueryTime($start, $end) {
    $this->queryTime += ($end - $start);
  }
}

class ePDOStatement extends \PDOStatement {
  private $pdo;

  protected function __construct(ePDO $pdo) {
    $this->pdo = $pdo;
  }

  public function execute($params = null) {
    $this->pdo->increaseQueryCount();

    $start = microtime();
    $data = parent::execute($params);
    $this->pdo->incraseQueryTime($start, microtime());

    return $data;
  }

  public function rowCountSelect() {
    $this->execute();
    $count = count($this->fetchAll(\PDO::FETCH_ASSOC));
    $this->closeCursor();
    return $count;
  }

  public function fetchAllKey($key) {
    $ret = array();

    $this->execute();
    while($row = $this->fetch(\PDO::FETCH_ASSOC)) {
      $ret[$row[$key]] = $row;
    }
    $this->closeCursor();

    return $ret;
  }
}

?>