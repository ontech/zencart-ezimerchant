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
  $output = '';

  // strip slashes in case they were added to handle apostrophes:
  foreach ($ipn->fields as $key=>$value){
    $ipn->fields[$key] = stripslashes($value);
  }
  
  if($ipn->fields['paymentmethod'])
  $outputPayMethod = '<strong>' . MODULE_PAYMENT_EZIMERCHANT_PAYMENTMETHOD_EZI . ':</strong> ' . $ipn->fields['paymentmethod'] . '<br />'. "\n";  

    $outputStartBlock .= '<td><table class="noprint">'."\n";
    $outputStartBlock .= '<tr style="background-color : #cccccc; border-style : dotted;">'."\n";
    $outputEndBlock .= '</tr>'."\n";
    $outputEndBlock .='</table></td>'."\n";


  if (method_exists($this, '_doRefund') && ($ipn->fields['paymentcompleted'] == 'true' || $ipn->fields['captureamount'] > 0) && ($ipn->fields['refundamount'] != $ipn->fields['captureamount']) && $order->info['orders_status'] < 3) {
    $outputRefund .= '<td><table class="noprint">'."\n";
    $outputRefund .= '<tr style="background-color : #eeeeee; border-style : dotted;">'."\n";
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
    $outputRefund .='</td></tr></table></td>'."\n";
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
    $outputCapt .= '<td valign="top"><table class="noprint">'."\n";
    $outputCapt .= '<tr style="background-color : #eeeeee; border-style : dotted;">'."\n";
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
    $outputCapt .='</td></tr></table></td>'."\n";
  }

  if (method_exists($this, '_doVoid') && $ipn->fields['paymentcompleted'] == 'false' && $ipn->fields['paymentvoided'] == 'false' && $ipn->fields['pendingreason'] == 'authorization' && $ipn->fields['captureamount'] == 0) {
    $outputVoid .= '<td valign="top"><table class="noprint">'."\n";
    $outputVoid .= '<tr style="background-color : #eeeeee; border-style : dotted;">'."\n";
    $outputVoid .= '<td class="main">' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_TITLE . '<br />'. "\n";
    $outputVoid .= zen_draw_form('ppvoid', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=doVoid', 'post', '', true) . zen_hide_session_id();
    //$outputVoid .= MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID . '<br />' . zen_draw_input_field('voidauthid', 'enter auth ID', 'length="8"');
    $outputVoid .= '<input type="submit" name="ordervoid" value="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_BUTTON_TEXT_FULL . '" title="' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_BUTTON_TEXT_FULL . '" />' . ' ' . MODULE_PAYMENT_EZIMERCHANT_TEXT_VOID_CONFIRM_CHECK . zen_draw_checkbox_field('voidconfirm', '', false);
    //comment field
    $outputVoid .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_TEXT_COMMENTS . '<br />' . zen_draw_textarea_field('voidnote', 'soft', '50', '3', MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_DEFAULT_MESSAGE);
    //message text
    $outputVoid .= '<br />' . MODULE_PAYMENT_EZIMERCHANT_ENTRY_VOID_SUFFIX;
    $outputVoid .= '</form>';
    $outputVoid .='</td></tr></table></td>'."\n";
  }




// prepare output based on suitable content components
  $output = '<!-- BOF: ezi admin transaction processing tools -->';
  
  $output .= $outputStartBlock;
  $output .= $outputPayMethod;
//debug
//$output .= '<pre>' . print_r($response, true) . '</pre>';

  
       $output .= $outputRefund;
	   $output .= $outputCapt;
       $output .= $outputVoid;
      
  
  
  $output .= $outputEndBlock;
  $output .= $outputEndBlock;
  $output .= '<!-- EOF: ezi admin transaction processing tools -->';
