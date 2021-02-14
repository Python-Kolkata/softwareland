<?php
/**
 * Plugin Name: PSLicenseBank
 * Plugin URI: https://padrasafe.com/
 * Description: allow wp users to login in RL Panel Directly.
 * Version: 1.0
 * Author: padrasafe
 * Author URI: https://padrasafe.com/
 * License: GPL2
 */
/////NEW
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
function PSLB_DB_install()
{
	global $wpdb;
	$table_name = $wpdb->prefix . "PSLB_Products";
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
            `ID`	bigint(20) NOT NULL AUTO_INCREMENT,
            `PID`  bigint(20) NOT NULL,
			`SPID`  bigint(20) NOT NULL,
			`SPPrice`  int(5) NOT NULL,
			`Interest_Rate`  SMALLINT NOT NULL,
			`SPTitle` VARCHAR(200) CHARACTER SET utf8 NOT NULL,
			PRIMARY KEY (`ID`)
          ) $charset_collate; ";
	dbDelta($sql);
}
register_activation_hook(__FILE__, 'PSLB_DB_install');
/////New
register_deactivation_hook(__FILE__, 'PSLB_deactivation');
function PSLB_deactivation()
{
	wp_clear_scheduled_hook('PSLB_CheckStock_event');
	wp_clear_scheduled_hook('PSLB_CheckOrder_event');
}
////////////New
function PSLB_make_safe_for_utf8_use($string)
{
	$encoding = mb_detect_encoding($string, mb_detect_order(), true);
	if ($encoding != 'UTF-8') {
		return iconv($encoding, 'UTF-8', $string);
	} else {
		return $string;
	}
}
function PSLB_Rond_Price($SUMPrice)
{
	$RondNum=get_option('PSLB_Rond_Price_Num');
	$icount=0;
	$RoundCoeff=1;
	if($RondNum>0)
	{
		while($icount<$RondNum)
		{
		$icount++;
		$RoundCoeff=$RoundCoeff*10;
		}
		$TempPrice=$SUMPrice/$RoundCoeff;
		$TempPrice=round($TempPrice);
		$TempPrice=$TempPrice*$RoundCoeff;
		Return $TempPrice;
	}
	
	else if($RondNum<0){
		$RondNum=$RondNum*(-1);
		while($icount<$RondNum)
		{
			$icount++;
			$RoundCoeff=$RoundCoeff*10;
		}
		$TempPrice=$SUMPrice*$RoundCoeff;
		$TempPrice=round($TempPrice);
		$TempPrice=$TempPrice/$RoundCoeff;
		Return $TempPrice;
	}
	else {
		Return round($SUMPrice);
	}
}
////////////////////SET COOKIES  NEW
function PS_Set_User_Cookies_onlogin($user_login) {
        setcookie(
          "PS_User_Email",
          $user->user_email,
          time() + (365 * 24 * 60 * 60)
          , "/"
          , ".padrasafe.com"
        );
        
		//do stuff
}
add_action('wp_login', 'PS_Set_User_Cookies_onlogin');
///////NEW
function PS_Set_User_Cookies_register_fields( $username, $email, $validation_errors ) {
    if(!isset($_COOKIE["PS_User_Email"]) || strlen($_COOKIE["PS_User_Email"])<5 ||is_email($email) ){
        setcookie(
          "PS_User_Email",
          $email,
          time() + (365 * 24 * 60 * 60)
          , "/"
          , ".padrasafe.com"
        );
    }
}
add_action( 'woocommerce_register_post', 'PS_Set_User_Cookies_register_fields', 10, 3 );
//////NEW
function PS_Set_User_Cookies_register_completed( $customer_id ) {
	$user = get_user_by( 'id', $customer_id );
	setcookie(
		"PS_User_Email",
		$user->user_email,
		time() + ( 365 * 24 * 60 * 60)
		, "/"
		, ".padrasafe.com"
	);
}
add_action( 'woocommerce_created_customer', 'PS_Set_User_Cookies_register_completed' );
///////////
if (is_admin()) {
	define('PSLBROOTDIR', plugin_dir_path(__FILE__) . 'inc/');
	if (!function_exists("PSLB_Register_setting")) {
		add_action('admin_init', 'PSLB_Register_setting');
		function PSLB_Register_setting()
		{
			register_setting('PS-LBAPI-settings', 'PSLB_Reg_Key', '');
			register_setting('PS-LBAPI-settings', 'PSLB_Stock_CheckTime', 'hourly');
			register_setting('PS-LBAPI-settings', 'PSLB_Retry_GetLicenseTime', 'hourly');
			register_setting('PS-LBAPI-settings', 'PSLB_ControlStock_Type', 'NotCheck');
			register_setting('PS-LBAPI-settings', 'PSLB_ControlPrice_Type', 'NotCheck');
			register_setting('PS-LBAPI-settings', 'PSLB_Site_Currency_Coeff', '1');
			register_setting('PS-LBAPI-settings', 'PSLB_Rond_Price_Num', '-2');
			register_setting('PS-LBAPI-settings', 'PSLB_OGet_Type', 'Get');
			register_setting('PS-LBAPI-settings', 'PSLB_RegetFailed', 'NotGet');
			register_setting('PS-LBAPI-settings', 'PSLB_CheckOrderTerm', '24');
			register_setting('PS-LBAPI-settings', 'PSLB_APILink', 'https://PS.com/ControlPanel/Controller/PSLB_Operations.php');
			register_setting('PS-LBAPI-settings', 'PSLB_CheckOrderTerm', '24');
			register_setting('PS-LBAPI-settings', 'PSLB_Empty_Stock', 'Sorry! Exist %s Number of this product!');
			register_setting('PS-LBAPI-settings', 'PSLB_Reservation_failed', 'Reserving Product Failed!');
			register_setting('PS-LBAPI-settings', 'PSLB_EmailCompletedOrder', 'NotSend');
		}
	}
	/////////////
	add_action('admin_menu', 'ps_License_Bank_AddMenue');
	function ps_License_Bank_AddMenue()
	{
		//this is the main item for the menu
		add_menu_page(
			'PS License Bank', //page title
			'PS License Bank', //menu title
			'manage_options', //capabilities
			'padra-LBAPI-setting', //menu slug
			'PLBA_Setting_page'	//function
		);
		//this is a submenu
		add_submenu_page(
			'padra-LBAPI-setting', //parent slug
			'PS License Bank Products', //page title
			'Products', //menu title
			'manage_options', //capability
			'padra-LBAPI-Products', //menu slug
			'PSLB_Product_list'
		); //function
		//this submenu is HIDDEN, however, we need to add it anyways
		add_submenu_page(
			null, //parent slug
			'PS License Bank Add Product', //page title
			'Add Product', //menu title
			'manage_options', //capability
			'padra-LBAPI-AddProduct', //menu slug
			'PSLB_AddProduct'
		); //function
		add_submenu_page(
			null, //parent slug
			'PS License Bank Delete Product', //page title
			'Delete Product', //menu title
			'manage_options', //capability
			'padra-LBAPI-DeleteProduct', //menu slug
			'PSLB_DeleteProduct'
		); //function
		add_submenu_page(
			null, //parent slug
			'PS License Bank Edite Product', //page title
			'Edite Product', //menu title
			'manage_options', //capability
			'padra-LBAPI-EditeProduct', //menu slug
			'PSLB_EditeProduct'
		); //function
		//this is a submenu
		add_submenu_page(
			'padra-LBAPI-setting', //parent slug
			'PS License Bank Orders', //page title
			'Orders', //menu title
			'manage_options', //capability
			'padra-LBAPI-Orders', //menu slug
			'PSLB_Orders_list'
		); //function
		add_submenu_page(
			null, //parent slug
			'PS License Bank Proccess Order', //page title
			'Proccess Order', //menu title
			'manage_options', //capability
			'padra-LBAPI-Order-Proccess', //menu slug
			'PSLB_Order_Proccess'
		); //function
	}

	///////////////////////
	function PLBA_Setting_page()
	{
		if (isset($_POST['SavePSLBSetting'])) {
			$newPSLB_Reg_Key = $_POST['PSLB_Reg_Key'];
			$newPSLB_ControlStock_Type = $_POST['PSLB_ControlStock_Type'];
			$newPSLB_ControlPrice_Type = $_POST['PSLB_ControlPrice_Type'];
			$newPSLB_Site_Currency_Coeff = $_POST['PSLB_Site_Currency_Coeff'];
			$newPSLB_Rond_Price_Num = $_POST['PSLB_Rond_Price_Num'];
			$newPSLB_Stock_CheckTime = $_POST['PSLB_Stock_CheckTime'];
			$newPSLB_OGet_Type = $_POST['PSLB_OGet_Type'];
			$newPSLB_RegetFailed = $_POST['PSLB_RegetFailed'];
			$newPSLB_Retry_GetLicenseTime = $_POST['PSLB_Retry_GetLicenseTime'];
			$newPSLB_CheckOrderTerm = $_POST['PSLB_CheckOrderTerm'];
			$newPSLB_APILink = $_POST['PSLB_APILink'];
			$newPSLB_Empty_Stock = $_POST['PSLB_Empty_Stock'];
			$newPSLB_Reservation_failed = $_POST['PSLB_Reservation_failed'];
			$newPSLB_EmailCompletedOrder = $_POST['PSLB_EmailCompletedOrder'];
			///////////////
			$oldPSLB_Reg_Key = get_option('PSLB_Reg_Key');
			$oldPSLB_ControlStock_Type = get_option('PSLB_ControlStock_Type');
			$oldPSLB_ControlPrice_Type = get_option('PSLB_ControlPrice_Type');
			$oldPSLB_Site_Currency_Coeff = get_option('PSLB_Site_Currency_Coeff');
			$oldPSLB_Rond_Price_Num = get_option('PSLB_Rond_Price_Num');
			$oldPSLB_Stock_CheckTime = get_option('PSLB_Stock_CheckTime');
			$oldPSLB_OGet_Type = get_option('PSLB_OGet_Type');
			$oldPSLB_RegetFailed = get_option('PSLB_RegetFailed');
			$oldPSLB_Retry_GetLicenseTime = get_option('PSLB_Retry_GetLicenseTime');
			$oldPSLB_CheckOrderTerm = get_option('PSLB_CheckOrderTerm');
			$oldPSLB_APILink = get_option('PSLB_APILink');
			$oldPSLB_Empty_Stock = get_option('PSLB_Empty_Stock');
			$oldPSLB_Reservation_failed = get_option('PSLB_Reservation_failed');
			$oldPSLB_EmailCompletedOrder = get_option('PSLB_EmailCompletedOrder');
			////////////////
			if ($newPSLB_Reg_Key != $oldPSLB_Reg_Key) update_option('PSLB_Reg_Key', $newPSLB_Reg_Key);
			if ($newPSLB_ControlStock_Type != $oldPSLB_ControlStock_Type) update_option('PSLB_ControlStock_Type', $newPSLB_ControlStock_Type);
			if ($newPSLB_ControlPrice_Type != $oldPSLB_ControlPrice_Type) update_option('PSLB_ControlPrice_Type', $newPSLB_ControlPrice_Type);
			if ($newPSLB_Site_Currency_Coeff != $oldPSLB_Site_Currency_Coeff) update_option('PSLB_Site_Currency_Coeff', $newPSLB_Site_Currency_Coeff);
			if ($newPSLB_Rond_Price_Num!= $oldPSLB_Rond_Price_Num) update_option('PSLB_Rond_Price_Num', $newPSLB_Rond_Price_Num);
			if ($newPSLB_Stock_CheckTime != $oldPSLB_Stock_CheckTime) update_option('PSLB_Stock_CheckTime', $newPSLB_Stock_CheckTime);
			if ($newPSLB_OGet_Type != $oldPSLB_OGet_Type) update_option('PSLB_OGet_Type', $newPSLB_OGet_Type);
			if ($newPSLB_RegetFailed != $oldPSLB_RegetFailed) update_option('PSLB_RegetFailed', $newPSLB_RegetFailed);
			if ($newPSLB_Retry_GetLicenseTime != $oldPSLB_Retry_GetLicenseTime) update_option('PSLB_Retry_GetLicenseTime', $newPSLB_Retry_GetLicenseTime);
			if ($newPSLB_CheckOrderTerm != $oldPSLB_CheckOrderTerm) update_option('PSLB_CheckOrderTerm', $newPSLB_CheckOrderTerm);
			if ($newPSLB_APILink != $oldPSLB_APILink) update_option('PSLB_APILink', $newPSLB_APILink);
			if ($newPSLB_Empty_Stock != $oldPSLB_Empty_Stock) update_option('PSLB_Empty_Stock', $newPSLB_Empty_Stock);
			if ($newPSLB_Reservation_failed != $oldPSLB_Reservation_failed) update_option('PSLB_Reservation_failed', $newPSLB_Reservation_failed);
			if ($newPSLB_EmailCompletedOrder != $oldPSLB_EmailCompletedOrder) update_option('PSLB_EmailCompletedOrder', $newPSLB_EmailCompletedOrder);
			//////////////
			if ($newPSLB_ControlStock_Type != $oldPSLB_ControlStock_Type || $newPSLB_ControlPrice_Type != $oldPSLB_ControlPrice_Type || $newPSLB_Stock_CheckTime != $oldPSLB_Stock_CheckTime || (!wp_next_scheduled('PSLB_CheckStock_event'))) {
				wp_clear_scheduled_hook('PSLB_CheckStock_event');
				if ($newPSLB_ControlStock_Type == "Check" || $newPSLB_ControlPrice_Type == "Check") {
					if (!wp_next_scheduled('PSLB_CheckStock_event')) {
						wp_schedule_event(time(), $newPSLB_Stock_CheckTime, 'PSLB_CheckStock_event');
					}
				}
			}
			/////////////////
			if ($newPSLB_RegetFailed != $oldPSLB_RegetFailed || $newPSLB_Retry_GetLicenseTime != $oldPSLB_Retry_GetLicenseTime || (!wp_next_scheduled('PSLB_CheckOrder_event'))) {
				wp_clear_scheduled_hook('PSLB_CheckOrder_event');
				if ($newPSLB_RegetFailed == "Get") {
					if (!wp_next_scheduled('PSLB_CheckOrder_event')) {
						wp_schedule_event(time(), $newPSLB_Retry_GetLicenseTime, 'PSLB_CheckOrder_event');
					}
				}
			}
			/////////////

		} else {
			$newPSLB_Reg_Key = get_option('PSLB_Reg_Key');
			$newPSLB_ControlStock_Type = get_option('PSLB_ControlStock_Type');
			$newPSLB_ControlPrice_Type = get_option('PSLB_ControlPrice_Type');
			$newPSLB_Site_Currency_Coeff = get_option('PSLB_Site_Currency_Coeff');
			$newPSLB_Rond_Price_Num = get_option('PSLB_Rond_Price_Num');
			$newPSLB_Stock_CheckTime = get_option('PSLB_Stock_CheckTime');
			$newPSLB_OGet_Type = get_option('PSLB_OGet_Type');
			$newPSLB_RegetFailed = get_option('PSLB_RegetFailed');
			$newPSLB_Retry_GetLicenseTime = get_option('PSLB_Retry_GetLicenseTime');
			$newPSLB_CheckOrderTerm = get_option('PSLB_CheckOrderTerm');
			$newPSLB_APILink = get_option('PSLB_APILink');
			$newPSLB_Empty_Stock = get_option('PSLB_Empty_Stock');
			$newPSLB_Reservation_failed = get_option('PSLB_Reservation_failed');
			$newPSLB_EmailCompletedOrder = get_option('PSLB_EmailCompletedOrder');
		}
		?>
		<h1>PS License Bank Plugin</h1>
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="PLBASettingForm">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Connect Key : </th>
					<td><input type="text" name="PSLB_Reg_Key" value="<?php echo $newPSLB_Reg_Key; ?>" size="35" /></textarea></td>
				</tr>
				<tr valign="top">
					<th scope="row">API LINK : </th>
					<td><input type="text" name="PSLB_APILink" value="<?php echo $newPSLB_APILink; ?>" size="35" /></textarea></td>
				</tr>
				<tr valign="top">
					<th scope="row">Control Stock From partner Server :</th>
					<td><select id="PSLB_ControlStock_Type" name="PSLB_ControlStock_Type">
							<option value="Check" <?php if ($newPSLB_ControlStock_Type == 'Check') {
														echo 'selected';
													} ?>>Check From Server</option>
							<option value="NotCheck" <?php if ($newPSLB_ControlStock_Type == 'NotCheck') {
															echo 'selected';
														} ?>>No Check </option>
						</select></td>
				</tr>
				<tr valign="top">
					<th scope="row">Control Price From partner Server :</th>
					<td><select id="PSLB_ControlPrice_Type" name="PSLB_ControlPrice_Type">
							<option value="Check" <?php if ($newPSLB_ControlPrice_Type == 'Check') {
														echo 'selected';
													} ?>>Check From Server</option>
							<option value="NotCheck" <?php if ($newPSLB_ControlPrice_Type == 'NotCheck') {
															echo 'selected';
														} ?>>No Check </option>
						</select></td>
				</tr>
				<tr valign="top">
					<th scope="row">Your Site Currency Transfer Rate : </th>
					<td><input type="text" name="PSLB_Site_Currency_Coeff" value="<?php echo $newPSLB_Site_Currency_Coeff; ?>" size="35" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Rond Price Rate : </th>
					<td><input type="number" min="-10" max="10" step="1" name="PSLB_Rond_Price_Num" value="<?php echo $newPSLB_Rond_Price_Num; ?>"/></td>
				</tr>
				<tr valign="top">
					<th scope="row">Time For Renew Product Stock/Price :</th>
					<td><select id="PSLB_Stock_CheckTime" name="PSLB_Stock_CheckTime">
							<option value="hourly" <?php if ($newPSLB_Stock_CheckTime == 'hourly') {
														echo 'selected';
													} ?>>Hourly</option>
							<option value="twicedaily" <?php if ($newPSLB_Stock_CheckTime == 'twicedaily') {
															echo 'selected';
														} ?>>TwiceDaily</option>
							<option value="daily" <?php if ($newPSLB_Stock_CheckTime == 'daily') {
														echo 'selected';
													} ?>>Daily</option>
						</select><label> Next Excute : <?php
														$date = new DateTime(date("d-m-Y H:i:s", wp_next_scheduled('PSLB_CheckStock_event')), new DateTimeZone('UTC'));
														echo date_format($date, 'Y-m-d H:i:s');
														$date->setTimezone(new DateTimeZone('Asia/Tehran'));
														echo "(Tehran Time : " . date_format($date, 'H:i:s') . ")";
														?></label></td>
				</tr>
				<tr valign="top">
					<th scope="row">Get License And Send in Order Completed :</th>
					<td><select id="PSLB_OGet_Type" name="PSLB_OGet_Type">
							<option value="Get" <?php if ($newPSLB_OGet_Type == 'Get') {
													echo 'selected';
												} ?>>Do This</option>
							<option value="NotGet" <?php if ($newPSLB_OGet_Type == 'NotGet') {
														echo 'selected';
													} ?>>Not Do  </option>
						    <option value="AfterAdminVerify" <?php if ($newPSLB_OGet_Type == 'AfterAdminVerify') {
														echo 'selected';
													} ?>>After Admin Verify  </option>
						</select></td>
				</tr>
				<tr valign="top">
					<th scope="row">Reget Failed Orders License:</th>
					<td><select id="PSLB_RegetFailed" name="PSLB_RegetFailed">
							<option value="Get" <?php if ($newPSLB_RegetFailed == 'Get') {
													echo 'selected';
												} ?>>Do This</option>
							<option value="NotGet" <?php if ($newPSLB_RegetFailed == 'NotGet') {
														echo 'selected';
													} ?>>Not Do This</option>
						</select></td>
				</tr>
				<tr valign="top">
					<th scope="row">Time For Reget Failed Get License Orders :</th>
					<td><select id="PSLB_Retry_GetLicenseTime" name="PSLB_Retry_GetLicenseTime">
							<option value="hourly" <?php if ($newPSLB_Retry_GetLicenseTime == 'hourly') {
														echo 'selected';
													} ?>>Hourly</option>
							<option value="twicedaily" <?php if ($newPSLB_Retry_GetLicenseTime == 'twicedaily') {
															echo 'selected';
														} ?>>TwiceDaily</option>
							<option value="daily" <?php if ($newPSLB_Retry_GetLicenseTime == 'daily') {
														echo 'selected';
													} ?>>Daily</option>
						</select><label> Next Excute : <?php
														$date = new DateTime(date("d-m-Y H:i:s", wp_next_scheduled('PSLB_CheckOrder_event')), new DateTimeZone('UTC'));
														echo date_format($date, 'Y-m-d H:i:s');
														$date->setTimezone(new DateTimeZone('Asia/Tehran'));
														echo "(Tehran Time : " . date_format($date, 'H:i:s') . ")";
														?></label></td>
				</tr>
				<tr valign="top">
					<th scope="row">Check Order in Term Of </th>
					<td><select id="PSLB_CheckOrderTerm" name="PSLB_CheckOrderTerm">
							<option value="24" <?php if ($newPSLB_CheckOrderTerm == '24') {
													echo 'selected';
												} ?>>Last 24 Hours</option>
							<option value="36" <?php if ($newPSLB_CheckOrderTerm == '36') {
													echo 'selected';
												} ?>>Last 36 Hours</option>
							<option value="48" <?php if ($newPSLB_CheckOrderTerm == '48') {
													echo 'selected';
												} ?>>Last 48 Hours</option>
						</select></td>
				</tr>
				<tr valign="top">
					<th scope="row">Empty Stock Error : </th>
					<td><input type="text" name="PSLB_Empty_Stock" value="<?php echo $newPSLB_Empty_Stock; ?>" size="35" /></textarea></td>
				</tr>
				<tr valign="top">
					<th scope="row">Reservation failed Error : </th>
					<td><input type="text" name="PSLB_Reservation_failed" value="<?php echo $newPSLB_Reservation_failed; ?>" size="35" /></textarea></td>
				</tr>
				<tr valign="top">
					<th scope="row">Email Order after Completed To Customer :</th>
					<td><select id="PSLB_EmailCompletedOrder" name="PSLB_EmailCompletedOrder">
							<option value="Send" <?php if ($newPSLB_EmailCompletedOrder== 'Send') {
														echo 'selected';
													} ?>>Send Email</option>
							<option value="NotSend" <?php if ($newPSLB_EmailCompletedOrder== 'NotSend') {
															echo 'selected';
														} ?>>Not Send Email </option>
						</select></td>
				</tr>
			</table>
			<input tabindex="1" type='submit' name="SavePSLBSetting" value='Update' class='button'>
		</form>
	<?php
}
require_once(PSLBROOTDIR . 'PSLB_ProductList.php');
require_once(PSLBROOTDIR . 'PSLB_AddProduct.php');
require_once(PSLBROOTDIR . 'PSLB_OrderList.php');
}
///////////NEW
function PSLB_get_the_user_ip() {
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
    //check ip from share internet
    $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
    //to check ip is pass from proxy
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
    $ip = $_SERVER['REMOTE_ADDR'];
    }
    return  $ip ;
}
function PSLB_query_results($Req)
{
	$user_index="";
    $user_index.=PSLB_get_the_user_ip();
    $current_user = wp_get_current_user();
    if ($current_user->ID) {
        $user_index.="_".$current_user->user_email;
    }
    else if(isset($_COOKIE["PS_User_Email"])){
        $user_index.="_".$_COOKIE["PS_User_Email"];
    }
	$ch = curl_init();
	$request = "connkey=" . get_option('PSLB_Reg_Key') ."&user_index=".$user_index. $Req;
	$apilink = get_option('PSLB_APILink');
	curl_setopt($ch, CURLOPT_URL, $apilink);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$server_output = curl_exec($ch);
	if ($server_output=== false)	$server_output = curl_error($ch);
	curl_close($ch);
	return $server_output;
}
///////////NEW
add_action('PSLB_CheckStock_event', 'do_Check_Stock');
function do_Check_Stock()
{
	global $wpdb;
	$table_name = $wpdb->prefix . "PSLB_Products";
	$sqlquery = "SELECT DISTINCT $table_name.PID,$table_name.SPID,$table_name.SPPrice,$table_name.Interest_Rate from $table_name";
	$rows = $wpdb->get_results($sqlquery);
	$SiteCurrencyRate=get_option('PSLB_Site_Currency_Coeff');
	foreach ($rows as $row) {
		product_Check_Stock($row->PID, $row->SPID,$row->Interest_Rate,$SiteCurrencyRate);
	}
}
///////////////NEW
function product_Check_Stock($PID, $SPID,$Interest_Rate,$SiteCurrencyRate)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "PSLB_Products";
	$_product = wc_get_product($PID);
	if ($_product) {
		
		$Reqvalue = "&action=get_proname&PID=" . $SPID;
		$reqresult = PSLB_query_results($Reqvalue);
	//	echo "<h1>$reqresult</h1>";
		$resultarray =json_decode($reqresult,256);
		if($resultarray["result"]){
			if ($resultarray["result"] == "Success") {
				$SPTitle =$resultarray["product_title"] ;
				$SPStock = $resultarray["product_stock"];
				$SPPrice = $resultarray["product_price"];
				$CalPrice =PSLB_Rond_Price(((($Interest_Rate/100)*$SPPrice)+$SPPrice)*$SiteCurrencyRate);
				$wpdb->update(
					$table_name, //table
					array('SPTitle' => $SPTitle,'SPPrice'=>$SPPrice),//data
					array('PID' => $PID), //where
					array('%s','%s'), //data format
					array('%d') //where format
				);
				
				if (get_option('PSLB_ControlStock_Type') == 'Check') {
					PSLB_Set_Stock($PID, $SPStock);
				}
				if (get_option('PSLB_ControlPrice_Type') == 'Check') {
					$sp	=	update_post_meta( $PID, '_sale_price',$CalPrice );
					$sp	=	update_post_meta( $PID, '_price', $CalPrice );
				}
				return $SPStock;
			}
			else return 0;
		}
		else return 0;
	}
}
////////New
function product_GET_PSLB_Stock($SPID)
{
	$Reqvalue = "&action=get_proname&PID=" . $SPID;
	$reqresult = PSLB_query_results($Reqvalue);
	//echo "<h1>$reqresult</h1>";
	$resultarray =json_decode($reqresult,256);
	if($resultarray["result"]){
		if ($resultarray["result"] == "Success") {
			$SPStock = $resultarray["product_stock"];
			return $SPStock;
		}
		else return 0;
	}
	else return 0;
}
///////////////New
function product_Get_Server_Stock($SPID)
{
	$Reqvalue = "&action=get_proname&PID=" . $SPID;
	$reqresult = PSLB_query_results($Reqvalue);
	$resultarray =json_decode($reqresult,256);
	if($resultarray["result"]){
		if ($resultarray["result"] == "Success") {
			$SPTitle =$resultarray["product_title"] ;
			$SPStock = $resultarray["product_stock"];
			$SPPrice = $resultarray["product_price"];
			return $SPStock;
		}
		else return false;
	}
	return false;
}
////////////////////New
function product_Get_Server_Reserve($SPID,$quantity)
{
	$Reqvalue = "&action=Reserve_product&PID=". $SPID."&Quantity=".$quantity;
	$reqresult = PSLB_query_results($Reqvalue);
	//echo "<h1>".$reqresult."</h1>";
	$resultarray =json_decode($reqresult,256);
	if($resultarray["result"]){
		if ($resultarray["result"] == "Success") {
			return $resultarray["Reserve_id"];
		}
		else return false;
	}
	return false;
}
function product_Update_Server_Reserve($SPID,$quantity,$reserve_id)
{
	$Reqvalue = "&action=Reserve_product_Update&PID=". $SPID."&Quantity=".$quantity."&Reserve_id=".$reserve_id;
	$reqresult = PSLB_query_results($Reqvalue);
//	echo "<h1>".$reqresult."</h1>";
	$resultarray =json_decode($reqresult,256);
	if($resultarray["result"]){
		if ($resultarray["result"] == "Success") {
			return $resultarray["Reserve_id"];
		}
		else return false;
	}
	return false;
}
function product_Delete_Server_Reserve($reserve_id)
{
	$Reqvalue = "&action=Reserve_product_Delete&Reserve_id=".$reserve_id;
	$reqresult = PSLB_query_results($Reqvalue);
	//echo "<h1>".$reqresult."</h1>";
	$resultarray =json_decode($reqresult,256);
	if($resultarray["result"]){
		if ($resultarray["result"] == "Success") {
			return true;
		}
		else return false;
	}
	return false;
}
///////////
add_action('PSLB_CheckOrder_event', 'do_Check_Orders');
function do_Check_Orders()
{
	$PSLB_CheckOrderTerm = $_POST['PSLB_CheckOrderTerm'];
	if ($PSLB_CheckOrderTerm != 24 && $PSLB_CheckOrderTerm != 36 && $PSLB_CheckOrderTerm != 48) {
		$PSLB_CheckOrderTerm = 36;
	}
	$customer_orders = get_posts(array(
		'numberposts' => -1,
		'post_type'   => array('shop_order'),
		'post_status' => array('wc-completed', 'wc-processing'),
		'date_query' => array(array('after' => ($PSLB_CheckOrderTerm . ' hours ago')))
	));
	foreach ($customer_orders as $FndOrder) {
		PSLB_Check_Order($FndOrder->ID);
	}
}
///////////
function PSLB_Get_Order_status($order_id)
{
	$order = new WC_Order($order_id);
	$items = $order->get_items();
	$HaveLicense = false;
	$HavePadraLicense = false;
	$FailCount = 0;
	$SuccessCount = 0;
	foreach ($items as $key => $value) {
		$Licensesstr = wc_get_order_item_meta($key, 'License Code(s)', 1);
		if ($Licensesstr) {
			$HaveLicense = true;
			$LicensesArr = explode("<br>", $Licensesstr);
			$Licensesstr = "";
			$changeLicense = false;
			foreach ($LicensesArr as $License) {
				if (strpos($License, "Serial ID") !== false) {
					$HavePadraLicense = true;
					if (strpos($License, "Your order's license key will send as soon as possible.") !== false) {
						$FailCount = $FailCount + 1;
					}
				} else if (strpos($License, "(SID") !== false) {
					$HavePadraLicense = true;
					$SuccessCount = $SuccessCount + 1;
				}
				$Licensesstr .= $License . "<br>";
			}
		}
	}
	if (!$HaveLicense) {
		return "Not have License Or PadraSerial";
	}
	if (!$HavePadraLicense) {
		return "Not have PadraSerial";
	}
	$returnstr = "";
	if ($FailCount > 0) {
		$returnstr = "Have " . $FailCount . " failled Padra Serial <br>";
	}
	if ($SuccessCount > 0) {
		$returnstr = "Have " . $SuccessCount . " Success Recieved Padra Serial <br>";
	}
	return $returnstr;
}
////////////New
function PSLB_Set_Order_Cart_License($order_id,$PSLBCart){
	$order = wc_get_order($order_id);
	$order_items = $order->get_items();
	foreach($order_items as $ItemId=>$item){
		foreach($PSLBCart as $PSLBCartitem){
			if($PSLBCartitem["Item_id"]==$ItemId){
				$temp="";
				foreach($PSLBCartitem["Serials"] as $License){
					$temp.="<strong>SID".$License["SID"]." : </strong>".$License["LicenseCode"]."<br>";
				}
				wc_update_order_item_meta( $ItemId, 'LicenseCode', $temp );
				wc_delete_order_item_meta( $ItemId, "reserve_id" ); 
				break;
			}
		}
	}
}
//////////////NEW
function PSLB_Check_Order($order_id)
{
	$PSLB_result= get_post_meta($order_id, 'PSLB_Result', true );
	$order = wc_get_order($order_id);
	$order_items = $order->get_items();
	$PSProductList=array();
	foreach ($order_items as $ItemId=>$item) {
		if(wc_get_order_item_meta( $ItemId, '_variation_id', true )>0 ) $product_id=wc_get_order_item_meta( $ItemId, '_variation_id', true );
		else $product_id = $item->get_product_id() ;
		if($PS_Product = getSProductByPID($product_id)){
			$spproduct=array();
			$spproduct['Item_id']=$ItemId;
			$spproduct['PID']=$PS_Product->SPID;
			$spproduct['Reserve_id']=wc_get_order_item_meta( $ItemId, 'reserve_id', true );
			$spproduct['Quantity']=$item->get_quantity();
			$PSProductList[]=$spproduct;
		}
	}
	if(!empty( $PSLB_result) ){
		$PSLB_resultarray =json_decode($PSLB_result,256);
		if($PSLB_resultarray["Invoice_Id"]>0){
			$Reqvalue = "&action=Get_Order_Info&Invoice_Id=".$PSLB_resultarray["Invoice_Id"]."&Type=Auto&Tracking_Code=".$order_id."&Cart=". json_encode($PSProductList,256);
			$reqresult = PSLB_query_results($Reqvalue);
			$resultarray =json_decode($reqresult,256);
			if ($resultarray["result"] == "Success") {
				if($resultarray["Invoice_Id"]>0){
					 update_post_meta( $order_id, 'PSLB_Result', $reqresult );
					if($resultarray["InvoiceProccess"]=="Success_CS_RS" || $resultarray["InvoiceProccess"]=="Success_CS"){
						PSLB_Set_Order_Cart_License($order_id,$resultarray["Cart"]);
					}
				}
			}
			else{
				$note = __($reqresult);
				$order->add_order_note($note);
				$order->save();
			}			
		}
	}
	else{
	//$user_id = get_post_meta($order_id, '_customer_user', true);
	if(!empty($PSProductList)){
		$Reqvalue = "&action=Ordering_Cart&Type=Auto&Tracking_Code=".$order_id."&Cart=". json_encode($PSProductList,256);
		$reqresult = PSLB_query_results($Reqvalue);
		$resultarray =json_decode($reqresult,256);
		// Add the note
		if($resultarray["result"]){
			if ($resultarray["result"] == "Success") {
				if($resultarray["Invoice_Id"]>0){
					 update_post_meta( $order_id, 'PSLB_Result', $reqresult );
					 if($resultarray["Payment"]>0){
						if($resultarray["InvoiceProccess"]=="Success_CS_RS" || $resultarray["InvoiceProccess"]=="Success_CS"){
							PSLB_Set_Order_Cart_License($order_id,$resultarray["Cart"]);
						}
					 }
				}
			}
			else{
				$note = __($reqresult);
				$order->add_order_note($note);
				$order->save();
			}
		}
		else{
			$note = __($reqresult);
			$order->add_order_note($note);
			$order->save();
		}
	}
	}
}
//////////////New
function PSLB_Set_Stock($PID, $stock)
{
	$_product = wc_get_product($PID);
	if ($_product) {
		if ($stock > 0) {
			$in_stock_staus = 'instock';
			// 1. Updating the stock quantity
			update_post_meta($PID, '_stock', $stock);
			// 2. Updating the stock quantity
			update_post_meta($PID, '_stock_status', wc_clean($in_stock_staus));
			update_post_meta($PID, '_manage_stock', wc_clean('yes'));
			// 3. Updating post term relationship
			wp_set_post_terms($PID, 'instock', 'product_visibility', true);
			// And finally (optionally if needed)
			wc_delete_product_transients($PID); //
		} else {
			$out_of_stock_staus = 'outofstock';
			// 1. Updating the stock quantity
			update_post_meta($PID, '_stock', 0);
			// 2. Updating the stock quantity
			update_post_meta($PID, '_stock_status', wc_clean($out_of_stock_staus));
			update_post_meta($PID, '_manage_stock', wc_clean('yes'));
			// 3. Updating post term relationship
			wp_set_post_terms($PID, 'outofstock', 'product_visibility', true);
			// And finally (optionally if needed)
			wc_delete_product_transients($PID); //
		}
	}
}
//////not used
function PSLB_Get_License_Stock($PID)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "wc_product_licences";

	$sqlquery = "SELECT licence_id,licence_code,licence_status ";
	$sqlquery = $sqlquery . " from $table_name ";
	$sqlquery = $sqlquery . " WHERE product_id=" . $PID . " AND licence_status LIKE '%available%' ";
	$rows = $wpdb->get_results($sqlquery);
	$TC = 0;
	$PC = 0;
	foreach ($rows as $row) {
		$TC = $TC + 1;
		if (strpos($row->licence_code, "Serial ID") !== false) {
			$PC = $PC + 1;
		}
	}
	return array($TC, $PC);
}
///////////////New
function PSLB_update_cart_item_RserveId($Product_Id,$variation_id,$Reserve_Id) {
	$cart = WC()->cart->cart_contents;
	foreach( $cart as $cart_item_id=>$cart_item ) {
	    if($variation_id>0){
	        if($cart_item['product_id']==$Product_Id && $cart_item['variation_id']==$variation_id){	
    			$cart_item['reserve_id'] = $Reserve_Id;
    		}
	    }
		else if($cart_item['product_id']==$Product_Id){	
			$cart_item['reserve_id'] = $Reserve_Id;
		}
		WC()->cart->cart_contents[$cart_item_id] = $cart_item;
	}
	WC()->cart->set_session();
}
/////////////////NEW
function getProductSpid($PID)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "PSLB_Products";
	$sqlquery = "SELECT $table_name.SPID from $table_name WHERE $table_name.PID = $PID LIMIT 1";
	$rows = $wpdb->get_results($sqlquery);
	foreach ($rows as $row) {
		return $row->SPID;
	}
}
function get_product_pid_by_spid($SPID)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "PSLB_Products";
	$sqlquery = "SELECT $table_name.PID from $table_name WHERE $table_name.SPID = $SPID LIMIT 1";
	$rows = $wpdb->get_results($sqlquery);
	foreach ($rows as $row) {
		return $row->PID;
	}
	return false;
}
function getSProductByPID($PID)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "PSLB_Products";
	$sqlquery = "SELECT $table_name.PID,$table_name.SPID,$table_name.SPPrice,$table_name.Interest_Rate from $table_name WHERE $table_name.PID = $PID LIMIT 1";
	$rows = $wpdb->get_results($sqlquery);
	foreach ($rows as $row) {
		return $row;
	}
	return false;
}
//////////////////   NEW
function so_validate_add_cart_item($passed, $product_id, $quantity,$variation_id = '')
{	
	$Reserved=false;
	$LastQuantity=0;
    if($variation_id>0) $PS_Product = getSProductByPID($variation_id);
    else $PS_Product = getSProductByPID($product_id);
	if ($PS_Product) {
		//echo $quantity;
		//api check stock
		$items = WC()->cart->get_cart();
		///////////
		foreach ($items as $item => $values) {
		    if($variation_id>0 ){
		        if($values['product_id']==$product_id && $values['variation_id']==$variation_id){
    				if ($values['reserve_id']) {
    					$Reserved=$values['reserve_id'];
    					$LastQuantity=$values['quantity'];
    					break;
    				}
    			}
		    }
			else if($values['product_id']==$product_id){
				if ($values['reserve_id']) {
					$Reserved=$values['reserve_id'];
					$LastQuantity=$values['quantity'];
					break;
				}
			}
		}
		$SiteCurrencyRate=get_option('PSLB_Site_Currency_Coeff');
		$stock =product_GET_PSLB_Stock($PS_Product->SPID);
		$totalquantity=$quantity+$LastQuantity;
		if ($stock>=$quantity) {
			//add to rezerv
			if(!$Reserved){
				$reserve_id=product_Get_Server_Reserve($PS_Product->SPID,$totalquantity);
				if ($reserve_id) {
					PSLB_update_cart_item_RserveId($product_id,$variation_id,$reserve_id);
					unset($_SESSION["reserve_id"]);
					$_SESSION["reserve_id"] = $reserve_id;
					add_filter('woocommerce_add_cart_item_data',
						function ($cart_item_meta, $product_id) {
							global $woocommerce;
							$cart_item_meta['reserve_id'] = $_SESSION["reserve_id"];
							return $cart_item_meta;
						},10,2);
				} else {
					$passed = false;
					wc_add_notice(__(get_option('PSLB_Reservation_failed'), 'textdomain'), 'error');
				}
			}
			else{
				$reserve_id=product_Update_Server_Reserve($PS_Product->SPID,$totalquantity,$Reserved);
				if($reserve_id){
					PSLB_update_cart_item_RserveId($product_id,$variation_id,$reserve_id);
					unset($_SESSION["reserve_id"]);
					$_SESSION["reserve_id"] = $reserve_id;
					add_filter('woocommerce_add_cart_item_data',
						function ($cart_item_meta, $product_id) {
							global $woocommerce;
							$cart_item_meta['reserve_id'] = $_SESSION["reserve_id"];
							return $cart_item_meta;
						},10,2);
				}
				else {
					$passed = false;
					wc_add_notice(__(get_option('PSLB_Reservation_failed'), 'textdomain'), 'error');
				}
			}
		} else {
			$passed = false;
			$error = sprintf(get_option('PSLB_Empty_Stock'), $stock);
			wc_add_notice(__($error, 'textdomain'), 'error');
		}
	}

	return $passed;
}
///////////// new
function PSLB_woocommerce_update_cart_validation( $true, $cart_item_key, $values, $quantity ) { 
    // make filter magic happen here... \
	$items = WC()->cart->get_cart();
	foreach ($items as $item => $values) {
		if ($values['key'] == $cart_item_key) {
			if ($values['reserve_id'] && $values['product_id']) {
			    if($values['variation_id']>0){
			        if ($PS_Product = getSProductByPID($values['variation_id'])) {
    					if($quantity!=$values['quantity']){
    						//return $true;//return false;return $true;
    						$reserve_id=product_Update_Server_Reserve($PS_Product->SPID,$quantity,$values['reserve_id']);
    						if($reserve_id>0){
    							if($reserve_id!=$values['reserve_id']){
    								PSLB_update_cart_item_RserveId($values['product_id'],$values['variation_id'],$reserve_id);
    							}
    							return $true;
    						}
    						else return false;
    					}
    				}
			    }
			    else if ($PS_Product = getSProductByPID($values['product_id'])) {
					if($quantity!=$values['quantity']){
						//return $true;//return false;return $true;
						$reserve_id=product_Update_Server_Reserve($PS_Product->SPID,$quantity,$values['reserve_id']);
						if($reserve_id>0){
							if($reserve_id!=$values['reserve_id']){
								PSLB_update_cart_item_RserveId($values['product_id'],'',$reserve_id);
							}
							return $true;
						}
						else return false;
					}
				}
			}
		}
	}
    return $true; 
}; 
// add the filter 
add_filter( 'woocommerce_update_cart_validation', 'PSLB_woocommerce_update_cart_validation', 10, 4 ); 
////////////////  NEW
function rei_after_checkout_validation($posted)
{
	$items = WC()->cart->get_cart();
	foreach ($items as $item => $values) {
		if ($values['reserve_id']) {
		    
			// check reserve 
			if($values['variation_id']>0){
			    if (!checkReserve($values['reserve_id'], getProductSpid($values['variation_id']), $values['quantity'])) {
    				wc_add_notice(__(get_option('PSLB_Reservation_failed'), 'textdomain'), 'error');
    			}
			}
			else if (!checkReserve($values['reserve_id'], getProductSpid($values['product_id']), $values['quantity'])) {
				wc_add_notice(__(get_option('PSLB_Reservation_failed'), 'textdomain'), 'error');
			}
		}
	}
}
function checkReserve($reserve_id, $SPID, $quantity)
{
	$Reqvalue = "&action=Reserve_Check&PID=". $SPID."&Quantity=".$quantity."&Reserve_id=".$reserve_id;
	$reqresult = PSLB_query_results($Reqvalue);
	//echo "<h1>".$reqresult."</h1>";
	$resultarray =json_decode($reqresult,256);
	if($resultarray["result"]){
		if ($resultarray["result"] == "Success") {
			return $resultarray["Reserve_id"];
		}
		else return false;
	}
	return false;
}
function action_woocommerce_review_order_before_payment()
{
	$items = WC()->cart->get_cart();
	foreach ($items as $item => $values) {
		if ($values['reserve_id']) {
			// check reserve 
			if($values['_variation_id']>0){
			    if (!checkReserve($values['reserve_id'], getProductSpid($values['_variation_id']), $values['quantity'])) {
    				wc_add_notice(__(get_option('PSLB_Reservation_failed'), 'textdomain'), 'error');
    			}
			}
			else if (!checkReserve($values['reserve_id'], getProductSpid($values['product_id']), $values['quantity'])) {
				wc_add_notice(__(get_option('PSLB_Reservation_failed'), 'textdomain'), 'error');
			}
		}
	}
};
///////New
add_action('woocommerce_before_single_product', 'PSLB_update_product_stock', 10);
function PSLB_update_product_stock(){
    if(isset($_COOKIE["PS_Visit_Time"])){
        return;
    }
    setcookie(
		"PS_Visit_Time",
		time(),
		time() + ( 24 * 60 * 60)
	);
	global $product;
	$product_id=$product->get_id();
	if( $product->is_type( 'variable' ) ){
	    $current_products = $product->get_children();
	    $SiteCurrencyRate=get_option('PSLB_Site_Currency_Coeff');
	    $CHANGED=false;
	    foreach($current_products as $child_id){
	        if ($PS_Product = getSProductByPID($child_id)) {
	            $nowstock=get_post_meta( $child_id, '_stock', true );
	            product_Check_Stock($child_id, $PS_Product->SPID,$PS_Product->Interest_Rate,$SiteCurrencyRate);
	            $newstock=get_post_meta( $child_id, '_stock', true );
	            if($newstock!=$nowstock){
	                $CHANGED=true;
        		}
	        }
	    }
	    if($CHANGED) header("Refresh:0");
	}
	else{
    	if ($PS_Product = getSProductByPID($product_id)) {
    		$nowstock=$product->get_stock_quantity();
    		$SiteCurrencyRate=get_option('PSLB_Site_Currency_Coeff');
    		product_Check_Stock($product_id, $PS_Product->SPID,$PS_Product->Interest_Rate,$SiteCurrencyRate);
    		$newstock=$product->get_stock_quantity();
    		if($newstock!=$nowstock){
    			header("Refresh:0");
    		}
    	}
	}
	
}
/////////////////////////////
function action_woocommerce_remove_cart_item($cart_item_key, $instance)
{
	$items = WC()->cart->get_cart();
	foreach ($items as $item => $values) {
		if ($values['key'] == $cart_item_key) {
			if ($values['reserve_id']) {
				product_Delete_Server_Reserve($values['reserve_id']);
			}
		}
	}
};
//////////////New
add_action('woocommerce_add_to_cart', 'PSLB_action_woocommerce_add_to_cart', 20, 3);
function PSLB_action_woocommerce_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id='', $variation='' )
{
    $carts = WC()->cart->cart_contents;
	if (array_keys(array_column($carts, 'product_id'), $product_id)) {
		foreach ($carts as $key => $cart) {
		    if($variation_id>0 || $cart["variation_id"]>0){
                if ($cart["product_id"] == $product_id && $cart["variation_id"] == $variation_id && $key != $cart_item_key) {
    				$cart['quantity'] += $quantity;
    				WC()->cart->remove_cart_item($key);
    				WC()->cart->cart_contents[$cart_item_key] = $cart;
    				WC()->cart->set_session();
    			}
            }
			else if ($cart["product_id"] == $product_id && $key != $cart_item_key) {
				$cart['quantity'] += $quantity;
				WC()->cart->remove_cart_item($key);
				WC()->cart->cart_contents[$cart_item_key] = $cart;
				WC()->cart->set_session();
			}
		}
	}
};
//////

