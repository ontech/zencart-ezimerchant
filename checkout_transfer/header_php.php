<?php

$db_query = $db->Execute("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_PAYMENT_EZIMERCHANT_MERCHANT'");
$ezi_merchantid = $db_query->fields['configuration_value'];

$db_query = $db->Execute("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_PAYMENT_EZIMERCHANT_APIKEY'");
$ezi_apikey = $db_query->fields['configuration_value'];


$paramhash = array();
$paramhash["ACTION"] = "CreateOrder";
$paramhash["RETURNURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "checkout_complete&o={orderid}", $_SERVER["REQUEST_URI"]);
$paramhash["CANCELURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "shopping_cart", $_SERVER["REQUEST_URI"]);
$paramhash["NOTIFYURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "checkout_complete", $_SERVER["REQUEST_URI"]);
//$paramhash["TEMPLATEURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "checkout_template", $_SERVER["REQUEST_URI"]);
$paramhash["CALCULATECALLBACKURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "checkout_calculate", $_SERVER["REQUEST_URI"]);
$paramhash["LOGINCALLBACKURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "checkout_login", $_SERVER["REQUEST_URI"]);
$paramhash["READONLY"] = 1;

$customerid = (int)$_SESSION['customer_id'];
if($customerid)
{
	$db_query = $db->Execute("SELECT CONCAT(c.customers_firstname, ' ', c.customers_lastname) AS Name, c.customers_email_address AS Email, c.customers_telephone AS Phone, ab.entry_company AS Company, ab.entry_street_address AS Address1, ab.entry_suburb AS Suburb, ab.entry_state AS State, ab.entry_postcode AS PostalCode, (SELECT countries_iso_code_2 FROM ".TABLE_COUNTRIES." WHERE countries_id = ab.entry_country_id) AS CountryCode FROM ".TABLE_CUSTOMERS." c LEFT JOIN ".TABLE_ADDRESS_BOOK." ab ON c.customers_default_address_id = ab.address_book_id WHERE c.customers_id = ".(int)$_SESSION['customer_id']);

	if($db_query->RecordCount() > 0)
	{
		$paramhash["BILLNAME"] = $db_query->fields["Name"];
		$paramhash["BILLPHONE"] = $db_query->fields["Phone"];
		$paramhash["BILLCOMPANY"] = $db_query->fields["Company"];
		$paramhash["BILLADDRESS1"] = $db_query->fields["Address1"];
		$paramhash["BILLEMAIL"] = $db_query->fields["Email"];
		$paramhash["BILLPLACE"] = $db_query->fields["Suburb"];
		$paramhash["BILLDIVISION"] = $db_query->fields["State"];
		$paramhash["BILLPOSTALCODE"] = $db_query->fields["PostalCode"];
		$paramhash["BILLCOUNTRYCODE"] = $db_query->fields["CountryCode"];
	}
	
	$paramhash["CUSTOMERID"] = $customerid;
	
}

$products = $_SESSION['cart']->get_products();

$itemidx = 0;
foreach($products as $product)
{
	$productid = zen_get_prid($product["id"]);
	$db_query = $db->Execute("SELECT products_image, COALESCE(products_weight, 0.0) AS products_weight FROM ".TABLE_PRODUCTS." WHERE products_id = ".$productid);
	$productimage = $db_query->fields['products_image'];
	$productweight = $db_query->fields['products_weight'];

	$paramhash["PRODUCTCODE(" . $itemidx . ")"] = utf8_encode($product["model"]);
	$paramhash["PRODUCTNAME(" . $itemidx . ")"] = utf8_encode($product["name"]);
	$paramhash["PRODUCTPRICE(" . $itemidx . ")"] = $product["final_price"];
	$paramhash["PRODUCTQUANTITY(" .$itemidx . ")"] = $product["quantity"];
	$paramhash["PRODUCTIMAGEURL(" .$itemidx . ")"] = 'http://'.$_SERVER["HTTP_HOST"].preg_replace("/index\.php\?.*/", "images/".$productimage, $_SERVER["REQUEST_URI"]);

	$attridx = 0;
	foreach($product['attributes'] as $attribute => $attributevalue)
	{
		$db_query = $db->Execute("SELECT products_options_name AS name, COALESCE((SELECT products_attributes_weight FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id = ".$productid." AND options_id = ".$attribute." AND options_values_id = ".$attributevalue."), 0) AS weight, CASE WHEN ".$attributevalue." = 0 THEN '".zen_db_prepare_input($product['attributes_values'][$attribute])."' ELSE (SELECT products_options_values_name FROM ".TABLE_PRODUCTS_OPTIONS_VALUES." WHERE products_options_values_id = ".$attributevalue." AND language_id = 1) END AS value FROM ".TABLE_PRODUCTS_OPTIONS." WHERE products_options_id = ".$attribute." AND language_id = 1");

		$paramhash["PRODUCTATTRIBUTE(".$itemidx.")(".$attridx.")"] = $db_query->fields['name'];
		$paramhash["PRODUCTATTRIBUTEVALUE(".$itemidx.")(".$attridx.")"] = $db_query->fields['value'];
		
		$productweight = $productweight + $db_query->fields['weight'];

		$attridx = $attridx + 1;
	}
	
	/*try
	{
		$db_query = $db->Execute("SELECT products_length, products_height, products_width FROM ".TABLE_PRODUCTS." WHERE products_id = ".$productid);
		
		if(is_numeric($db_query->fields['product_width']))
			$paramhash["PRODUCTWIDTH(".$itemidx.")"] = $db_query->fields['product_width'];
		if(is_numeric($db_query->fields['product_length']))
			$paramhash["PRODUCTLENGTH(".$itemidx.")"] = $db_query->fields['product_length'];
		if(is_numeric($db_query->fields['product_height']))
			$paramhash["PRODUCTHEIGHT(".$itemidx.")"] = $db_query->fields['product_height'];
	}
	catch(Exception $e)
	{
	
	}*/

	if(is_numeric($productweight))
		$paramhash["PRODUCTWEIGHT(" .$itemidx . ")"] = $productweight;	

	$itemidx = $itemidx + 1;		
}



$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$ezi_merchantid."/orders/");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded", 'X-APIKEY: '.$ezi_apikey ));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paramhash));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

$apiresponse = curl_exec($ch);
curl_close($ch);
 
$xml = simplexml_load_string($apiresponse);

$checkouturl = (string)$xml->entry->link["href"];
if(isset($_GET['type']) && $_GET['type']=="ec")
	$checkouturl .= "&pp=1";

zen_redirect($checkouturl);
?>
