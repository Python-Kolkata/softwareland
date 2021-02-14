<?php
function PSLB_AddProduct()
{
	$PID = $_GET["PID"];
	$SPID = $_GET["SPID"];
	$SPTitle = $_GET["SPTitle"];
	global $wpdb;
     $posttable_name= $wpdb->prefix . "posts";
	 $Protable_name= $wpdb->prefix . "PSLB_Products";
	 $postmetatable_name= $wpdb->prefix . "postmeta";
	/////
	if (isset($_POST['ProAddQuery']))
	 {
	     ?>
	     <p><h3><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-AddProduct'); ?>">Back To Products List </a></h3></p>
	     <?php
		 if( $_POST['SProID'])
		 {
			 $Reqvalue="&action=get_proname&PID=".$_POST['SProID'];
			 $reqresult=PSLB_query_results($Reqvalue);
			 $resultpieces = explode("(&&)", $reqresult);
			 $SPTitle="";
			 $SPStock=0;
			 $SPPrice =0;
			
			$resultarray =json_decode($reqresult,256);
			if($resultarray["result"]){
				if ($resultarray["result"] == "Success") {
					$SPTitle =$resultarray["product_title"] ;
					$SPStock = $resultarray["product_stock"];
					
					$SPPrice = $resultarray["product_price"];
				}
				else $SPTitle = $reqresult;
			}
			else $SPTitle = $reqresult;
			$Found=get_product_pid_by_spid($_POST['SProID']);
			if($Found){
			    $post = get_post( $Found );
                $title = isset( $post->post_title ) ? $post->post_title : '';
			    echo "<h1 style='color:red;'>This `ID` had been linked to another product in your shop with the product's id: ".$Found."(".$title.")</h1>";
			}
		 }
		 
		 ?>
		    
			<p><h3>are you sure you want to add below product ?</h3></p>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <table class='wp-list-table widefat fixed'>
                <tr>
                    <th class="ss-th-width"> Product Name :</th>
                    <td><?php echo $_GET['Proname']; ?></td>
					<td><input type="hidden" name="ProID" value="<?php echo $_POST['ProID']; ?>"></td>
                </tr>
				<tr>
                    <th class="ss-th-width"> PSLB Product ID :</th>
                    <td><input  tabindex="1"  type="number" min="1" max ="10000000000" step="1" name="SProID" value="<?php echo $_POST['SProID']; ?>" class="ss-field-width"  readonly /></td>
                </tr>
				<tr>
                    <th class="ss-th-width"> PSLB Product Title :</th>
                    <td><?php echo $SPTitle; ?>
                    <input type="hidden" name="SPTitle" value="<?php echo $SPTitle; ?>"/>
                    </td>
                    
                </tr>
				<tr>
                    <th class="ss-th-width"> PSLB Product Stock :</th>
                    <td><input  name="SPStock" type="text" value="<?php echo $SPStock; ?>" class="ss-field-width"  readonly /></td>
                </tr>
				<tr>
                    <th class="ss-th-width"> PSLB Product Price :</th>
                    <td><input  name="SPPrice" type="text" value="<?php echo $SPPrice; ?>" class="ss-field-width"  readonly /></td>
                </tr>
				<tr>
                    <th class="ss-th-width"> Interest Rate :</th>
                    <td><input  name="Interest_Rate" type="text" value="30" class="ss-field-width"/></td>
                </tr>
            </table>
            <input  tabindex="1"  type='submit' name="ProAddQuery2" value='Yes' class='button'>
        </form>
		 <?php
	 }
    if (isset($_POST['ProAddQuery2']))
	 {
		 if( $_POST['SProID'] && $_POST['ProID'] )
		 {
			 $res=$wpdb->get_results("SELECT PID FROM $Protable_name WHERE PID = ".$_POST['ProID']);
			 $rowscount1=0;
			 foreach ($res as $row) {
				$rowscount1++;
			 }
			 if($rowscount1==0){
				$wpresault=$wpdb->insert(
				$Protable_name, //table
                 array( 'PID' => $_POST['ProID'],'SPID'=>$_POST['SProID'],'SPTitle'=>$_POST['SPTitle'],'SPPrice'=>$_POST['SPPrice'],'Interest_Rate'=>$_POST['Interest_Rate']), //data
                 array('%s','%s', '%s', '%s', '%s') //data format			
				);
				if(!$wpresault)
				{
					?><p><h3>Adding Error!!! (Insert query)<?php echo $wpresault."__".$_POST['SProID']."__".$_POST['ProID']; ?></h3></p>
					<p><h3><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-AddProduct'); ?>">Back To Products List </a></h3></p><?php
				}
				else{
					if(get_option('PSLB_ControlStock_Type')=='Check'){
					    PSLB_Set_Stock($_POST['ProID'],$_POST['SPStock']);
				    }
					?><p><h3>Added Success!!!</h3></p>
					<p><h3><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-AddProduct'); ?>">Back To Products List </a></h3></p><?php
				}
			}
			else{
					?><p><h3>This Product Added in last!!!</h3></p>
					<p><h3><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-AddProduct'); ?>">Back To Products List </a></h3></p><?php
				}
		 }
		 else{
			 ?><p><h3>Adding Error!!!</h3></p>
			 <p><h3><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-AddProduct'); ?>">Back To Products List </a></h3></p><?php
		 }
	 }
	else if (isset($_GET['ProAddForm']))
	 {
		 $rows1 = $wpdb->get_results("SELECT CID,CurName,CurPrice from $table_name");
		  global $wp_roles;
		  if ( !isset( $wp_roles ) ) $wp_roles = new WP_Roles();
			$available_roles = array();
			$available_roles = $wp_roles->get_names();
		////////
		 ?>
		<div class="wrap">
        <h2>Add Product to Dependent Products To Padra License Bank</h2>
		<p><h3><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-AddProduct'); ?>">Back To Products List </a></h3></p>
        <?php if (isset($message)): ?><div class="updated"><p><?php echo $message; ?></p></div><?php endif; ?>
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <table class='wp-list-table widefat fixed'>
                <tr>
                    <th class="ss-th-width"> Product Name :</th>
                    <td><?php echo $_GET['Proname']; ?></td>
					<td><input type="hidden" name="ProID" value="<?php echo $_GET['PID']; ?>"></td>
                </tr>
                <?php
                $product = wc_get_product($_GET['PID']);
                $current_products = $product->get_children();
                if( !$product->is_type( 'variable' ) ){
                    // a simple product
                    ?>
    				<tr>
                        <th class="ss-th-width"> Padra ID :</th>
                        <td><input  tabindex="1"  type="number" min="1" max ="10000000000" step="1" name="SProID" value="" class="ss-field-width" /></td>
                    </tr>
                    <tr>
                        <td><input  tabindex="1"  type='submit' name="ProAddQuery" value='Add' class='button'></td>
                    </tr>
                  <?php
                } else if( $product->is_type( 'variable' ) ){
                    
                    $count_child=0;
                    $childs_str="";
                    foreach($current_products as $child_id){
                        if($count_child>0) $childs_str.=",";
                        $count_child++;
                        $childs_str.=$child_id;
            	    }
            	    
            	    $sqlquery="SELECT $posttable_name.ID,$posttable_name.post_name,$posttable_name.post_title ";
            		$sqlquery=$sqlquery." FROM $posttable_name ";
            		$sqlquery=$sqlquery." WHERE $posttable_name.ID NOT IN (SELECT PID FROM $Protable_name) AND $posttable_name.ID IN($childs_str) ";
                    $rows = $wpdb->get_results($sqlquery,OBJECT);
                    $rowscount1=0;
                    foreach ($rows as $row) {
                        $rowscount1++;
                    }
                    $radif=1;
                    foreach ($rows as $row) {
                        ?>
                        <tr>
                        <th class="ss-th-width">Variable Product #<?php echo  $radif;  ?> :</th>
                        <td><?php echo $_GET['Proname']."#".$row->post_title; ?></td>
					    <td><?php echo "ID : ".$row->ID; ?></td>
					    <td><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-AddProduct&ProAddForm=AddNew&PID=' . $row->ID.'&Proname='.$_GET['Proname']."_".$row->post_title); ?>">Add</a></td>
					    </tr>
					    <?php
					    $radif++;
                    }
                }
                ?>
            </table>
            
        </form>
        </div><?php
	 }
	 else
	 {
		$sqlquery="SELECT $posttable_name.ID,$posttable_name.post_name,$posttable_name.post_title ";
		$sqlquery=$sqlquery." FROM $posttable_name ";
		$sqlquery=$sqlquery." WHERE $posttable_name.post_type='product' AND $posttable_name.post_status = 'publish' AND $posttable_name.ID NOT IN (SELECT PID FROM $Protable_name)";
        $rows = $wpdb->get_results($sqlquery,OBJECT);
        $rowscount1=0;
        foreach ($rows as $row) {
            $rowscount1++;
        }
        $radif=1;
        ?>
        <meta charset="UTF-8">

		<div>
		<p> Not Added Products :   &nbsp;&nbsp;&nbsp;&nbsp;  List Count :<?php    echo $rowscount1;?> </p>  <table class='wp-list-table widefat fixed striped posts'>
            <tr>
                <th class="manage-column ss-list-width">row</th>
                <th class="manage-column ss-list-width">WP Product ID</th>
				<th class="manage-column ss-list-width">Title</th>
                <th class="manage-column ss-list-width">Name</th>
                <th>&nbsp;</th>
            </tr>
            <?php foreach ($rows as $row) { ?>
                <tr>
                    <td class="manage-column ss-list-width"><?php 
                    echo $radif;
                    $radif++;
                    ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->ID; ?></td>
					<td class="manage-column ss-list-width"><?php
					echo urldecode($row->post_title);
					?></td>
					<td class="manage-column ss-list-width"><?php
					echo urldecode($row->post_name);
					?></td>
                    <td><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-AddProduct&ProAddForm=AddNew&PID=' . $row->ID.'&Proname='.$row->post_name); ?>">Add</a></td>
					<td><a href="<?php echo get_edit_post_link($row->ID,'display'); ?>">Show Product</a></td>
                </tr>
            <?php } ?>
        </table></div><?php
	 }
}
function PSLB_DeleteProduct()
{
	global $wpdb;
	$Protable_name= $wpdb->prefix . "PSLB_Products";
	 
	if (isset($_GET['PID']))
	 {
		 $res=$wpdb->query($wpdb->prepare("DELETE FROM $Protable_name WHERE PID = %d", $_GET['PID']));
		 ?>
		<p><h2> Delete Success .</h2></p>
		<p><h3><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-Products'); ?>">Back To Products List </a></h3></p><?php
	 }
	 else{
		 ?>
		 <p><h2> Delete Error !!! .</h2></p>
		<p><h3><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-Products'); ?>">Back To Products List </a></h3></p><?php
	 }
}
function PSLB_EditeProduct()
{
	$ID = $_GET["ID"];
	global $wpdb;
    $posttable_name= $wpdb->prefix . "posts";
	$Protable_name= $wpdb->prefix . "PSLB_Products";
	$postmetatable_name= $wpdb->prefix . "postmeta";
	//////
	if(isset($_POST['ProEditeQuery'])){
		if( $_POST['PSLB_ID'] && $_POST['Interest_Rate'])
		{
			$wpdb->update(
					$Protable_name, //table
					array('Interest_Rate' => $_POST['Interest_Rate']),//data
					array('ID' => $_POST['PSLB_ID']), //where
					array('%d'), //data format
					array('%d') //where format
				);
			?><p><h3>Success.</h3></p><?php
		}
	}
	$res=$wpdb->get_results("SELECT * FROM $Protable_name WHERE ID = ".$ID);
	$rowscount1=0;
	foreach ($res as $row){
		$rowscount1++;
	}
	if($rowscount1==0){
		?><p><h3>Not Found!!!</h3></p>
			 <p><h3><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-Products'); ?>">Back To Products List </a></h3></p><?php
	}
	else{
		$row=$res[0];
		?>
		    <p><h3><a href="<?php echo admin_url('admin.php?page=padra-LBAPI-Products'); ?>">Back To Products List </a></h3></p>
			<p><h3>are you sure you want to add below product ?</h3></p>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <table class='wp-list-table widefat fixed'>
				<tr>
					<input type="hidden" name="PSLB_ID" value="<?php echo $row->ID; ?>" readonly />
                    <th class="ss-th-width"> PSLB Product ID :</th>
                    <td><input  tabindex="1"  type="number" min="1" max ="10000000000" step="1" name="SProID" value="<?php echo $row->PID; ?>" class="ss-field-width"  readonly /></td>
                </tr>
				<tr>
                    <th class="ss-th-width"> PSLB Product Title :</th>
                    <td><input  name="SPTitle" type="text" value="<?php echo $row->SPTitle; ?>" class="ss-field-width"  readonly /></td>
                </tr>
				<tr>
                    <th class="ss-th-width"> PSLB Product Price :</th>
                    <td><input  name="SPPrice" type="text" value="<?php echo $row->SPPrice; ?>" class="ss-field-width"  readonly /></td>
                </tr>
				<tr>
                    <th class="ss-th-width"> Interest Rate :</th>
                    <td><input  name="Interest_Rate" id="Interest_Rate" type="number" min="0" max="100" type value="<?php echo $row->Interest_Rate; ?>" class="ss-field-width"/></td>
                </tr>
            </table>
            <input  tabindex="1"  type='submit' name="ProEditeQuery" value='Yes' class='button'>
        </form>
		 <?php
	}
	///////////
	
}