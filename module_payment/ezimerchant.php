<?php
/**
 * @package money order payment module
 *
 * @package paymentMethod
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: ezimerchant.php 2011-05-20 00:00:00Z drbyte $
 */
  class ezimerchant {
    var $code, $title, $description, $enabled;

// class constructor
    function ezimerchant() {
      global $order;

      $this->code = 'ezimerchant';
      $this->title = MODULE_PAYMENT_EZIMERCHANT_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_EZIMERCHANT_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_EZIMERCHANT_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_EZIMERCHANT_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_EZIMERCHANT_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_EZIMERCHANT_ORDER_STATUS_ID;
      }

      if (is_object($order)) $this->update_status();

      $this->email_footer = MODULE_PAYMENT_EZIMERCHANT_TEXT_EMAIL_FOOTER;
    }

// class methods
    function update_status() {
      global $order, $db;
	  $this->enabled = true;
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->title);
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
      return array('title' => MODULE_PAYMENT_EZIMERCHANT_TEXT_DESCRIPTION);
    }

    function process_button() {
      return false;
    }

    function before_process() {
		global $order;
		$order->info['cc_type']    = $_SESSION['payment_eziinfo']['cc_type'];
		$order->info['cc_owner']   = $_SESSION['payment_eziinfo']['cc_owner'];
		$order->info['cc_number']  = $_SESSION['payment_eziinfo']['cc_number'];
		$order->info['cc_expires'] = $_SESSION['payment_eziinfo']['cc_expires']; 
		$order->info['cc_cvv']     = '***'; //$_POST['cc_cvv'];	
     
    }

    function after_process() {
	global $db, $insert_id, $order;
	
		if($_SESSION['payment_eziinfo']['ezi_paymentcompleted'] == 'true')
		$query_addcapture = $_SESSION['payment_eziinfo']['ezi_amount'];
		else
		$query_addcapture = 0;
		$db->Execute("INSERT INTO ezi_order_mapping (eziorder_id, order_id, paymentmethod, amount, settleamount, paymentcompleted, paymentvoided, pendingreason, captureamount) VALUES ('".$_SESSION['eziorder_id']."', '".$insert_id."', '".$_SESSION['payment_eziinfo']['ezi_paymentmethod']."', '".$_SESSION['payment_eziinfo']['ezi_amount']."', '".$_SESSION['payment_eziinfo']['ezi_settleamount']."', '".$_SESSION['payment_eziinfo']['ezi_paymentcompleted']."', '".$_SESSION['payment_eziinfo']['ezi_paymentvoided']."', '".$_SESSION['payment_eziinfo']['ezi_pendingreason']."', '".$query_addcapture."' )" );
	
		
		// add a new OSH record for this order's PP details
		if($_SESSION['payment_eziinfo']['ezi_paymentmethod'] == 'paypalec')
		{
		$commentString = "Transaction ID: :transID: " .
						 "\nPayment Type: :pmtType: " .
						 "\nTimestamp: :pmtTime: " .
						 "\nPayment Status: :pmtStatus: " .
						 "\nAmount: :orderAmt: ";
		$commentString = $db->bindVars($commentString, ':transID:', $_SESSION['payment_eziinfo']['ezi_transactionid'], 'noquotestring');
		$commentString = $db->bindVars($commentString, ':pmtType:', $_SESSION['payment_eziinfo']['ezi_paymenttype'], 'noquotestring');
		$commentString = $db->bindVars($commentString, ':pmtTime:', $_SESSION['payment_eziinfo']['ezi_paymentdate'], 'noquotestring');
		$commentString = $db->bindVars($commentString, ':pmtStatus:', $_SESSION['payment_eziinfo']['ezi_paymentcompleted'], 'noquotestring');
		$commentString = $db->bindVars($commentString, ':orderAmt:', $_SESSION['payment_eziinfo']['ezi_amount'], 'noquotestring');

		$sql_data_array= array(array('fieldName'=>'orders_id', 'value'=>$insert_id, 'type'=>'integer'),
							   array('fieldName'=>'orders_status_id', 'value'=>$order->info['order_status'], 'type'=>'integer'),
							   array('fieldName'=>'date_added', 'value'=>'now()', 'type'=>'noquotestring'),
							   array('fieldName'=>'customer_notified', 'value'=>0, 'type'=>'integer'),
							   array('fieldName'=>'comments', 'value'=>$commentString, 'type'=>'string'));
		$db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
		
		
		// store the PayPal order meta data -- used for later matching and back-end processing activities
    $paypal_order = array('order_id' => $insert_id,
                          'txn_type' => 'expresscheckout',
                          'module_name' => 'paypal',
                          'module_mode' => 'AUS',
                          'reason_code' => '',
                          'payment_type' => $_SESSION['payment_eziinfo']['ezi_paymenttype'],
                          'payment_status' => $_SESSION['payment_eziinfo']['ezi_paymentcompleted'],
                          'pending_reason' => '',
                          'invoice' => '',
                          'first_name' => $order->billing['firstname'],
                          'last_name' => $order->billing['lastname'],
                          'payer_business_name' => '',
                          'address_name' => '',
                          'address_street' => $order->billing['street_address'],
                          'address_city' => $order->billing['city'],
                          'address_state' => $order->billing['state'],
                          'address_zip' => $order->billing['postcode'],
                          'address_country' => $order->billing['country']['title'],
                          'address_status' => 'verified',
                          'payer_email' => $order->customer['email_address'],
                          'payer_id' => '',
                          'payer_status' => '',
                          'payment_date' => trim(preg_replace('/[^0-9-:]/', ' ', $_SESSION['payment_eziinfo']['ezi_paymentdate'])),
                          'business' => '',
                          'receiver_email' => '',
                          'receiver_id' => '',
                          'txn_id' => $_SESSION['payment_eziinfo']['ezi_transactionid'],
                          'parent_txn_id' => '',
                          'num_cart_items' => sizeof($order->products),
                          'mc_gross' => (float)$_SESSION['payment_eziinfo']['ezi_amount'],
                          'mc_fee' => '',
                          'mc_currency' => $order->info['currency'],
                          'settle_amount' => (float)$_SESSION['payment_eziinfo']['ezi_amount'],
                          'settle_currency' => $order->info['currency'],
                          'exchange_rate' => '',
                          'notify_version' => '0',
                          'verify_sign' =>'',
                          'date_added' => 'now()',
                          'memo' => '',
                         );
			zen_db_perform(TABLE_PAYPAL, $paypal_order);    
		}
	
		// Unregister the paypal session variables, making it necessary to start again for another purchase
		unset($_SESSION['payment_eziinfo']);
		
      return false;
    }

    function get_error() {
      return false;
    }

    function check() {
      global $db;
      if (!isset($this->_check)) {
        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_EZIMERCHANT_STATUS'");
        $this->_check = $check_query->RecordCount();
      }
      return $this->_check;
    }

	
	function ezimerchant_init($ezi_orderid, $parameters_array) {
						/*CONFIGURATION ELEMENTS NEED TO CALL EZI SITE*/
						$ezi_merchantid = MODULE_PAYMENT_EZIMERCHANT_MERCHANT;
						$ezi_apikey = MODULE_PAYMENT_EZIMERCHANT_APIKEY;
						/*PASSING VALUES TO AN ARRAY*/
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$ezi_merchantid."/orders/".$ezi_orderid);
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded", 'X-APIKEY: '.$ezi_apikey ));
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters_array));

						$apiresponse = curl_exec($ch);
						curl_close($ch);
						
						return $apiresponse;
	}
	
	
	/**
    * Build admin-page components
    *
    * @param int $zf_order_id
    * @return string
    */
  function admin_notification($zf_order_id) {
    if (!defined('MODULE_PAYMENT_EZIMERCHANT_STATUS')) return '';
    global $db, $order;
    $module = $this->code;
    $output = '';
    //$response = $this->_GetTransactionDetails($zf_order_id);
    //$response = $this->_TransactionSearch('2006-12-01T00:00:00Z', $zf_order_id);
    $sql = "SELECT * from ezi_order_mapping WHERE order_id = :orderID ";
    $sql = $db->bindVars($sql, ':orderID', $zf_order_id, 'integer');
    $ipn = $db->Execute($sql);
    if ($ipn->RecordCount() == 0) $ipn->fields = array();
    if (file_exists(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/ezimerchant/ezimerchant_admin_notification.php')) require(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/ezimerchant/ezimerchant_admin_notification.php');
    return $output;
  }
	
	
	/**
   * Used to submit a refund for a given transaction.  FOR FUTURE USE.
   */
  function _doRefund($oID, $amount = 'Full', $note = '') {
    global $db, $messageStack;
    //$new_order_status = (int)MODULE_PAYMENT_PAYPALDP_REFUNDED_STATUS_ID;
    $orig_order_amount = 0;
    $proceedToRefund = false;
    $refundNote = strip_tags(zen_db_input($_POST['refnote']));
    if (isset($_POST['fullrefund']) && $_POST['fullrefund'] == MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_FULL) {
      $refundAmt = 'Full';
      if (isset($_POST['reffullconfirm']) && $_POST['reffullconfirm'] == 'on') {
        $proceedToRefund = true;
      } else {
        $messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_REFUND_FULL_CONFIRM_ERROR, 'error');
      }
    }
    if (isset($_POST['partialrefund']) && $_POST['partialrefund'] == MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_PARTIAL) {
      $refundAmt = (float)$_POST['refamt'];
      $proceedToRefund = true;
      if ($refundAmt == 0) {
        $messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_INVALID_REFUND_AMOUNT, 'error');
        $proceedToRefund = false;
      }
    }

    // look up history on this order from PayPal table
    $sql = "select * from ezi_order_mapping where order_id = :orderID";
    $sql = $db->bindVars($sql, ':orderID', $oID, 'integer');
    $zc_ppHist = $db->Execute($sql);
    if ($zc_ppHist->RecordCount() == 0) return false;
    $amount = (float)$zc_ppHist->fields['amount'];
	$refundedamount = (float)$zc_ppHist->fields['refundamount'];
	$ezi_orderid = (int)$zc_ppHist->fields['eziorder_id'];
	$capturedamount = (float)$zc_ppHist->fields['captureamount'];
	$balance_amt = (float)($capturedamount - $refundedamount);
	
	
	
    if ($refundAmt == 'Full') $refundAmt = $amount;
	else
	{
		if($refundAmt > $balance_amt)
		{
			$messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_INVALID_REFUND_AMOUNT, 'error');
			$proceedToRefund = false;
			return false;
		}
	}

    /**
     * Submit refund request to PayPal
     */
    if ($proceedToRefund) {
	
						/*CONFIGURATION ELEMENTS NEED TO CALL EZI SITE
						$ezi_merchantid = MODULE_PAYMENT_EZIMERCHANT_MERCHANT;
						$ezi_apikey = MODULE_PAYMENT_EZIMERCHANT_APIKEY;
						/*PASSING VALUES TO AN ARRAY*/
						
						$paramhash["ACTION"] = "RefundOrder";
						$paramhash["AMOUNT"] = $refundAmt;
						
						/*$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, "https://api.ezimerchant.com/".$ezi_merchantid."/orders/".$ezi_orderid);
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/x-www-form-urlencoded", 'X-APIKEY: '.$ezi_apikey ));
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paramhash));

						$apiresponse = curl_exec($ch);
						curl_close($ch);       */       						
						$apiresponse = $this->ezimerchant_init($ezi_orderid, $paramhash);
						$xml = simplexml_load_string($apiresponse);							
						
						$refundcontent = $xml->entry->children('http://api.ezimerchant.com/schemas/2009/');
						if($refundcontent->success == 1)
						{
						//$new_order_status = ($new_order_status > 0 ? $new_order_status : 1);
						  $new_order_status = 1;
						// Success, so save the results
						$sql_data_array = array('orders_id' => $oID,
												'orders_status_id' => (int)$new_order_status,
												'date_added' => 'now()',
												'comments' => 'REFUND INITIATED. Gross Refund Amt: ' . $refundAmt . "\n" . $refundNote,
												'customer_notified' => 0
											 );
						zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
						$db->Execute("update " . TABLE_ORDERS  . "
									  set orders_status = '" . (int)$new_order_status . "'
									  where orders_id = '" . (int)$oID . "'");
						$db->Execute("update ezi_order_mapping
									  set refundamount = refundamount + '" . (float)$refundAmt . "'
									  where order_id = '" . (int)$oID . "'");
						$messageStack->add_session(sprintf(MODULE_PAYMENT_EZIMERCHANT_TEXT_REFUND_INITIATED, $refundAmt), 'success');
						return true;
						}
						else
						{
							$messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_REFUND_ERROR, 'error');
							return false;
						}
      
      
      
    }
  }
  /**
   * Used to capture part or all of a given previously-authorized transaction.  FOR FUTURE USE.
   * (alt value for $captureType = 'NotComplete')
   */
  function _doCapt($oID, $note = '') {
    global $db, $messageStack;
    

    //@TODO: Read current order status and determine best status to set this to
    //$new_order_status = (int)MODULE_PAYMENT_PAYPALDP_ORDER_STATUS_ID;

    $orig_order_amount = 0;
    $proceedToCapture = false;
    $captureNote = strip_tags(zen_db_input($_POST['captnote']));
    if (isset($_POST['captfullconfirm']) && $_POST['captfullconfirm'] == 'on') {
      $proceedToCapture = true;
    } else {
      $messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_CAPTURE_FULL_CONFIRM_ERROR, 'error');
    }
    if (isset($_POST['captfinal']) && $_POST['captfinal'] == 'on') {
      $captureType = 'Complete';
    } else {
      $captureType = 'NotComplete';
    }
    if (isset($_POST['btndocapture']) && $_POST['btndocapture'] == MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_BUTTON_TEXT_FULL) {
      $captureAmt = (float)$_POST['captamt'];
      if ($captureAmt == 0) {
        $messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_INVALID_CAPTURE_AMOUNT, 'error');
        $proceedToCapture = false;
      }
    }
    // look up history on this order from ezi table
    $sql = "select * from ezi_order_mapping where order_id = :orderID";
    $sql = $db->bindVars($sql, ':orderID', $oID, 'integer');
    $zc_ppHist = $db->Execute($sql);
    if ($zc_ppHist->RecordCount() == 0) return false;
    $amount = (float)$zc_ppHist->fields['amount'];
	$capturedamount = (float)$zc_ppHist->fields['captureamount'];
	$ezi_orderid = (int)$zc_ppHist->fields['eziorder_id'];
	$balance_amt = (float)($amount - $capturedamount);
	
	
		if($captureAmt > $balance_amt)
		{
			$messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_INVALID_CAPTURE_AMOUNT, 'error');
			$proceedToCapture = false;
			return false;
		}
	
    /**
     * Submit capture request to PayPal
     */
    if ($proceedToCapture) {
		  $paramhash["ACTION"] = "CaptureOrderPayment";
		  $paramhash["AMOUNT"] = $captureAmt;
		  
			$apiresponse = $this->ezimerchant_init($ezi_orderid, $paramhash);
						$xml = simplexml_load_string($apiresponse);							
						
						$capturecontent = $xml->entry->children('http://api.ezimerchant.com/schemas/2009/');
						if($capturecontent->success == 1)
						{
								$new_order_status = 1;
								// Success, so save the results
								$sql_data_array = array('orders_id' => (int)$oID,
														'orders_status_id' => (int)$new_order_status,
														'date_added' => 'now()',
														'comments' => 'FUNDS COLLECTED. Amount: ' . $captureAmt . "\n" . $captureNote,
														'customer_notified' => 0
													 );
								zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
								$db->Execute("update " . TABLE_ORDERS  . "
											  set orders_status = '" . (int)$new_order_status . "'
											  where orders_id = '" . (int)$oID . "'");
							$add_query = '';
							if($captureType == 'Complete' || (string)$captureAmt == (string)$balance_amt)
							$add_query = ", finalcapture = 1, paymentcompleted = 'true'";
							$db->Execute("update ezi_order_mapping
										  set captureamount = captureamount + '" . (float)$captureAmt . "' ". $add_query ."
										  where order_id = '" . (int)$oID . "'");
							$messageStack->add_session(sprintf(MODULE_PAYMENT_EZIMERCHANT_TEXT_CAPT_INITIATED, $captureAmt), 'success');
							return true;
						  }
						  else
						  {
							$messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_CAPT_INITIATED_ERROR, 'error');
							return false;
						  }
    }
  }
  /**
   * Used to void a given previously-authorized transaction.  FOR FUTURE USE.
   */
  function _doVoid($oID, $note = '') {
    global $db, $messageStack;
    //$new_order_status = (int)MODULE_PAYMENT_PAYPALDP_REFUNDED_STATUS_ID;
    $voidNote = strip_tags(zen_db_input($_POST['voidnote']));
    if (isset($_POST['ordervoid']) && $_POST['ordervoid'] == MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_BUTTON_TEXT_FULL) {
      if (isset($_POST['voidconfirm']) && $_POST['voidconfirm'] == 'on') {
        $proceedToVoid = true;
      } else {
        $messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_VOID_CONFIRM_ERROR, 'error');
      }
    }
    // look up history on this order from PayPal table
    $sql = "select * from ezi_order_mapping where order_id = :orderID";
    $sql = $db->bindVars($sql, ':orderID', $oID, 'integer');
    $zc_ppHist = $db->Execute($sql);
    if ($zc_ppHist->RecordCount() == 0) return false;
    $ezi_orderid = (int)$zc_ppHist->fields['eziorder_id'];
    /**
     * Submit void request to PayPal
     */
	 
    if ($proceedToVoid) {
      $paramhash["ACTION"] = "VoidOrderPayment";
		  
		  
			$apiresponse = $this->ezimerchant_init($ezi_orderid, $paramhash);
						$xml = simplexml_load_string($apiresponse);							
						
						$voidcontent = $xml->entry->children('http://api.ezimerchant.com/schemas/2009/');
						if($voidcontent->success == 1)
						{
      //$new_order_status = ($new_order_status > 0 ? $new_order_status : 1);
	  $new_order_status = 1;
      
        // Success, so save the results
        $sql_data_array = array('orders_id' => (int)$oID,
                                'orders_status_id' => (int)$new_order_status,
                                'date_added' => 'now()',
                                'comments' => 'VOIDED. '.$voidNote,
                                'customer_notified' => 0
                             );
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_ORDERS  . "
                      set orders_status = '" . (int)$new_order_status . "'
                      where orders_id = '" . (int)$oID . "'");
		$db->Execute("update ezi_order_mapping
										  set paymentvoided = 'true'
										  where order_id = '" . (int)$oID . "'");
        $messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_VOID_INITIATED, 'success');
        return true;
      }
	  else
	  {
		$messageStack->add_session(MODULE_PAYMENT_EZIMERCHANT_TEXT_VOID_INITIATED_ERROR, 'error');
        return false;
	  }
    }
  }
	
	
	
    function install() {
      global $db, $messageStack;
      if (defined('MODULE_PAYMENT_EZIMERCHANT_STATUS')) {
        $messageStack->add_session('Ezimerchant module already installed.', 'error');
        zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=ezimerchant', 'NONSSL'));
        return 'failed';
      }
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Ezimerchant Module', 'MODULE_PAYMENT_EZIMERCHANT_STATUS', 'True', 'Do you want to enable ezimerchant checkout?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now());");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Ezimerchant MerchantID:', 'MODULE_PAYMENT_EZIMERCHANT_MERCHANT', '0', 'Please mention Ezimerchant MercahantID?', '6', '1', now());");
	  $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Ezimerchant APIKEY:', 'MODULE_PAYMENT_EZIMERCHANT_APIKEY', '0', 'Please mention Ezimerchant APIKEY?', '6', '2', now());");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_EZIMERCHANT_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '3', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now());");
	  $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_EZIMERCHANT_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
	  $db->Execute("CREATE TABLE IF NOT EXISTS `ezi_order_mapping` (
					  `eziorder_id` int(11) NOT NULL auto_increment,
					  `order_id` int(11) NOT NULL,
					  `amount` varchar(255) NOT NULL,
					  `settleamount` varchar(255) NOT NULL,
					  `paymentcompleted` varchar(255) NOT NULL,
					  `paymentvoided` varchar(255) NOT NULL,
					  `pendingreason` varchar(255) NOT NULL,
					  `refundamount` varchar(255) NOT NULL default '0',
					  `captureamount` varchar(255) NOT NULL default '0',
					  `paymentmethod` varchar(255) NOT NULL,
					  `finalcapture` int(11) NOT NULL,
					  PRIMARY KEY  (`eziorder_id`)
					) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;");
    }

    function remove() {
	  global $db;
      $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_EZIMERCHANT_STATUS', 'MODULE_PAYMENT_EZIMERCHANT_APIKEY', 'MODULE_PAYMENT_EZIMERCHANT_ORDER_STATUS_ID',  'MODULE_PAYMENT_EZIMERCHANT_MERCHANT');
    }
  }
