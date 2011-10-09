<?php
/*
 * db.inc.php -
 * Copyright (c) 2004  ogp team@uga-agga.de
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class DbResult{
  var $result;
  var $row;

  function DbResult($result){
    $this->result = $result;
  }

  function isEmpty(){
    return mysql_num_rows($this->result) == 0;
  }

  function numRows(){
    return mysql_num_rows($this->result);
  }

  function nextRow($result_type = MYSQL_ASSOC){
    $this->row = &mysql_fetch_array($this->result, $result_type);
    return $this->row;
  }

  function nextField(){
    return mysql_fetch_field($this->result);
  }

  function actRow(){
    return $this->row;
  }
  /** nur verwenden, wenn nextRow() nicht
   *  mit dieser Instanz verwendet wird
   */
  function field($row, $column){
    return mysql_result($this->result, $row, $column);
  }

  function free(){
    mysql_free_result($this->result);
  }
}


class Db{

  var $con;

  /** Verbindung erzeugen
   */
  function & Db($host, $user, $pwd, $name){

    if (!($this->con = mysql_connect($host, $user, $pwd, TRUE)))
      exit('could not connect');

    if (!(mysql_select_db($name, $this->con)))
      exit('could not select');

    mysql_query("SET NAMES 'latin1'", $this->con);
    mysql_query("SET CHARACTER SET 'latin_swedish_ci'", $this->con);

    return $this;
  }

  /** Query absetzen
   */
  function query($query){
    if (!($rs = mysql_query($query, $this->con))){
      return 0;
    }
    return new DbResult($rs);
  }

  /** insertID holen
   */
  function insertID(){
    return mysql_insert_id($this->con);
  }

  function affected_rows(){
    return mysql_affected_rows($this->con);
  }

  function get_error(){
    return mysql_error($this->con);
  }
}
?>
