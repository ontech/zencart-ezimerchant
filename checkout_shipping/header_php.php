<?php
$checkouturl = '';
/*SHIPPING FILE WITH THE EZI CONFIGURATION*/
if( MODULE_PAYMENT_EZIMERCHANT_STATUS == 'True' && MODULE_PAYMENT_EZIMERCHANT_MERCHANT != '' && MODULE_PAYMENT_EZIMERCHANT_APIKEY != '' && strlen(MODULE_PAYMENT_EZIMERCHANT_APIKEY) > 10)
{

	/*CONFIGURATION ELEMENTS NEED TO CALL EZI SITE*/
	$ezi_merchantid = MODULE_PAYMENT_EZIMERCHANT_MERCHANT;
	$ezi_apikey = MODULE_PAYMENT_EZIMERCHANT_APIKEY;
	/*PASSING VALUES TO AN ARRAY*/

$paramhash = array();
$paramhash["ACTION"] = "CreateOrder";
$paramhash["RETURNURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "checkout_complete&o={orderid}", $_SERVER["REQUEST_URI"]);
$paramhash["CANCELURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "shopping_cart", $_SERVER["REQUEST_URI"]);
$paramhash["NOTIFYURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "checkout_complete", $_SERVER["REQUEST_URI"]);
//$paramhash["TEMPLATEURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "checkout_template", $_SERVER["REQUEST_URI"]);
$paramhash["CALCULATECALLBACKURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "checkout_calculate", $_SERVER["REQUEST_URI"]);
$paramhash["LOGINCALLBACKURL"] = "http://".$_SERVER["HTTP_HOST"].preg_replace("/".$_GET["main_page"]."/", "checkout_login", $_SERVER["REQUEST_URI"]);
$paramhash["READONLY"] = 0;

$customerid = (int)$_SESSION['customer_id'];
if($customerid)
{
	$db_query = $db->Execute("SELECT CONCAT(c.customers_firstname, ' ', c.customers_lastname) AS Name, c.customers_email_address AS Email, c.customers_telephone AS Phone, ab.entry_company AS Company, ab.entry_street_address AS Address1, COALESCE(NULLIF(ab.entry_suburb, ''), ab.entry_city) AS Suburb, ab.entry_state AS State, ab.entry_postcode AS PostalCode, (SELECT countries_iso_code_2 FROM ".TABLE_COUNTRIES." WHERE countries_id = ab.entry_country_id) AS CountryCode FROM ".TABLE_CUSTOMERS." c LEFT JOIN ".TABLE_ADDRESS_BOOK." ab ON c.customers_default_address_id = ab.address_book_id WHERE c.customers_id = ".(int)$_SESSION['customer_id']);

	if($db_query->RecordCount() > 0)
	{
		$paramhash["BILLNAME"] = $db_query->fields["Name"];
		$paramhash["BILLPHONE"] = $db_query->fields["Phone"];
		$paramhash["BILLCOMPANY"] = $db_query->fields["Company"];
		$paramhash["BILLADDRESS1"] = $db_query->fields["Address1"];
		$paramhash["BILLEMAIL"] = $db_query->fields["Email"];
		$paramhash["BILLPLACE"] = $db_query->fields["Suburb"];
		$paramhash["BILLDIVISION"] = 'VIC';//$db_query->fields["State"];
		$paramhash["BILLPOSTALCODE"] = $db_query->fields["PostalCode"];
		$paramhash["BILLCOUNTRYCODE"] = $db_query->fields["CountryCode"];
	}
	
	//$paramhash["CUSTOMERID"] = $customerid;
	
}

$products = $_SESSION['cart']->get_products();

$itemidx = 0;
foreach($products as $product)
{
	$productid = zen_get_prid($product["id"]);
	/*$db_query = $db->Execute("SELECT products_image, COALESCE(products_weight, 0.0) AS products_weight FROM ".TABLE_PRODUCTS." WHERE products_id = ".$productid);*/
	$productimage = $product['image'];
	$productweight = $product['weight'];

	$paramhash["PRODUCTCODE(" . $itemidx . ")"] = utf8_encode($product["model"]);
	$paramhash["PRODUCTID(" . $itemidx . ")"] = $productid;
	$paramhash["PRODUCTNAME(" . $itemidx . ")"] = utf8_encode($product["name"]);
	$paramhash["PRODUCTPRICEINCTAX(" . $itemidx . ")"] = zen_add_tax($product['final_price'],zen_get_tax_rate($product['tax_class_id']));
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


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$ezi_merchantid."/orders/");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded", 'X-APIKEY: '.$ezi_apikey ));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paramhash));

$apiresponse = curl_exec($ch);
curl_close($ch);
 
$xml = simplexml_load_string($apiresponse);

$checkouturl = (string)$xml->entry->link["href"];

header('Location: ' . $checkouturl);
      session_write_close();
}

if(!$checkouturl)
{
	$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_SHIPPING');

  require_once(DIR_WS_CLASSES . 'http_client.php');

// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($_SESSION['cart']->count_contents() <= 0) {
    zen_redirect(zen_href_link(FILENAME_TIME_OUT));
  }

// if the customer is not logged on, redirect them to the login page
  if (!isset($_SESSION['customer_id']) || !$_SESSION['customer_id']) {
    $_SESSION['navigation']->set_snapshot();
    zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
  } else {
    // validate customer
    if (zen_get_customer_validate_session($_SESSION['customer_id']) == false) {
      $_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_SHIPPING));
      zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
    }
  }

// Validate Cart for checkout
  $_SESSION['valid_to_checkout'] = true;
  $_SESSION['cart']->get_products(true);
  if ($_SESSION['valid_to_checkout'] == false) {
    $messageStack->add('header', ERROR_CART_UPDATE, 'error');
    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
  }

// Stock Check
  if ( (STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true') ) {
    $products = $_SESSION['cart']->get_products();
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
      if (zen_check_stock($products[$i]['id'], $products[$i]['quantity'])) {
        zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
        break;
      }
    }
  }
// if no shipping destination address was selected, use the customers own address as default
  if (!$_SESSION['sendto']) {
    $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
  } else {
// verify the selected shipping address
    $check_address_query = "SELECT count(*) AS total
                            FROM   " . TABLE_ADDRESS_BOOK . "
                            WHERE  customers_id = :customersID
                            AND    address_book_id = :addressBookID";

    $check_address_query = $db->bindVars($check_address_query, ':customersID', $_SESSION['customer_id'], 'integer');
    $check_address_query = $db->bindVars($check_address_query, ':addressBookID', $_SESSION['sendto'], 'integer');
    $check_address = $db->Execute($check_address_query);

    if ($check_address->fields['total'] != '1') {
      $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
      $_SESSION['shipping'] = '';
    }
  }

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

// register a random ID in the session to check throughout the checkout procedure
// against alterations in the shopping cart contents
  $_SESSION['cartID'] = $_SESSION['cart']->cartID;

// if the order contains only virtual products, forward the customer to the billing page as
// a shipping address is not needed
  if ($order->content_type == 'virtual') {
    $_SESSION['shipping'] = 'free_free';
    $_SESSION['shipping']['title'] = 'free_free';
    $_SESSION['sendto'] = false;
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
  }

  $total_weight = $_SESSION['cart']->show_weight();
  $total_count = $_SESSION['cart']->count_contents();

// load all enabled shipping modules
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping;

  if ( defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true') ) {
    $pass = false;

    switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
      case 'national':
        if ($order->delivery['country_id'] == STORE_COUNTRY) {
          $pass = true;
        }
        break;
      case 'international':
        if ($order->delivery['country_id'] != STORE_COUNTRY) {
          $pass = true;
        }
        break;
      case 'both':
        $pass = true;
        break;
    }

    $free_shipping = false;
    if ( ($pass == true) && ($_SESSION['cart']->show_total() >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) ) {
      $free_shipping = true;
    }
  } else {
    $free_shipping = false;
  }

  require(DIR_WS_MODULES . zen_get_module_directory('require_languages.php'));

  if (isset($_SESSION['comments'])) {
    $comments = $_SESSION['comments'];
  }


// process the selected shipping method
  if ( isset($_POST['action']) && ($_POST['action'] == 'process') ) {
    if (zen_not_null($_POST['comments'])) {
      $_SESSION['comments'] = zen_db_prepare_input($_POST['comments']);
    }
    $comments = $_SESSION['comments'];

    if ( (zen_count_shipping_modules() > 0) || ($free_shipping == true) ) {
      if ( (isset($_POST['shipping'])) && (strpos($_POST['shipping'], '_')) ) {
        $_SESSION['shipping'] = $_POST['shipping'];

        list($module, $method) = explode('_', $_SESSION['shipping']);
        if ( is_object($$module) || ($_SESSION['shipping'] == 'free_free') ) {
          if ($_SESSION['shipping'] == 'free_free') {
            $quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
            $quote[0]['methods'][0]['cost'] = '0';
          } else {
            $quote = $shipping_modules->quote($method, $module);
          }
          if (isset($quote['error'])) {
            $_SESSION['shipping'] = '';
          } else {
            if ( (isset($quote[0]['methods'][0]['title'])) && (isset($quote[0]['methods'][0]['cost'])) ) {
              $_SESSION['shipping'] = array('id' => $_SESSION['shipping'],
                                'title' => (($free_shipping == true) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
                                'cost' => $quote[0]['methods'][0]['cost']);

              zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
            }
          }
        } else {
          $_SESSION['shipping'] = false;
        }
      }
    } else {
      $_SESSION['shipping'] = false;

      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
    }
  }

// get all available shipping quotes
  $quotes = $shipping_modules->quote();

// if no shipping method has been selected, automatically select the cheapest method.
// if the modules status was changed when none were available, to save on implementing
// a javascript force-selection method, also automatically select the cheapest shipping
// method if more than one module is now enabled
  if ( !$_SESSION['shipping'] || ( $_SESSION['shipping'] && ($_SESSION['shipping'] == false) && (zen_count_shipping_modules() > 1) ) ) $_SESSION['shipping'] = $shipping_modules->cheapest();


  // Should address-edit button be offered?
  $displayAddressEdit = (MAX_ADDRESS_BOOK_ENTRIES >= 2);

  // if shipping-edit button should be overridden, do so
  $editShippingButtonLink = zen_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL');	
  if (isset($_SESSION['payment']) && method_exists($$_SESSION['payment'], 'alterShippingEditButton')) {
    $theLink = $$_SESSION['payment']->alterShippingEditButton();
    if ($theLink) {
      $editShippingButtonLink = $theLink;
      $displayAddressEdit = true;
    }
  }

  $breadcrumb->add(NAVBAR_TITLE_1, zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2);

// This should be last line of the script:
  $zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_SHIPPING');

}
?>
