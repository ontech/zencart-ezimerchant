<?php
$cart = new shoppingCart();
$data = $_POST;
	
foreach ($data as $key => $value)
{
	preg_match_all('/PRODUCTNAME\((.+?)\)/is', $key, $new);
	if($new[1][0] >= "0" && $new[1][0] > $prod_count)$prod_count = $new[1][0];
}

for($i=0;$i<=$prod_count;$i++)
{	
	$product_name = $data['PRODUCTNAME('.$i.')'];
	$product_model = trim($data['PRODUCTCODE('.$i.')']);
	$quantity 	  = $data['PRODUCTQUANTITY('.$i.')'];
	$prodid       =  $data['PRODUCTID('.$i.')'];
	$product = $db->Execute("SELECT products_id from " . TABLE_PRODUCTS . " WHERE products_id = '".mysql_real_escape_string($prodid)."' LIMIT 0,1");
	if ($product->RecordCount() == 1) {
          $product_id = $product->fields['products_id'];
        }
	else
		{
			$product_id = 0;
		}
	if($data['PRODUCTATTRIBUTE('.$i.')(0)'] && $data['PRODUCTATTRIBUTEVALUE('.$i.')(0)'])
	{
		reset($data);
		foreach ($data as $key1 => $value1)
		{
			preg_match_all('/PRODUCTATTRIBUTE\('.$i.'\)\((.+?)\)/is', $key1, $new1);
			if($new1[1][0] >= "0" && (int)$new1[1][0] > (int)$prodattr_count)$prodattr_count = $new1[1][0];
		}
		$attributes = array();
		for($j=0;$j<=$prodattr_count;$j++)
		{
			$product_option = $db->Execute("SELECT products_options_id, products_options_type FROM " . TABLE_PRODUCTS_OPTIONS . " AS o WHERE EXISTS(SELECT 1 FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id = ".$product_id." AND options_id = o.products_options_id) AND products_options_name = '".mysql_real_escape_string(stripslashes($data['PRODUCTATTRIBUTE('.$i.')('.$j.')']))."' AND language_id = 1 LIMIT 0,1");
			if ($product_option->RecordCount() == 1) {
				  $product_options_id = $product_option->fields['products_options_id'];				  
				  
				  if($product_option->fields['products_options_type'] == 3)
						{
							$val_in_attr = explode(',',stripslashes((string)$data['PRODUCTATTRIBUTEVALUE('.$i.')('.$j.')']));
							
							for($kk=0;$kk<count($val_in_attr);$kk++)
							{
							$product_optionvalue = $db->Execute("SELECT products_options_values_id FROM " . TABLE_PRODUCTS_OPTIONS_VALUES . " AS v WHERE EXISTS(SELECT 1 FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id = ".$product_id." AND options_id = ".$product_option->fields['products_options_id']." AND products_options_values_id = options_values_id) AND products_options_values_name = '".mysql_real_escape_string(stripslashes(trim($val_in_attr[$kk])))."' AND language_id = 1 LIMIT 0,1");		
							if($product_optionvalue->RecordCount() == 1)
							{	
								
								$aray_val[$product_optionvalue->fields['products_options_values_id']] = $product_optionvalue->fields['products_options_values_id'];
																
							}
							$attributes[$product_option->fields['products_options_id']] = $aray_val;
							}
							
						}
						else
						{
						$product_optionvalue = $db->Execute("SELECT products_options_values_id FROM " . TABLE_PRODUCTS_OPTIONS_VALUES . " AS v WHERE EXISTS(SELECT 1 FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id = ".$product_id." AND options_id = ".$product_option->fields['products_options_id']." AND products_options_values_id = options_values_id) AND products_options_values_name = '".mysql_real_escape_string(stripslashes($data['PRODUCTATTRIBUTEVALUE('.$i.')('.$j.')']))."' AND language_id = 1 LIMIT 0,1");		
						if($product_optionvalue->RecordCount() == 1)
						{
							$attributes[$product_option->fields['products_options_id']] = $product_optionvalue->fields['products_options_values_id'];
						}
						else
						{
							$attributes[TEXT_PREFIX . $product_option->fields['products_options_id']] = (string)$data['PRODUCTATTRIBUTEVALUE('.$i.')('.$j.')'];
						}
						}				  
				}

		}
	}
	if($product_id)
	$cart->add_cart($product_id, $quantity, $attributes, false);
}
$_SESSION['cart'] = $cart;

require(DIR_WS_CLASSES . 'order.php');
$order = new order();

