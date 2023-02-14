<?php
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
dol_include_once('../lib/expedamounts.lib.php');

initExtrafieldsValues();
function initExtrafieldsValues()
{
	global $db;
	dol_include_once('expedamounts/lib/expedamounts.lib.php');


	$sql = "SELECT e.rowid FROM ".MAIN_DB_PREFIX."expedition e";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expedition_extrafields ee on ee.fk_object = e.rowid";
	$sql.= " WHERE ee.total_ht is null";
	$sql.= " OR ee.total_ht = 0";


	$resql = $db->query($sql);
	if ($resql)
	{
		echo 'starting script ...<br>';
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

		while ($obj = $db->fetch_object($resql))
		{
			$exped = new Expedition($db);
			$resfetch = $exped->fetch($obj->rowid);
			$exped->fetch_lines();
			if ($resfetch > 0 && !empty($exped->lines))
			{
				$ret1 = updateLineShippingTotalPrice($exped, true);
				$ret2 = updateShippingTotalPrice($exped, true );
				echo $ret1 . $ret2;
				flush();
			}
		}
	}
	echo 'end of script.';
}
