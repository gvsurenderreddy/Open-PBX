<?php

class PopupModule {

	private $_name = 'devices_phones';
	private $_title = 'Add/Modify Phone';

	function getName() {
		return($this->_name);
	}

	function getTitle () {
		return($this->_title);
	}

	private function _execute_dev_update ($ba) {

		$query = $ba->select("SELECT id FROM phone_devices WHERE name = '" . $_POST['name'] . "'");
		if ($ba->num_rows($query) == 0) {
			return("<script>window.history.back(); alert('Device '" . $_POST['name'] . "' does not exist!');</script>\n");
		}

		foreach ($ba->fetch_array($query) as $entry) {
			if ($_POST['id_upd'] != $entry['id']) {
				return("<script>window.history.back(); alert('Invalid Name!');</script>\n");
			}
		}

		$ba->update(	"UPDATE " .
					"phone_devices " .
				"SET " .
					"name = '" .		$_POST['name']		. "'," .
					"ipaddr = '" .		$_POST['ip']		. "'," .
					"macaddr = '" .		$_POST['mac']		. "'," .
					"typeid = '" .		$_POST['type']		. "'," .
					"tmplid = '" .		$_POST['template']	. "' " .
				"WHERE " .
					"id = '" . $_POST['id_upd'] . "'");

		$ba->update("UPDATE activate SET option = 1 WHERE id = 'activate' AND option < 1");

		if ($ba->is_error()) {
			return("<script>alert(" . $ba->error() . ");</script>\n");
		}

		$ret =	"<script>window.opener.location='" . BAF_URL_BASE . "/index.php?m=" . $this->_name . "'</script>\n" .
			"<script>this.window.close();</script>\n";

		return($ret);
	}

	private function _execute_dev_create ($ba) {

		if (($_POST['submit'] == 'Save') && (!isset($_POST['name']))) {
			return("<script>window.history.back(); alert('Please fill in the form completly!')</script>\n");
		}

		$query = $ba->select("SELECT id FROM phone_devices WHERE name = '" . $_POST['name'] . "'");
		if ($ba->num_rows($query) > 0) {
			return("<script> window.history.back(); alert('Name '" . $_POST['name'] . "' is already in use!');</script>\n");
		}

		$ba->insert_("INSERT INTO phone_devices (name, typeid, ipaddr, macaddr, tmplid) VALUES ('" .
					$_POST['name'] . "', '" .
					$_POST['type'] . "', '" .
					$_POST['ip'] . "', '" .
					$_POST['mac'] . "', '" .
					$_POST['template'] . "')");

		$ba->update("UPDATE activate SET option = 1 WHERE id = 'activate' AND option < 1");

		if ($ba->is_error()) {
			return("<script>alert(" . $ba->error() . ");</script>\n");
		}

		$ret =	"<script>window.opener.location='" . BAF_URL_BASE . "/index.php?m=" . $this->_name . "'</script>\n" .
			"<script>this.window.close();</script>\n";

		return($ret);
	}

	function execute() {

		if (!isset($_GET['execute'])) {
			return('');
		}

		$ba = new beroAri();
		if ($ba->is_error()) {
			die($ba->error());
		}

		if (isset($_POST['id_upd'])) {
			$ret = $this->_execute_dev_update($ba);
		} else {
			$ret = $this->_execute_dev_create($ba);
		}

		return($ret);
	}

	private function _display_type ($ba, $type) {

		$query = $ba->select("SELECT * FROM phone_types");
		while ($entry = $ba->fetch_array($query)) {
			$selected = (isset($_GET['id']) && ($type == $entry['id'])) ? 'selected ' : '';
			$opts .= "\t<option value=\"" . $entry['id'] . "\" " . $selected . "/>" . $entry['name'] . "</option>\n";
		}

		$ret =	"<select class=\"fill\" name=\"type\">\n" .
			$opts .
			"</select>\n";

		return($ret);
	}

	private function _display_template ($ba, $tmpl) {

		$query = $ba->select("SELECT * FROM phone_templates");
		while ($entry = $ba->fetch_array($query)) {
			$selected = (isset($_GET['id']) && ($tmpl == $entry['id'])) ? 'selected ' : '';
			$opts .= "\t<option value=\"" . $entry['id'] . "\" " . $selected . "/>" . $entry['name'] . "</option>\n";
		}

		$ret =	"<select class=\"fill\" name=\"template\">\n" .
			$opts .
			"</select>\n";

		return($ret);
	}

	function display () {

		$ba = new beroAri();

		if (isset($_GET['id'])) {
			$query = $ba->select("SELECT * FROM phone_devices WHERE id = '" . $_GET['id'] . "'");
			$entry = $ba->fetch_array($query);
		}

		$ret =	"<form name=\"devices\" action=\"" . BAF_URL_BASE . "/popup/index.php?m=" . $_GET['m'] . "&execute\" method=\"POST\" onsubmit=\"return verifyIP(ip.value);\">\n" .
			"\t<table class=\"default\">\n" .
			"\t\t<tr class=\"sub_head\">\n" .
			"\t\t\t<td>Name</td>\n" .
			"\t\t\t<td>\n" .
			"\t\t\t\t<input type=\"text\" class=\"fill\" name=\"name\" value=\"" . $entry['name'] . "\" />\n" .
			"\t\t\t</td>\n" .
			"\t\t</tr>\n" .
			"\t\t<tr class=\"sub_head\">\n" .
			"\t\t\t<td>Type</td>\n" .
			"\t\t\t<td>\n" .
			$this->_display_type($ba, $entry['typeid']) .
			"\t\t\t</td>\n" .
			"\t\t</tr>\n" .
			"\t\t<tr class=\"sub_head\">\n" .
			"\t\t\t<td>IP-Address</td>\n" .
			"\t\t\t<td>\n" .
			"\t\t\t\t<input type=\"text\" class=\"fill\" name=\"ip\" value=\"" . $entry['ipaddr'] . "\" />\n" .
			"\t\t\t</td>\n" .
			"\t\t</tr>\n" .
			"\t\t<tr class=\"sub_head\">\n" .
			"\t\t\t<td>MAC-Address</td>\n" .
			"\t\t\t<td>\n" .
			"\t\t\t\t<input type=\"text\" class=\"fill\" name=\"mac\" value=\"" . $entry['macaddr'] . "\" />\n" .
			"\t\t\t</td>\n" .
			"\t\t</tr>\n" .
			"\t\t<tr class=\"sub_head\">\n" .
			"\t\t\t<td>Template</td>\n" .
			"\t\t\t<td>\n" .
			$this->_display_template($ba, $entry['tmplid']) .
			"\t\t\t</td>\n" .
			"\t\t</tr>\n" .
			"\t</table>\n" .
			(isset($entry['id']) ? "<input name=\"id_upd\" type=\"hidden\" value=\"" . $entry['id'] . "\" />\n" : '') .
			"\t<input type=\"submit\" name=\"submit\" value=\"Save\" />\n" .
			"\t&nbsp&nbsp\n" .
			"\t<input type=\"button\" name=\"close\" value=\"Close\" onclick=\"javascript:popup_close();\" />\n" .
			"</form>\n";

		return($ret);
	}
}

?>
