<?php
/*
Template.php - Template engine using PHP
Copyright (C) 2006 Marcus Lunzenauer <mlunzena@uos.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

/**
 * Abstract template class representing the presentation layer of an action.
 * Output can be customized by supplying attributes, which a template can
 * manipulate and display.
 *
 * @author    Marcus Lunzenauer (mlunzena@uos.de)
 * @copyright (c) Authors
 */
class Template {

	// +---------------------------------------------------------------------+
	// | PRIVATE VARIABLES                                                   |
	// +---------------------------------------------------------------------+
	var
		$attributes, $layout, $template;

	/**
	 * Constructor
	 *
	 * @param string A name of a template which will be resolve to
	 *               'templates/[name].php'.
	 */
	function Template($template) {
		$this->openTemplate($template);
		$this->attributes = array();
	}

	/**
	 * Clear all attributes associated with this template.
	 *
	 * @return void
	 */
	function clearAttributes() {
		$this->attributes = array();
	}

	/**
	 * Set the template.
	 *
	 * @param string A name of a template which will be resolve to
	 *               'templates/[name].php'.
	 *
	 * @return void
	 */
	function openTemplate($template) {
		$this->template = 'templates/' . $template . '.php';
	}

	/**
	 * Parse, render and return the presentation.
	 *
	 * @param array An optional associative array of attributes and their
	 *              associated values.
	 * @param string A name of a layout template.
	 *
	 * @return string A string representing the rendered presentation.
	 */
	function render($attributes = null, $layout = null) {

		if ($layout) $this->setLayout($layout);

		// put attributes into scope
		extract($this->attributes);
		if (!is_null($attributes))
			extract($attributes);

		// include template, parse it and get output
		ob_start();
		include($this->template);
		$content_for_layout = ob_get_contents();
		ob_end_clean();

		// include layout, parse it and get output
		if (isset($this->layout)) {
			ob_start();
			include($this->layout);
			$content_for_layout = ob_get_contents();
			ob_end_clean();
		}

		return $content_for_layout;
	}

	/**
	 * Class method to parse, render and return the presentation of a
	 * component.
	 *
	 * @param string A name of a template which will be resolve to
	 *               'templates/[name].php'.
	 * @param array An associative array of attributes and their associated
	 *              values.
	 * @param string A name of a layout template.
	 *
	 * @return string A string representing the rendered presentation.
	 */
	function renderComponent($name, $attributes = null, $layout = null) {
		$component = new Template($name);
		return $component->render($attributes, $layout);
	}

	/**
	 * Set an attribute.
	 *
	 * @param string An attribute name.
	 * @param mixed  An attribute value.
	 *
	 * @return void
	 *
	 */
	function setAttribute($name, $value) {
		$this->attributes[$name] = $value;
	}

	/**
	 * Set an array of attributes.
	 *
	 * @param array An associative array of attributes and their associated
	 *              values.
	 *
	 * @return void
	 */
	function setAttributes($attributes) {
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	/**
	 * Set the template's layout.
	 *
	 * @param string A name of a layout template which will be resolve to
	 *               'templates/layouts/[name].php'.
	 *
	 * @return void
	 */
	function setLayout($layout) {
		$this->layout = 'templates/layouts/' . $layout . '.php';
	}
}

?>