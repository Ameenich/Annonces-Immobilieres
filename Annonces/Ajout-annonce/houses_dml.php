<?php

// Data functions (insert, update, delete, form) for table houses

// This script and data application were generated by AppGini 5.71
// Download AppGini for free from https://bigprof.com/appgini/download/

function houses_insert(){
	global $Translation;

	// mm: can member insert record?
	$arrPerm=getTablePermissions('houses');
	if(!$arrPerm[1]){
		return false;
	}

	$data['house_number'] = makeSafe($_REQUEST['house_number']);
		if($data['house_number'] == empty_lookup_value){ $data['house_number'] = ''; }
	$data['features'] = br2nl(makeSafe($_REQUEST['features']));
	$data['rent'] = makeSafe($_REQUEST['rent']);
		if($data['rent'] == empty_lookup_value){ $data['rent'] = ''; }
	$data['status'] = makeSafe($_REQUEST['status']);
		if($data['status'] == empty_lookup_value){ $data['status'] = ''; }
	if($data['house_number']== ''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">" . $Translation['error:'] . " 'Numéro de propriété': " . $Translation['field not null'] . '<br><br>';
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	if($data['features']== ''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">" . $Translation['error:'] . " 'Features': " . $Translation['field not null'] . '<br><br>';
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	if($data['rent']== ''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">" . $Translation['error:'] . " 'Rent': " . $Translation['field not null'] . '<br><br>';
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	if($data['status']== ''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">" . $Translation['error:'] . " 'Status': " . $Translation['field not null'] . '<br><br>';
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}

	// hook: houses_before_insert
	if(function_exists('houses_before_insert')){
		$args=array();
		if(!houses_before_insert($data, getMemberInfo(), $args)){ return false; }
	}

	$o = array('silentErrors' => true);
	sql('insert into `houses` set       `house_number`=' . (($data['house_number'] !== '' && $data['house_number'] !== NULL) ? "'{$data['house_number']}'" : 'NULL') . ', `features`=' . (($data['features'] !== '' && $data['features'] !== NULL) ? "'{$data['features']}'" : 'NULL') . ', `rent`=' . (($data['rent'] !== '' && $data['rent'] !== NULL) ? "'{$data['rent']}'" : 'NULL') . ', `status`=' . (($data['status'] !== '' && $data['status'] !== NULL) ? "'{$data['status']}'" : 'NULL'), $o);
	if($o['error']!=''){
		echo $o['error'];
		echo "<a href=\"houses_view.php?addNew_x=1\">{$Translation['< back']}</a>";
		exit;
	}

	$recID = db_insert_id(db_link());

	// hook: houses_after_insert
	if(function_exists('houses_after_insert')){
		$res = sql("select * from `houses` where `id`='" . makeSafe($recID, false) . "' limit 1", $eo);
		if($row = db_fetch_assoc($res)){
			$data = array_map('makeSafe', $row);
		}
		$data['selectedID'] = makeSafe($recID, false);
		$args=array();
		if(!houses_after_insert($data, getMemberInfo(), $args)){ return $recID; }
	}

	// mm: save ownership data
	set_record_owner('houses', $recID, getLoggedMemberID());

	return $recID;
}

function houses_delete($selected_id, $AllowDeleteOfParents=false, $skipChecks=false){
	// insure referential integrity ...
	global $Translation;
	$selected_id=makeSafe($selected_id);

	// mm: can member delete record?
	$arrPerm=getTablePermissions('houses');
	$ownerGroupID=sqlValue("select groupID from membership_userrecords where tableName='houses' and pkValue='$selected_id'");
	$ownerMemberID=sqlValue("select lcase(memberID) from membership_userrecords where tableName='houses' and pkValue='$selected_id'");
	if(($arrPerm[4]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[4]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[4]==3){ // allow delete?
		// delete allowed, so continue ...
	}else{
		return $Translation['You don\'t have enough permissions to delete this record'];
	}

	// hook: houses_before_delete
	if(function_exists('houses_before_delete')){
		$args=array();
		if(!houses_before_delete($selected_id, $skipChecks, getMemberInfo(), $args))
			return $Translation['Couldn\'t delete this record'];
	}

	// child table: tenants
	$res = sql("select `id` from `houses` where `id`='$selected_id'", $eo);
	$id = db_fetch_row($res);
	$rires = sql("select count(1) from `tenants` where `house`='".addslashes($id[0])."'", $eo);
	$rirow = db_fetch_row($rires);
	if($rirow[0] && !$AllowDeleteOfParents && !$skipChecks){
		$RetMsg = $Translation["couldn't delete"];
		$RetMsg = str_replace("<RelatedRecords>", $rirow[0], $RetMsg);
		$RetMsg = str_replace("<TableName>", "tenants", $RetMsg);
		return $RetMsg;
	}elseif($rirow[0] && $AllowDeleteOfParents && !$skipChecks){
		$RetMsg = $Translation["confirm delete"];
		$RetMsg = str_replace("<RelatedRecords>", $rirow[0], $RetMsg);
		$RetMsg = str_replace("<TableName>", "tenants", $RetMsg);
		$RetMsg = str_replace("<Delete>", "<input type=\"button\" class=\"button\" value=\"".$Translation['yes']."\" onClick=\"window.location='houses_view.php?SelectedID=".urlencode($selected_id)."&delete_x=1&confirmed=1';\">", $RetMsg);
		$RetMsg = str_replace("<Cancel>", "<input type=\"button\" class=\"button\" value=\"".$Translation['no']."\" onClick=\"window.location='houses_view.php?SelectedID=".urlencode($selected_id)."';\">", $RetMsg);
		return $RetMsg;
	}

	sql("delete from `houses` where `id`='$selected_id'", $eo);

	// hook: houses_after_delete
	if(function_exists('houses_after_delete')){
		$args=array();
		houses_after_delete($selected_id, getMemberInfo(), $args);
	}

	// mm: delete ownership data
	sql("delete from membership_userrecords where tableName='houses' and pkValue='$selected_id'", $eo);
}

function houses_update($selected_id){
	global $Translation;

	// mm: can member edit record?
	$arrPerm=getTablePermissions('houses');
	$ownerGroupID=sqlValue("select groupID from membership_userrecords where tableName='houses' and pkValue='".makeSafe($selected_id)."'");
	$ownerMemberID=sqlValue("select lcase(memberID) from membership_userrecords where tableName='houses' and pkValue='".makeSafe($selected_id)."'");
	if(($arrPerm[3]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[3]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[3]==3){ // allow update?
		// update allowed, so continue ...
	}else{
		return false;
	}

	$data['house_number'] = makeSafe($_REQUEST['house_number']);
		if($data['house_number'] == empty_lookup_value){ $data['house_number'] = ''; }
	if($data['house_number']==''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Numéro de propriété': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	$data['features'] = br2nl(makeSafe($_REQUEST['features']));
	if($data['features']==''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Features': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	$data['rent'] = makeSafe($_REQUEST['rent']);
		if($data['rent'] == empty_lookup_value){ $data['rent'] = ''; }
	if($data['rent']==''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Rent': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	$data['status'] = makeSafe($_REQUEST['status']);
		if($data['status'] == empty_lookup_value){ $data['status'] = ''; }
	if($data['status']==''){
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Status': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">'.$Translation['< back'].'</a></div>';
		exit;
	}
	$data['selectedID']=makeSafe($selected_id);

	// hook: houses_before_update
	if(function_exists('houses_before_update')){
		$args=array();
		if(!houses_before_update($data, getMemberInfo(), $args)){ return false; }
	}

	$o=array('silentErrors' => true);
	sql('update `houses` set       `house_number`=' . (($data['house_number'] !== '' && $data['house_number'] !== NULL) ? "'{$data['house_number']}'" : 'NULL') . ', `features`=' . (($data['features'] !== '' && $data['features'] !== NULL) ? "'{$data['features']}'" : 'NULL') . ', `rent`=' . (($data['rent'] !== '' && $data['rent'] !== NULL) ? "'{$data['rent']}'" : 'NULL') . ', `status`=' . (($data['status'] !== '' && $data['status'] !== NULL) ? "'{$data['status']}'" : 'NULL') . " where `id`='".makeSafe($selected_id)."'", $o);
	if($o['error']!=''){
		echo $o['error'];
		echo '<a href="houses_view.php?SelectedID='.urlencode($selected_id)."\">{$Translation['< back']}</a>";
		exit;
	}


	// hook: houses_after_update
	if(function_exists('houses_after_update')){
		$res = sql("SELECT * FROM `houses` WHERE `id`='{$data['selectedID']}' LIMIT 1", $eo);
		if($row = db_fetch_assoc($res)){
			$data = array_map('makeSafe', $row);
		}
		$data['selectedID'] = $data['id'];
		$args = array();
		if(!houses_after_update($data, getMemberInfo(), $args)){ return; }
	}

	// mm: update ownership data
	sql("update membership_userrecords set dateUpdated='".time()."' where tableName='houses' and pkValue='".makeSafe($selected_id)."'", $eo);

}

function houses_form($selected_id = '', $AllowUpdate = 1, $AllowInsert = 1, $AllowDelete = 1, $ShowCancel = 0, $TemplateDV = '', $TemplateDVP = ''){
	// function to return an editable form for a table records
	// and fill it with data of record whose ID is $selected_id. If $selected_id
	// is empty, an empty form is shown, with only an 'Add New'
	// button displayed.

	global $Translation;

	// mm: get table permissions
	$arrPerm=getTablePermissions('houses');
	if(!$arrPerm[1] && $selected_id==''){ return ''; }
	$AllowInsert = ($arrPerm[1] ? true : false);
	// print preview?
	$dvprint = false;
	if($selected_id && $_REQUEST['dvprint_x'] != ''){
		$dvprint = true;
	}


	// populate filterers, starting from children to grand-parents

	// unique random identifier
	$rnd1 = ($dvprint ? rand(1000000, 9999999) : '');
	// combobox: status
	$combo_status = new Combo;
	$combo_status->ListType = 0;
	$combo_status->MultipleSeparator = ', ';
	$combo_status->ListBoxHeight = 10;
	$combo_status->RadiosPerLine = 1;
	if(is_file(dirname(__FILE__).'/hooks/houses.status.csv')){
		$status_data = addslashes(implode('', @file(dirname(__FILE__).'/hooks/houses.status.csv')));
		$combo_status->ListItem = explode('||', entitiesToUTF8(convertLegacyOptions($status_data)));
		$combo_status->ListData = $combo_status->ListItem;
	}else{
		$combo_status->ListItem = explode('||', entitiesToUTF8(convertLegacyOptions("Occupé;;Libre")));
		$combo_status->ListData = $combo_status->ListItem;
	}
	$combo_status->SelectName = 'status';
	$combo_status->AllowNull = false;

	if($selected_id){
		// mm: check member permissions
		if(!$arrPerm[2]){
			return "";
		}
		// mm: who is the owner?
		$ownerGroupID=sqlValue("select groupID from membership_userrecords where tableName='houses' and pkValue='".makeSafe($selected_id)."'");
		$ownerMemberID=sqlValue("select lcase(memberID) from membership_userrecords where tableName='houses' and pkValue='".makeSafe($selected_id)."'");
		if($arrPerm[2]==1 && getLoggedMemberID()!=$ownerMemberID){
			return "";
		}
		if($arrPerm[2]==2 && getLoggedGroupID()!=$ownerGroupID){
			return "";
		}

		// can edit?
		if(($arrPerm[3]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[3]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[3]==3){
			$AllowUpdate=1;
		}else{
			$AllowUpdate=0;
		}

		$res = sql("select * from `houses` where `id`='".makeSafe($selected_id)."'", $eo);
		if(!($row = db_fetch_array($res))){
			return error_message($Translation['No records found'], 'houses_view.php', false);
		}
		$urow = $row; /* unsanitized data */
		$hc = new CI_Input();
		$row = $hc->xss_clean($row); /* sanitize data */
		$combo_status->SelectedData = $row['status'];
	}else{
		$combo_status->SelectedText = ( $_REQUEST['FilterField'][1]=='5' && $_REQUEST['FilterOperator'][1]=='<=>' ? (get_magic_quotes_gpc() ? stripslashes($_REQUEST['FilterValue'][1]) : $_REQUEST['FilterValue'][1]) : "");
	}
	$combo_status->Render();

	// code for template based detail view forms

	// open the detail view template
	if($dvprint){
		$template_file = is_file("./{$TemplateDVP}") ? "./{$TemplateDVP}" : './templates/houses_templateDVP.html';
		$templateCode = @file_get_contents($template_file);
	}else{
		$template_file = is_file("./{$TemplateDV}") ? "./{$TemplateDV}" : './templates/houses_templateDV.html';
		$templateCode = @file_get_contents($template_file);
	}

	// process form title
	$templateCode = str_replace('<%%DETAIL_VIEW_TITLE%%>', 'Détails Propriètés', $templateCode);
	$templateCode = str_replace('<%%RND1%%>', $rnd1, $templateCode);
	$templateCode = str_replace('<%%EMBEDDED%%>', ($_REQUEST['Embedded'] ? 'Embedded=1' : ''), $templateCode);
	// process buttons
	if($AllowInsert){
		if(!$selected_id) $templateCode = str_replace('<%%INSERT_BUTTON%%>', '<button type="submit" class="btn btn-success" id="insert" name="insert_x" value="1" onclick="return houses_validateData();"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Save New'] . '</button>', $templateCode);
		$templateCode = str_replace('<%%INSERT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="insert" name="insert_x" value="1" onclick="return houses_validateData();"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Save As Copy'] . '</button>', $templateCode);
	}else{
		$templateCode = str_replace('<%%INSERT_BUTTON%%>', '', $templateCode);
	}

	// 'Back' button action
	if($_REQUEST['Embedded']){
		$backAction = 'AppGini.closeParentModal(); return false;';
	}else{
		$backAction = '$j(\'form\').eq(0).attr(\'novalidate\', \'novalidate\'); document.myform.reset(); return true;';
	}

	if($selected_id){
		if(!$_REQUEST['Embedded']) $templateCode = str_replace('<%%DVPRINT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="dvprint" name="dvprint_x" value="1" onclick="$$(\'form\')[0].writeAttribute(\'novalidate\', \'novalidate\'); document.myform.reset(); return true;" title="' . html_attr($Translation['Print Preview']) . '"><i class="glyphicon glyphicon-print"></i> ' . $Translation['Print Preview'] . '</button>', $templateCode);
		if($AllowUpdate){
			$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '<button type="submit" class="btn btn-success btn-lg" id="update" name="update_x" value="1" onclick="return houses_validateData();" title="' . html_attr($Translation['Save Changes']) . '"><i class="glyphicon glyphicon-ok"></i> ' . $Translation['Save Changes'] . '</button>', $templateCode);
		}else{
			$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '', $templateCode);
		}
		if(($arrPerm[4]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[4]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[4]==3){ // allow delete?
			$templateCode = str_replace('<%%DELETE_BUTTON%%>', '<button type="submit" class="btn btn-danger" id="delete" name="delete_x" value="1" onclick="return confirm(\'' . $Translation['are you sure?'] . '\');" title="' . html_attr($Translation['Delete']) . '"><i class="glyphicon glyphicon-trash"></i> ' . $Translation['Delete'] . '</button>', $templateCode);
		}else{
			$templateCode = str_replace('<%%DELETE_BUTTON%%>', '', $templateCode);
		}
		$templateCode = str_replace('<%%DESELECT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="deselect" name="deselect_x" value="1" onclick="' . $backAction . '" title="' . html_attr($Translation['Back']) . '"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Back'] . '</button>', $templateCode);
	}else{
		$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '', $templateCode);
		$templateCode = str_replace('<%%DELETE_BUTTON%%>', '', $templateCode);
		$templateCode = str_replace('<%%DESELECT_BUTTON%%>', ($ShowCancel ? '<button type="submit" class="btn btn-default" id="deselect" name="deselect_x" value="1" onclick="' . $backAction . '" title="' . html_attr($Translation['Back']) . '"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Back'] . '</button>' : ''), $templateCode);
	}

	// set records to read only if user can't insert new records and can't edit current record
	if(($selected_id && !$AllowUpdate && !$AllowInsert) || (!$selected_id && !$AllowInsert)){
		$jsReadOnly .= "\tjQuery('#house_number').replaceWith('<div class=\"form-control-static\" id=\"house_number\">' + (jQuery('#house_number').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#features').replaceWith('<div class=\"form-control-static\" id=\"features\">' + (jQuery('#features').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#rent').replaceWith('<div class=\"form-control-static\" id=\"rent\">' + (jQuery('#rent').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#status').replaceWith('<div class=\"form-control-static\" id=\"status\">' + (jQuery('#status').val() || '') + '</div>'); jQuery('#status-multi-selection-help').hide();\n";
		$jsReadOnly .= "\tjQuery('.select2-container').hide();\n";

		$noUploads = true;
	}elseif($AllowInsert){
		$jsEditable .= "\tjQuery('form').eq(0).data('already_changed', true);"; // temporarily disable form change handler
			$jsEditable .= "\tjQuery('form').eq(0).data('already_changed', false);"; // re-enable form change handler
	}

	// process combos
	$templateCode = str_replace('<%%COMBO(status)%%>', $combo_status->HTML, $templateCode);
	$templateCode = str_replace('<%%COMBOTEXT(status)%%>', $combo_status->SelectedData, $templateCode);

	/* lookup fields array: 'lookup field name' => array('parent table name', 'lookup field caption') */
	$lookup_fields = array();
	foreach($lookup_fields as $luf => $ptfc){
		$pt_perm = getTablePermissions($ptfc[0]);

		// process foreign key links
		if($pt_perm['view'] || $pt_perm['edit']){
			$templateCode = str_replace("<%%PLINK({$luf})%%>", '<button type="button" class="btn btn-default view_parent hspacer-md" id="' . $ptfc[0] . '_view_parent" title="' . html_attr($Translation['View'] . ' ' . $ptfc[1]) . '"><i class="glyphicon glyphicon-eye-open"></i></button>', $templateCode);
		}

		// if user has insert permission to parent table of a lookup field, put an add new button
		if($pt_perm['insert'] && !$_REQUEST['Embedded']){
			$templateCode = str_replace("<%%ADDNEW({$ptfc[0]})%%>", '<button type="button" class="btn btn-success add_new_parent hspacer-md" id="' . $ptfc[0] . '_add_new" title="' . html_attr($Translation['Add New'] . ' ' . $ptfc[1]) . '"><i class="glyphicon glyphicon-plus-sign"></i></button>', $templateCode);
		}
	}

	// process images
	$templateCode = str_replace('<%%UPLOADFILE(id)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(house_number)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(features)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(rent)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(status)%%>', '', $templateCode);

	// process values
	if($selected_id){
		if( $dvprint) $templateCode = str_replace('<%%VALUE(id)%%>', safe_html($urow['id']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(id)%%>', html_attr($row['id']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(id)%%>', urlencode($urow['id']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(house_number)%%>', safe_html($urow['house_number']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(house_number)%%>', html_attr($row['house_number']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(house_number)%%>', urlencode($urow['house_number']), $templateCode);
		if($dvprint || (!$AllowUpdate && !$AllowInsert)){
			$templateCode = str_replace('<%%VALUE(features)%%>', safe_html($urow['features']), $templateCode);
		}else{
			$templateCode = str_replace('<%%VALUE(features)%%>', html_attr($row['features']), $templateCode);
		}
		$templateCode = str_replace('<%%URLVALUE(features)%%>', urlencode($urow['features']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(rent)%%>', safe_html($urow['rent']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(rent)%%>', html_attr($row['rent']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(rent)%%>', urlencode($urow['rent']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(status)%%>', safe_html($urow['status']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(status)%%>', html_attr($row['status']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(status)%%>', urlencode($urow['status']), $templateCode);
	}else{
		$templateCode = str_replace('<%%VALUE(id)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(id)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(house_number)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(house_number)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(features)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(features)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(rent)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(rent)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(status)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(status)%%>', urlencode(''), $templateCode);
	}

	// process translations
	foreach($Translation as $symbol=>$trans){
		$templateCode = str_replace("<%%TRANSLATION($symbol)%%>", $trans, $templateCode);
	}

	// clear scrap
	$templateCode = str_replace('<%%', '<!-- ', $templateCode);
	$templateCode = str_replace('%%>', ' -->', $templateCode);

	// hide links to inaccessible tables
	if($_REQUEST['dvprint_x'] == ''){
		$templateCode .= "\n\n<script>\$j(function(){\n";
		$arrTables = getTableList();
		foreach($arrTables as $name => $caption){
			$templateCode .= "\t\$j('#{$name}_link').removeClass('hidden');\n";
			$templateCode .= "\t\$j('#xs_{$name}_link').removeClass('hidden');\n";
		}

		$templateCode .= $jsReadOnly;
		$templateCode .= $jsEditable;

		if(!$selected_id){
		}

		$templateCode.="\n});</script>\n";
	}

	// ajaxed auto-fill fields
	$templateCode .= '<script>';
	$templateCode .= '$j(function() {';


	$templateCode.="});";
	$templateCode.="</script>";
	$templateCode .= $lookups;

	// handle enforced parent values for read-only lookup fields

	// don't include blank images in lightbox gallery
	$templateCode = preg_replace('/blank.gif" data-lightbox=".*?"/', 'blank.gif"', $templateCode);

	// don't display empty email links
	$templateCode=preg_replace('/<a .*?href="mailto:".*?<\/a>/', '', $templateCode);

	/* default field values */
	$rdata = $jdata = get_defaults('houses');
	if($selected_id){
		$jdata = get_joined_record('houses', $selected_id);
		if($jdata === false) $jdata = get_defaults('houses');
		$rdata = $row;
	}
	$cache_data = array(
		'rdata' => array_map('nl2br', array_map('html_attr_tags_ok', $rdata)),
		'jdata' => array_map('nl2br', array_map('html_attr_tags_ok', $jdata))
	);
	$templateCode .= loadView('houses-ajax-cache', $cache_data);

	// hook: houses_dv
	if(function_exists('houses_dv')){
		$args=array();
		houses_dv(($selected_id ? $selected_id : FALSE), getMemberInfo(), $templateCode, $args);
	}

	return $templateCode;
}
?>