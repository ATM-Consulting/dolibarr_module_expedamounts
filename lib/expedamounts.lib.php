<?php


/**
* @param Expedition | stdClass $object
* @return void | string
*/
function updateLineShippingTotalPrice(&$object, $scripted = false){
	global $db, $langs;
	$langs->load('expedamounts@expedamounts');
	$totalExped = 0;
	foreach ($object->lines as $line){

		$ol = new OrderLine($db);
		$res = $ol->fetch($line->fk_origin_line);

		// on créer une commande avec fk_origin_line
		if ( $res > 0 ) {
			if (empty($line->array_options)) $line->fetch_optionals();
			// ici c'est la ligne de commande  linkée à la ligne d'expedition que l'on veut remonter !
			$line->array_options['options_shippingline_total_ht'] = ($ol->subprice * $line->qty) ;
			$line->insertExtraFields();
		}else{
			setEventMessages($langs->trans('errorGetOriginOrder',$line->fk_origin_line),'errors');
			if ($scripted){
				echo $langs->trans('updateScriptedLineExpedition',$object->ref );
			}
		}
	}
	return  $scripted ? $langs->trans('updateScriptedLineExpedition',$object->ref )  : '';
}


/**
* @param Expedition $object
* @return void | string
*/
function updateShippingTotalPrice($object, $scripted = false){

	global $langs;

	$langs->load('expedamounts@expedamounts');

	$object->fetchLinesCommon();
	$object->fetch_optionals();
	$cumulHt = 0;
	foreach ($object->lines as $line){
		$line->fetch_optionals();
		if (!empty($line->array_options['options_shippingline_total_ht'])){
			$cumulHt += $line->array_options['options_shippingline_total_ht'];
		}
	}

	if (!empty($object->array_options)){
		$object->array_options['options_total_ht'] = $cumulHt;
		$res = $object->insertExtraFields();
	}

	return  $scripted ? $langs->trans('updateScriptedTotalExpedition', $object->ref ) : '';
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