$country = $db->Execute("SELECT countries_id, countries_name, countries_iso_code_3 FROM ".TABLE_COUNTRIES." WHERE countries_iso_code_2='".$data['COUNTRYCODE']."' LIMIT 0,1");
			if ($country->RecordCount() == 1) {
				  $country_id = $country->fields['countries_id'];
				  $country_name = $country->fields['countries_name'];
				  $country_isocode3 = $country->fields['countries_iso_code_3'];
				}

$cache_state_prov_values = $db->Execute("select zone_id from " . TABLE_ZONES . " where zone_country_id = '" . $country_id . "' and zone_code = '" . $data['DIVISION'] . "'");
$cache_state_prov_id = $cache_state_prov_values->fields['zone_id'];

$order->delivery['street_address'] = $data['ADDRESS1'];
$order->delivery['city'] = $data['PLACE'];
$order->delivery['state'] = $data['DIVISION'];
$order->delivery['zone_id'] = $cache_state_prov_id;
$order->delivery['postcode'] = $data['POSTALCODE'];
$order->delivery['country_id'] = $country_id;
$order->delivery['country'] = array('id' => $country_id, 'title' => $country_name, 'iso_code_2' => $data['COUNTRYCODE'], 'iso_code_3' =>  $country_isocode3);
$order->delivery['format_id'] = zen_get_address_format_id($country_id);
				
$order->info = array('total' => $cart->show_total(), // TAX ????
                         'currency' => $currency,
                         'currency_value'=> $currencies->currencies[$currency]['value']);	

	

$total_weight = $cart->show_weight();
$total_count = $cart->count_contents();


if($data['EMAIL'])
{
$sql = "SELECT customers_id, customers_firstname, customers_lastname
              FROM " . TABLE_CUSTOMERS . "
              WHERE customers_email_address = :emailAddress ";
      $sql = $db->bindVars($sql, ':emailAddress', $data['EMAIL'], 'string');
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
            'customers_email_address'       => $data['EMAIL'],
            'customers_email_format'        => (ACCOUNT_EMAIL_PREFERENCE == '1' ? 'HTML' : 'TEXT'),
            //'customers_telephone'           => $customer->phone,
            //'customers_fax'                 => $customer->fax,
            //'customers_gender'              => '',
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
            //'entry_firstname'           => $firstname,
            //'entry_lastname'            => $lastname,
            'entry_street_address'      => $data['ADDRESS1'],
            'entry_suburb'              => $data['ADDRESS1'],
            'entry_city'                => $data['PLACE'],
            'entry_zone_id'             => $cache_state_prov_id,
            'entry_postcode'            => $data['POSTALCODE'],
            'entry_country_id'          => $country_id);
        
          //$sql_data_array['entry_company'] = $customer->companyname;
        
        if ($cache_state_prov_id > 0) {
          $sql_data_array['entry_zone_id'] = $cache_state_prov_id;
          $sql_data_array['entry_state'] = '';
        } else {
          $sql_data_array['entry_zone_id'] = 0;
          $sql_data_array['entry_state'] = $data['DIVISION'];
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

        }

		
		user_login($data['EMAIL'], false);
		
		$address_book_id = findMatchingAddressBookEntry($_SESSION['customer_id'], $order->delivery);
      // no match add the record
      if (!$address_book_id) {
        $address_book_id = addAddressBookEntry($_SESSION['customer_id'], $order->delivery);
      }
      
      $_SESSION['sendto'] = $address_book_id;
	  $_SESSION['billto'] = $address_book_id;
	  $_SESSION['payment'] = 'ezimerchant';
}

// get coupon code
if ($data['COUPONCODE']) {
  $discount_coupon_query = "SELECT coupon_code
                            FROM " . TABLE_COUPONS . "
                            WHERE coupon_code = :couponID";

  $discount_coupon_query = $db->bindVars($discount_coupon_query, ':couponID', $data['COUPONCODE'], 'string');
  $discount_coupon = $db->Execute($discount_coupon_query);
}


require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping;
  $quotes = $shipping_modules->quote();
  //$order->info['subtotal'] = $cart->show_total();

  
  
   // check free shipping based on order $total
  if ( defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true')) {
    switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
      case 'national':
      if ($order->delivery['country_id'] == STORE_COUNTRY) $pass = true; break;
      case 'international':
      if ($order->delivery['country_id'] != STORE_COUNTRY) $pass = true; break;
      case 'both':

      $pass = true; break;
      default:
      $pass = false; break;
    }
    $free_shipping = false;
    if ( ($pass == true) && ($cart->show_total() >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) {
      $free_shipping = true;
      include(DIR_WS_LANGUAGES . 'english/modules/order_total/ot_shipping.php');
    }
  } else {
    $free_shipping = false;
  }
  
  
