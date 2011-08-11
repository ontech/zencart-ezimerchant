<?php

require('includes/languages/english/modules/order_total/ot_total.php');
$cart = new shoppingCart();
if(!isset($_GET["o"]))
{
	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		$xml = simplexml_load_string(file_get_contents("php://input"));

		$ordercontent = $xml->entry->content->children('http://api.ezimerchant.com/schemas/2009/');
		if($ordercontent)
		{
			$orderid = (int)$ordercontent->orderid;
			$db_query = $db->Execute("SELECT eziorder_id FROM ezi_order_mapping WHERE eziorder_id = '".$orderid."'");
			if($db_query->RecordCount() == 0)
			ezi_process_xml($orderid, $xml);
			else
			{
			
				// look up history on this order from ezi table
					$sql = "select * from ezi_order_mapping where eziorder_id = :orderID";
					$sql = $db->bindVars($sql, ':orderID', $orderid, 'integer');
					$zc_ppHist = $db->Execute($sql);
					if ($zc_ppHist->RecordCount() == 0) exit;
					$ezi_orderid = (int)$zc_ppHist->fields['eziorder_id'];
					$zen_orderid = (int)$zc_ppHist->fields['order_id'];
				
				$orderstate_fromezi 	= sprintf("%s",$ordercontent->orderstate);
				$paymentstatus_fromezi  = sprintf("%s",$ordercontent->paymentstatus);
				$shipmentstatus_fromezi = sprintf("%s",$ordercontent->shipmentstatus);
					
					if($orderstate_fromezi == "captured" && $paymentstatus_fromezi = "captured" && $shipmentstatus_fromezi = "unshipped")
					{
						$new_order_status = 1;
								// Success, so save the results
								$sql_data_array = array('orders_id' => (int)$zen_orderid,
														'orders_status_id' => (int)$new_order_status,
														'date_added' => 'now()',
														'comments' => 'FUND CAPTURED.',
														'customer_notified' => 0
													 );
								zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
								$db->Execute("update " . TABLE_ORDERS  . "
											  set orders_status = '" . (int)$new_order_status . "'
											  where orders_id = '" . (int)$zen_orderid . "'");						
					}
					if($orderstate_fromezi == "captured" && $paymentstatus_fromezi = "complete" && $shipmentstatus_fromezi = "unshipped")
					{
						$new_order_status = 1;
								// Success, so save the results
								$sql_data_array = array('orders_id' => (int)$zen_orderid,
														'orders_status_id' => (int)$new_order_status,
														'date_added' => 'now()',
														'comments' => 'PAYMENT COMPLETED.',
														'customer_notified' => 0
													 );
								zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
								$db->Execute("update " . TABLE_ORDERS  . "
											  set orders_status = '" . (int)$new_order_status . "'
											  where orders_id = '" . (int)$zen_orderid . "'");
								$db->Execute("update ezi_order_mapping
										  set paymentcompleted = 'true', captureamount = amount
										  where order_id = '" . (int)$zen_orderid . "'");
					}
					if($orderstate_fromezi == "cancelled" && $paymentstatus_fromezi = "unpaid" && $shipmentstatus_fromezi = "unshipped")
					{
						$new_order_status = 1;
								// Success, so save the results
								$sql_data_array = array('orders_id' => (int)$zen_orderid,
														'orders_status_id' => (int)$new_order_status,
														'date_added' => 'now()',
														'comments' => 'REFUND DONE SUCCESSFULLY.',
														'customer_notified' => 0
													 );
								zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
								$db->Execute("update " . TABLE_ORDERS  . "
											  set orders_status = '" . (int)$new_order_status . "'
											  where orders_id = '" . (int)$zen_orderid . "'");
								$db->Execute("update ezi_order_mapping
										  set paymentcompleted = 'false', refundamount = captureamount
										  where order_id = '" . (int)$zen_orderid . "'");
					}
					if($orderstate_fromezi == "complete" && $paymentstatus_fromezi = "complete" && $shipmentstatus_fromezi = "complete")
					{
						$new_order_status = 3;
								// Success, so save the results
								$sql_data_array = array('orders_id' => (int)$zen_orderid,
														'orders_status_id' => (int)$new_order_status,
														'date_added' => 'now()',
														'comments' => 'ORDER DELIVERED.',
														'customer_notified' => 0
													 );
								zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
								$db->Execute("update " . TABLE_ORDERS  . "
											  set orders_status = '" . (int)$new_order_status . "'
											  where orders_id = '" . (int)$zen_orderid . "'");
								$db->Execute("update ezi_order_mapping
										  set paymentcompleted = 'true', captureamount = amount
										  where order_id = '" . (int)$zen_orderid . "'");
					}
					
					
					
					
					
				
			}

			echo "OK";
			die();
		}
	}

	Header("Status: 404 Page Not Found");
	die();
}

$orderid = (int)$_GET["o"];

	/*CONFIGURATION ELEMENTS NEED TO CALL EZI SITE*/
	$ezi_merchantid = MODULE_PAYMENT_EZIMERCHANT_MERCHANT;
	$ezi_apikey = MODULE_PAYMENT_EZIMERCHANT_APIKEY;
	/*PASSING VALUES TO AN ARRAY*/


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$ezi_merchantid."/orders/".$orderid."/");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded", "X-APIKEY: ".$ezi_apikey ));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

$apiresponse = curl_exec($ch);
curl_close($ch);                                                          

$xml = simplexml_load_string($apiresponse);
$db_query = $db->Execute("SELECT eziorder_id FROM ezi_order_mapping WHERE eziorder_id = '".$orderid."'");
if($db_query->RecordCount() == 0)
ezi_process_xml($orderid, $xml);

$_SESSION['cart']->reset(true);

zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));


function ezi_process_xml($orderid, $xml)
{

	global $db;
	global $currencies;
	global $zco_notifier;
	global $order_totals;
	global $order;
	global $order_total_modules;
	global $cart;

	$ordercontent = $xml->entry->content->children('http://api.ezimerchant.com/schemas/2009/');
	
	$customer = $ordercontent->orderaddresses->orderaddress[0];
	$billing = $customer;
	$delivery = $ordercontent->orderaddresses->orderaddress[1];
	
	
	$name = explode(' ', sprintf("%s",$customer->name));

			$firstname = $name[0];
			$lastname = $name[1];
			
			$deliveryname = explode(' ', sprintf("%s",$delivery->name));

			$delivery_firstname = $deliveryname[0];
			$delivery_lastname = $deliveryname[1];
	
	
	
	$sql = "SELECT countries_id, countries_name, address_format_id, countries_iso_code_2, countries_iso_code_3
                FROM " . TABLE_COUNTRIES . "
                WHERE countries_iso_code_2 = :countryId
                   OR countries_name = :countryId
                LIMIT 1";
    $sql1 = $db->bindVars($sql, ':countryId', $customer->countrycode, 'string');
    $country1 = $db->Execute($sql1);
    if ($country1->RecordCount() > 0) {
      $country_id = $country1->fields['countries_id'];
	  $country_name = $country1->fields['countries_name'];
      $country_code2 = $country1->fields['countries_iso_code_2'];
      $country_code3 = $country1->fields['countries_iso_code_3'];
      $address_format_id = (int)$country1->fields['address_format_id'];
    }
	
	
	$sql = "SELECT zone_id, zone_name
                  FROM " . TABLE_ZONES . "
                  WHERE zone_country_id = :zCountry
                  AND (zone_code = :zoneCode
                   OR zone_name = :zoneCode)
                  LIMIT 1";
    $sql = $db->bindVars($sql, ':zCountry', $country_id, 'integer');
    $sql = $db->bindVars($sql, ':zoneCode', $customer->division, 'string');
    $states = $db->Execute($sql);
    if ($states->RecordCount() > 0) {
      $state_id = $states->fields['zone_id'];
	  $state_name = $states->fields['zone_name'];
    } else
	{
      $state_id = '0';
	}
	
	
	
	$sql = "SELECT countries_id, countries_name, address_format_id, countries_iso_code_2, countries_iso_code_3
                FROM " . TABLE_COUNTRIES . "
                WHERE countries_iso_code_2 = :countryId
                   OR countries_name = :countryId
                LIMIT 1";
    $sql1 = $db->bindVars($sql, ':countryId', $delivery->countrycode, 'string');
    $country1 = $db->Execute($sql1);
    if ($country1->RecordCount() > 0) {
      $delivery_country_id = $country1->fields['countries_id'];
	  $delivery_country_name = $country1->fields['countries_name'];
      $delivery_country_code2 = $country1->fields['countries_iso_code_2'];
      $delivery_country_code3 = $country1->fields['countries_iso_code_3'];
      $delivery_address_format_id = (int)$country1->fields['address_format_id'];
    }
	
	
	$sql = "SELECT zone_id, zone_name
                  FROM " . TABLE_ZONES . "
                  WHERE zone_country_id = :zCountry
                  AND (zone_code = :zoneCode
                   OR zone_name = :zoneCode)
                  LIMIT 1";
    $sql = $db->bindVars($sql, ':zCountry', $delivery_country_id, 'integer');
    $sql = $db->bindVars($sql, ':zoneCode', $delivery->division, 'string');
    $states = $db->Execute($sql);
    if ($states->RecordCount() > 0) {
      $delivery_state_id = $states->fields['zone_id'];
	  $delivery_state_name = $states->fields['zone_name'];
    } else
	{
      $state_id = '0';
	}
	
	
	
	$orderstatus = DEFAULT_ORDERS_STATUS_ID;
	
	if($ordercontent->paymentstatus == "complete")
         $orderstatus = 2;
     if($ordercontent->orderstate == "complete")
         $orderstatus = 3;
	
	
	
	// customer
    $order->customer['firstname']       = $firstname;
	$order->customer['lastname']        = $lastname;
    $order->customer['company']         = sprintf("%s",$customer->companyname);
    $order->customer['street_address']  = sprintf("%s",$customer->address1);
    $order->customer['suburb']          = sprintf("%s",$customer->address2);
    $order->customer['city']            = sprintf("%s",$customer->place);
    $order->customer['postcode']        = sprintf("%s",$customer->postalcode);
    $order->customer['state']           = $state_name;
    $order->customer['country']         = array('id' => $country_id, 'title' => $country_name, 'iso_code_2' => $country_code2, 'iso_code_3' => $country_code3);
    $order->customer['country']['id']   = $country_id;
    $order->customer['country']['iso_code_2'] = $country_code2;
    $order->customer['format_id']       = $address_format_id;
    $order->customer['email_address']   = sprintf("%s",$customer->email);
    $order->customer['telephone']       = sprintf("%s",$customer->phone);
    $order->customer['zone_id']         = $state_id;

    // billing
    $order->billing['firstname']       = $firstname;
	$order->billing['lastname']        = $lastname;
    $order->billing['company']         = sprintf("%s",$customer->companyname);
    $order->billing['street_address']  = sprintf("%s",$customer->address1);
    $order->billing['suburb']          = sprintf("%s",$customer->address2);
    $order->billing['city']            = sprintf("%s",$customer->place);
    $order->billing['postcode']        = sprintf("%s",$customer->postalcode);
    $order->billing['state']           = $state_name;
    $order->billing['country']         = array('id' => $country_id, 'title' => $country_name, 'iso_code_2' => $country_code2, 'iso_code_3' => $country_code3);
    $order->billing['country']['id']   = $country_id;
    $order->billing['country']['iso_code_2'] = $country_code2;
    $order->billing['format_id']       = $address_format_id;
    $order->billing['zone_id']         = $state_id;

    // delivery
    
      $order->delivery['firstname']     = $delivery_firstname;
	  $order->delivery['lastname']      = $delivery_lastname;
      $order->delivery['company']       = sprintf("%s",$delivery->companyname);
      $order->delivery['street_address']= sprintf("%s",$delivery->address1);
      $order->delivery['suburb']        = sprintf("%s",$delivery->address2);
      $order->delivery['city']          = sprintf("%s",$delivery->place);
      $order->delivery['postcode']      = sprintf("%s",$delivery->postalcode);
      $order->delivery['state']         = $delivery_state_name;
      $order->delivery['country']       = array('id' => $country_id, 'title' => $delivery_country_name, 'iso_code_2' => $country_code2, 'iso_code_3' => $country_code3);
      $order->delivery['country_id']    = $country_id;
      $order->delivery['format_id']     = $address_format_id;
      $order->delivery['zone_id']       = $delivery_state_id;
    

    // process submitted customer notes
   
      $order->info['comments'] = $ordercontent->instructions;
	
	
	
	
	// see if the user is logged in
    if (!empty($_SESSION['customer_first_name']) && !empty($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0) {
      // They're logged in, so forward them straight to checkout stages, depending on address needs etc
		$order->customer['id'] = $_SESSION['customer_id'];

        // This is the address matching section
        // try to match it first
        // note: this is by no means 100%
        $address_book_id = findMatchingAddressBookEntry($_SESSION['customer_id'], (isset($order->delivery) ? $order->delivery : $order->billing));

        // no match, so add the record
        if (!$address_book_id) {
          $address_book_id = addAddressBookEntry($_SESSION['customer_id'], (isset($order->delivery) ? $order->delivery : $order->billing), false);
        }

        // set the address for use
        $_SESSION['sendto'] = $address_book_id;
      
	  $address_book_billid = findMatchingAddressBookEntry($_SESSION['customer_id'], $order->billing);
	  
	  if (!$address_book_billid) {
          $address_book_billid = addAddressBookEntry($_SESSION['customer_id'], $order->billing, true);
        }
      // set the users billto information (default address)
      if (!isset($_SESSION['billto'])) {
        $_SESSION['billto'] = $address_book_billid;
      }
	}else{
	
	      // They're not logged in.  Create an account if necessary, and then log them in.
			// First, see if they're an existing customer, and log them in automatically
		$acct_exists = false;
		$sql = "SELECT customers_id, customers_firstname, customers_lastname
              FROM " . TABLE_CUSTOMERS . "
              WHERE customers_email_address = :emailAddress ";
      $sql = $db->bindVars($sql, ':emailAddress', $customer->email, 'string');
      $check_customer = $db->Execute($sql);
	  
	  if (!$check_customer->EOF) {
        $acct_exists = true;
		}
	// Create an account, if the account does not exist
      if (!$acct_exists) {

        

        // Generate a random 8-char password
        $password = zen_create_random_value(8);

        $sql_data_array = array();

        // set the customer information in the array for the table insertion
        $sql_data_array = array(
            'customers_firstname'           => $firstname,
            'customers_lastname'            => $lastname,
            'customers_email_address'       => sprintf("%s",$customer->email),
            'customers_email_format'        => (ACCOUNT_EMAIL_PREFERENCE == '1' ? 'HTML' : 'TEXT'),
            'customers_telephone'           => sprintf("%s",$customer->phone),
            'customers_fax'                 => sprintf("%s",$customer->fax),
            'customers_gender'              => '',
            'customers_newsletter'          => '0',
            'customers_password'            => zen_encrypt_password($password));

        // insert the data
        $result = zen_db_perform(TABLE_CUSTOMERS, $sql_data_array);

        // grab the customer_id (last insert id)
        $customer_id = $db->Insert_ID();

        // set the customer ID 
        $_SESSION['customer_id'] = $customer_id;

        // set the customer address information in the array for the table insertion
        $sql_data_array = array(
            'customers_id'              => $customer_id,
            'entry_gender'              => '',
            'entry_firstname'           => $firstname,
            'entry_lastname'            => $lastname,
            'entry_street_address'      => sprintf("%s",$customer->address1),
            'entry_suburb'              => sprintf("%s",$customer->address2),
            'entry_city'                => sprintf("%s",$customer->place),
            'entry_zone_id'             => $state_id,
            'entry_postcode'            => sprintf("%s",$customer->postalcode),
            'entry_country_id'          => $country_id);
        
          $sql_data_array['entry_company'] = sprintf("%s",$customer->companyname);
        
        if ($state_id > 0) {
          $sql_data_array['entry_zone_id'] = $state_id;
          $sql_data_array['entry_state'] = '';
        } else {
          $sql_data_array['entry_zone_id'] = 0;
          $sql_data_array['entry_state'] = sprintf("%s",$customer->division);
        }

        // insert the data
        zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

        // grab the address_id (last insert id)
        $address_id = $db->Insert_ID();

        // set the address id lookup for the customer
        $sql = "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_default_address_id = :addrID
                WHERE customers_id = :custID";
        $sql = $db->bindVars($sql, ':addrID', $address_id, 'integer');
        $sql = $db->bindVars($sql, ':custID', $customer_id, 'integer');
        $db->Execute($sql);

        // insert the new customer_id into the customers info table for consistency
        $sql = "INSERT INTO " . TABLE_CUSTOMERS_INFO . "
                       (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created, customers_info_date_of_last_logon)
                VALUES (:custID, 1, now(), now())";
        $sql = $db->bindVars($sql, ':custID', $customer_id, 'integer');
        $db->Execute($sql);

        // send Welcome Email if appropriate
        
          // require the language file
          global $language_page_directory, $template_dir;
          if (!isset($language_page_directory)) $language_page_directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/';
          if (file_exists($language_page_directory . $template_dir . '/create_account.php')) {
            $template_dir_select = $template_dir . '/';
          } else {
            $template_dir_select = '';
          }
          require($language_page_directory . $template_dir_select . '/create_account.php');

          // set the mail text
          $email_text = sprintf(EMAIL_GREET_NONE, $customer->name) . EMAIL_WELCOME . "\n\n" . EMAIL_TEXT;
          $email_text .= "\n\n" . EMAIL_EC_ACCOUNT_INFORMATION . "\nUsername: " . $customer->email . "\nPassword: " . $password . "\n\n";
          $email_text .= EMAIL_CONTACT;
          // include create-account-specific disclaimer
          $email_text .= "\n\n" . sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, STORE_OWNER_EMAIL_ADDRESS). "\n\n";
          $email_html = array();
          $email_html['EMAIL_GREETING']      = sprintf(EMAIL_GREET_NONE, $customer->name) ;
          $email_html['EMAIL_WELCOME']       = EMAIL_WELCOME;
          $email_html['EMAIL_MESSAGE_HTML']  = nl2br(EMAIL_TEXT . "\n\n" . EMAIL_EC_ACCOUNT_INFORMATION . "\nUsername: " . $customer->email . "\nPassword: " . $password . "\n\n");
          $email_html['EMAIL_CONTACT_OWNER'] = EMAIL_CONTACT;
          $email_html['EMAIL_CLOSURE']       = nl2br(EMAIL_GV_CLOSURE);
          $email_html['EMAIL_DISCLAIMER']    = sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, '<a href="mailto:' . STORE_OWNER_EMAIL_ADDRESS . '">'. STORE_OWNER_EMAIL_ADDRESS .' </a>');

          // send the mail
          zen_mail($customer->name, $customer->email, EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $email_html, 'welcome');

          

      }

      // log the user in with the email sent back from ezimerchant response
      user_login(sprintf("%s",$customer->email), false);


      // This is the address matching section
      // try to match it first
      // note: this is by no means 100%
      $address_book_id = findMatchingAddressBookEntry($_SESSION['customer_id'], (isset($order->delivery) ? $order->delivery : $order->billing));
      // no match add the record
      if (!$address_book_id) {
        $address_book_id = addAddressBookEntry($_SESSION['customer_id'], (isset($order->delivery) ? $order->delivery : $order->billing), false);
        if (!$address_book_id) {
          $address_book_id = $_SESSION['customer_default_address_id'];
        }
      }
      
      $_SESSION['sendto'] = $address_book_id;
	  
	  
      // set billto in the session
	  $address_book_billid = findMatchingAddressBookEntry($_SESSION['customer_id'], $order->billing);
	  
	  if (!$address_book_billid) {
          $address_book_billid = addAddressBookEntry($_SESSION['customer_id'], $order->billing, true);
        }
      // set the users billto information (default address)
      if (!isset($_SESSION['billto'])) {
        $_SESSION['billto'] = $address_book_billid;
      }
      //$_SESSION['billto'] = $_SESSION['customer_default_address_id'];

	}
	
	
	
	
	$products = $ordercontent->orderlines->orderline;
/*$i = 0;
	foreach($products as $product)
	{
	if('FREIGHT' != $product->productcode)
	{
		$db_query = $db->Execute("SELECT products_id FROM ".TABLE_PRODUCTS." WHERE products_model = '".zen_db_input($product->productcode)."'");
		$productid = $db_query->fields['products_id'];
		$_SESSION['cart']->contents[$productid]['qty'] = $product->quantity;
		// push product data back in
		$order->products[$i]['id'] = $productid;
		$order->products[$i]['model'] = (string)$product->productcode;
		$order->products[$i]['name'] = stripslashes((string)$product->productname);
		$order->products[$i]['price'] = (string)$product->price;
		$order->products[$i]['final_price'] = (string)$product->priceinctax;
		$order->products[$i]['tax'] = round((((float)$product->defaultcurrencylinetotalinctax / (float)$product->defaultcurrencylinetotal) - 1.0) * 100.0);
		$order->products[$i]['qty'] = (string)$product->quantity;
		$order->products[$i]['products_prid'] = $productid;
		

		    if(isset($product->attributes->attribute))
		    { $k=0;
				foreach($product->attributes->attribute as $attribute)
				{
					$attributes = $attribute->attributes();
					$db_query = $db->Execute("SELECT products_options_id FROM ".TABLE_PRODUCTS_OPTIONS." WHERE products_options_name = '".(string)$attributes['name']."' AND language_id = '".$_SESSION['languages_id']."'");
					$product_optionid = $db_query->fields['products_options_id'];
					$db_query = $db->Execute("SELECT products_options_values_id FROM ".TABLE_PRODUCTS_OPTIONS_VALUES." WHERE products_options_values_name = '".(string)$attributes['value']."' AND language_id = '".$_SESSION['languages_id']."'");
					$product_values_id = $db_query->fields['products_options_values_id'];
					$order->products[$i]['attributes'][$k]['option_id'] = $product_optionid;
					$order->products[$i]['attributes'][$k]['value_id'] = $product_values_id;
					/*$order->products[$i]['attributes'][$k]['products_options_values'] = (string)$attributes['value'];
					/*$order->products[$i]['attributes'][$k]['products_options'] = (string)$attributes['name'];
					$order->products[$i]['attributes'][$k]['products_options'] = (string)$attributes['name'];*/
					/*$k++;
				}	
			}	
			$i++;
		}
	else
	{
	$j = 0;
	$ot_modules = array();
	$ot_modules[$j]['title'] = $product->productname;
	$ot_modules[$j]['text'] = '<b>' . $currencies->format((float)$product->priceinctax, false, (string)$ordercontent->transactcurrency) . '</b>';
	$ot_modules[$j]['value'] =  (float)$product->priceinctax;
	$ot_modules[$j]['code'] = "ot_shipping";
	$ot_modules[$j]['sort_order'] = 200;
		
	$shipping_price = (float)$product->priceinctax;
	$shipping_name = $product->productname;
	$j++;
	}
	
	$ot_modules[$j]['title'] = 'Sub-Total';
	$ot_modules[$j]['text'] = '<b>' . $currencies->format((float)$ordercontent->ordertotal - (float)$shipping_price, false, (string)$ordercontent->transactcurrency) . '</b>';
	$ot_modules[$j]['value'] =  (float)$ordercontent->ordertotal - (float)$shipping_price;
	$ot_modules[$j]['code'] = "ot_subtotal";
	$ot_modules[$j]['sort_order'] = 100;
	
	
	$j++;
	
	$ot_modules[$j]['title'] = MODULE_ORDER_TOTAL_TOTAL_TITLE;
	$ot_modules[$j]['text'] = '<b>' . $currencies->format((float)$ordercontent->ordertotal, false, (string)$ordercontent->transactcurrency) . '</b>';
	$ot_modules[$j]['value'] =  (float)$ordercontent->ordertotal;
	$ot_modules[$j]['code'] = "ot_total";
	$ot_modules[$j]['sort_order'] = 999;	
		
	}*/
	//$order_totals = $ot_modules;
	
	
	
	foreach($products as $product)
	{
	$freight_selected = sprintf("%s",$product->productcode);
	if('FREIGHT' != $freight_selected)
	{
		$db_query = $db->Execute("SELECT products_id FROM ".TABLE_PRODUCTS." WHERE products_id = '".zen_db_input($product->productid)."'");
		//$productid = $db_query->fields['products_id'];
		
		
		if ($db_query->RecordCount() == 1) 
		{
			$product_id = $db_query->fields['products_id'];
		}
		else
		{
			$product_id = 0;
		}
        
		$attributes_addval = array();
		
		if(isset($product->attributes->attribute))
		    { 
				$aray_val = array();
				foreach($product->attributes->attribute as $attribute)
				{
					$attributes_val = $attribute->attributes();
					$product_option = $db->Execute("SELECT products_options_id, products_options_type FROM " . TABLE_PRODUCTS_OPTIONS . " AS o WHERE EXISTS(SELECT 1 FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id = ".$product_id." AND options_id = o.products_options_id) AND products_options_name = '".(string)$attributes_val['name']."' AND language_id = 1 LIMIT 0,1");
					if($product_option->RecordCount() == 1)
					{
						
						if($product_option->fields['products_options_type'] == 3)
						{
							$val_in_attr = explode(',',stripslashes((string)$attributes_val['value']));
							
							for($kk=0;$kk<count($val_in_attr);$kk++)
							{
							$product_optionvalue = $db->Execute("SELECT products_options_values_id FROM " . TABLE_PRODUCTS_OPTIONS_VALUES . " AS v WHERE EXISTS(SELECT 1 FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id = ".$product_id." AND options_id = ".$product_option->fields['products_options_id']." AND products_options_values_id = options_values_id) AND products_options_values_name = '".mysql_real_escape_string((string)$val_in_attr[$kk])."' AND language_id = 1 LIMIT 0,1");		
							if($product_optionvalue->RecordCount() == 1)
							{	
								
								$aray_val[$product_optionvalue->fields['products_options_values_id']] = $product_optionvalue->fields['products_options_values_id'];
																
							}
							$attributes_addval[$product_option->fields['products_options_id']] = $aray_val;
							}
							
						}
						else
						{
						$product_optionvalue = $db->Execute("SELECT products_options_values_id FROM " . TABLE_PRODUCTS_OPTIONS_VALUES . " AS v WHERE EXISTS(SELECT 1 FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id = ".$product_id." AND options_id = ".$product_option->fields['products_options_id']." AND products_options_values_id = options_values_id) AND products_options_values_name = '".(string)$attributes_val['value']."' AND language_id = 1 LIMIT 0,1");		
						if($product_optionvalue->RecordCount() == 1)
						{
							$attributes_addval[$product_option->fields['products_options_id']] = $product_optionvalue->fields['products_options_values_id'];
						}
						else
						{
							$attributes_addval[TEXT_PREFIX . $product_option->fields['products_options_id']] = (string)$attributes_val['value'];
						}
						}
					}
				}	
			}
		if($product_id)	
		$cart->add_cart($product_id, $product->quantity, $attributes_addval, false);		
	}
	else
	{
	$j = 0;		
	$shipping_price = (float)$product->priceinctax;
	$shipping_name = sprintf("%s",$product->productname);
	$j++;
	}
	}
	
	
	$info = array();
	$payment_details = $ordercontent->orderpayments->orderpayment;
	$info['ezi_paymentmethod'] 		= sprintf("%s",$payment_details->paymentmethod);
	$info['ezi_paymentdate'] 		= sprintf("%s",$payment_details->paymentdate);
	$info['ezi_amount'] 			= sprintf("%s",$payment_details->amount);
	$info['ezi_paymentcompleted'] 	= sprintf("%s",$payment_details->paymentcompleted);
	$info['ezi_transactionid'] 		= sprintf("%s",$payment_details->transactionid);
	$info['ezi_paymenttype'] 		= sprintf("%s",$payment_details->paymenttype);
	$info['ezi_pendingreason'] 		= sprintf("%s",$payment_details->pendingreason);
	$info['ezi_settleamount'] 		= sprintf("%s",$payment_details->settleamount);
	$info['ezi_paymentvoided'] 		= sprintf("%s",$payment_details->paymentvoided);
	/*if($payment_details->paymentmethod == "creditcard")
	{*/
		$info['cc_type'] 	= sprintf("%s",$payment_details->cardtype);
		$info['cc_owner'] 	= sprintf("%s",$payment_details->cardname);
		$info['cc_number'] 	= sprintf("%s",$payment_details->cardnumber);
		$info['cc_expires']	= sprintf("%s",$payment_details->expiryyear);
	//}
	$_SESSION['payment_eziinfo'] = $info;
	
	
	/*$order->info['shipping_method']  = $shipping_name;
	$order->info['orders_status']    = $orderstatus;
	$order->info['payment_method']   = (string)$ordercontent->orderpayments->orderpayment[0]->paymentmethod;
	$order->info['payment_module_code']   = 'ezimerchant';
	$order->info['currency']         = (string)$ordercontent->transactcurrency;
	$order->info['currency_value']   = (float)$ordercontent->ordertotal / (float)$ordercontent->defaultcurrencytotal;
	$order->info['ip_address']       = (string)$ordercontent->ipaddress;
	$order->info['tax']              = ((float)$ordercontent->ordertotal - (float)$shipping_price)*100/110;
	$order->info['total']            = (float)$ordercontent->ordertotal;
	$order->info['subtotal']         = (float)$ordercontent->ordertotal - (float)$shipping_price;
	$order->info['shipping_cost']    = (float)$shipping_price;*/
$_SESSION['cart']->reset(true);
$_SESSION['cart'] = $cart;
$_SESSION['payment'] = 'ezimerchant';
	
	
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping;
  $quotes = $shipping_modules->quote();

  // get coupon code
if ($ordercontent->coupons->couponcode) {
  $discount_coupon_query = "SELECT coupon_id
                            FROM " . TABLE_COUPONS . "
                            WHERE coupon_code = :couponID";

  $discount_coupon_query = $db->bindVars($discount_coupon_query, ':couponID', $ordercontent->coupons->couponcode, 'string');
  $discount_coupon = $db->Execute($discount_coupon_query);
  if($discount_coupon->RecordCount() > 0)
  $_SESSION['cc_id'] = $discount_coupon->fields['coupon_id'];
}
  

 for ($i=0, $n=sizeof($quotes); $i<$n; $i++) {
		$taxrate  =  $quotes[$i]['tax'];
		for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
		$cur_shipping = strip_tags($quotes[$i]['module']. " ( ". $quotes[$i]['methods'][$j]['title']. " )");
		if($shipping_name == $cur_shipping)
		{ 
			$order->info['shipping_module_code']  = $quotes[$i]['id'];
			$_SESSION['shipping']['id'] = $quotes[$i]['id'].'_'.$quotes[$i]['id'];
			$_SESSION['shipping']['title'] = $cur_shipping;
			$_SESSION['shipping']['cost'] = $shipping_price;
			break;
		}
		}
		}
	$_SESSION['eziorder_id'] = $orderid;
	//$db->Execute("INSERT INTO ezi_order_mapping (eziorder_id, order_id) VALUES ('".$orderid."', (SELECT MAX( orders_id ) +1 FROM `orders` ))" );

	zen_redirect(zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'));
	
	
	
	
}











  function findMatchingAddressBookEntry($customer_id, $address_question_arr) {
    global $db;

    // if address is blank, don't do any matching
    if ($address_question_arr['street_address'] == '') return false;

    // default
    $country_id = '223';
    $address_format_id = 2; //2 is the American format

    // first get the zone id's from the 2 digit iso codes
    // country first
    $sql = "SELECT countries_id, address_format_id
            FROM " . TABLE_COUNTRIES . "
            WHERE countries_iso_code_2 = :countryId
               OR countries_name = :countryId
            LIMIT 1";
    $sql = $db->bindVars($sql, ':countryId', $address_question_arr['country']['iso_code_2'], 'string');
    $country = $db->Execute($sql);

    // see if we found a record, if not default to American format
    if (!$country->EOF) {
      $country_id = $country->fields['countries_id'];
      $address_format_id = $country->fields['address_format_id'];
    }

    // see if the country code has a state
    $sql = "SELECT zone_id
            FROM " . TABLE_ZONES . "
            WHERE zone_country_id = :zoneId
            LIMIT 1";
    $sql = $db->bindVars($sql, ':zoneId', $country_id, 'integer');
    $country_zone_check = $db->Execute($sql);
    $check_zone = $country_zone_check->RecordCount();

    $zone_id = 0;
    

    // now try and find the zone_id (state/province code)
    // use the country id above
    if ($check_zone) {
      $sql = "SELECT zone_id
              FROM " . TABLE_ZONES . "
              WHERE zone_country_id = :zoneId
                AND (zone_code = :zoneCode
                 OR zone_name = :zoneCode )
              LIMIT 1";
      $sql = $db->bindVars($sql, ':zoneId', $country_id, 'integer');
      $sql = $db->bindVars($sql, ':zoneCode', $address_question_arr['state'], 'string');
      $zone = $db->Execute($sql);
      if (!$zone->EOF) {
        // grab the id
        $zone_id = $zone->fields['zone_id'];
        $logMsg = $zone->fields;
      } else {
        $check_zone = false;
      }
    }
    

    // do a match on address, street, street2, city
    $sql = "SELECT address_book_id, entry_street_address, entry_suburb, entry_city, entry_company, entry_firstname, entry_lastname
                FROM " . TABLE_ADDRESS_BOOK . "
                WHERE customers_id = :customerId
                AND entry_country_id = :countryId";
    if ($check_zone) {
      $sql .= "  AND entry_zone_id = :zoneId";
    }
    $sql = $db->bindVars($sql, ':zoneId', $zone_id, 'integer');
    $sql = $db->bindVars($sql, ':countryId', $country_id, 'integer');
    $sql = $db->bindVars($sql, ':customerId', $customer_id, 'integer');
    $answers_arr = $db->Execute($sql);
    // debug
    

    if (!$answers_arr->EOF) {
      // build a base string to compare street+suburb+city content
      //$matchQuestion = str_replace("\n", '', $address_question_arr['company']);
      //$matchQuestion = str_replace("\n", '', $address_question_arr['name']);
      $matchQuestion = str_replace("\n", '', $address_question_arr['street_address']);
      $matchQuestion = trim($matchQuestion);
      $matchQuestion = $matchQuestion . str_replace("\n", '', $address_question_arr['suburb']);
      $matchQuestion = $matchQuestion . str_replace("\n", '', $address_question_arr['city']);
      $matchQuestion = str_replace("\t", '', $matchQuestion);
      $matchQuestion = trim($matchQuestion);
      $matchQuestion = strtolower($matchQuestion);
      $matchQuestion = str_replace(' ', '', $matchQuestion);

      // go through the data
      while (!$answers_arr->EOF) {
        // now the matching logic

        // first from the db
        $fromDb = '';
//        $fromDb = str_replace("\n", '', $answers_arr->fields['entry_company']);
///        $fromDb = str_replace("\n", '', $answers_arr->fields['entry_firstname'].$answers_arr->fields['entry_lastname']);
        $fromDb = str_replace("\n", '', $answers_arr->fields['entry_street_address']);
        $fromDb = trim($fromDb);
        $fromDb = $fromDb . str_replace("\n", '', $answers_arr->fields['entry_suburb']);
        $fromDb = $fromDb . str_replace("\n", '', $answers_arr->fields['entry_city']);
        $fromDb = str_replace("\t", '', $fromDb);
        $fromDb = trim($fromDb);
        $fromDb = strtolower($fromDb);
        $fromDb = str_replace(' ', '', $fromDb);

        

        // check the strings
        if (strlen($fromDb) == strlen($matchQuestion)) {
          if ($fromDb == $matchQuestion) {
            // exact match return the id
            return $answers_arr->fields['address_book_id'];
          }
        } elseif (strlen($fromDb) > strlen($matchQuestion)) {
          if (substr($fromDb, 0, strlen($matchQuestion)) == $matchQuestion) {
            // we have a match return it
            return $answers_arr->fields['address_book_id'];
          }
        } else {
          if ($fromDb == substr($matchQuestion, 0, strlen($fromDb))) {
            // we have a match return it (DB)
            return $answers_arr->fields['address_book_id'];
          }
        }

        $answers_arr->MoveNext();
      }
    }

    // no matches found
    return false;
  }




  function addAddressBookEntry($customer_id, $address_question_arr, $make_default = false) {
    global $db;

    // if address is blank, don't do any matching
    if ($address_question_arr['street_address'] == '') return false;

    // set some defaults
    $country_id = '223';
    $address_format_id = 2; //2 is the American format

    // first get the zone id's from the 2 digit iso codes
    // country first
    $sql = "SELECT countries_id, address_format_id
                FROM " . TABLE_COUNTRIES . "
                WHERE countries_iso_code_2 = :countryId
                OR countries_name = :countryId
                LIMIT 1";
    $sql = $db->bindVars($sql, ':countryId', $address_question_arr['country']['title'], 'string');
    $country = $db->Execute($sql);

    // see if we found a record, if not default to American format
    if (!$country->EOF) {
      $country_id = $country->fields['countries_id'];
      $address_format_id = (int)$country->fields['address_format_id'];
    } else {
      // if defaulting to US, make sure US is valid
      $sql = "SELECT countries_id FROM " . TABLE_COUNTRIES . " WHERE countries_id = :countryId LIMIT 1";
      $sql = $db->bindVars($sql, ':countryId', $country_id, 'integer');
      $result = $db->Execute($sql);
      if ($result->EOF) {
        return FALSE;
      }
    }

    // see if the country code has a state
    $sql = "SELECT zone_id
                FROM " . TABLE_ZONES . "
                WHERE zone_country_id = :zoneId
                LIMIT 1";
    $sql = $db->bindVars($sql, ':zoneId', $country_id, 'integer');
    $country_zone_check = $db->Execute($sql);
    $check_zone = $country_zone_check->RecordCount();

    // now try and find the zone_id (state/province code)
    // use the country id above
    if ($check_zone) {
      $sql = "SELECT zone_id
                    FROM " . TABLE_ZONES . "
                    WHERE zone_country_id = :zoneId
                    AND (zone_code = :zoneCode
                    OR zone_name = :zoneCode )
                    LIMIT 1";
      $sql = $db->bindVars($sql, ':zoneId', $country_id, 'integer');
      $sql = $db->bindVars($sql, ':zoneCode', $address_question_arr['state'], 'string');
      $zone = $db->Execute($sql);
      if (!$zone->EOF) {
        // grab the id
        $zone_id = $zone->fields['zone_id'];
      } else {
        $zone_id = 0;
      }
    }

    // now run the insert
	
    // this isn't the best way to get fname/lname but it will get the majority of cases
    //list($fname, $lname) = explode(' ', $address_question_arr['name']);

    $sql_data_array= array(array('fieldName'=>'entry_firstname', 'value'=>$address_question_arr['firstname'], 'type'=>'string'),
                           array('fieldName'=>'entry_lastname', 'value'=>$address_question_arr['lastname'], 'type'=>'string'),
                           array('fieldName'=>'entry_street_address', 'value'=>$address_question_arr['street_address'], 'type'=>'string'),
                           array('fieldName'=>'entry_postcode', 'value'=>$address_question_arr['postcode'], 'type'=>'string'),
                           array('fieldName'=>'entry_city', 'value'=>$address_question_arr['city'], 'type'=>'string'),
                           array('fieldName'=>'entry_country_id', 'value'=>$country_id, 'type'=>'integer'));
    if ($address_question_arr['company'] != '' && $address_question_arr['company'] != $address_question_arr['name']) $sql_data_array[] = array('fieldName'=>'entry_company', 'value'=>$address_question_arr['company'], 'type'=>'string');
    $sql_data_array[] = array('fieldName'=>'entry_gender', 'value'=>$address_question_arr['payer_gender'], 'type'=>'enum:m|f');
    $sql_data_array[] = array('fieldName'=>'entry_suburb', 'value'=>$address_question_arr['suburb'], 'type'=>'string');
    if ($zone_id > 0) {
      $sql_data_array[] = array('fieldName'=>'entry_zone_id', 'value'=>$zone_id, 'type'=>'integer');
      $sql_data_array[] = array('fieldName'=>'entry_state', 'value'=>'', 'type'=>'string');
    } else {
      $sql_data_array[] = array('fieldName'=>'entry_zone_id', 'value'=>'0', 'type'=>'integer');
      $sql_data_array[] = array('fieldName'=>'entry_state', 'value'=>$address_question_arr['state'], 'type'=>'string');
    }
    $sql_data_array[] = array('fieldName'=>'customers_id', 'value'=>$customer_id, 'type'=>'integer');
    $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array);

    $new_address_book_id = $db->Insert_ID();

    

    // make default if set, update
    if ($make_default) {
      $sql_data_array = array();
      $sql_data_array[] = array('fieldName'=>'customers_default_address_id', 'value'=>$new_address_book_id, 'type'=>'integer');
      $where_clause = "customers_id = :customersID";
      $where_clause = $db->bindVars($where_clause, ':customersID', $customer_id, 'integer');
      $db->perform(TABLE_CUSTOMERS, $sql_data_array, 'update', $where_clause);
      $_SESSION['customer_default_address_id'] = $new_address_book_id;
    }

    // set the sendto
    //$_SESSION['sendto'] = $new_address_book_id;

    // return the address_id
    return $new_address_book_id;
  }
  
  
  
  
  
function user_login($email_address, $redirect = true) {
    global $db, $order, $messageStack;
    
    $sql = "SELECT * FROM " . TABLE_CUSTOMERS . "
            WHERE customers_email_address = :custEmail ";
    $sql = $db->bindVars($sql, ':custEmail', $email_address, 'string');
    $check_customer = $db->Execute($sql);

    
    $sql = "SELECT entry_country_id, entry_zone_id
            FROM " . TABLE_ADDRESS_BOOK . "
            WHERE customers_id = :custID
            AND address_book_id = :addrID ";
    $sql = $db->bindVars($sql, ':custID', $check_customer->fields['customers_id'], 'integer');
    $sql = $db->bindVars($sql, ':addrID', $check_customer->fields['customers_default_address_id'], 'integer');
    $check_country = $db->Execute($sql);
    $_SESSION['customer_id'] = (int)$check_customer->fields['customers_id'];
    $_SESSION['customer_default_address_id'] = $check_customer->fields['customers_default_address_id'];
    $_SESSION['customer_first_name'] = $check_customer->fields['customers_firstname'];
    $_SESSION['customer_country_id'] = $check_country->fields['entry_country_id'];
    $_SESSION['customer_zone_id'] = $check_country->fields['entry_zone_id'];
    $order->customer['id'] = $_SESSION['customer_id'];
    $sql = "UPDATE " . TABLE_CUSTOMERS_INFO . "
            SET customers_info_date_of_last_logon = now(),
                customers_info_number_of_logons = customers_info_number_of_logons+1
            WHERE customers_info_id = :custID ";
    $sql = $db->bindVars($sql, ':custID', $_SESSION['customer_id'], 'integer');
    $db->Execute($sql);

    return true;
  }

?>