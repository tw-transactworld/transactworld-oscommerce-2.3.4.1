<?php
/**
 * Developed by TransactWorld.com
 * Version: 1.0.0
 * Do not change any code with permission of TransactWorld.com
 */

class paymentz{
    var $code, $title, $description, $enabled;
    function paymentz()
    {

        global $order;
        $this->code =defined('MODULE_PAYMENT_PAYMENTZ_TOTYPE') ? MODULE_PAYMENT_PAYMENTZ_TOTYPE : 'paymentz';
        $this->codeVersion = '1.0.0';
        $this->title = MODULE_PAYMENT_PAYMENTZ_TEXT_TITLE;

        $this->enabled = defined('MODULE_PAYMENT_PAYMENTZ_STATUS') && (MODULE_PAYMENT_PAYMENTZ_STATUS == 'True') ? true : false;
        if (IS_ADMIN_FLAG === true && (MODULE_PAYMENT_TRANSACTWORLD_MERCHANT_ID == 'TransactWorldMerchantID' || MODULE_PAYMENT_TRANSACTWORLD_MERCHANT_ID == '')) $this->title .= '<span class="alert"> (not configured - needs MerchantID)</span>';
        $this->description = MODULE_PAYMENT_PAYMENTZ_TEXT_DESCRIPTION;
        $this->sort_order = defined('MODULE_PAYMENT_PAYMENTZ_SORT_ORDER') ? MODULE_PAYMENT_PAYMENTZ_SORT_ORDER : 0;
        $this->title_admin = MODULE_PAYMENT_PAYMENTZ_TITLE;
        $this->description_admin = MODULE_PAYMENT_TRANSACTWORLD_DESCRIPTION;
        $this->order_status = defined('MODULE_PAYMENT_PAYMENTZ_ORDER_STATUS_ID') && ((int)MODULE_PAYMENT_PAYMENTZ_ORDER_STATUS_ID > 0) ? (int)MODULE_PAYMENT_PAYMENTZ_ORDER_STATUS_ID : 0;
      if (is_object($order)) $this->update_status();

        if (MODULE_PAYMENT_PAYMENTZ_MODE == 'True') {
            //$this->form_action_url = 'https://staging.paymentz.com/transaction/PayProcessController';
			$this->form_action_url = 'https://' . MODULE_PAYMENT_PAYMENTZ_LIVE_URL . '/transaction/PayProcessController';
             
            //$this->form_action_url = 'https:' . MODULE_PAYMENT_PAYMENTZ_TEST_URL . '/PayProcessController';
        } else {
           // $this->form_action_url = 'https://secure.paymentz.com/icici/servlet/PayProcessController';
            $this->form_action_url = 'https://' . MODULE_PAYMENT_PAYMENTZ_TEST_URL . '/transaction/PayProcessController';
           // $this->form_action_url = 'https:' . MODULE_PAYMENT_PAYMENTZ_LIVE_URL . '/PayProcessController';
        }
        $this->email_footer = MODULE_PAYMENT_PAYMENTZ_TEXT_EMAIL_FOOTER;
    }


