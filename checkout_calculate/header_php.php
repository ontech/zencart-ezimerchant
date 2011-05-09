<?php
require(DIR_WS_CLASSES . 'order.php');

$cart = new shoppingCart();

$cartidx = 0;
while(1==1)
{
	if(!isset($_POST['PRODUCTCODE('.$cartidx.')']))
		break;

	$product = $db->Execute("SELECT products_id FROM ".TABLE_PRODUCTS." WHERE products_model='".zen_db_prepare_input($_POST['PRODUCTCODE('.$cartidx.')'])."'");

	// TODO: we are passing '' for attributes here - that isn't right, also lookup quantity
	
	$cart->add_cart($product->fields['products_id'], 1, '', false);
	
	$cartidx += 1;
}

$cart->calculate();

$_SESSION['customerid'] = 0;
$_SESSION['cart'] = $cart;
$total_weight = $_SESSION['cart']->show_weight();
$total_count = $_SESSION['cart']->count_contents();
$order = new order();
$order->cart();
$order->delivery['street_address'] = $_POST['ADDRESS1'];
$order->delivery['city'] = $_POST['PLACE'];
$order->delivery['state'] = $_POST['DIVISION'];
$order->delivery['postcode'] = $_POST['POSTALCODE'];
$countryid = $db->Execute("SELECT countries_id FROM ".TABLE_COUNTRIES." WHERE countries_iso_code_2='".zen_db_prepare_input($_POST['COUNTRYCODE'])."'");
$order->delivery['country']['id'] = 13;
$order->delivery['country']['iso_code_2'] = $_POST['COUNTRYCODE'];

// load all enabled shipping modules
require(DIR_WS_CLASSES . 'shipping.php');
$shipping_modules = new shipping;

// get all available shipping quotes
$quotes = $shipping_modules->quote();

$methodidx = 0;
foreach ($quotes as $quote)
{
	$taxrate = $quote['tax'];
	foreach ($quote['methods'] as $method)
	{
		$freightrule['FREIGHTNAME('.$methodidx.')'] = $method['title'];
		$freightrule['FREIGHTCHARGE('.$methodidx.')'] = $method['cost'];
		$freightrule['FREIGHTCHARGEINCTAX('.$methodidx.')'] = $cost * (1 + ($taxrate / 100));
		$methodidx = $methodidx + 1;
	}
}

echo http_build_query($freightrule);
die();
?>