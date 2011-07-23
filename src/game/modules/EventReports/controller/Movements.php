<?php
/*
 * Movements.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Controller.php');
require_once('modules/EventReports/model/Movements.php');
require_once('modules/EventReports/view/Movements.php');

require_once('include/formula_parser.inc.php');
require_once('lib/Movement.php');

class EventReports_Movements_Controller extends Controller {

  function EventReports_Movements_Controller() {
  }

  function execute($caveID, $caves) {
    global $unitTypeList;

    // get model
    $model = new EventReports_Movements_Model($caveID, $caves);

    // get movements
    $movements = $model->getGroupedMovements();

    // get movement categories
    $ua_movements = Movement::getMovements();

    // prepare data for the view
    $categories = array();

    // foreach movement category..
    foreach ($movements as $category => $moves) {

      // set name and caves-header
      $category = array('name' => $ua_movements[$category]->description,
                        'CAVE' => $caves);

      // foreach unittype..
      foreach ($unitTypeList as $unitType) {

        // get sum
        $sum = isset($moves[$unitType->dbFieldName])
               ? array_sum($moves[$unitType->dbFieldName])
               : 0;
        if (!$sum) continue;

        // set name and sum
        $unit = array('name'        => $unitType->name,
                      'dbFieldName' => $unitType->dbFieldName,
                      'sum'         => $sum);

        // foreach cave..
        foreach ($caves as $caveID => $cave)
          $unit['CAVE'][] = array('amount' => intval($moves[$unitType->dbFieldName][$caveID]));

        $category['UNIT'][] = $unit;
      }
      $categories[] = $category;
    }

    // create View
    $view = new EventReports_Movements_View($_SESSION['player']->language,
                                            $_SESSION['player']->template);

    // set tmpl data
    if (sizeof($categories))
      $view->setCategories($categories);

    // return view
    //return $view->toString();
    // FIXME
    return array($view->getTitle(), $view->getContent());
  }
}

?>