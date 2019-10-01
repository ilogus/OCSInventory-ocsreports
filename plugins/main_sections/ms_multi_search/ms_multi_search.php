<?php
/*
 * Copyright 2005-2016 OCSInventory-NG/OCSInventory-ocsreports contributors.
 * See the Contributors file for more details about them.
 *
 * This file is part of OCSInventory-NG/OCSInventory-ocsreports.
 *
 * OCSInventory-NG/OCSInventory-ocsreports is free software: you can redistribute
 * it and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 2 of the License,
 * or (at your option) any later version.
 *
 * OCSInventory-NG/OCSInventory-ocsreports is distributed in the hope that it
 * will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OCSInventory-NG/OCSInventory-ocsreports. if not, write to the
 * Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

if (AJAX) {
    parse_str($protectedPost['ocs']['0'], $params);
    $protectedPost += $params;
    ob_start();
}

require('require/function_search.php');
require('require/function_computers.php');
require("require/search/DatabaseSearch.php");
require("require/search/AccountinfoSearch.php");
require("require/search/TranslationSearch.php");
require("require/search/GroupSearch.php");
require("require/search/LegacySearch.php");
require("require/search/Search.php");
require_once('require/function_admininfo.php');

// Get tables and columns infos
$databaseSearch = new DatabaseSearch();

// Get columns infos datamap structure
$accountInfoSearch = new AccountinfoSearch();

// Get columns infos datamap structure
$translationSearch = new TranslationSearch();

// Get columns infos datamap structure
$groupSearch = new GroupSearch();

// Get search object to perform action and show result
//$legacySearch = new LegacySearch();

$search = new Search($translationSearch, $databaseSearch, $accountinfoSearch, $groupSearch);

if (isset($protectedPost['table_select'])) {
	$defaultTable = $protectedPost['table_select'];
} else {
	$defaultTable = null;
}

/**
 * Ajout d'un système temporaire d'isolation des windows
 */
if(empty($_SESSION['edit_tmp_unix'])) {
    $_SESSION['edit_tmp_unix'] = 'NO_POST';
} else {
    $_SESSION['edit_tmp_unix'] = 'AL_POST';
}

?>
<div class="panel panel-default">

	<?php printEnTete($l->g(9)); ?>

	<div class="row">
		<div class="col-md-12">


			<?php echo open_form('addSearchCrit', '', '', '') ?>

			<div class="row">
				<div class="col-sm-2"></div>
				<div class="col-sm-3">
					<div class="form-group">
						<select class="form-control" name="table_select" onchange="this.form.submit()">
							<?php echo $search->getSelectOptionForTables($defaultTable)  ?>
						</select>
					</div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						<select class="form-control" name="columns_select">
							<?php
								if (!is_null($defaultTable)) {
									echo $search->getSelectOptionForColumns($defaultTable);
								}
							?>
						</select>
					</div>
				</div>
				<div class="col-sm-2">
					<input type="submit" class="btn btn-info" value="<?php echo $l->g(116) ?>">
				</div>
				<div class="col-sm-2"></div>
			</div>

			<input name="old_table" type="hidden" value="<?php echo $defaultTable ?>">

			<?php echo close_form(); ?>

		</div>
	</div>
</div>

<?php

// Add var to session datamap
if (isset($protectedPost['old_table']) && isset($protectedPost['table_select']) && !isset($protectedPost['search_ok'])) {
	if ($protectedPost['old_table'] === $protectedPost['table_select']) {
		if(!AJAX){
			$search->addSessionsInfos($protectedPost);
		}
	}
}

if(isset($protectedGet['delete_row'])){
	if(!AJAX){
		$search->removeSessionsInfos($protectedGet['delete_row']);
	}
}

if ( isset($protectedPost['del_check']) ){
	if(!AJAX){
		if(strlen($protectedPost['del_check'] == 1)){
			deleteDid($protectedPost['del_check']);
		}else{
			$delIdArray = explode(",", $protectedPost['del_check']);
			foreach ($delIdArray as $index) {
				deleteDid($index);
			}
		}
	}
}

if(isset($protectedGet['fields'])){
  $search->link_index($protectedGet['fields'], $protectedGet['comp'], $protectedGet['values'], $protectedGet['values2']);
}

