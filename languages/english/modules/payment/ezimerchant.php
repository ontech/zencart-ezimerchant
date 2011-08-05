<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
// $Id: ezimerchant.php 2011-05-20 00:00:00z drbyte $
//

  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_TITLE', 'Ezimerchant');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_DESCRIPTION', 'Ezimerchant will make all the checkout procedure in ei site and will send the cofirmation email. Please set the registered merchant ID and APIKEY.' . 'Your order will not ship until we receive payment.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_EMAIL_FOOTER', "\n\n" . 'Your order will ship once we receive payment.');
  
  
  // These are used for displaying raw transaction details in the Admin area:
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_FIRST_NAME', 'First Name:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_LAST_NAME', 'Last Name:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_BUSINESS_NAME', 'Business Name:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_ADDRESS_NAME', 'Address Name:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_ADDRESS_STREET', 'Address Street:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_ADDRESS_CITY', 'Address City:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_ADDRESS_STATE', 'Address State:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_ADDRESS_ZIP', 'Address Zip:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_ADDRESS_COUNTRY', 'Address Country:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_EMAIL_ADDRESS', 'Payer Email:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_EBAY_ID', 'Ebay ID:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_PAYER_ID', 'Payer ID:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_PAYER_STATUS', 'Payer Status:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_ADDRESS_STATUS', 'Address Status:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_PAYMENT_TYPE', 'Payment Type:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_PAYMENT_STATUS', 'Payment Status:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_PENDING_REASON', 'Pending Reason:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_INVOICE', 'Invoice:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_PAYMENT_DATE', 'Payment Date:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CURRENCY', 'Currency:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_GROSS_AMOUNT', 'Gross Amount:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_PAYMENT_FEE', 'Payment Fee:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_EXCHANGE_RATE', 'Exchange Rate:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CART_ITEMS', 'Cart items:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_TXN_TYPE', 'Trans. Type:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_TXN_ID', 'Trans. ID:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_PARENT_TXN_ID', 'Parent Trans. ID:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_TITLE', '<strong>Order Refunds</strong>');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_FULL', 'If you wish to refund this order in its entirety, click here:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_FULL', 'Do Full Refund');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_PARTIAL', 'Do Partial Refund');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_TEXT_FULL_OR', '<br />... or enter the partial ');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_PAYFLOW_TEXT', 'Enter the ');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_PARTIAL_TEXT', 'refund amount here and click on Partial Refund');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_SUFFIX', '*A Full refund may not be issued after a Partial refund has been applied.<br />*Multiple Partial refunds are permitted up to the remaining unrefunded balance.');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_TEXT_COMMENTS', '<strong>Note to display to customer:</strong>');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_DEFAULT_MESSAGE', 'Refunded by store administrator.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_REFUND_FULL_CONFIRM_CHECK','Confirm: ');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_VOID_CONFIRM_CHECK', 'Confirm');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_COMMENTS', 'System Comments: ');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_PROTECTIONELIG', 'Protection Eligibility:');

  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_AUTH_TITLE', '<strong>Order Authorizations</strong>');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_AUTH_PARTIAL_TEXT', 'If you wish to authorize part of this order, enter the amount  here:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_AUTH_BUTTON_TEXT_PARTIAL', 'Do Authorization');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_AUTH_SUFFIX', '');

  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_TITLE', '<strong>Capturing Authorizations</strong>');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_FULL', 'If you wish to capture all or part of the outstanding authorized amounts for this order, enter the Capture Amount and select whether this is the final capture for this order.  Check the confirm box before submitting your Capture request.<br />');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_BUTTON_TEXT_FULL', 'Do Capture');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_AMOUNT_TEXT', 'Amount to Capture:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_FINAL_TEXT', 'Is this the final capture?');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_SUFFIX', '');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_TEXT_COMMENTS', '<strong>Note to display to customer:</strong>');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_DEFAULT_MESSAGE', 'Thank you for your order.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_CAPTURE_FULL_CONFIRM_CHECK','Confirm: ');

  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_TITLE', '<strong>Voiding Order Authorizations</strong>');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID', 'If you wish to void an authorization, enter the authorization ID here, and confirm:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_TEXT_COMMENTS', '<strong>Note to display to customer:</strong>');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_DEFAULT_MESSAGE', 'Thank you for your patronage. Please come again.');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_BUTTON_TEXT_FULL', 'Do Void');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_SUFFIX', '');

  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_TRANSSTATE', 'Trans. State:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_AUTHCODE', 'Auth. Code:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_AVSADDR', 'AVS Address match:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_AVSZIP', 'AVS ZIP match:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_CVV2MATCH', 'CVV2 match:');
  define('MODULE_PAYMENT_EZIMERCHANT_ENTRY_DAYSTOSETTLE', 'Days to Settle:');
  
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_REFUND_INITIATED', 'Refund for %s initiated. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_REFUND_ERROR', 'Refund is not done because of some network problem. Please try after some time.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_CAPT_INITIATED', 'Capture for %s initiated. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_CAPT_INITIATED_ERROR', 'Capture is not done because of some network problem. Please try after some time.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_VOID_INITIATED', 'Void is initiated. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_VOID_INITIATED_ERROR', 'Void is not done because of some network problem. Please try after some time.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_INVALID_REFUND_AMOUNT', 'Invalid refund amount. Please try again.');
  
  
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_REFUND_FULL_CONFIRM_ERROR', 'You requested a full refund but did not check the Confirm box to verify your intent.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_INVALID_AUTH_AMOUNT', 'You requested an authorization but did not specify an amount.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_INVALID_CAPTURE_AMOUNT', 'You requested a capture but did not specify an amount.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_VOID_CONFIRM_CHECK', 'Confirm');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_VOID_CONFIRM_ERROR', 'You requested to void a transaction but did not check the Confirm box to verify your intent.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_AUTH_FULL_CONFIRM_CHECK', 'Confirm');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_AUTH_CONFIRM_ERROR', 'You requested an authorization but did not check the Confirm box to verify your intent.');
  define('MODULE_PAYMENT_EZIMERCHANT_TEXT_CAPTURE_FULL_CONFIRM_ERROR', 'You requested funds-Capture but did not check the Confirm box to verify your intent.');
?>
