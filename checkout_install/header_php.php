<?php
$db->Execute("REPLACE INTO ".TABLE_CONFIGURATION." (configuration_title, configuration_key, configuration_value, configuration_description) VALUES ('ezimerchant MerchantID', 'MODULE_PAYMENT_EZIMERCHANT_MERCHANT', '22518', 'ezimerchant MerchantID')" );
$db->Execute("REPLACE INTO ".TABLE_CONFIGURATION." (configuration_title, configuration_key, configuration_value, configuration_description) VALUES ('ezimerchant Signing Key', 'MODULE_PAYMENT_EZIMERCHANT_APIKEY', 'uEnn6XG4cjorr0PHI4d6Cw==', 'ezimerchant Signing Key')" );
?>