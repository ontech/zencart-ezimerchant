<?php

require('includes/languages/english/modules/order_total/ot_total.php');

if(!isset($_GET["o"]))
{
	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		$xml = simplexml_load_string(file_get_contents("php://input"));

		$ordercontent = $xml->entry->content->children('http://api.ezimerchant.com/schemas/2009/');
		if($ordercontent)
		{
			$orderid = (int)$ordercontent->orderid;

			ezi_process_xml($orderid, $xml);

			echo "OK";
			die();
		}
	}

	Header("Status: 404 Page Not Found");
	die();
}

$orderid = (int)$_GET["o"];

$db_query = $db->Execute("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_PAYMENT_EZIMERCHANT_MERCHANT'");
$ezi_merchantid = $db_query->fields['configuration_value'];

$db_query = $db->Execute("SELECT configuration_value FROM ".TABLE_CONFIGURATION." WHERE configuration_key = 'MODULE_PAYMENT_EZIMERCHANT_APIKEY'");
$ezi_apikey = $db_query->fields['configuration_value'];


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$ezi_merchantid."/orders/".$orderid."/");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded", "X-APIKEY: ".$ezi_apikey ));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

$apiresponse = curl_exec($ch);
curl_close($ch);                                                          

$xml = simplexml_load_string($apiresponse);

ezi_process_xml($orderid, $xml);

$_SESSION['cart']->reset(true);

zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));