    function update_status() {

        global $order;
        if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAYMENTZ_ZONE > 0) ) {
            $check_flag = false;
            $checking_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYMENTZ_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");


            while ($check = mysqli_fetch_array($checking_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->delivery['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }

// disable the module if the order only contains virtual products
        if ($this->enabled == true) {
            if ($order->content_type == 'virtual') {
                $this->enabled = false;
            }
        }
    }

    function get_zone_code($zone_name){
      $zone_query = tep_db_query("select zone_code from " . TABLE_ZONES . " where  zone_name = '" . $zone_name . "'");
      $zone_code =mysqli_fetch_array($zone_query);
      return $zone_code;
    }


    function process_button() {
        global $order, $currencies,$customer_id, $MerchantId, $totype, $AccessCode, $CurrencyCode, $Amount, $OrderId, $Url, $WorkingKey, $Checksum,$zone_code;

        $MerchantId = MODULE_PAYMENT_TRANSACTWORLD_MERCHANT_ID;
		$totype     = MODULE_PAYMENT_PAYMENTZ_PARTNER_NAME;
        $WorkingKey = MODULE_PAYMENT_PAYMENTZ_WORKING_KEY;
        $AccessCode = MODULE_PAYMENT_PAYMENTZ_ACCESS_CODE;
        $CurrencyCode = MODULE_PAYMENT_PAYMENTZ_CURRENCY;

        //$totype = "Paymentz";
        //$totype = $totype;
        $currency = $order->info['currency'];
        $Amount = $order->info['total'];
        $OrderId = $customer_id . '-' . date('Ymdhis');
        $Url = tep_href_link(FILENAME_CHECKOUT_PROCESS,'','SSL',true,false);
        $pattern='http://www';
        if(!(stristr($pattern,$Url)))
        str_replace('http://', $pattern, $Url);
        $customamount = number_format(($order->info['total'] * $currencies->get_value($currency)), $currencies->get_decimal_places($currency));
        $str = "$MerchantId|$totype|$customamount|$OrderId|$Url|$WorkingKey";
        $Checksum = md5($str);
        $zone_coding  = $this->get_zone_code($order->billing['state']);
        $zone_code=$zone_coding['zone_code'];

        for ($i=0; $i<sizeof($order->products); $i++)
        {
            $quantity = $order->products[$i]['qty'];
            $products = $order->products[$i]['name'];
            $gg .= $quantity."-".$products." ";

            if ( (isset($order->products[$i]['attributes'])) && (sizeof($order->products[$i]['attributes']) > 0) )
            {
                for ($j=0; $j<sizeof($order->products[$i]['attributes']); $j++)
                {
                    $attrib = $order->products[$i]['attributes'][$j]['option'];
                    $attribs = $order->products[$i]['attributes'][$j]['value'];

                    $gg .=  "$attrib - $attribs";
                }
            }
        }
$ggs=str_replace(":","-",$gg);
        $orderdescription = $ggs;

        $process_button_string = tep_draw_hidden_field('toid', $MerchantId) .
            tep_draw_hidden_field('totype', $totype) .
            tep_draw_hidden_field('key', $WorkingKey) .
            tep_draw_hidden_field('amount', $customamount) .
            tep_draw_hidden_field('TMPL_AMOUNT', $customamount) .
            tep_draw_hidden_field('description', $OrderId) .
            tep_draw_hidden_field('orderdescription', $orderdescription) .
            tep_draw_hidden_field('TMPL_CURRENCY', $order->info['currency']) .
            tep_draw_hidden_field('fromtype', 'icicicredit').
            tep_draw_hidden_field('TMPL_street', $order->billing['street_address']) .
            tep_draw_hidden_field('TMPL_city', $order->billing['city']) .
            tep_draw_hidden_field('TMPL_state', $zone_code) .
            tep_draw_hidden_field('TMPL_zip', $order->billing['postcode']) .
            tep_draw_hidden_field('TMPL_telnocc', '011').
            tep_draw_hidden_field('TMPL_telno', $order->customer['telephone']).
            tep_draw_hidden_field('TMPL_' . $order->billing['country']['iso_code_2'], 'selected') .
            tep_draw_hidden_field('TMPL_emailaddr', $order->customer['email_address']) .
            tep_draw_hidden_field('checksum',$Checksum) .
            tep_draw_hidden_field('redirecturl',$Url).
            tep_draw_hidden_field('paymenttype',"").
            tep_draw_hidden_field('cardtype',"");
        return $process_button_string;
    }

    function before_process() {

        global $_REQUEST,$WorkingKey,$sum;
        $key = MODULE_PAYMENT_PAYMENTZ_WORKING_KEY;
        $trackingid=$_REQUEST['trackingid'];
        $amount = $_REQUEST['amount'];
        $desc = $_REQUEST['desc'];
        $newchecksum = $_REQUEST['checksum'];
        $status = $_REQUEST['status'];
        $str = "$trackingid|$desc|$amount|$status|$key";
        $sum = md5($str);

        if($sum == $newchecksum)
            $Checksum = 'true' ;
        else
            $Checksum = 'false';

        if($Checksum != 'true'){
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'credit_class_error=' . urlencode(MODULE_PAYMENT_PAYMENTZ_ALERT_ERROR_MESSAGE), 'SSL',true, false));
        }

        if($Checksum =='true' && $status == 'N'){
			 tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'credit_class_error=' . urlencode(MODULE_PAYMENT_PAYMENTZ_FAILED_ERROR_MESSAGE), 'SSL',true, false));
            return false;
        }
    }

    function after_process() {
        return false;
    }


    function javascript_validation() {

        return false;
    }

    function selection()
    {
        return array('id' => $this->code,
            'module' => $this->title,
        );
    }

    function pre_confirmation_check() {

        return false;
    }

    function confirmation() {
        return false;
    }

    function output_error()
    {
        global $HTTP_GET_VARS;


        $output_error_string = '<table border="0" cellspacing="0" cellpadding="0" width="100%">' . "\n" .
            '  <tr>' . "\n" .
            '    <td class="main">&nbsp;<font color="#FF0000"><b>' . MODULE_PAYMENT_PAYMENTZ_TEXT_ERROR . '</b></font><br>&nbsp;' . MODULE_PAYMENT_PAYMENTZ_TEXT_ERROR_MESSAGE . '&nbsp;</td>' . "\n" .
            '  </tr>' . "\n" .
            '</table>' . "\n";

        return $output_error_string;
    }

    function check() {
     global $db;
        if (!isset($this->_check)) {
            $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYMENTZ_STATUS'");
            $this->_check = mysqli_num_rows($check_query);
        }

        return $this->_check;
    }

    function install() {
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Title :', 'MODULE_PAYMENT_PAYMENTZ_TITLE', 'Title', 'The Title to use for the TransactWorld service', '6', '1', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Description :', 'MODULE_PAYMENT_TRANSACTWORLD_DESCRIPTION', 'Description', 'The description need to give for the TransactWorld service', '6', '2', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Partner Name', 'MODULE_PAYMENT_PAYMENTZ_PARTNER_NAME', '', 'Enter Your Partner ID', '6', '2', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,configuration_description,configuration_group_id, sort_order, date_added, set_function) values ('Enable TRANSACTWORLD Module','MODULE_PAYMENT_PAYMENTZ_STATUS','True','Do you want to accept TRANSACTWORLD payments?','6', '0', now(),'tep_cfg_select_option(array(\'True\', \'False\'),')");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant Id', 'MODULE_PAYMENT_TRANSACTWORLD_MERCHANT_ID', 'TransactWorldMerchantID', 'The Merchant Id to use for the TransactWorld service', '6', '3', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Test Url', 'MODULE_PAYMENT_PAYMENTZ_TEST_URL', 'Test Url', 'Enter Test Url', '6', '3', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Live Url', 'MODULE_PAYMENT_PAYMENTZ_LIVE_URL', 'Live Url', 'Enter Live Url', '6', '3', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('WorkingKey', 'MODULE_PAYMENT_PAYMENTZ_WORKING_KEY', '', 'Put in the 32 bit alphanumeric key. To get this key, Login to your TransactWorld Merchant Account and click Settings -> Generate Key', '6', '2', now())");
        //tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function,date_added) values ('Transaction Mode', 'MODULE_PAYMENT_PAYMENTZ_MODE', 'Test', 'Transaction mode used for processing orders', '6', '3', 'tep_cfg_select_option(array(\'Test\', \'Live\'), ', now())");
        //tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Transaction Mode', 'MODULE_PAYMENT_PAYMENTZ_MODE', '', 'Is live mode [N/Y]', '6', '2', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value,configuration_description,configuration_group_id, sort_order, date_added, set_function) values ('Is live mode','MODULE_PAYMENT_PAYMENTZ_MODE','False','Is live mode activation','6', '0', now(),'tep_cfg_select_option(array(\'True\', \'False\'),')");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PAYMENTZ_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '5', now())");
//        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Payment Logo', 'MODULE_PAYMENT_PAYMENTZ_TEXT_LOGO','', 'Name of image will be the logo image for paymentz payment method.', '6', '6','tep_draw_browse_image(', now())");

        //tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title,configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order,set_function, date_added) values ('Payment Logo','MODULE_PAYMENT_PAYMENTZ_LOGO','','Name of image will be the logo image for paymentz payment method.', '6', '6','tep_upload_file(',now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAYMENTZ_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '6', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PAYMENTZ_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '7', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    }

    function remove() {
        $keys = '';
        $keys_array = $this->keys();

        for ($i=0; $i<sizeof($keys_array); $i++) {
            $keys .= "'" . $keys_array[$i] . "',";
        }
        $keys = substr($keys, 0, -1);

        tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
        return array('MODULE_PAYMENT_PAYMENTZ_TITLE','MODULE_PAYMENT_TRANSACTWORLD_DESCRIPTION','MODULE_PAYMENT_PAYMENTZ_STATUS',  'MODULE_PAYMENT_TRANSACTWORLD_MERCHANT_ID', 'MODULE_PAYMENT_PAYMENTZ_TEST_URL', 'MODULE_PAYMENT_PAYMENTZ_LIVE_URL', 'MODULE_PAYMENT_PAYMENTZ_WORKING_KEY', 'MODULE_PAYMENT_PAYMENTZ_ORDER_STATUS_ID', 'MODULE_PAYMENT_PAYMENTZ_PARTNER_NAME','MODULE_PAYMENT_PAYMENTZ_MODE', 'MODULE_PAYMENT_PAYMENTZ_SORT_ORDER', 'MODULE_PAYMENT_PAYMENTZ_ZONE');
    }


}
?>





