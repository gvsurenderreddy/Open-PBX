<?php

include('/apps/OpenPBX/www/includes/variables.php');

function _create_sip_OpenPBX_codecs ($ba, $trunkid) {

	$query = $ba->select(	"SELECT " .
					"c.name AS name " .
				"FROM " .
					"sip_codecs AS c," .
					"sip_rel_trunk_codec AS r " .
				"WHERE " .
					"r.trunkid = '" . $trunkid . "' " .
				"AND " .
					"c.id = r.codecid " .
				"ORDER BY " .
					"priority " .
				"ASC");
	while ($entry = $ba->fetch_array($query)) {
		$ret .= $entry['name'] . ',';
	}

	return(substr_replace($ret, '', -1));
}

function create_sip_OpenPBX ($ba, $ami) {

	$fn = BAF_APP_AST_CFG . '/sip_OpenPBX.conf';

	$query = $ba->select(	"SELECT " .
					"s.id AS id," .
					"s.name AS name," .
					"s.user AS user," .
					"s.password AS password," .
					"s.registrar AS registrar," .
					"s.proxy AS proxy," .
					"d.name AS dtmfmode " .
				"FROM " .
					"sip_trunks AS s," .
					"sip_dtmfmodes AS d " .
				"WHERE " .
					"s.dtmfmode = d.id");

	while ($entry = $ba->fetch_array($query)) {

		$regs .= "register => " . $entry['user'] . ":" . $entry['password'] . "@" . $entry['registrar'] . "\n";

		$name = str_replace(' ', '_', $entry['name']);
		$host = explode(':', $entry['registrar']);

		$secs .=	"[" . $name . "]\n" .
				"context = inbound_" . $name . "\n" .
				"type = friend\n" .
				"host = " .	$host[0]		. "\n" .
				"username = " .	$entry['user']		. "\n" .
				"secret = " .	$entry['password']	. "\n" .
				"proxy = " .	$entry['proxy']		. "\n" .
				"dtmfmode = " .	$entry['dtmfmode']	. "\n" .
				"disallow = all\n" .
				"allow = " . _create_sip_OpenPBX_codecs($ba, $entry['id']) . "\n" .
				((!empty($entry['details'])) ? $entry['details'] . "\n" : '') .
				"\n";
	}
	unset($entry);
	unset($query);

	$cont =	"; Generated by OpenPBX 'create_files_sip.php'\n\n" .
		"[general](+)\n" .
		(!empty($regs) ? $regs . "\n" : '') .
		(!empty($secs) ? $secs : '');

	unset($regs);
	unset($secs);

	$query = $ba->select(	"SELECT " .
					"u.name AS name," .
					"u.password AS password," .
					"u.voicemail AS voicemail," .
					"e.extension AS extension " .
				"FROM " .
					"sip_users AS u," .
					"sip_extensions AS e " .
				"WHERE " .
					"u.extension = e.id");

	while ($entry = $ba->fetch_array($query)) {

		$cont .=	"[" . $entry['extension'] . "]\n" .
				"context = intern\n" .
				"type = friend\n" .
				"host = dynamic\n" .
				"username = " . $entry['extension'] . "\n" .
				"secret = " . $entry['password'] . "\n" .
				"callerid = \"" . $entry['name'] . "\" <" . $entry['extension'] . ">\n" .
				(($entry['voicemail'] == 1) ? "mailbox = " . $entry['extension'] . "\n" : '') .
				"dtmfmode = rfc2833\n" .
				"disallow = all\n" .
				"allow = alaw\n" .
				"nat = no\n" .
				"canreinvite = no\n" .
				"call-limit = 2\n" .
				"\n";

		$res = $ami->DBGET('CFWD', $entry['extension']);
		if(strpos($res, 'Error')) {
			$ami->DBPUT('CFWD', $entry['extension'], '0');
		}
	}
	unset($entry);
	unset($query);

	_create_dirs();
	_save_conf($fn, $cont);
}

function create_sip ($ba) {

	$fn = BAF_APP_AST_CFG . '/sip.conf';

	$cont =	"[general](+)\n" .
		"context = default\t\t\t; Default context for incoming calls\n" .
		"allowoverlap = no\t\t\t; Disable overlap dialing support. (Default is yes)\n" .
		"srvlookup = yes\t\t\t\t; Enable DNS SRV lookups on outbound calls\n" .
		"allow = ulaw\t\t\t\t; dtmfmode = inband only works with ulaw or alaw!\n" .
		"defaultexpire = 60\n" .
		"allowsubscribe = yes\n" .
		"subscribecontext = intern\n" .
		"notifyringing = yes\n" .
		"notifyhold = yes\n" .
		"progressinband = yes\n" .
//		"limitonpeer = yes\n" .
		"\n" .
		"[default]\n" .
		"call-limit = 2\n" .
//		"type = peer\n" .
		"\n" .
		"#include " . BAF_APP_AST_CFG . "/sip_OpenPBX.conf\n";

	_create_dirs();
	_save_conf($fn, $cont);
}

?>