$paramhash = array();
$methodidx = 0;
 for ($i=0, $n=sizeof($quotes); $i<$n; $i++) {
		$taxrate  =  $quotes[$i]['tax'];
		for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
		$paramhash['FREIGHTNAME('.$methodidx.')'] = strip_tags($quotes[$i]['module']. " ( ". $quotes[$i]['methods'][$j]['title']. " ) ");
		$paramhash['FREIGHTCHARGE('.$methodidx.')'] = $quotes[$i]['methods'][$j]['cost'] * (1 + ($taxrate / 100));
		//$freightrule['FREIGHTCHARGEINCTAX('.$methodidx.')'] = $method['cost'] * (1 + ($taxrate / 100));
		if($j == 0)
		{
			$_SESSION['shipping']['id'] = $quotes[$i]['id'].'_'.$quotes[$i]['id'];
			$_SESSION['shipping']['title'] = strip_tags($quotes[$i]['module']. " ( ". $quotes[$i]['methods'][$j]['title']. " ) ");
			$_SESSION['shipping']['cost'] = $quotes[$i]['methods'][$j]['cost'] * (1 + ($taxrate / 100));
		}
		$methodidx = $methodidx + 1;
		}
	}
	




  
   

  
  
  
  
 
$products = $cart->get_products();
$itemidx = 0;
foreach($products as $product)
{
	$productid = zen_get_prid($product["id"]);
	$productimage = $product['image'];
	$productweight = $product['weight'];

	$paramhash["PRODUCTCODE(" . $itemidx . ")"] = $product["model"];
	$paramhash["PRODUCTID(" . $itemidx . ")"] = $productid;
	$paramhash["PRODUCTNAME(" . $itemidx . ")"] = $product["name"];
	$paramhash["PRODUCTPRICEINCTAX(" . $itemidx . ")"] = zen_add_tax($product["final_price"],zen_get_tax_rate($product["tax_class_id"]));
	//$paramhash["PRODUCTPRICE(" . $itemidx . ")"] = $product['final_price'];
	$paramhash["PRODUCTQUANTITY(" .$itemidx . ")"] = $product["quantity"];
	$paramhash["PRODUCTIMAGEURL(" .$itemidx . ")"] = 'http://'.$_SERVER["HTTP_HOST"].preg_replace("/index\.php\?.*/", "images/".$productimage, $_SERVER["REQUEST_URI"]);

	$attridx = 0;
	$repeat_item = array();
	foreach($product['attributes'] as $attribute => $attributevalue)
	{
		$attr = explode('_',$attribute);	
		$db_query = $db->Execute("SELECT products_options_name AS name, COALESCE((SELECT products_attributes_weight FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id = ".$productid." AND options_id = '".$attr[0]."' AND options_values_id = ".$attributevalue."), 0) AS weight, CASE WHEN ".$attributevalue." = 0 THEN '".zen_db_prepare_input($product['attributes_values'][$attribute])."' ELSE (SELECT products_options_values_name FROM ".TABLE_PRODUCTS_OPTIONS_VALUES." WHERE products_options_values_id = ".$attributevalue." AND language_id = 1) END AS value FROM ".TABLE_PRODUCTS_OPTIONS." WHERE products_options_id = '".$attr[0]."' AND language_id = 1");
		if(in_array($attr[0], $repeat_item))
		{		
			reset($repeat_item);
				while ($attributeval_name = current($repeat_item)) 
				{				
					$add_withthis = $paramhash["PRODUCTATTRIBUTEVALUE(".$itemidx.")(".key($repeat_item).")"];
					if ($attributeval_name == $attr[0]) 
					{					
						$add_this = $add_withthis.', '.$db_query->fields['value'];
						$paramhash["PRODUCTATTRIBUTEVALUE(".$itemidx.")(".key($repeat_item).")"] = $add_this;
						break;
					}					
					next($repeat_item);
				}		
		}
		else
		{
		$paramhash["PRODUCTATTRIBUTE(".$itemidx.")(".$attridx.")"] = $db_query->fields['name'];
		$paramhash["PRODUCTATTRIBUTEVALUE(".$itemidx.")(".$attridx.")"] = $db_query->fields['value'];
		
		$productweight = $productweight + $db_query->fields['weight'];
		
		$attridx = $attridx + 1;
		}
		$repeat_item[$attridx - 1] = $attr[0];
		
		
	}
	$itemidx = $itemidx + 1;		
}