// add the action   NEW
add_action('woocommerce_remove_cart_item', 'action_woocommerce_remove_cart_item', 10, 2);
add_action('woocommerce_review_order_before_payment', 'action_woocommerce_review_order_before_payment', 10, 0);
add_filter('woocommerce_add_to_cart_validation', 'so_validate_add_cart_item', 10, 5);
add_action('woocommerce_after_checkout_validation', 'rei_after_checkout_validation');
/////////////////////////////NEW
//**********************
//************************
add_action('woocommerce_add_order_item_meta', function ($itemId, $values, $key) {
	if (isset($values['reserve_id'])) {
		wc_add_order_item_meta($itemId, 'reserve_id', $values['reserve_id']);
		wc_add_order_item_meta($itemId, 'LicenseCode', "");
	}
}, 10, 3);
add_action('woocommerce_order_status_completed', 'PSLB_action_woocommerce_order_status_completed');
add_action('woocommerce_order_status_processing', 'PSLB_action_woocommerce_order_status_completed');
function PSLB_action_woocommerce_order_status_completed($order_id)
{
	if(get_option('PSLB_OGet_Type')=='Get'){
		PSLB_Check_Order($order_id);
		if(get_option('PSLB_EmailCompletedOrder')=='Send'){
    	    PSLB_Email_Licenses($order_id);
    	}
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////
//////////*******************
//****************************
add_action( 'woocommerce_email_after_order_table', 'pslb_add_admin_verify_method', 10, 2 );
function pslb_add_admin_verify_method( $order, $is_admin_email ) {
    if($is_admin_email){
        $user = $order->get_user();
        $html= '<p style="background-color:powderblue;"><label style="font-size:16px;">Customer Phone : <strong>'.$user->billing_phone." </strong></label>\t\t\t\t\t\t\t\t";
        if(!isset($user->VerifiedMobile)){
			$html .= '<label style="font-size:14px; color:red;">Not Verified</label>';
		}
		else if($user->VerifiedMobile!=$user->billing_phone){
			$html .= '<label style="font-size:14px; color:red;">Not Verified</label>';
			///////////////////////////////////////////////
    		$html .= '<br>';
    		$ID=$user->ID."(&&)".$user->billing_phone."(&&)".date("d-m-Y H:i:s");
    		$ID=PSLB_encrypt_decrypt("encrypt",$ID,'PSLB1597852Padra');
    		$html .= '<a href="'.get_site_url().'/wp-json/pslb/verifyphone/?ID='.$ID.'"  style="background-color: #4CAF50;border: none;color: white;padding: 8px 10px;text-align: center;text-decoration: none;display: inline-block;font-size: 16px;margin: 4px 2px;cursor: pointer;">Verify Phone</a>';
    		////////////////////////////////////////
		}
		else $html .= '<label style="font-size:14px; color:green;">Verified</label>';
		$html .= '</p>';
		//////////////////////////////
		$html.= '<p style="background-color:LightGray;"><label style="font-size:16px;">Customer Email : <strong>'.$user->user_email." </strong></label>\t\t\t\t\t\t\t\t";
        if(!isset($user->VerifiedEmail)){
			$html .= '<label style="font-size:14px; color:red;">Not Verified</label>';
		}
		else if($user->VerifiedEmail!=$user->user_email){
			$html .= '<label style="font-size:14px; color:red;">Not Verified</label>';
		}
		else $html .= '<label style="font-size:14px; color:green;">Verified</label>';
		///////////////////////////////////////////////
		$html .= '</p>';
		$ID=$order->get_id()."(&&)".date("d-m-Y H:i:s");
		$ID=PSLB_encrypt_decrypt("encrypt",$ID,'PSLB1597852Padra');
		$html .= '<a href="'.get_site_url().'/wp-json/pslb/verifyorder/?ID='.$ID.'"  style="background-color: #4CAF50;border: none;color: white;padding: 8px 10px;text-align: center;text-decoration: none;display: inline-block;font-size: 16px;margin: 4px 2px;cursor: pointer;">Verify & Send Licenses</a>';
		////////////////////////////////////////
		echo $html;
    }

}
function PSLB_Verify_Order_endpoint_phrase() {
    $ID=$_GET["ID"];
    $ID=PSLB_encrypt_decrypt("decrypt",$ID,'PSLB1597852Padra');
    $pieces = explode("(&&)", $ID);
    $order_id=$pieces[0];
    $PSLB_OGet_Type = get_option('PSLB_OGet_Type');
    if ($PSLB_OGet_Type == 'AfterAdminVerify') {
        if(PSLB_Check_Order($order_id)){
            PSLB_Email_Licenses($order_id);
            return ( 'Order Verified Successfully.' );
        }
        else  return ( 'Order Verifing Failed!!!!' );
    }
    return ( 'Verifing Not Allowed!!!' );
}
function PSLB_Verify_Phone_endpoint_phrase() {
    $ID=$_GET["ID"];
    $ID=PSLB_encrypt_decrypt("decrypt",$ID,'PSLB1597852Padra');
    $pieces = explode("(&&)", $ID);
    $user_id=$pieces[0];
    $phone=$pieces[1];
    $user = get_user_by( 'id', $user_id );
    ////////////
    if(!isset($user->VerifiedMobile)){
		add_user_meta( $user->ID , "VerifiedMobile", $phone);
		return ( 'Phone Verified Successfully.');
	}
	else if($phone==$user->billing_phone){
		update_user_meta( $user->ID, 'VerifiedMobile', $phone);
		return ( 'Phone Verified Successfully.' );
	}
    ///////////////////
    else  return ( 'Phone Verifing Failed!!!!' );
}
function PSLB_Register_Verify_Order_Routes() {
    register_rest_route( 'pslb', '/verifyorder/', array(
        'methods' => 'GET',
        'callback' => 'PSLB_Verify_Order_endpoint_phrase',
    ));
    ////////////////
    register_rest_route( 'pslb', '/verifyphone/', array(
        'methods' => 'GET',
        'callback' => 'PSLB_Verify_Phone_endpoint_phrase',
    ));
}
add_action( 'rest_api_init', 'PSLB_Register_Verify_Order_Routes' );
function PSLB_encrypt_decrypt($action, $string,$secret_key) {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_iv = 'OvzFJ3m0O1DJDKM46cQ9RPD4g65WcmYD';
    // hash
    $key = hash('sha256', $secret_key);
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;

}
////////////////////
////////////////////
function PSLB_Email_Licenses($order_id){
    $mailer = WC()->mailer()->get_emails();
	$mailer['WC_Email_Customer_Completed_Order']->trigger( $order_id );
}
add_action( 'woocommerce_order_item_add_action_buttons', 'action_woocommerce_order_item_add_action_buttons', 10, 1);
// define the woocommerce_order_item_add_action_buttons callback
function action_woocommerce_order_item_add_action_buttons( $order )
{
    echo '<form><button type="button" onclick="document.getElementById(\'PS_GET_License\').value = 1;document.post.submit();" class="button generate-items">' . __( 'Accept And Set Order On License Bank !', 'hungred' ) . '</button>';
    // indicate its taopix order generator button
    echo '<input type="hidden" value="0" id="PS_GET_License" name="PS_GET_License" />';
    //
    
    echo '<button type="button" onclick="document.getElementById(\'PS_Email_License\').value = 1;document.post.submit();" class="button generate-items">' . __( ' Email Licenses To Customer!', 'hungred' ) . '</button>';
    // indicate its taopix order generator button
    echo '<input type="hidden" value="0" id="PS_Email_License" name="PS_Email_License" />';
};
// resubmit renew order handler
add_action('save_post', 'Do_PS_GET_License', 10, 3);
function Do_PS_GET_License($post_id, $post, $update){
    $slug = 'shop_order';
    if(is_admin()){
            // If this isn't a 'woocommercer order' post, don't update it.
            if ( $slug != $post->post_type ) {
                    return;
            }
            if(isset($_POST['PS_GET_License']) && $_POST['PS_GET_License']){
                   PSLB_Check_Order($post_id);
            }
            if(isset($_POST['PS_Email_License']) && $_POST['PS_Email_License']){
                   PSLB_Email_Licenses($post_id);
            }
    }
}