<?php
function PSLB_Product_list() {
	 global $wpdb;
     $table_name = $wpdb->prefix . "PSLB_Products";
     $posttable_name= $wpdb->prefix . "posts";
	 $postmetatable_name= $wpdb->prefix . "postmeta";
	if (isset($_POST['ProUpdateQuery']))
	{
		do_Check_Stock();
	}
    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/padrasafe-update-price/css/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>PS License Bank Dependent Products</h2>
        <div class="tablenav top">
            <div class="alignleft actions">
               <a href="<?php echo admin_url('admin.php?page=padra-LBAPI-AddProduct'); ?>"style="background-color: #4CAF50; border: none; color: white;padding: 5px 10px;text-align: center;text-decoration: none;display: inline-block;font-size: 16px;margin: 4px 2px;cursor: pointer;">ADD Products</a>
			   
            </div>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
				<input  tabindex="1"  type='submit' name="ProUpdateQuery" value='Update Products From Server' class='button' style="background-color: #4CAF50; border: none; color: white;padding: 0px 10px;text-align: center;text-decoration: none;display: inline-block;font-size: 16px;margin: 4px 2px;cursor: pointer;">
			    <input  tabindex="1"  type='submit' name="ProGetServerStock" value='Show Padra Server Stock' class='button' style="background-color: #4CAF50; border: none; color: white;padding: 0px 10px;text-align: center;text-decoration: none;display: inline-block;font-size: 16px;margin: 4px 2px;cursor: pointer;">
			</form>
            <br class="clear">
        </div>
		<br class="clear">
        <?php
		$sqlquery="SELECT DISTINCT $table_name.ID, $table_name.PID,$table_name.SPID,$table_name.SPPrice,$table_name.Interest_Rate,$table_name.SPTitle,$posttable_name.post_name,$posttable_name.post_title,$postmetatable_name.meta_value as Stock ";
		$sqlquery=$sqlquery." from $table_name INNER JOIN $posttable_name ON $table_name.PID=$posttable_name.ID ";
		$sqlquery=$sqlquery." LEFT JOIN $postmetatable_name ON $posttable_name.ID=$postmetatable_name.post_id ";
		$sqlquery=$sqlquery." WHERE $postmetatable_name.meta_key='_stock'";
        $rows = $wpdb->get_results($sqlquery);
        $rowscount1=0;
        foreach ($rows as $row) {
            $rowscount1++;
        }
        $radif1=1;
        ?>
    	<p>  &nbsp;&nbsp;&nbsp;&nbsp;  List Count :<?php    echo $rowscount1;?> </p>
        <table class='wp-list-table widefat fixed striped posts'>
            <tr>
                <th class="manage-column ss-list-width">Row</th>
                <th class="manage-column ss-list-width">WP Product ID</th>
				<th class="manage-column ss-list-width">Title</th>
                <th class="manage-column ss-list-width">Name</th>
                <?php 
    				if(isset($_POST['ProGetServerStock'])){
    				   echo ' <th class="manage-column ss-list-width">Padra Panel Stock</th>';
    				}
				?>
				<th class="manage-column ss-list-width">Stock</th>
				<th class="manage-column ss-list-width">SP ID</th>
				<th class="manage-column ss-list-width">SP Name</th>
				<th class="manage-column ss-list-width">SP Price($)</th>
				<th class="manage-column ss-list-width">Interest_Rate(%)</th>
				<th class="manage-column ss-list-width">Price calculated</th>
				<th class="manage-column ss-list-width">Current Product Price</th>
                <th>&nbsp;</th>
            </tr>
            <?php 
			$SiteCurrencyRate=get_option('PSLB_Site_Currency_Coeff');
			foreach ($rows as $row) {
				$_product = wc_get_product( $row->PID );
				if($row->Interest_Rate<>0)$Interest_Rate=$row->Interest_Rate/100;
				else $Interest_Rate=0;
				?>
                <tr>
                    <td class="manage-column ss-list-width"><?php 
                    echo $radif1;
                    $radif1++;
                    $LCArray=PSLB_Get_License_Stock($row->PID);
                    ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->PID; ?></td>
					<td class="manage-column ss-list-width"><?php echo urldecode($row->post_title); ?></td>
					<td class="manage-column ss-list-width"><?php echo urldecode($row->post_name); ?></td>
					<?php 
        				if(isset($_POST['ProGetServerStock'])){
        				    $PStock=product_Get_Server_Stock($row->SPID);
        				   echo ' <td class="manage-column ss-list-width">'.$PStock.'</td>';
        				}
				    ?>
					<td class="manage-column ss-list-width"><?php echo $row->Stock; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->SPID; ?></td>
					<td class="manage-column ss-list-width"><?php echo $row->SPTitle; ?></td>
					<td class="manage-column ss-list-width"><?php echo $row->SPPrice; ?></td>
					<td class="manage-column ss-list-width"><?php echo $row->Interest_Rate; ?></td>
					<td class="manage-column ss-list-width"><?php echo PSLB_Rond_Price((($Interest_Rate*$row->SPPrice)+$row->SPPrice)*$SiteCurrencyRate); ?></td>
					<td class="manage-column ss-list-width"><?php echo $_product->get_price(); ?></td>
                    <td><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-DeleteProduct&PID=' . $row->PID); ?>">Delete dependency</a></td>
					<td><a href="<?php echo get_edit_post_link($row->PID,'display'); ?>">Show Product</a></td>
					<td><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-EditeProduct&ID=' . $row->ID); ?>">Edit dependency</a></td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <?php
}