<?php


/**
* @param Expedition | stdClass $object
* @return void | string
*/
function updateLineShippingTotalPrice(&$object, $scripted = false){
	global $db, $langs;
	$langs->load('expedamounts@expedamounts');
	$totalExped = 0;
	if (is_array($object->lines) &&  !empty($object->lines)){
		$errors = array();
		foreach ($object->lines as $line){

			// on créer une commande avec fk_origin_line
			$ol = new OrderLine($db);
			$res = $ol->fetch($line->fk_origin_line);
			if ( $res > 0 ) {
				if (empty($line->array_options)) $line->fetch_optionals();
				// ici c'est la ligne de commande  linkée à la ligne d'expedition que l'on veut remonter !
				$line->array_options['options_shippingline_total_ht'] = ($ol->subprice * $line->qty) ;
				$resExtra = $line->insertExtraFields();

				if ($resExtra < 0 ){
					dol_syslog( $langs->trans('errorInsertExtrafield', $line->id), 'LOG_ERR');
				}
			}else{
				setEventMessages($langs->trans('errorGetOriginOrder',$line->fk_origin_line),'errors');
				if ($scripted){
					echo $langs->trans('updateScriptedLineExpedition',$object->ref );
				}
			}
		}
		return  $scripted ? $langs->trans('updateScriptedLineExpedition',$object->ref )  : '';
	}

	return 0;
}


/**
* @param Expedition $object
* @return void | string
*/
function updateShippingTotalPrice($object, $scripted = false){

	global $langs;

	$langs->load('expedamounts@expedamounts');

		$res = $object->fetch_optionals();
		if ($res > 0 ) {
			$cumulHt = 0;
			$object->fetch_lines();
			if (is_array($object->lines) && !empty($object->lines)){

				foreach ($object->lines as $line) {
					$res = $line->fetch_optionals();
					if ($res > 0 ){
						if (!empty($line->array_options['options_shippingline_total_ht'])) {
							$cumulHt += $line->array_options['options_shippingline_total_ht'];
						}
					}else{
						dol_syslog( $langs->trans('errorLineFetchOptional', $line->id), 'LOG_ERR');
					}

				}

				if (!empty($object->array_options)) {
					$object->array_options['options_total_ht'] = $cumulHt;
					$res = $object->insertExtraFields();
				}
				return $scripted ? $langs->trans('updateScriptedTotalExpedition', $object->ref) : '';

			}else{

				if (!empty($object->array_options)) {
					$object->array_options['options_total_ht'] = 0;
					$res = $object->insertExtraFields();
				}
			}

		}else{
		  dol_syslog( $langs->trans('errorFetchOptional', $object->id), 'LOG_ERR');
		}

	return 0;
}

/**
 * @param $db
 * @param $object
 * @param $langs
 * @return void
 */
function updateShipping($db, $object,$langs){

	$langs->load('expedamounts@expedamounts');

	$exp = new Expedition($db);
	$res = $exp->fetch($object->fk_expedition);
	if ($res > 0 ){
		updateShippingTotalPrice($exp);
	}else {
		setEventMessages($langs->trans('errorDeleteLinetotalHtShipping', $object->fk_expedition ),'errors');
	}
}

/**
 * @param $db
 * @param $scripted
 * @return void
 */
function initExtrafieldsValues($db ,$scripted = false)
{


	$sql = "SELECT e.rowid FROM ".MAIN_DB_PREFIX."expedition e";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expedition_extrafields ee on ee.fk_object = e.rowid";
	$sql.= " WHERE ee.total_ht is null";
	$sql.= " OR ee.total_ht = 0";


	$resql = $db->query($sql);
	if ($resql)
	{

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

				if ($scripted){
					echo $ret1 . $ret2;
					flush();
				}
			}
		}
	}

}