if(isset($protectedGet['prov'])){
  if($protectedGet['prov'] == 'allsoft'){
    $search->link_multi($protectedGet['prov'], $protectedGet['value']);
  }elseif($protectedGet['prov'] == 'ipdiscover1'){
    $search->link_multi($protectedGet['prov'], $protectedGet['value']);
  }elseif($protectedGet['prov'] == 'stat'){
    $options['idPackage'] = $databaseSearch->get_package_id($protectedGet['id_pack']);
    $options['stat'] = $protectedGet['stat'];
    $search->link_multi($protectedGet['prov'], $protectedGet['value'], $options);
  }elseif($protectedGet['prov'] == 'saas'){
    $search->link_multi($protectedGet['prov'], $protectedGet['value']);
  }
}

?>
<div name="multiSearchCritsDiv">
<?php

echo open_form('multiSearchCrits', '', '', '');

if (!empty($_SESSION['OCS']['multi_search'])) {

	if(isset($protectedPost['search_ok'])){
		$search->updateSessionsInfos($protectedPost);
	}

	foreach ($_SESSION['OCS']['multi_search'] as $table => $infos) {
    $i = 0;

		foreach ($infos as $uniqid => $values) {
			?>
			<div class="row" name="<?php echo $uniqid ?>">
        <?php if($i != 0){
          $htmlComparator = $search->returnFieldHtmlAndOr($uniqid, $values, $infos, $table, $values['comparator']);
            if($htmlComparator != ""){
              echo "<div class='col-sm-5'></div><div class='col-sm-1'>
        					     <div class='form-group'>
        							        ".$htmlComparator."
        					     </div>
        				    </div></br></br></br>";
            }
          } ?>
				<div class="col-sm-3">
					<div class="btn btn-info disabled" style="cursor:default;"><?php
            if(strpos($values['fields'], 'fields_') !== false){
              $fields = $accountInfoSearch->getAccountInfosList();
              echo $translationSearch->getTranslationFor($table)." : ".$fields['COMPUTERS'][$values['fields']];
            }else{
              echo $translationSearch->getTranslationFor($table)." : ".$translationSearch->getTranslationFor($values['fields']);
            }
					?></div>
				</div>

				<div class="col-sm-3">
					<div class="form-group">
						<select class="form-control" name="<?php echo $search->getOperatorUniqId($uniqid, $table); ?>" onchange="isnull('<?php echo $search->getOperatorUniqId($uniqid, $table); ?>', '<?php echo $search->getFieldUniqId($uniqid, $table); ?>');" id="<?php echo $search->getOperatorUniqId($uniqid, $table);?>">
							<?php if((strpos($values['fields'], 'fields_') !== false) || ($values['fields'] == "CATEGORY_ID") || ($values['fields'] == 'CATEGORY')){
                echo $search->getSelectOptionForOperators($values['operator'], $table, $values['fields']);
              } else {
                // DIFFERENT
                if($values['fields'] === "USERAGENT" && $protectedGet['values2'] === "unix" && $_SESSION['edit_tmp_unix'] === "NO_POST"){
                    echo $search->getSelectOptionForOperators('DIFFERENT', $table);
                } else {
                    // Ligne pas touche
                    echo $search->getSelectOptionForOperators($values['operator'], $table);
                }
              } ?>
						</select>
					</div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						<?php if((strpos($values['fields'], 'fields_') !== false) || ($values['fields'] == "CATEGORY_ID") || ($values['fields'] == 'CATEGORY')){
              echo $search->returnFieldHtml($uniqid, $values, $table, $values['fields']);
            }else {
              $nvalues = [
                    'fields' => 'USERAGENT',
                    'value' => 'WINDOWS',
                    'operator' => 'DIFFERENT'
              ];
              if($values['fields'] === "USERAGENT" && $protectedGet['values2'] === "unix" && $_SESSION['edit_tmp_unix'] === "NO_POST"){
                  echo $search->returnFieldHtml($uniqid, $nvalues, $table);
              } else {
                  // Ligne pas touche
                  echo $search->returnFieldHtml($uniqid, $values, $table);
              }
            } ?>
					</div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						<a href="?function=visu_search&delete_row=<?php echo $uniqid."_".$table ?>">
							<button type="button" class="btn btn-danger" aria-label="Close" style="padding: 10px;">
								<span class="glyphicon glyphicon-remove"></span>
							</button>
						</a>
					</div>
				</div>
			</div>
			<?php
      $i++;
		}
	}
}