if($data['EMAIL'])
{	
	
// load selected payment module
require(DIR_WS_CLASSES . 'payment.php');
$payment_modules = new payment($_SESSION['payment']);
// load the selected shipping module
$shipping_modules = new shipping($_SESSION['shipping']);

$order = new order;

// load OT modules so that discounts and taxes can be assessed
    require(DIR_WS_CLASSES . 'order_total.php');
    $order_total_modules = new order_total;
	$order_total_modules->collect_posts();
	$order_total_modules->pre_confirmation_check();

    $order_totals = $order_total_modules->process();



$creditsApplied = 0;
$prod_datacount = 1;

if (sizeof($order_totals)) {
      // prepare subtotals
      for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
        if ($order_totals[$i]['code'] == '') continue;
        if (in_array($order_totals[$i]['code'], array('ot_total','ot_subtotal','ot_tax','ot_shipping')) || strstr($order_totals[$i]['code'], 'insurance')) {
          
          if ($order_totals[$i]['code'] == 'ot_tax') 
		  {
		  if (DISPLAY_PRICE_WITH_TAX != 'true') 
		  {
		  $paramhash["PRODUCTCODE(" . $itemidx . ")"]      = $order_totals[$i]['title'];
		  $paramhash["PRODUCTPRICEINCTAX(" . $itemidx . ")"] = round($order_totals[$i]['value'],2);
		  $paramhash["PRODUCTNAME(" . $itemidx . ")"]      = $order_totals[$i]['title'];
		  $paramhash["PRODUCTQUANTITY(" .$itemidx . ")"] = 1;
		  $itemidx++;
		  }
		  }
          
          if (strstr($order_totals[$i]['code'], 'insurance')) 
		  {
			  $paramhash["PRODUCTCODE(" . $itemidx . ")"]      = 'INSURANCE';
			  $paramhash["PRODUCTPRICEINCTAX(" . $itemidx . ")"] = round($order_totals[$i]['value'],2);
			  $paramhash["PRODUCTNAME(" . $itemidx . ")"]      = 'INSURANCE';
			  $paramhash["PRODUCTQUANTITY(" .$itemidx . ")"] = 1;
			  $itemidx++;
		  }
          
        } else {
          if ((substr($order_totals[$i]['text'], 0, 1) == '-') || (isset($$order_totals[$i]['code']->credit_class) && $$order_totals[$i]['code']->credit_class == true)) {
            // handle credits
            $creditsApplied += round($order_totals[$i]['value'], 2);
          } else {
            // treat all other OT's as if they're related to handling fees or other extra charges to be added/included
            $surcharges += $order_totals[$i]['value'];
          }
        }
      }

      if ($creditsApplied > 0)
	 {	  
		$paramhash["PRODUCTCODE(" . $itemidx . ")"]      = 'DISCOUNT';
		$paramhash["PRODUCTPRICEINCTAX(" . $itemidx . ")"] = -$creditsApplied;
		$paramhash["PRODUCTNAME(" . $itemidx . ")"]      = 'DISCOUNT';
		$paramhash["PRODUCTQUANTITY(" .$itemidx . ")"] = 1;
		$itemidx++;
	 }
      if ($surcharges > 0) 
	  {
		$paramhash["PRODUCTCODE(" . $itemidx . ")"]      = 'SURCHARGE';
		$paramhash["PRODUCTPRICEINCTAX(" . $itemidx . ")"] = round($surcharges,2);
		$paramhash["PRODUCTNAME(" . $itemidx . ")"]      = 'SURCHARGE';
		$paramhash["PRODUCTQUANTITY(" .$itemidx . ")"] = 1;
	  }

      
    }




if (!$acct_exists) {

$db->Execute("DELETE FROM " . TABLE_CUSTOMERS . " WHERE customers_id = '".$customer_id."'");
$db->Execute("DELETE FROM " . TABLE_ADDRESS_BOOK . " WHERE address_book_id = '".$address_id."'");

}

}


$sql = "select * from " . TABLE_COUPONS . " where `coupon_expire_date` >= NOW() and `coupon_active` = 'Y' and `coupon_type` != 'G'";
$coupon_result=$db->Execute($sql);

if ($coupon_result->RecordCount() > 0 ) {
          $paramhash["COUPONACTIVE"] = 1;
        }
    
echo http_build_query($paramhash);
die();
	
function LogToFile($logfile, $message)
{	
	//$fp = fopen($logfile, 'w+');
	$fp = fopen(DIR_FS_CATALOG. 'test.txt', "a+");
	fwrite($fp, $message);
	fclose($fp);
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