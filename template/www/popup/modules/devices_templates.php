<?php

class PopupModule {

	private $_name = 'devices_templates';
	private $_title = 'Add/Modify a device template';

	function getName() {
		return($this->_name);
	}

	function getTitle() {
		return($this->_title);
	}

	private function _execute_dev_templ_write($path, $content) {
		if (!($fp = @fopen($path, 'w'))) {
			return(false);
		}

		fputs($fp, str_replace('\"', '"', $content));
		fclose($fp);

		@chown($path, 'admin');
		@chgrp($path, 'admin');

		return(true);
	}

	private function _execute_dev_templ_create ($ba) {

		$name = str_replace(' ', '_', trim($_POST['name']));
		if ($name == 'New_Template_Name') {
			return("<script>window.history.back(); alert('Please enter a valid name');</script>\n");
		}

		$query = $ba->select("SELECT id FROM phone_templates where name = '" . $name . "'");
		if ($ba->num_rows($query) > 0) {
			return("<script>window.history.back(); alert('Name \'" . $name . "\' already exists.');</script>\n");
		}
		unset($query);

		$path = BAF_APP_ETC . '/settings/default/' . $name . '.xml';
		if (!$this->_execute_dev_templ_write($path, $_POST['template'])) {
			return("<script>window.history.back(); alert(Could not write to '" . $path . "'!);</script>\n");
		}

		$ba->insert_("INSERT INTO phone_templates ('name', 'description', 'path') VALUES('" . $name ."', '" . $_POST['description'] . "', '" . $path . "')");
		if ($ba->is_error()) {
			return("<script>window.history.back(); alert(" . $ba->error() . ");</script>\n");
		}

		$ret =	"<script>window.opener.location='" . BAF_URL_BASE . "/index.php?m=" . $this->_name . "'</script>\n" .
			"<script>this.window.close();</script>";

		return($ret);
	}

	private function _execute_dev_templ_update ($ba) {

		if (!file_exists($_POST['path'])) {
			return("<script>window.history.back(); alert(File '" . $_POST['path'] . "' does not exists!);</script>\n");
		}

		if (!$this->_execute_dev_templ_write($_POST['path'], $_POST['template'])) {
			return("<script>window.history.back(); alert(Could not write to '" . $path . "'!);</script>\n");
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

		$ret = ((empty($_POST['id'])) ? $this->_execute_dev_templ_create($ba) : $this->_execute_dev_templ_update($ba));

		return($ret);
	}

	function display() {

		$ba = new beroAri();
		if ($ba->is_error()) {
			die($ba->error());
		}

		if (isset($_GET['id'])) {
			$query = $ba->select("SELECT * FROM phone_templates WHERE id = '" . $_GET['id'] . "'");
			$entry = $ba->fetch_array($query);
			unset($query);
		}

		if (file_exists($entry['path'])) {
			$template = implode(file($entry['path']));
		}

		$modify = (isset($_GET['modify'])) ? 1 : 0;

		$ret =	"<form name=\"device_templates\" action=\"" . BAF_URL_BASE . "/popup/index.php?m=" . $_GET['m'] . "&execute\" method=\"POST\" onsubmit=\"return is_submit(this);\">\n" .
			"\t<table class=\"default\" id=\"device_templates\">\n" .
			"\t\t<tr>\n" .
			"\t\t\t<th colspan=\"2\">" . ($modify ? 'Modify' : 'Copy') . " Template</th>\n" .
			"\t\t</tr>\n" .
			"\t\t<tr class=\"sub_head\">\n" .
			"\t\t\t<td>Template Name</td>\n" .
			"\t\t\t<td>\n" .
			"\t\t\t\t<input type=\"text\" name=\"name\" size=\"40\"" . ($modify ? " readonly=\"readonly\"" : '') . " value=\"" . ($modify ? $entry['name'] : "New Template Name") . "\" />\n" .
			"\t\t\t</td>\n" .
			"\t\t</tr>\n" .
			"\t\t<tr class=\"sub_head\">\n" .
			"\t\t\t<td>Description</td>\n" .
			"\t\t\t<td>\n" .
			"\t\t\t\t<input type=\"text\" name=\"description\" size=\"40\"" . ($modify ? " readonly=\"readonly\"" : '') . " value=\"" . ($modify ? $entry['description'] : "New Template Description") . "\" />\n" .
			"\t\t\t</td>\n" .
			"\t\t</tr>\n" .
			"\t\t<tr>\n" .
			"\t\t\t<td colspan=\"2\">\n" .
			"\t\t\t\t<textarea class=\"template_edit\" name=\"template\">\n" .
			$template .
			"\t\t\t\t</textarea>\n" .
			"\t\t\t</td>\n" .
			"\t</table>\n" .
			($modify ? "\t<input type=\"hidden\" name=\"id\" value=\"" . $entry['id'] . "\" />\n" : '') .
			($modify ? "\t<input type=\"hidden\" name=\"path\" value=\"" . $entry['path'] . "\" />\n" : '') .
			"\t<input type=\"submit\" name=\"submit\" value=\"Save\" />\n" .
			"\t&nbsp;&nbsp;\n" .
			"\t<input type=\"button\" name=\"close\" value=\"Close\" onclick=\"javascript:popup_close();\" />\n" .
			"</form>\n";

		return($ret);
	}
}

?>
