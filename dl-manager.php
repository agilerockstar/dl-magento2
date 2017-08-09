<?php

function postMagento($userData, $uri) {
	global $token;
	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if (!$token) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Lenght: " . strlen(json_encode($userData))));
	}
	else {
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Lenght: " . strlen(json_encode($userData)), "Authorization: Bearer " . json_decode($token)));
	}		
	return curl_exec($ch);
}

function changeOrderStatus($entity_id, $status) {
	global $token;
	global $base_url;
	if ($status === "processing") {
		$newStatus = "readytoship";
	}
	if ($status === "sendvirtual") {
		$newStatus = "complete";
	}
	$userData = array('statusHistory' => array( 'status' => $newStatus ));
	$uri = $base_url."/index.php/rest/V1/orders/".$entity_id."/comments";
	postMagento($userData, $uri, $token);
}
	
function createKey(){
	global $resDB;
	//create a random key
	$strKey = md5(microtime());
	
	//check to make sure this key isnt already in use
	$resCheck = mysqli_query($resDB, "SELECT count(*) FROM downloads WHERE downloadkey = '{$strKey}' LIMIT 1");
	$arrCheck = mysqli_fetch_assoc($resCheck);
	if($arrCheck['count(*)']){
		//key already in use
		return createKey();
	}else{
		//key is OK
		return $strKey;
	}
}

//

// Headers to send HTML mail
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/html; charset=iso-8859-1';
$headers[] = 'From: *** <***>';
$headers[] = 'Bcc: ***';

$base_url = "***";

$mg_user = "***";
$mg_pwd = "***";

$db_user = "***";
$db_pwd = "***";
$db_name = "***";

//

// get token from Magento
$userData = array("username" => $mg_user, "password" => $mg_pwd);
$uri = $base_url."/index.php/rest/V1/integration/admin/token";
$token = postMagento($userData, $uri, false);
 
// get invoiced orders from Magento with status {processing, sendvirtual}
$ch = curl_init($base_url."/index.php/rest/V1/orders?searchCriteria[filter_groups][0][filters][0][field]=status&searchCriteria[filter_groups][0][filters][0][value]=processing&searchCriteria[filter_groups][0][filters][0][condition_type]=finset&searchCriteria[filter_groups][0][filters][1][field]=status&searchCriteria[filter_groups][0][filters][1][value]=sendvirtual&searchCriteria[filter_groups][0][filters][1][condition_type]=finset");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . json_decode($token)));
$result = json_decode(curl_exec($ch), 1);

// connect to the download manager DB
$resDB = mysqli_connect("localhost", $db_user, $db_pwd);
mysqli_select_db($resDB, $db_name);

// process each order
foreach ($result['items'] as $i) {
	// retrieve order information
	$order = array('entity_id' => (int)$i['entity_id'],
				   'status' => $i['status'],
				   'name' => $i['billing_address']['firstname'].' '.$i['billing_address']['lastname'],
		  		   'email' => $i['customer_email'], 'order_no' => $i['increment_id']);
	$items = array();
    foreach ($i['items'] as $key => $value ) {
		for ($j = 1; $j <= (int)$value['qty_invoiced']; $j++)
		array_push($items, array('sku'=>$value['sku'],'title'=>$value['name']));
	}

	// retrieve virtual items information and generate download links
	$virtualItems = array();
	foreach($items as $k) {
		$skuCheck = mysqli_query($resDB, "SELECT * FROM products WHERE sku = '{$k['sku']}'");
		$skuRes = mysqli_fetch_assoc($skuCheck);
		if ($skuRes) {
			//get a unique download key
			$strKey = createKey();
			//insert the download record into the database
			mysqli_query($resDB, "INSERT INTO downloads (downloadkey, bucket, filename, expires) VALUES ('{$strKey}', '{$skuRes['bucket']}', '{$skuRes['filename']}', '".(time()+(60*60*24*7))."')");
			array_push($virtualItems, array('title'=>$k['title'],
								'download_link'=>$base_url."/dl-multimedia.php?key=".$strKey,
								'size_mb'=>$skuRes['size_mb']));	
		}
	}
	
	if (!$virtualItems) {
		changeOrderStatus($order['entity_id'], $order['status'], $token);
	}
	else {
		$to = $order['name'] . " <" . $order['email'] .">";
		$subject = "Link multimediali per l'ordine ".$order["order_no"];

		$links = "";
		foreach($virtualItems as $k) {
			$links .= '<li><a href="'.$k["download_link"].'">
			'.$k["title"].'</a> ('.$k["size_mb"].' MB)
			</li>';
		}
		
		ob_start();
		include("dl-email-template.php");
		$message = ob_get_contents();
		ob_end_clean();

		// Mail it
		$sent = mail($to, $subject, $message, implode("\r\n", $headers));
		if ($sent) {
			changeOrderStatus($order['entity_id'], $order['status']);
		}

	}
}
?>