<?php
/**
 * ezimerchant_admin_notification.php admin display component
 *
 * @package paymentMethod
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: ezimerchant_admin_notification.php 15870 2010-04-11 02:18:43Z drbyte $
 */

  $outputStartBlock = '';
  $outputPayPal = '';
  $outputPFmain = '';
  $outputAuth = '';
  $outputCapt = '';
  $outputVoid = '';
  $outputRefund = '';
  $outputEndBlock = '';
  $outputPayMethod = '';
  $outputMessage = '';
  $output = '';

    // strip slashes in case they were added to handle apostrophes:
  foreach ($ipn->fields as $key=>$value){
    $ipn->fields[$key] = stripslashes($value);
  }
  
  if($ipn->fields['paymentmethod'])
  $outputPayMethod = '<strong>' . MODULE_PAYMENT_EZIMERCHANT_PAYMENTMETHOD_EZI . ':</strong> ' . $ipn->fields['paymentmethod'] . '<br />'. "\n";  

	
	
	
  if (($ipn->fields['paymentcompleted'] == 'true' || $ipn->fields['captureamount'] > 0) && $ipn->fields['refundamount'] == 0 && $order->info['orders_status'] < 3) {
    $sql1 = "SELECT * from orders_status_history WHERE orders_id = :orderID AND comments LIKE '%PAYMENT COMPLETED%' ";
    $sql1 = $db->bindVars($sql1, ':orderID', $zf_order_id, 'integer');
    $ipn1 = $db->Execute($sql1);
	if ($ipn1->RecordCount() > 0)
	{
	$outputMessage = '<td><div class="ezimerchant" style="color:#00CD00;font-size:12px;font-weight:bold;border: 2px solid rgb(0, 0, 0);padding:2px;">This order is ready to be processed.<br> Payment received on '.date("l j, F Y",strtotime($ipn1->fields['date_added']));
	$outputMessage .= '</div></td>';
	}
	}
	if(($ipn->fields['refundamount'] == $ipn->fields['captureamount']) && $ipn->fields['refundamount'] > 0)
	{
	$sql2 = "SELECT * from orders_status_history WHERE orders_id = :orderID ORDER BY orders_status_history_id DESC LIMIT 0,1";
    $sql2 = $db->bindVars($sql2, ':orderID', $zf_order_id, 'integer');
    $ipn2 = $db->Execute($sql2);
	if ($ipn2->RecordCount() > 0)
	{
	$outputMessage = '<td><div class="ezimerchant"  style="border: 2px solid rgb(0, 0, 0);color:#FF0000;font-size:12px;font-weight:bold;">This order has been cancelled.<br> Cancelled on '.date("l j, F Y",strtotime($ipn2->fields['date_added']));
	$outputMessage .= '</div></td>';
	}
	}
	
	
	if($ipn->fields['cardtype'] && $ipn->fields['cardowner'] && $ipn->fields['cardnumber'] && $ipn->fields['cardexpiry'])
	{
		$addatendblock = '<tr><td colspan="2"><b>Credit Card Details</b></td></tr><tr><td>Credit Card Type:</td><td>'.$ipn->fields['cardtype'].'</td></tr><tr><td>Credit Card Owner:</td><td>'.$ipn->fields['cardowner'].'</td></tr><tr><td>Credit Card Number:</td><td>'.$ipn->fields['cardnumber'].'</td></tr><tr><td>Credit Card Expires:</td><td>'.$ipn->fields['cardexpiry'].'</td></tr>';
	}
	if($ipn->fields['transactionid'] && $ipn->fields['paymenttype'] && $ipn->fields['paypaltimestamp'] && $ipn->fields['paymentcompleted'])
	{
		$addatendblock = '<tr><td colspan="2"><b>Paypal Details</b></td></tr><tr><td>TransactionID:</td><td>'.$ipn->fields['transactionid'].'</td></tr><tr><td>Payment Type:</td><td>'.$ipn->fields['paymenttype'].'</td></tr><tr><td>Payment Status:</td><td>'.$ipn->fields['paymentcompleted'].'</td></tr><tr><td>Payment Time:</td><td>'.$ipn->fields['paypaltimestamp'].'</td></tr><tr><td>Payment Amount:</td><td>'.$ipn->fields['amount'].'</td></tr>';
	}
	$buttons = '';
	$addatendblock = '';
	if (method_exists($this, '_doRefund') && ($ipn->fields['paymentcompleted'] == 'true' || $ipn->fields['captureamount'] > 0) && ($ipn->fields['refundamount'] != $ipn->fields['captureamount']) && $order->info['orders_status'] < 3) {
	$buttons .= '<button class="example8" style="float:right;">Process Refund</button>';
	$jqueryscript = 'jQuery(".example8").colorbox({width:"50%", inline:true, href:"#ezirefund"});';
	}
	
	if (method_exists($this, '_doVoid') && $ipn->fields['paymentcompleted'] == 'false' && $ipn->fields['paymentvoided'] == 'false' && $ipn->fields['pendingreason'] == 'authorization' && $ipn->fields['captureamount'] == 0) {
	$buttons .= '<button class="example6" style="float:right;">Void Fund</button>';
	$jqueryscript .= 'jQuery(".example6").colorbox({width:"50%", inline:true, href:"#ezivoid"});';
	}
	
	if (method_exists($this, '_doCapt') && $ipn->fields['paymentcompleted'] == 'false' && $ipn->fields['paymentvoided'] == 'false' && $ipn->fields['pendingreason'] == 'authorization' && $ipn->fields['captureamount'] != $ipn->fields['amount'] && $ipn->fields['finalcapture'] != 1) {
	$buttons .= '<button class="example7" style="float:right;">Capture Fund</button>&nbsp;';
	$jqueryscript .= 'jQuery(".example7").colorbox({width:"50%", inline:true, href:"#ezicapture"});';
	}
	
	
	
    $outputStartBlock .= '<td style="border: 2px solid rgb(0, 0, 0);"><table class="noprint" cellspacing="0" cellpadding="0" width="100%">'."\n";
	$outputStartBlock .= '<tr><td><table style="background-color: #D7D6CC;" width="100%"><tr><td class="ezimerchantclick" style="background: #D7D6CC url(\''.HTTP_SERVER.DIR_WS_CATALOG.DIR_WS_MODULES.'payment/ezimerchant/logo.png\') no-repeat; height:35px;width:182px;">&nbsp;</td><td>'.$buttons.'</td></tr></table></td></tr>'.$outputMessage."\n";
    $outputStartBlock .= '<tr style="background-color : #cccccc; border-style : dotted;">'."\n";
    $outputEndBlock .= '</tr>'."\n";
	$outputEndBlock .= '<tr><td><div class="ezimerchant"><table><tr><td><table>'.$addatendblock.'</table></td><td rowspan="5" style="padding-left:20px;"><br><canvas id="a" width="250" height="50"></canvas></td></table></div></td></tr>'."\n";
    $outputEndBlock .='</table></td>'."\n";

	
	
	/*if (method_exists($this, '_doRefund') && ($ipn->fields['paymentcompleted'] == 'true' || $ipn->fields['captureamount'] > 0) && ($ipn->fields['refundamount'] != $ipn->fields['captureamount']) && $order->info['orders_status'] < 3) {
    $outputRefund .= '<td><div style="display:none;"><div id="ezirefund">';
    $outputRefund .=  MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_TITLE . '<br />';
    $outputRefund .= zen_draw_form('pprefund', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=doRefund', 'post', '', true) . zen_hide_session_id();
    if ($ipn->fields['refundamount'] == 0) {
    // full refund (if partially refund, then this button is not shown)
      $outputRefund .= MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_FULL;
      $outputRefund .= '<br /><input type="submit" name="fullrefund" value="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_FULL . '" title="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_FULL . '" />' . ' ' . MODULE_PAYMENT_EZIMERCHANT_TEXT_REFUND_FULL_CONFIRM_CHECK . zen_draw_checkbox_field('reffullconfirm', '', false) . '<br />';
      $outputRefund .= MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_TEXT_FULL_OR;
    } 
    //partial refund - input field
    $outputRefund .= MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_PARTIAL_TEXT . ' ' . zen_draw_input_field('refamt', 'enter amount', 'length="8"');
    $outputRefund .= '<input type="submit" name="partialrefund" value="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_PARTIAL . '" title="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_PARTIAL . '" /><br />';
    //comment field
    $outputRefund .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_TEXT_COMMENTS . '<br />' . zen_draw_textarea_field('refnote', 'soft', '50', '3', MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_DEFAULT_MESSAGE);
    //message text
    $outputRefund .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_SUFFIX;
    $outputRefund .= '</form></div></div></td>';
  }*/

  if (method_exists($this, '_doRefund') && ($ipn->fields['paymentcompleted'] == 'true' || $ipn->fields['captureamount'] > 0) && ($ipn->fields['refundamount'] != $ipn->fields['captureamount']) && $order->info['orders_status'] < 3) {
    $outputRefund .= '<td><div style="display:none;"><div id="ezirefund"><table class="noprint">'."\n";
    $outputRefund .= '<tr>'."\n";
    $outputRefund .= '<td class="main">' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_TITLE . '<br />'. "\n";
    $outputRefund .= zen_draw_form('pprefund', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=doRefund', 'post', '', true) . zen_hide_session_id();
    if ($ipn->fields['refundamount'] == 0) {
    // full refund (if partially refund, then this button is not shown)
      $outputRefund .= MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_FULL;
      $outputRefund .= '<br /><input type="submit" name="fullrefund" value="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_FULL . '" title="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_FULL . '" />' . ' ' . MODULE_PAYMENT_EZIMERCHANT_TEXT_REFUND_FULL_CONFIRM_CHECK . zen_draw_checkbox_field('reffullconfirm', '', false) . '<br />';
      $outputRefund .= MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_TEXT_FULL_OR;
    } 
    //partial refund - input field
    $outputRefund .= MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_PARTIAL_TEXT . ' ' . zen_draw_input_field('refamt', 'enter amount', 'length="8"');
    $outputRefund .= '<input type="submit" name="partialrefund" value="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_PARTIAL . '" title="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_BUTTON_TEXT_PARTIAL . '" /><br />';
    //comment field
    $outputRefund .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_TEXT_COMMENTS . '<br />' . zen_draw_textarea_field('refnote', 'soft', '50', '3', MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_DEFAULT_MESSAGE);
    //message text
    $outputRefund .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_REFUND_SUFFIX;
    $outputRefund .= '</form>';
    $outputRefund .='</td></tr></table></div></div></td>'."\n";
  }

  /*if (method_exists($this, '_doAuth') && $ipn->fields['paymentcompleted'] == 'false' && $ipn->fields['pendingreason'] == 'authorization') {
    $outputAuth .= '<td valign="top"><table class="noprint">'."\n";
    $outputAuth .= '<tr style="background-color : #eeeeee; border-style : dotted;">'."\n";
    $outputAuth .= '<td class="main">' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_AUTH_TITLE . '<br />'. "\n";
    $outputAuth .= zen_draw_form('ppauth', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=doAuth', 'post', '', true);
    //partial auth - input field
    $outputAuth .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_AUTH_PARTIAL_TEXT . ' ' . zen_draw_input_field('authamt', 'enter amount', 'length="8"') . zen_hide_session_id();
    $outputAuth .= '<input type="submit" name="orderauth" value="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_AUTH_BUTTON_TEXT_PARTIAL . '" title="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_AUTH_BUTTON_TEXT_PARTIAL . '" />' . MODULE_PAYMENT_EZIMERCHANT_TEXT_AUTH_FULL_CONFIRM_CHECK . zen_draw_checkbox_field('authconfirm', '', false) . '<br />';
    //message text
    $outputAuth .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_AUTH_SUFFIX;
    $outputAuth .= '</form>';
    $outputAuth .='</td></tr></table></td>'."\n";
  }*/

  if (method_exists($this, '_doCapt') && $ipn->fields['paymentcompleted'] == 'false' && $ipn->fields['paymentvoided'] == 'false' && $ipn->fields['pendingreason'] == 'authorization' && $ipn->fields['captureamount'] != $ipn->fields['amount'] && $ipn->fields['finalcapture'] != 1) {
    $outputCapt .= '<td class="ezimerchant" valign="top"><div style="display:none;"><div id="ezicapture"><table class="noprint">'."\n";
    $outputCapt .= '<tr>'."\n";
    $outputCapt .= '<td class="main">' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_TITLE . '<br />'. "\n";
    $outputCapt .= zen_draw_form('ppcapture', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=doCapture', 'post', '', true) . zen_hide_session_id();
    $outputCapt .= MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_FULL;
    $outputCapt .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_AMOUNT_TEXT . ' ' . zen_draw_input_field('captamt', 'enter amount', 'length="8"');
    $outputCapt .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_FINAL_TEXT . ' ' . zen_draw_checkbox_field('captfinal', '', true) . '<br />';
    $outputCapt .= '<input type="submit" name="btndocapture" value="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_BUTTON_TEXT_FULL . '" title="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_BUTTON_TEXT_FULL . '" />' . ' ' . MODULE_PAYMENT_EZIMERCHANT_TEXT_REFUND_FULL_CONFIRM_CHECK . zen_draw_checkbox_field('captfullconfirm', '', false);
    //comment field
    $outputCapt .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_TEXT_COMMENTS . '<br />' . zen_draw_textarea_field('captnote', 'soft', '50', '2', MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_DEFAULT_MESSAGE);
    //message text
    $outputCapt .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_CAPTURE_SUFFIX;
    $outputCapt .= '</form>';
    $outputCapt .='</td></tr></table></div></div></td>'."\n";
  }

  if (method_exists($this, '_doVoid') && $ipn->fields['paymentcompleted'] == 'false' && $ipn->fields['paymentvoided'] == 'false' && $ipn->fields['pendingreason'] == 'authorization' && $ipn->fields['captureamount'] == 0) {
    $outputVoid .= '<td class="ezimerchant" valign="top"><div style="display:none;"><div id="ezivoid"><table class="noprint">'."\n";
    $outputVoid .= '<tr>'."\n";
    $outputVoid .= '<td class="main">' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_TITLE . '<br />'. "\n";
    $outputVoid .= zen_draw_form('ppvoid', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=doVoid', 'post', '', true) . zen_hide_session_id();
    //$outputVoid .= MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID . '<br />' . zen_draw_input_field('voidauthid', 'enter auth ID', 'length="8"');
    $outputVoid .= '<input type="submit" name="ordervoid" value="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_BUTTON_TEXT_FULL . '" title="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_BUTTON_TEXT_FULL . '" />' . ' ' . MODULE_PAYMENT_EZIMERCHANT_TEXT_VOID_CONFIRM_CHECK . zen_draw_checkbox_field('voidconfirm', '', false);
    //comment field
    $outputVoid .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_TEXT_COMMENTS . '<br />' . zen_draw_textarea_field('voidnote', 'soft', '50', '3', MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_DEFAULT_MESSAGE);
    //message text
    $outputVoid .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_SUFFIX;
    $outputVoid .= '</form>';
    $outputVoid .='</td></tr></table></div></div></td>'."\n";
  }


if($ipn->fields['riskscore'] != '' || $ipn->fields['riskscore'] != '0')
{
	$riskscore = (float)$ipn->fields['riskscore']*20;
}
else
$riskscore = 0;
if($buttons != '' || $addatendblock != '')
{
// prepare output based on suitable content components
  $output = '<!-- BOF: ezi admin transaction processing tools -->';
  
  $output .= $outputStartBlock;
  //$output .= $outputPayMethod;


//debug
//$output .= '<pre>' . print_r($response, true) . '</pre>';

  
       
	   $output .= $outputCapt;
       $output .= $outputVoid;
       $output .= $outputRefund;
  
  
  
  $output .= $outputEndBlock;
  $output .= '
  <link media="screen" rel="stylesheet" href="'.HTTP_SERVER.DIR_WS_CATALOG.DIR_WS_MODULES.'payment/ezimerchant/colorbox.css" />
  <script type="text/javascript">
			if(typeof(jQuery) == "undefined")
			{
				 var scriptElement = document.createElement("script");
				 scriptElement.src = "https://ajax.googleapis.com/ajax/libs/jquery/1.6.3/jquery.js";
				 scriptElement.onload = initAdmin;
				 document.body.appendChild(scriptElement);

				 var scriptElementcolor = document.createElement("script");
				 scriptElementcolor .src = "'.HTTP_SERVER.DIR_WS_CATALOG.DIR_WS_MODULES.'payment/ezimerchant/colorbox.js";
				 document.body.appendChild(scriptElementcolor);
				 
			}
			else
			{
				 jQuery(initAdmin);
			}

			function initAdmin() { 
				$(document).ready(function () {
					jQuery(".ezimerchant").slideToggle("fast");
					var tds = document.getElementsByTagName("td");
					var counter = tds.length;
					while(counter--) {
					if(tds[counter].innerHTML == "Ezimerchant")
						tds[counter].innerHTML = \''.$ipn->fields["paymentmethod"].'\';
						// if you want to strip html and care about text only, use innerText instead.

					}
					'.$jqueryscript.'
				});
				jQuery(".ezimerchantclick").click(function () {
					  jQuery(".ezimerchant").slideToggle("fast");
				});
			 }';
	if($riskscore > 0)
	{
	$output .= '
			 var addpar = '.$riskscore.';
			 var b_canvas = document.getElementById("a");
			 var b_context = b_canvas.getContext("2d");
			 b_context.fillRect(10, 25, 200, 3);
			 b_context.fillStyle = "red";  
			 b_context.beginPath();
			 b_context.moveTo(3+addpar, 35);
			 b_context.lineTo(23+addpar, 35);
			 b_context.lineTo(13+addpar, 20);
			 b_context.closePath();  
			 b_context.fill();  
			 b_context.strokeText("Fraud Score", 10, 10); 			 
			 b_context.strokeText("1", 10, 20); 
			 b_context.strokeText("5", 110, 20); 
			 b_context.strokeText("10", 205, 20);'; }
	$output .= '			 
			</script>';
  $output .= '<!-- EOF: ezi admin transaction processing tools -->';
}

