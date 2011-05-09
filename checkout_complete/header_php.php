<?php

require('includes/languages/english/modules/order_total/ot_total.php');

if(!is_int((int)$_GET["o"]))
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

	$products_ordered = "";
	$products_ordered_html = "";
	$products_ordered_attributes = "";
	foreach($products as $product)
	{
	if('FREIGHT' != $product->productcode)
	{
		$db_query = $db->Execute("SELECT products_id FROM ".TABLE_PRODUCTS." WHERE products_model = '".zen_db_input($product->productcode)."'");
		$productid = $db_query->fields['products_id'];

		if($neworder)
		{
		    $zco_notifier->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN');

			$db->Execute("UPDATE " . TABLE_PRODUCTS . " SET products_ordered = products_ordered + " . sprintf('%d', $product->quantity) . " WHERE products_id = '" . zen_get_prid($productid) . "'");

			$zco_notifier->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END');

		}

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

		foreach($product->attributes->attribute as $attribute)
		{
			$attributes = $attribute->attributes();

			$sql_data_array = array('orders_id' => (int)$ordercontent->orderid,
				'orders_products_id' => $order_products_id,
				'products_options' => (string)$attributes['name'],
				'products_options_values' => (string)$attributes['value'],
				'products_prid' => $productid);
			zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);
			
			
			$products_ordered_attributes .= "\n\t" . (string)$attributes['name'] . ' ' . zen_decode_specialchars((string)$attributes['value']);
			
			
		}	
				
	  $products_ordered .=  (int)$product->quantity . ' x ' . (string)$product->productname . ((string)$product->productcode != '' ? ' (' . (string)$product->productcode . ') ' : '') . ' = ' .
      $currencies->display_price((float)$product->priceinctax, '', (int)$product->quantity) .
      $products_ordered_attributes . "\n";
      $products_ordered_html .=
      '<tr>' . "\n" .
      '<td class="product-details" align="right" valign="top" width="30">' . $product->quantity . '&nbsp;x</td>' . "\n" .
      '<td class="product-details" valign="top">' . nl2br((string)$product->productname) . ((string)$product->productcode != '' ? ' (' . nl2br((string)$product->productcode) . ') ' : '') . "\n" .
      '<nobr>' .
      '<small><em> '. nl2br($products_ordered_attributes) .'</em></small>' .
      '</nobr>' .
      '</td>' . "\n" .
      '<td class="product-details-num" valign="top" align="right">' .
      $currencies->display_price((float)$product->priceinctax, '', (int)$product->quantity) .
      '</td></tr>' . "\n";		
	}
	else
	{
	
	
	$sql_data_array = array(
	"orders_id" => $ordercontent->orderid,
	"title" => 'Sub-Total',
	"text" => '<b>' . $currencies->format((float)$ordercontent->ordertotal - (float)$product->priceinctax, false, (string)$ordercontent->transactcurrency) . '</b>',
	"value" => (float)$ordercontent->ordertotal - (float)$product->priceinctax,
	"class" => "ot_subtotal",
	"sort_order" => 100
	);

	zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
	
	$order_totals[0]['title'] = 'Sub-Total';
	$order_totals[0]['text'] = '<b>' . $currencies->format((float)$ordercontent->ordertotal - (float)$product->priceinctax, false, (string)$ordercontent->transactcurrency) . '</b>';
	
	
	
	$sql_data_array = array(
	"orders_id" => $ordercontent->orderid,
	"title" => $product->productname,
	"text" => '<b>' . $currencies->format((float)$product->priceinctax, false, (string)$ordercontent->transactcurrency) . '</b>',
	"value" => (float)$product->priceinctax,
	"class" => "ot_shipping",
	"sort_order" => 200
	);

	zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
	
	$order_totals[1]['title'] = $product->productname;
	$order_totals[1]['text'] = '<b>' . $currencies->format((float)$product->priceinctax, false, (string)$ordercontent->transactcurrency) . '</b>';
	
	$sql_data_array = array(
	"orders_id" => $ordercontent->orderid,
	"title" => MODULE_ORDER_TOTAL_TOTAL_TITLE,
	"text" => '<b>' . $currencies->format((float)$ordercontent->ordertotal, false, (string)$ordercontent->transactcurrency) . '</b>',
	"value" => (float)$ordercontent->ordertotal,
	"class" => "ot_total",
	"sort_order" =>999	);

	zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);

	$order_totals[2]['title'] = MODULE_ORDER_TOTAL_TOTAL_TITLE;
	$order_totals[2]['text'] = '<b>' . $currencies->format((float)$ordercontent->ordertotal, false, (string)$ordercontent->transactcurrency) . '</b>';	
		
	}
	}

	$_SESSION['customer_id'] = (int)$ordercontent->customerid[0];


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
	        if ($db_query->RecordCount() == 1) {
	          $zone_id = $db_query->fields['zone_id'];
	        }		
		
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

		}
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
	

		if (!isset($_SESSION['language']))
		$_SESSION['language'] = 'english';
		if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'checkout_process.php')) {
		require(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $template_dir_select . 'checkout_process.php');
		} else {
		require(DIR_WS_LANGUAGES . $_SESSION['language'] . '/checkout_process.php');
		}
  
  
	$html_msg=array();
  

  //intro area
    $email_order = EMAIL_TEXT_HEADER . EMAIL_TEXT_FROM . STORE_NAME . "\n\n" .
    $customer->name . "\n\n" .
    EMAIL_THANKS_FOR_SHOPPING . "\n" . EMAIL_DETAILS_FOLLOW . "\n" .
    EMAIL_SEPARATOR . "\n" .
    EMAIL_TEXT_ORDER_NUMBER . ' ' . $ordercontent->orderid . "\n" .
    EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n" .
    EMAIL_TEXT_INVOICE_URL . ' ' . zen_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $ordercontent->orderid, 'SSL', false) . "\n\n";
    $html_msg['EMAIL_TEXT_HEADER']     = EMAIL_TEXT_HEADER;
    $html_msg['EMAIL_TEXT_FROM']       = EMAIL_TEXT_FROM;
    $html_msg['INTRO_STORE_NAME']      = STORE_NAME;
    $html_msg['EMAIL_THANKS_FOR_SHOPPING'] = EMAIL_THANKS_FOR_SHOPPING;
    $html_msg['EMAIL_DETAILS_FOLLOW']  = EMAIL_DETAILS_FOLLOW;
    $html_msg['INTRO_ORDER_NUM_TITLE'] = EMAIL_TEXT_ORDER_NUMBER;
    $html_msg['INTRO_ORDER_NUMBER']    = $ordercontent->orderid;
    $html_msg['INTRO_DATE_TITLE']      = EMAIL_TEXT_DATE_ORDERED;
    $html_msg['INTRO_DATE_ORDERED']    = strftime(DATE_FORMAT_LONG);
    $html_msg['INTRO_URL_TEXT']        = EMAIL_TEXT_INVOICE_URL_CLICK;
    $html_msg['INTRO_URL_VALUE']       = zen_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $ordercontent->orderid, 'SSL', false);
	
    //comments area
    if ($ordercontent->instructions) {
      $email_order .= zen_db_output($ordercontent->instructions) . "\n\n";
      $html_msg['ORDER_COMMENTS'] = nl2br(zen_db_output($ordercontent->instructions));
    } else {
      $html_msg['ORDER_COMMENTS'] = '';
    }

    //products area
    $email_order .= EMAIL_TEXT_PRODUCTS . "\n" .
    EMAIL_SEPARATOR . "\n" .
    $products_ordered .
    EMAIL_SEPARATOR . "\n";
    $html_msg['PRODUCTS_TITLE'] = EMAIL_TEXT_PRODUCTS;
    $html_msg['PRODUCTS_DETAIL']='<table class="product-details" border="0" width="100%" cellspacing="0" cellpadding="2' . $products_ordered_html . '</table>';
	//zen_mail($customer->name, $customer->email, EMAIL_TEXT_SUBJECT . EMAIL_ORDER_NUMBER_SUBJECT . $ordercontent->orderid, $email_order, STORE_NAME, EMAIL_FROM, $html_msg, 'checkout');

    //order totals area
    $html_ot .= '<td class="order-totals-text" align="right" width="100%">' . '&nbsp;' . '</td> ' . "\n" . '<td class="order-totals-num" align="right" nowrap="nowrap">' . '---------' .'</td> </tr>' . "\n" . '<tr>';
    for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
      $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
      $html_ot .= '<td class="order-totals-text" align="right" width="100%">' . $order_totals[$i]['title'] . '</td> ' . "\n" . '<td class="order-totals-num" align="right" nowrap="nowrap">' .($order_totals[$i]['text']) .'</td> </tr>' . "\n" . '<tr>';
    }
    $html_msg['ORDER_TOTALS'] = '<table border="0" width="100%" cellspacing="0" cellpadding="2"> ' . $html_ot . ' </table>';

	
	
    //addresses area: Delivery
    $html_msg['HEADING_ADDRESS_INFORMATION']= HEADING_ADDRESS_INFORMATION;
    $html_msg['ADDRESS_DELIVERY_TITLE']     = EMAIL_TEXT_DELIVERY_ADDRESS;
    $html_msg['ADDRESS_DELIVERY_DETAIL']    = zen_address_label($_SESSION['customer_id'], $_SESSION['sendto'], true, '', "<br />");
    
    
      $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
      EMAIL_SEPARATOR . "\n" .
      zen_address_label($_SESSION['customer_id'], $_SESSION['sendto'], 0, '', "\n") . "\n";
    

    //addresses area: Billing
    $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
    EMAIL_SEPARATOR . "\n" .
    zen_address_label($_SESSION['customer_id'], $_SESSION['billto'], 0, '', "\n") . "\n\n";
    $html_msg['ADDRESS_BILLING_TITLE']   = EMAIL_TEXT_BILLING_ADDRESS;
    $html_msg['ADDRESS_BILLING_DETAIL']  = zen_address_label($_SESSION['customer_id'], $_SESSION['billto'], true, '', "<br />");

	
	
	
	
	
    
      $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
      EMAIL_SEPARATOR . "\n";
      $email_order .=  (string)$ordercontent->orderpayments->orderpayment[0]->paymentmethod . "\n\n";
    
    $html_msg['PAYMENT_METHOD_TITLE']  = EMAIL_TEXT_PAYMENT_METHOD;
    $html_msg['PAYMENT_METHOD_DETAIL'] = (string)$ordercontent->orderpayments->orderpayment[0]->paymentmethod;
    

    // include disclaimer
    if (defined('EMAIL_DISCLAIMER') && EMAIL_DISCLAIMER != '') $email_order .= "\n-----\n" . sprintf(EMAIL_DISCLAIMER, STORE_OWNER_EMAIL_ADDRESS) . "\n\n";
    // include copyright
    if (defined('EMAIL_FOOTER_COPYRIGHT')) $email_order .= "\n-----\n" . EMAIL_FOOTER_COPYRIGHT . "\n\n";

    while (strstr($email_order, '&nbsp;')) $email_order = str_replace('&nbsp;', ' ', $email_order);
    $html_msg['EMAIL_FIRST_NAME'] = $firstname;
    $html_msg['EMAIL_LAST_NAME'] = $lastname;
    $html_msg['EMAIL_TEXT_HEADER'] = EMAIL_TEXT_HEADER;
    $html_msg['EXTRA_INFO'] = '';
   
    zen_mail($customer->name, $customer->email, EMAIL_TEXT_SUBJECT . EMAIL_ORDER_NUMBER_SUBJECT . $ordercontent->orderid, $email_order, STORE_NAME, EMAIL_FROM, $html_msg, 'checkout');

    // send additional emails
    if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
      $extra_info=email_collect_extra_info('','', $firstname . ' ' . $lastname, $customer->email, $customer->phone);
      $html_msg['EXTRA_INFO'] = $extra_info['HTML'];

           

      zen_mail('', SEND_EXTRA_ORDER_EMAILS_TO, SEND_EXTRA_NEW_ORDERS_EMAILS_TO_SUBJECT . ' ' . EMAIL_TEXT_SUBJECT . EMAIL_ORDER_NUMBER_SUBJECT . $ordercontent->orderid,
      $email_order . $extra_info['TEXT'], STORE_NAME, EMAIL_FROM, $html_msg, 'checkout_extra');
    }
  
  
  
 	$db->Execute("COMMIT");

	$zco_notifier->notify('NOTIFY_MODULE_END_CREATE_ACCOUNT');
}

?>