function ezi_process_xml($orderid, $xml)
{

	global $db;
	global $currencies;
	global $zco_notifier;
	global $order_total_modules;

	$db->Execute("START TRANSACTION");

	$neworder = true;

	$db->Execute("DELETE FROM ".TABLE_ORDERS." WHERE orders_id = ".$orderid);

	if(!isset($_GET["new"]) && mysql_affected_rows() > 0)
		$neworder = false;

	$db->Execute("DELETE FROM ".TABLE_ORDERS_TOTAL." WHERE orders_id = ".$orderid);
	$db->Execute("DELETE FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_id = ".$orderid);

	$ordercontent = $xml->entry->content->children('http://api.ezimerchant.com/schemas/2009/');

	$customer = $ordercontent->orderaddresses->orderaddress[0];
	$billing = $customer;
	$delivery = $ordercontent->orderaddresses->orderaddress[1];
	

	$db_query = $db->Execute("SELECT countries_id, countries_name, address_format_id FROM ".TABLE_COUNTRIES." WHERE countries_iso_code_2 = '".$customer->countrycode."'");
	$customer_country_id = $db_query->fields['countries_id'];
	$customer_address_format_id = $db_query->fields['address_format_id'];
	$customer_country_name = $db_query->fields['countries_name'];
	
	$orderstatus = DEFAULT_ORDERS_STATUS_ID;
	
	if($ordercontent->paymentstatus == "complete")
         $orderstatus = 2;
     if($ordercontent->orderstate == "complete")
         $orderstatus = 3;

	$sql_data_array = array(
	"orders_id" => (int)$ordercontent->orderid,
	"customers_id" => (int)$ordercontent->customerid,
	"customers_name" => $customer->name,
	"customers_company" => $customer->companyname,
	"customers_street_address" => $customer->address1,
	"customers_suburb" => $customer->address2,
	"customers_city" => $customer->place,
	"customers_postcode" => $customer->postalcode,
	"customers_state" => $customer->division,
	"customers_country" => $customer_country_name,
	"customers_telephone" => $customer->phone,
	"customers_email_address" => $customer->email,
	"customers_address_format_id" => $customer_address_format_id,
	"delivery_name" => $delivery->name,
	"delivery_company" => $delivery->companyname,
	"delivery_street_address" => $delivery->address1,
	"delivery_suburb" => $delivery->address2,
	"delivery_city" => $delivery->place,
	"delivery_postcode" => $delivery->postalcode,
	"delivery_state" => $delivery->division,
	"delivery_country" => $delivery_country_name,
	"delivery_address_format_id" => $delivery_address_format_id,
	"billing_name" => $customer->name,
	"billing_company" => $customer->companyname,
	"billing_street_address" => $customer->address1,
	"billing_suburb" => $customer->address2,
	"billing_city" => $customer->place,
	"billing_postcode" => $customer->postalcode,
	"billing_state" => $customer->division,
	"billing_country" => $customer_country_name,
	"billing_address_format_id" => $customer_address_format_id,
	"orders_status" => $orderstatus,
	"last_modified" => (string)$ordercontent->createddate, 
	"date_purchased" => (string)$ordercontent->createddate,
	"payment_method" => (string)$ordercontent->orderpayments->orderpayment[0]->paymentmethod,
	"currency" => (string)$ordercontent->transactcurrency,
	"currency_value" => (float)$ordercontent->ordertotal / (float)$ordercontent->defaultcurrencytotal,
	'ip_address' => (string)$ordercontent->ipaddress
	);

	zen_db_perform(TABLE_ORDERS, $sql_data_array);
	
	$insert_orderid = $db->Insert_ID();

	if($neworder)
	{
		$sql_data_array = array('orders_id' => (int)$ordercontent->orderid, 
                        'orders_status_id' => DEFAULT_ORDERS_STATUS_ID, 
                        'date_added' => 'now()', 
                        'customer_notified' => 1,
                        'comments' => (string)$ordercontent->instructions);

		zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
	}

	$products = $ordercontent->orderlines->orderline;

	foreach($products as $product)
	{
		if('FREIGHT' != $product->productcode)
		{
			$db_query = $db->Execute("SELECT products_id FROM ".TABLE_PRODUCTS." WHERE products_model = '".zen_db_input($product->productcode)."'");
			$productid = $db_query->fields['products_id'];
	
			// push product data back in
			$sql_data_array = array('orders_id' => (int)$ordercontent->orderid, 
	                            'products_id' => zen_get_prid($productid), 
	                            'products_model' => (string)$product->productcode, 
	                            'products_name' => (string)$product->productname, 
	                            'products_price' => (float)$product->price, 
	                            'final_price' => (float)$product->priceinctax, 
	                            'products_tax' => round((((float)$product->defaultcurrencylinetotalinctax / (float)$product->defaultcurrencylinetotal) - 1.0) * 100.0), 
	                            'products_quantity' => (int)$product->quantity,
	                            'products_prid' => $productid);
	
		    zen_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
		    $order_products_id = $db->Insert_ID();

		    if(isset($product->attributes->attribute))
		    {
				foreach($product->attributes->attribute as $attribute)
				{
					$attributes = $attribute->attributes();
		
					$sql_data_array = array('orders_id' => (int)$ordercontent->orderid,
						'orders_products_id' => $order_products_id,
						'products_options' => (string)$attributes['name'],
						'products_options_values' => (string)$attributes['value'],
						'products_prid' => $productid);
					zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);
				}	
			}
		}
		else
		{						
			$sql_data_array = array(
			"orders_id" => $ordercontent->orderid,
			"title" => $product->productname,
			"text" => '<b>' . $currencies->format((float)$product->priceinctax, false, (string)$ordercontent->transactcurrency) . '</b>',
			"value" => (float)$product->priceinctax,
			"class" => "ot_shipping",
			"sort_order" => 200
			);
			zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);		
		}
	}
	
	$sql_data_array = array(
	"orders_id" => $ordercontent->orderid,
	"title" => 'Sub-Total',
	"text" => '<b>' . $currencies->format((float)$ordercontent->ordertotal - (float)$product->priceinctax, false, (string)$ordercontent->transactcurrency) . '</b>',
	"value" => (float)$ordercontent->ordertotal - (float)$product->priceinctax,
	"class" => "ot_subtotal",
	"sort_order" => 100
	);
	zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
	
	$sql_data_array = array(
	"orders_id" => $ordercontent->orderid,
	"title" => MODULE_ORDER_TOTAL_TOTAL_TITLE,
	"text" => '<b>' . $currencies->format((float)$ordercontent->ordertotal, false, (string)$ordercontent->transactcurrency) . '</b>',
	"value" => (float)$ordercontent->ordertotal,
	"class" => "ot_total",
	"sort_order" =>999	);		
	zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);			


	$_SESSION['customer_id'] = (int)$ordercontent->customerid;

	if($neworder)
	{
		$zco_notifier->notify('NOTIFY_MODULE_START_CREATE_ACCOUNT');

		$db_query = $db->Execute("SELECT customers_id FROM ".TABLE_CUSTOMERS." WHERE customers_email_address = '".zen_db_input($customer->email)."'");
		if($db_query->RecordCount() == 0)
		{
			$name = preg_split("/[\s]+/", $customer->name, 2);

			$firstname = $name[0];
			$lastname = $name[1];
			$zone_id = 0;
            $customerid = (int)$ordercontent->customerid;
		
	        $db_query = $db->Execute("select distinct zone_id from " . TABLE_ZONES . " where zone_country_id = '" . (int) $customer_country_id . "' and (zone_name = '" . zen_db_input($customer->division) . "' or zone_code = '" . zen_db_input($customer->division) . "')");
	        if ($db_query->RecordCount() == 1)
	          $zone_id = $db_query->fields['zone_id'];
		
			$db->Execute("DELETE FROM ".TABLE_CUSTOMERS." WHERE customers_id = ".$customerid);
			$db->Execute("DELETE FROM ".TABLE_CUSTOMERS_INFO." WHERE customers_info_id = ".$customerid);

		    $sql_data_array = array('customers_id' => $customerid,
								'customers_firstname' => $firstname,
	                            'customers_lastname' => $lastname,
	                            'customers_email_address' => $customer->email,
	                            'customers_telephone' => $customer->phone,
	                            'customers_fax' => $customer->fax,
	                            'customers_newsletter' => 1);
		    zen_db_perform(TABLE_CUSTOMERS, $sql_data_array);

		    $zco_notifier->notify('NOTIFY_MODULE_CREATE_ACCOUNT_ADDED_CUSTOMER_RECORD', array_merge(array('customer_id' => $customerid), $sql_data_array));
	
	
		    $sql_data_array = array('customers_info_id' => $customerid,
								'customers_info_date_of_last_logon' => $ordercontent->createddate,
								'customers_info_number_of_logons' => 1,
								'customers_info_date_account_created' => $ordercontent->createddate,
								'customers_info_date_account_last_modified' => $ordercontent->createddate);
		    zen_db_perform(TABLE_CUSTOMERS_INFO, $sql_data_array);
	
			$sql_data_array = array('customers_id' => $customerid,
		                        'entry_firstname' => $firstname,
		                        'entry_lastname' => $lastname,
		                        'entry_street_address' => $customer->address1,
		                        'entry_postcode' => $customer->postalcode,
		                        'entry_city' => $customer->place,
		                        'entry_country_id' => $customer_country_id,
								'entry_company' => $customer->company,
								'entry_suburb' => $customer->address2,
								'entry_state' => $customer->division,
								'entry_zone_id' => $zone_id);

			zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

			$address_id = $db->Insert_ID();
			
			
			
			$sql_data_array = array('customers_id' => $customerid,
		                        'entry_firstname' => $firstname,
		                        'entry_lastname' => $lastname,
		                        'entry_street_address' => $delivery->address1,
		                        'entry_postcode' => $delivery->postalcode,
		                        'entry_city' => $delivery->place,
		                        'entry_country_id' => $customer_country_id,
								'entry_company' => $delivery->company,
								'entry_suburb' => $delivery->address2,
								'entry_state' => $delivery->division,
								'entry_zone_id' => $zone_id);

			zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

			$sendaddress_id = $db->Insert_ID();
			
			
			$zco_notifier->notify('NOTIFY_MODULE_CREATE_ACCOUNT_ADDED_ADDRESS_BOOK_RECORD', array_merge(array('address_id' => $address_id), $sql_data_array));

			$db->Execute("UPDATE ".TABLE_CUSTOMERS." SET customers_default_address_id = ".$address_id." WHERE customers_id=".$customerid);

		    $_SESSION['customer_first_name'] = $firstname;
		    $_SESSION['customer_default_address_id'] = $address_id;
			$_SESSION['billto'] = $address_id;
			$_SESSION['sendto'] = $sendaddress_id;
		    $_SESSION['customer_country_id'] = $customer_country_id;
		    $_SESSION['customer_zone_id'] = $zone_id;
		    $_SESSION['customers_authorization'] = '';
	
			// include order procesing logic
			if (!isset($_SESSION['language']))
				$_SESSION['language'] = 'english';
			if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'checkout_process.php')) {
				require(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'checkout_process.php');
			} else {
				require(DIR_WS_LANGUAGES . $_SESSION['language'] . '/checkout_process.php');
			}
			require(DIR_WS_CLASSES . 'order.php');		
			require(DIR_WS_CLASSES . 'order_total.php');
			
			$order_total_modules = new order_total();		
			
			$order = new order();
			$order->query((int)$ordercontent->orderid);
			$order->create_add_products((int)$ordercontent->orderid);
	
			$order->send_order_email((int)$ordercontent->orderid, 2);
		}
		else
		{
			$db_query = $db->Execute("SELECT address_book_id, entry_firstname, entry_country_id, entry_zone_id FROM ".TABLE_ADDRESS_BOOK." WHERE address_book_id = (SELECT customers_default_address_id FROM ".TABLE_CUSTOMERS." WHERE customers_id = ".(int)$_SESSION['customer_id'].")");
			if($db_query->RecordCount() > 0)
			{
			    $_SESSION['customer_first_name'] = $db_query->fields['entry_firstname'];
			    $_SESSION['customer_default_address_id'] = $db_query->fields['address_book_id'];
				$_SESSION['billto'] = $db_query->fields['address_book_id'];
				$_SESSION['sendto'] = $db_query->fields['address_book_id'];
			    $_SESSION['customer_country_id'] = $db_query->fields['entry_country_id'];
			    $_SESSION['customer_zone_id'] = $db_query->fields['entry_zone_id'];
			}
		}		
	}
  
 	$db->Execute("COMMIT");

	$zco_notifier->notify('NOTIFY_MODULE_END_CREATE_ACCOUNT');
	
}

?>
