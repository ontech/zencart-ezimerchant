<?php

if(isset($_GET["customerid"]))
{
	$_SESSION['customer_id'] = (int)$_GET["customerid"];
    $_SESSION['customer_first_name'] = "Luke";
    $_SESSION['customer_default_address_id'] = 1;
    $_SESSION['customer_country_id'] = 1;
    $_SESSION['customer_zone_id'] = 1;
    $_SESSION['customers_authorization'] = '';	

	Header("Location: ".$_GET["returnurl"]);
	die();
}

if(isset($_GET["logout"]))
{
	$_SESSION['customer_id'] = (int)$_GET["customerid"];
    $_SESSION['customer_first_name'] = "Luke";
    $_SESSION['customer_default_address_id'] = 1;
    $_SESSION['customer_country_id'] = 1;
    $_SESSION['customer_zone_id'] = 1;
    $_SESSION['customers_authorization'] = '';	

	Header("Location: ".$_GET["returnurl"]);
	die();	
}


$check_customer_query = "SELECT customers_id, COALESCE(customers_firstname, '') AS customers_firstname, COALESCE(customers_lastname, '') AS customers_lastname, customers_password,
                                customers_email_address, COALESCE(customers_default_address_id, 0) AS customers_default_address_id, COALESCE(customers_telephone, '') AS customers_telephone, COALESCE(customers_fax, '') AS customers_fax,
                                customers_authorization, customers_referral, COALESCE((SELECT entry_company FROM ".TABLE_ADDRESS_BOOK." WHERE customers_id = c.customers_id ORDER BY CASE WHEN customers_default_address_id = address_book_id THEN 0 ELSE 1 END LIMIT 1), '') AS customers_company
                       FROM " . TABLE_CUSTOMERS . " AS c
                       WHERE customers_email_address = :emailAddress";

$check_customer_query  =$db->bindVars($check_customer_query, ':emailAddress', $_POST["email"], 'string');
$check_customer = $db->Execute($check_customer_query);

$loggedin = zen_validate_password($_POST['password'], $check_customer->fields['customers_password']);

$result = array(
	'CUSTOMERID' => $check_customer->fields['customers_id'],
	'LOGGEDIN' => $loggedin,
	'PHONE' => $check_customer->fields['customers_telephone'],
	'FAX' => $check_customer->fields['customers_fax'],
	'NAME' => $check_customer->fields['customers_firstname'].' '.$check_customer->fields['customers_lastname'],
	'COMPANY' => $check_customer->fields['customers_company']);

$default_address_id = (int)$check_customer->fields['customers_default_address_id'];
if(!$default_address_id)
	$default_address_id = 0;

$default_address = $db->Execute("SELECT COALESCE(entry_company, '') AS Company, COALESCE(entry_firstname, '') AS FirstName, COALESCE(entry_lastname, '') AS LastName, COALESCE(entry_street_address, '') AS Address1, COALESCE(entry_suburb, '') AS Address2, COALESCE(entry_postcode, '') AS PostalCode, COALESCE(entry_city, '') AS Place, COALESCE(entry_state, '') AS Division, (SELECT countries_iso_code_2 FROM ".TABLE_COUNTRIES." WHERE countries_id = AddrBook.entry_country_id) AS CountryCode FROM ".TABLE_ADDRESS_BOOK." AS AddrBook WHERE address_book_id=".$default_address_id);

if($default_address->RecordCount() > 0)
{
	$result["DEFAULTNAME"] = $default_address->fields['FirstName']." ".$default_address->fields['LastName'];
	$result["DEFAULTCOMPANY"] = $default_address->fields['Company'];
	$result["DEFAULTEMAIL"] = $check_customer->fields['customers_email_address'];
	$result["DEFAULTPHONE"] = $check_customer->fields['customers_telephone'];
	$result["DEFAULTMOBILEPHONE"] = '';
	$result["DEFAULTFAX"] = $check_customer->fields['customers_fax'];
	$result["DEFAULTADDRESS1"] = $default_address->fields['Address1'];
	$result["DEFAULTADDRESS2"] = $default_address->fields['Address2'];
	$result["DEFAULTADDRESS3"] = "";
	$result["DEFAULTPLACE"] = $default_address->fields['Place'];
	$result["DEFAULTDIVISION"] = $default_address->fields['Division'];
	$result["DEFAULTPOSTALCODE"] = $default_address->fields['PostalCode'];
	$result["DEFAULTCOUNTRYCODE"] = $default_address->fields['CountryCode'];
}
else
{
	//$result["DEFAULTNAME"] = $check_customer->fields['customers_firstname'].' '.$check_customer->fields['customers_lastname'];
	//$result["DEFAULTCOMPANY"] = $check_customer->fields['customers_company']);
	//$result["DEFAULTEMAIL"] = $check_customer->fields['customers_email_address'];
	//$result["DEFAULTPHONE"] = $check_customer->fields['customers_telephone'];
	//$result["DEFAULTMOBILEPHONE"] = '';
	//$result["DEFAULTFAX"] = $check_customer->fields['customers_fax'];
	//$result["DEFAULTADDRESS1"] = '';
	//$result["DEFAULTADDRESS2"] = '';
	$result["DEFAULTADDRESS3"] = '';
	//$result["DEFAULTPLACE"] = '';
	//$result["DEFAULTDIVISION"] = '';
	//$result["DEFAULTPOSTALCODE"] = '';
	//$result["DEFAULTCOUNTRYCODE"] = '';
}

/*
$addridx = 0;
$addresses = $db->Execute("SELECT entry_company, entry_firstname, entry_lastname, entry_street_address, entry_suburb, entry_postcode, entry_city, entry_state, (SELECT countries_iso_code_2 FROM ".TABLE_COUNTRIES." WHERE countries_id = AddrBook.entry_country_id) AS entry_countrycode FROM ".TABLE_ADDRESS_BOOK." AS AddrBook WHERE customers_id = ".$check_customer->fields['customers_id']." AND address_book_id!=".$check_customer->fields['customers_default_address_id']);
while(!$addresses->EOF)
{
	$result["ADDRESSNAME(".$addridx.")"] = $default_address->fields['entry_firstname']." ".$default_address->fields['entry_lastname'];
	$result["ADDRESSCOMPANY(".$addridx.")"] = $default_address->fields['entry_company'];
	$result["ADDRESSEMAIL(".$addridx.")"] = $_POST["email"];
	$result["ADDRESSPHONE(".$addridx.")"] = $check_customer->fields['customers_telephone'];
	$result["ADDRESSMOBILEPHONE(".$addridx.")"] = '';
	$result["ADDRESSFAX(".$addridx.")"] = $check_customer->fields['customers_fax'];
	$result["ADDRESSADDRESS1(".$addridx.")"] = $default_address->fields['entry_street_address'];
	$result["ADDRESSADDRESS2(".$addridx.")"] = $default_address->fields['entry_suburb'];
	$result["ADDRESSADDRESS3(".$addridx.")"] = "";
	$result["ADDRESSPLACE(".$addridx.")"] = $default_address->fields['entry_city'];
	$result["ADDRESSDIVISION(".$addridx.")"] = $default_address->fields['entry_state'];
	$result["ADDRESSPOSTALCODE(".$addridx.")"] = $default_address->fields['entry_postcode'];
	$result["ADDRESSCOUNTRYCODE(".$addridx.")"] = $default_address->fields['entry_countrycode'];

	$addridx += 1;
	$addresses->MoveNext();
}
*/
echo http_build_query($result);
die();

?>