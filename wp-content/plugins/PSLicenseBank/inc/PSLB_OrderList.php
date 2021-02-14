<?php
function PSLB_Orders_list() {
	 global $wpdb;
    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/padrasafe-update-price/css/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Orders List</h2>
		 <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <table class='wp-list-table widefat fixed'>
                    <tr>
					<th>from date :</th>
					<td><input type="date" name="firstdate" value ="<?php if(isset($_POST['firstdate'])){ echo $_POST['firstdate'];} else {echo date('Y-m-d', strtotime('-2 days'));} ?>"></td>
					<th>to date :</th>
					<td><input type="date" name="enddate" value ="<?php if(isset($_POST['enddate'])){ echo $_POST['enddate'];}  else {echo date('Y-m-d');}?>"></td>
					<td> order status :<SELECT name="orderstatus" >
					    <option value="wc-completed" <?php if(!isset($_POST['orderstatus'])){ echo " selected";}  else {if($_POST['orderstatus']=='wc-completed'){echo " selected";}} ?>>completed</option>
						<option value="wc-pending" <?php if(isset($_POST['orderstatus'])){ if($_POST['orderstatus']=='wc-pending'){echo " selected";} }?>>Pending payment</option>
						<option value="wc-processing" <?php if(isset($_POST['orderstatus'])){ if($_POST['orderstatus']=='wc-processing'){echo " selected";} }?>>Processing</option>
						<option value="wc-on-hold" <?php if(isset($_POST['orderstatus'])){ if($_POST['orderstatus']=='wc-on-hold'){echo " selected";} }?>>On-Hold</option>
						<option value="wc-cancelled" <?php if(isset($_POST['orderstatus'])){ if($_POST['orderstatus']=='wc-cancelled'){echo " selected";} }?>>Cancelled</option>
						<option value="wc-refunded" <?php if(isset($_POST['orderstatus'])){ if($_POST['orderstatus']=='wc-refunded'){echo " selected";} }?>>Refunded</option>
					</select>
					</td>
					<td><input type='submit' name="ProSearch" value='Show' class='button'> &nbsp;&nbsp;</td>
					</tr>
                </table>
        </form>
        <?php
		if(isset($_POST['firstdate']) && isset($_POST['enddate'])&& isset($_POST['ProSearch'])){
			$fdate=$_POST['firstdate'];
			$edate=$_POST['enddate'];
			$PostTbl=$wpdb->prefix . "posts";
			
			$sqlquery="SELECT DISTINCT  $PostTbl.ID,$PostTbl.post_date";
			$sqlquery=$sqlquery." from $PostTbl ";
			$sqlquery.=" WHERE $PostTbl.post_type='shop_order' AND ($PostTbl.post_date BETWEEN '$fdate' AND '$edate') ";
			if(isset($_POST['orderstatus'])){
				$sqlquery.=" AND $PostTbl.post_status='".$_POST['orderstatus']."' ";
			}
			$rows = $wpdb->get_results($sqlquery);
			 $rowscount1=0;
			foreach ($rows as $row) {
				$rowscount1++;
			}
			$radif1=1;
        ?>
    	<p>  &nbsp;&nbsp;&nbsp;&nbsp;  rowcount :<?php    echo $rowscount1;?> </p>
        <table class='wp-list-table widefat fixed striped posts'>
            <tr>
                <th class="manage-column ss-list-width">row</th>
                <th class="manage-column ss-list-width">Order ID</th>
				<th class="manage-column ss-list-width">Date</th>
                <th class="manage-column ss-list-width">Customer</th>
				<th class="manage-column ss-list-width">Phone</th>
				<th class="manage-column ss-list-width">Email</th>
				<th class="manage-column ss-list-width">Details</th>
				<th class="manage-column ss-list-width">License Status</th>
                <th>&nbsp;</th>
            </tr>
            <?php foreach ($rows as $row) {
				$meta_values = get_post_meta($row->ID );
				$order = wc_get_order($row->ID);
				$items = $order->get_items();
				?>
			
                <tr>
                    <td class="manage-column ss-list-width"><?php 
                    echo $radif1;
                    $radif1++;
                    ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->ID; ?></td>
					<td class="manage-column ss-list-width"><?php echo $row->post_date; ?></td>
					<td class="manage-column ss-list-width"><?php echo ($meta_values["_billing_first_name"][0])." ".($meta_values["_billing_last_name"][0]); ?></td>
                    <td class="manage-column ss-list-width"><?php echo $meta_values["_billing_phone"][0]; ?></td>
					<td class="manage-column ss-list-width"><?php echo $meta_values["_billing_email"][0]; ?></td>
					<td class="manage-column ss-list-width"><?php 
					foreach ( $items as $item ) {
						echo "<p>";
						echo edit_post_link( __( $item->get_name(), 'textdomain' ), '', '', $item->get_product_id(), 'btn btn-primary btn-edit-post-link' );
						echo ' | Price: '. number_format( ($item->get_total()/$item->get_quantity()), 0 ).' | Numbers: '.$item->get_quantity().' |Total Price: '. number_format( $item->get_total(), 0 );
						$product_variation_id = $item->get_variation_id();
						echo "</p>";
					}
					?>
					</td>
					<td class="manage-column ss-list-width"><?php echo PSLB_Get_Order_status($row->ID); ?></td>
					<td><?php edit_post_link( __( 'Order', 'textdomain' ), '', '<label>/--/</label>', $row->ID, 'btn btn-primary btn-edit-post-link' );
					echo "<a href='".get_edit_user_link( $meta_values["_customer_user"][0] )."'>Customer</a>";?>
					<label>/--/</label><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-Order-Proccess&OID='.$row->ID); ?>">Proccess Order For Get License</a>
					</td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <?php
		}
}
function PSLB_Order_Proccess(){
	$OID = $_GET["OID"];
	if($OID){
		PSLB_Check_Order($OID);
	}
	echo "<h1>proccessing Completed .</h1>";
	echo "<h3>proccess Result : ". PSLB_Get_Order_status($OID)."</h3>";
}