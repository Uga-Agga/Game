<?php
/*
Flash.php - Passing Objects Between Actions
Copyright (C) 2006 Marcus Lunzenauer <mlunzena@uos.de>
Copyright (C) 2006 Ruby-On-Rails-Team <http://www.rubyonrails.org/core>

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
 * The flash provides a way to pass temporary objects between actions.
 * Anything you place in the flash will be exposed to the very next action and
 * then cleared out. This is a great way of doing notices and alerts, such as
 * a create action that sets
 * <tt>$flash->set('notice', "Successfully created")</tt>
 * before redirecting to a display action that can then expose the flash to its
 * template.
 *
 * @author    Marcus Lunzenauer (mlunzena@uos.de)
 * @author    Ruby-On-Rails-Team <http://www.rubyonrails.org/core>
 * @copyright (c) Authors
 */
class Flash {

	/*
	+---------------------------------------------------------------------+
	| PRIVATE VARIABLES                                                   |
	+---------------------------------------------------------------------+
	*/
	var
		$flash, $used;

	/**
	 * Constructor
	 *
	 */
	function Flash() {
		$this->flash = array();
		$this->used  = array();
	}

	/**
	 * Used internally by the <tt>keep</tt> and <tt>discard</tt> methods
	 *     use()               # marks the entire flash as used
	 *     use('msg')          # marks the "msg" entry as used
	 *     use(null, false)    # marks the entire flash as unused (keeps it around for one more action)
	 *     use('msg', false)   # marks the "msg" entry as unused (keeps it around for one more action)
	 */
	function _use($k = NULL, $v = TRUE) {
		if ($k)
			$this->used[$k] = $v;
		else
			foreach ($this->used as $k => $v)
				$this->_use($k, $v);
	}

	/**
	 * Marks the entire flash or a single flash entry to be discarded by the end
	 * of the current action.
	 *
	 *     $flash->keep()                # keep entire flash available for the next action
	 *     $flash->discard('warning')    # discard the "warning" entry (it'll still be available for the current action)
	 */
	function discard($k = NULL) {
		$this->_use($k);
	}

	/**
	 * Marks flash entries as used and expose the flash to the view.
	 *
	 */
	function fire() {
		global $sess, $flash;
		if (!$sess->is_registered('flash')) {
			$flash = new Flash();
			$sess->register('flash');
		}
		else {
			$flash = unserialize(base64_decode($flash));
		}
		$flash->discard();
	}

	function get($k) {
		return $this->flash[$k];
	}

	/**
	 * Keeps either the entire current flash or a specific flash entry available for the next action:
	 *
	 *    $flash->keep()           # keeps the entire flash
	 *    $flash->keep('notice')   # keeps only the "notice" entry, the rest of the flash is discarded
	 */
	function keep($k = NULL) {
		$this->_use($k, FALSE);
	}

	/**
	 * Sets a flash that will not be available to the next action, only to the current.
	 *    $flash->now('message') = "Hello current action";
	 *
	 * This method enables you to use the flash as a central messaging system in your app.
	 * When you need to pass an object to the next action, you use the standard flash assign (<tt>set</tt>).
	 * When you need to pass an object to the current action, you use <tt>now</tt>, and your object will
	 * vanish when the current action is done.
	 *
	 * Entries set via <tt>now</tt> are accessed the same way as standard entries: <tt>$flash->get('my-key')</tt>.
	 */
	function now($k, $v) {
		$this->discard($k);
		$this->flash[$k] = $v;
	}

	function set($k, $v) {
		$this->keep($k);
		$this->flash[$k] = $v;
	}

	/**
	 * Deletes the flash entries that were not marked for keeping.
	 *
	 */
	function sweep(){
		global $sess, $flash;
		if (!$sess->is_registered('flash')) {
			$flash = new Flash();
			$sess->register('flash');
		}

		// actually sweep
		$keys = array_keys($flash->flash);
		foreach ($keys as $k) {
			if (!$flash->used[$k]) {
				$flash->_use($k);
			} else {
				unset($flash->flash[$k]);
				unset($flash->used[$k]);
			}
		}

		// cleanup if someone meddled with flash or used
		$fkeys = array_keys($flash->flash);
		$ukeys = array_keys($flash->used);
		foreach (array_diff($fkeys, $ukeys) as $k => $v)
			unset($flash->used[$k]);

		// serialize it
		$flash = base64_encode(serialize($flash));
	}
}

?>