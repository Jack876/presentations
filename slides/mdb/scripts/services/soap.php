<?php

require_once 'globals.inc';
require_once 'SOAP/Server.php';
require_once 'api/function.sqlquery.php';
require_once 'api/function.getpdbfrommetal.php';
require_once 'api/util_convert.php';

class MDB_SOAP_Services {

	var $method_namespace = '';
	var $dispatch_map = array();

	function MDB_SOAP_Services() {
		$this->method_namespace = 'urn:MDB_SOAP_Server';
	}

	function sql($query) {
		if (!eregi("^(select|show|describe)", $query)) {
			trigger_error('Invalid query. Only SELECT, SHOW and DESCRIBE allowed', 
							E_USER_ERROR);
		}
		$db = $GLOBALS['dsn'].$GLOBALS['datadb'];
		$result = @sqlquery($db, $query, false);
		if (is_object($result) && PEAR::isError($result)) {
			trigger_error($result->getMessage(), E_USER_ERROR);
		} else {
			return new SOAP_Value('result','Struct', $result);
		}
	}
	
	function metallopdb($metal, $mode, $count) {
		return $this->_dometallopdb($metal, $mode, $count);
	}

	function rssmetallopdb($metal, $mode, $count) {
		return $this->_dometallopdb($metal, $mode, $count, true);
	}

	function _dometallopdb($metal, $mode, $count, $rss=false) {
		$count = intval($count);
		if ($count <= 0) {
			trigger_error("Parameter count must be an integer > 0, value sent: $count",
							E_USER_ERROR);
		}
		$result = @getPDBFromMetal($metal, $mode, $count);
		if (is_object($result) &&  DB::isError($result)) {
			trigger_error($result->getMessage(), E_USER_ERROR);
		} elseif ($result == false) {
			trigger_error('No results were found', E_USER_WARNING);
		} else {
			if ($rss) {
				$pkt = toRSS(&$result);
				return new SOAP_Value('result','base64', base64_encode($pkt));
			} else {
				return new SOAP_Value('result','Struct', $result);
			}
		}
	}

}

$server = new SOAP_Server();
$mdb_services = new MDB_SOAP_Services($ns);
$server->addObjectMap($mdb_services);
$server->service($HTTP_RAW_POST_DATA);
?>

