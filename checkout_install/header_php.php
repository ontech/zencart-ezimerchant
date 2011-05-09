<?php
$db->Execute("REPLACE INTO ".TABLE_CONFIGURATION." (configuration_title, configuration_key, configuration_value, configuration_description) VALUES ('ezimerchant MerchantID', 'MODULE_PAYMENT_EZIMERCHANT_MERCHANT', 'MERCHANT_ID', 'ezimerchant MerchantID')" );
$db->Execute("REPLACE INTO ".TABLE_CONFIGURATION." (configuration_title, configuration_key, configuration_value, configuration_description) VALUES ('ezimerchant Signing Key', 'MODULE_PAYMENT_EZIMERCHANT_APIKEY', 'APIKEY', 'ezimerchant Signing Key')" );
?>