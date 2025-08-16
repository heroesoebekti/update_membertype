<?php
/**
 * Plugin Name: Update Member Type
 * Plugin URI: https://github.com/heroesoebekti/update_membertype
 * Description: Use for update member type 
 * Version: 0.0.1
 * Author: Heru Subekti
 * Author URI: https://github.com/heroesoebekti/
 */



// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

use SLiMS\DB;

// registering menus
$plugin->registerMenu('membership', __('Update Member Type'), __DIR__ . '/index.php');
$plugin->register('membership_custom_field_form', function ($form,$js,$data)  {
	if(isset($_GET['itemID'])){
		$memberID = utility::filterData('itemID', 'get', true, true, true);
		$membertype_q = DB::getInstance('mysqli')->query('SELECT mmt.member_type_name,ml.last_update FROM membertype_log ml LEFT JOIN mst_member_type mmt
			ON ml.member_type_id=mmt.member_type_id WHERE ml.member_id="'.$memberID.'"');
		if($membertype_q->num_rows > 0){
			$str_input = '';
			while($data = $membertype_q->fetch_row()){
				$str_input .=  '<i class="fa fa-angle-double-right" aria-hidden="true"></i>&nbsp;'.$data[0].' '.__('Before').' '.$data[1].'<br/>';
			}
			$form->addAnything(__('Member Type History'), $str_input);
		}
	}
});