if(!empty($_SESSION['OCS']['multi_search'])){
?>

<div class="col-sm-12">
	<input id="search_ok" name="search_ok" type="hidden" value="OK">
	<input type="submit" class="btn btn-success" value="<?php echo $l->g(13) ?>">
</div>

<?php

echo close_form();

?>
</div>
<br/>
<br/>
<hr/>
<div class="row">
	<div class="col-sm-12">
<?php

if($protectedPost['search_ok'] || $protectedGet['prov'] || $protectedGet['fields']){
  unset($_SESSION['OCS']['SEARCH_SQL_GROUP']);
	/**
	 * Generate Search fields
	 */
	$search->generateSearchQuery($_SESSION['OCS']['multi_search']);
	$sql = $search->baseQuery.$search->searchQuery.$search->columnsQueryConditions;

	$_SESSION['OCS']['multi_search_query'] = $sql;
	$_SESSION['OCS']['multi_search_args'] = $search->queryArgs;

	$form_name = "affich_multi_crit";
	$table_name = $form_name;
	$tab_options = $protectedPost;
	$tab_options['form_name'] = $form_name;
	$tab_options['table_name'] = $table_name;

	echo open_form($form_name, '', '', 'form-horizontal');

	$list_fields = $search->fieldsList;
	$list_col_cant_del = $search->defaultFields;
	$default_fields = $search->defaultFields;

  $_SESSION['OCS']['SEARCH_SQL_GROUP'][] = $search->create_sql_cache($_SESSION['OCS']['multi_search']);
	$tab_options['ARG_SQL'] = $search->queryArgs;
	$tab_options['CACHE'] = 'RESET';

  //BEGIN SHOW ACCOUNTINFO
	$option_comment['comment_be'] = $l->g(1210)." ";
	$tab_options['REPLACE_VALUE'] = replace_tag_value('',$option_comment);
  $tab_options['REPLACE_VALUE'][$l->g(66)] = $type_accountinfo;
  $tab_options['REPLACE_VALUE'][$l->g(1061)] = $array_tab_account;


	ajaxtab_entete_fixe($list_fields, $default_fields, $tab_options, $list_col_cant_del);

	if ($_SESSION['OCS']['profile']->getConfigValue('DELETE_COMPUTERS') == "YES"){
		$list_fonct["image/delete.png"]=$l->g(122);
		$list_pag["image/delete.png"]=$pages_refs["ms_custom_sup"];
			$tab_options['LBL_POPUP']['SUP']='name';
	}
	$list_fonct["image/cadena_ferme.png"]=$l->g(1019);
	$list_fonct["image/mass_affect.png"]=$l->g(430);
	if ($_SESSION['OCS']['profile']->getConfigValue('CONFIG') == "YES"){
		$list_fonct["image/config_search.png"]=$l->g(107);
		$list_pag["image/config_search.png"]=$pages_refs['ms_custom_param'];
	}
	if ($_SESSION['OCS']['profile']->getConfigValue('TELEDIFF') == "YES"){
		$list_fonct["image/tele_search.png"]=$l->g(428);
		$list_pag["image/tele_search.png"]=$pages_refs["ms_custom_pack"];
	}

	$list_fonct["image/groups_search.png"]=$l->g(583);
	$list_pag["image/groups_search.png"]=$pages_refs["ms_custom_groups"];

	$list_pag["image/cadena_ferme.png"]=$pages_refs["ms_custom_lock"];
	$list_pag["image/mass_affect.png"]=$pages_refs["ms_custom_tag"];

	$list_fonct["asset_cat"]=$l->g(2126);
	$list_pag["asset_cat"]=$pages_refs["ms_asset_cat"];

	$list_id = $databaseSearch->getIdList($search);
  $_SESSION['OCS']['ID_REQ']=id_without_idgroups($list_id);

	?>
	<div class='row' style='margin: 0'>
		<?php add_trait_select($list_fonct,$list_id,$form_name,$list_pag); ?>
	</div>
	<?php

}

echo close_form();

?>
	</div>
</div>
<?php

}

if (AJAX) {
    ob_end_clean();
    tab_req($list_fields, $default_fields, $list_col_cant_del, $sql, $tab_options);
}
