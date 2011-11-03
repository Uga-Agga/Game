<?php
/*
 * db.inc.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function DbConnect($host=0, $user=0, $pwd=0, $name=0) {
  global $config;

  if(!$host) $host  = $config->DB_HOST;
  if(!$user) $user  = $config->DB_USER;
  if(!$pwd)  $pwd   = $config->DB_PWD;
  if(!$name) $name  = $config->DB_NAME;

  /* Connect to an ODBC database using driver invocation */
  $dsn = "mysql:dbname={$name};host={$host}";

  try {
    $db = new ePDO($dsn, $user, $pwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'));
  } catch (PDOException $e) {
    return false;
  }

  // set right sql mode
  $db->query("SET sql_mode = 'NO_UNSIGNED_SUBTRACTION'");

  return $db;
}

class ePDO extends PDO {
  private $queryCount;
  private $queryTime;

  public function __construct($dsn, $user = NULL, $pwd = NULL, $options = NULL) {
    $this->queryCount = 0;
    $this->queryTime = 0;

    parent::__construct($dsn, $user, $pwd, $options);

    $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('ePDOStatement', array($this)));
    $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    //just for debugging
    if (SQL_DEBUG) {
      $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    else {
      $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
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
    } catch(PDOException $e) {
      $this->printBacktrace(debug_backtrace());
      $data = false;
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

  private function printBacktrace($backtrace) {
    if (SQL_DEBUG) {
      $sqlErrorMessage = $this->errorInfo();
      $backtrace[0]['sqlErrorMessage'] = $sqlErrorMessage[2];
      print_r($backtrace[0]);
    }
  }
}

class ePDOStatement extends PDOStatement {
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
    $count = count($this->fetchAll(PDO::FETCH_ASSOC));
    $this->closeCursor();
    return $count;
  }
}

?>