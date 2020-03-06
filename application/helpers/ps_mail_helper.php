<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Send Booking Request Email to hotel
 * @param  [type] $booking_id [description]
 * @return [type]             [description]
 */
if ( !function_exists( 'send_transaction_order_emails' )) {

	function send_transaction_order_emails( $trans_header_id, $to_who = "", $subject = "" )
	{
		// get ci instance
		$CI =& get_instance();
		
		$sender_name = $CI->config->item( 'sender_name' );

		$trans_header_obj = $CI->Transactionheader->get_one($trans_header_id);
		$shop_name = $CI->Shop->get_one($trans_header_obj->shop_id)->name;

		$shop_email = $CI->Shop->get_one($trans_header_obj->shop_id)->email;

		$trans_currency = $CI->Shop->get_one($trans_header_obj->shop_id)->currency_symbol;

		$user_email =  $CI->User->get_one($trans_header_obj->added_user_id)->user_email;

		$user_name =  $CI->User->get_one($trans_header_obj->added_user_id)->user_name;

		$is_shipping_zone = $trans_header_obj->is_zone_shipping;

		if ($is_shipping_zone == '1') {
			$is_zone_shipping = "Yes";
		} else if ($is_shipping_zone == '0') {
			$is_zone_shipping = "No";
		}

		//bank info 
		$bank_account = $CI->Shop->get_one($trans_header_obj->shop_id)->bank_account;
		$bank_name = $CI->Shop->get_one($trans_header_obj->shop_id)->bank_name;
		$bank_code = $CI->Shop->get_one($trans_header_obj->shop_id)->bank_code;
		$branch_code = $CI->Shop->get_one($trans_header_obj->shop_id)->branch_code;
		$swift_code = $CI->Shop->get_one($trans_header_obj->shop_id)->swift_code;

		$bank_info  = "Bank Account : " . $bank_account . " <br> " .
					"Bank Name : " . $bank_name . " <br> " .
					"Bank Code : " . $bank_code . " <br> " .
					"Branch Code : " . $branch_code . " <br> " .
		            "Swift Code : " . $swift_code . " <br><br> " ;

		//For Payment Method 
		$payment_info = "";
		if($trans_header_obj->payment_method == "COD") {
			$payment_info = "Payment Method : Cash On Delivery";
		} else if($trans_header_obj->payment_method == "PAYPAL") {
			$payment_info = "Payment Method : Paypal";
		} else if($trans_header_obj->payment_method == "STRIPE") {
			$payment_info = "Payment Method : Stripe";
		} else if($trans_header_obj->payment_method == "BANK") {
			$payment_info = "Payment Method : Bank Transfer <br>" . $bank_info;
		}


		$conds['transactions_header_id'] = $trans_header_obj->id;

		$trans_details_obj = $CI->Transactiondetail->get_all_by($conds)->result();

		//For Transaction Detials
		for($i=0;$i<count($trans_details_obj);$i++) 
		{
				if($trans_details_obj[$i]->product_attribute_id != "") {
					

					$att_name_info  = explode("#", $trans_details_obj[$i]->product_attribute_name);
					
					$att_price_info = explode("#", $trans_details_obj[$i]->product_attribute_price);

					$att_info_str = "";
					$att_flag = 0;
					if( count($att_name_info[0]) > 0 ) {

						//loop attribute info
						for($k = 0; $k < count($att_name_info); $k++) {
							
							if($att_name_info[$k] != "") {
								$att_flag = 1;
								$att_info_str .= $att_name_info[$k] . " : " . $att_price_info[$k] . "(". $trans_currency ."),";

							}
						}


					} else {
						$att_info_str = "";
					}

					$att_info_str = rtrim($att_info_str, ","); 

					


					$order_items .= $i + 1 .". " . $trans_details_obj[$i]->product_name . 
					" (Price : " .   $trans_details_obj[$i]->original_price  . html_entity_decode($trans_currency) . 
					", QTY : " . $trans_details_obj[$i]->qty . ", Unit : " . $trans_details_obj[$i]->product_measurement .' ' . $trans_details_obj[$i]->product_unit . ", Shipping Cost : " . $trans_details_obj[$i]->shipping_cost . html_entity_decode($trans_currency) . ") {". $att_info_str ."}<br>";





				} else {
					
					$order_items .= $i + 1 .". " . $trans_details_obj[$i]->product_name . 
					" (Price : " .   $trans_details_obj[$i]->original_price  . html_entity_decode($trans_currency) . 
					", QTY : " . $trans_details_obj[$i]->qty . ", Unit : " . $trans_details_obj[$i]->product_measurement .' ' . $trans_details_obj[$i]->product_unit . ", Shipping Cost : " . $trans_details_obj[$i]->shipping_cost . html_entity_decode($trans_currency) .") <br>";
					
				}
				
				$sub_total_amt += $trans_details_obj[$i]->original_price * $trans_details_obj[$i]->qty;
				
				
		}


		
		$trans_status = $CI->Transactionstatus->get_one($trans_header_obj->trans_status_id)->title;


		$billing_name = $trans_header_obj->billing_first_name . " " . $trans_header_obj->billing_last_name;
		$shipping_name = $trans_header_obj->shipping_first_name . " " . $trans_header_obj->shipping_last_name;

		$total_amt = $total_amount .' ' . html_entity_decode($trans_currency);

		$coupon_discount_amount = $trans_header_obj->coupon_discount_amount;
		$tax_amount = $trans_header_obj->tax_amount;
		$shipping_method_amount = $trans_header_obj->shipping_method_amount;
		$shipping_tax_amount = $trans_header_obj->shipping_method_amount * $trans_header_obj->shipping_tax_percent;

		$total_balance_amount = ($trans_header_obj->sub_total_amount + ($trans_header_obj->tax_amount + $trans_header_obj->shipping_method_amount + ($trans_header_obj->shipping_method_amount * $trans_header_obj->shipping_tax_percent)));  

		//Shop or User
		if($to_who == "shop") {
		
			$to = $shop_email;

			$msg = <<<EOL
<p>Hi {$shop_name},</p>

<p>New Order is received with following information.</p>

<p>
Trans. Code : {$trans_header_obj->trans_code}<br/>
</p>

<p>
Trans. Status : {$trans_status}<br/>
</p>

<p>
{$payment_info}<br/>
</p>

<p>
Billing Customer Name : {$billing_name}<br/>
</p>

<p>
Billing Address 1 : {$trans_header_obj->billing_address_1}<br/>
</p>

<p>
Billing Address 2 : {$trans_header_obj->billing_address_2}<br/>
</p>

<p>
Billing Phone : {$trans_header_obj->billing_phone}<br/>
</p>

<p>
Billing Email : {$trans_header_obj->billing_email}<br/>
</p>

<p>
Billing Shipping Name : {$shipping_name}<br/>
</p>

<p>
Shipping Address 1 : {$trans_header_obj->shipping_address_1}<br/>
</p>

<p>
Shipping Address 2 : {$trans_header_obj->shipping_address_2}<br/>
</p>

<p>
Shipping Phone : {$trans_header_obj->shipping_phone}<br/>
</p>

<p>
Shipping Email : {$trans_header_obj->shipping_email}<br/>
</p>

<p>
Memo: {$trans_header_obj->memo}
</p>

<p>
Zone Shipping: {$is_zone_shipping}
</p>


<p>Product details information at below :</p>
{$order_items}            


<p>
Sub Total : {$sub_total_amt} {$trans_currency}
</p>
<p>
Coupon Discount Amount(-) : {$coupon_discount_amount} {$trans_currency}
</p>
<p>
Overall Tax(+) : {$tax_amount} {$trans_currency}
</p>
<p>
Shipping Cost(+) : {$shipping_method_amount} {$trans_currency}
</p>
<p>
Shipping Tax(+) : {$shipping_tax_amount} {$trans_currency}
</p>
<p>
Total Balance Amount : {$total_balance_amount} {$trans_currency}
</p>


<p>
Best Regards,<br/>
{$sender_name}
</p>
EOL;

		} else if ($to_who == "user") {

			$to = $user_email;

			$msg = <<<EOL
<p>Hi {$user_name},</p>

<p>New Order is received with following information.</p>

<p>
Trans. Code : {$trans_header_obj->trans_code}<br/>
</p>

<p>
Trans. Status : {$trans_status}<br/>
</p>

<p>
{$payment_info}<br/>
</p>

<p>
Billing Customer Name : {$billing_name}<br/>
</p>

<p>
Billing Address 1 : {$trans_header_obj->billing_address_1}<br/>
</p>

<p>
Billing Address 2 : {$trans_header_obj->billing_address_2}<br/>
</p>

<p>
Billing Phone : {$trans_header_obj->billing_phone}<br/>
</p>

<p>
Billing Email : {$trans_header_obj->billing_email}<br/>
</p>

<p>
Billing Shipping Name : {$shipping_name}<br/>
</p>

<p>
Shipping Address 1 : {$trans_header_obj->shipping_address_1}<br/>
</p>

<p>
Shipping Address 2 : {$trans_header_obj->shipping_address_2}<br/>
</p>

<p>
Shipping Phone : {$trans_header_obj->shipping_phone}<br/>
</p>

<p>
Shipping Email : {$trans_header_obj->shipping_email}<br/>
</p>

<p>
Memo: {$trans_header_obj->memo}
</p>

<p>
Zone Shipping: {$is_zone_shipping}
</p>


<p>Product details information at below :</p>
{$order_items}            


<p>
Sub Total : {$sub_total_amt} {$trans_currency}
</p>
<p>
Coupon Discount Amount(-) : {$coupon_discount_amount} {$trans_currency}
</p>
<p>
Overall Tax(+) : {$tax_amount} {$trans_currency}
</p>
<p>
Shipping Cost(+) : {$shipping_method_amount} {$trans_currency}
</p>
<p>
Shipping Tax(+) : {$shipping_tax_amount} {$trans_currency}
</p>
<p>
Total Balance Amount : {$total_balance_amount} {$trans_currency}
</p>


<p>
Best Regards,<br/>
{$sender_name}
</p>
EOL;

		}

		// print_r($to); echo "<br><br>";
		// print_r($subject);   echo "<br><br>";
		// print_r($msg); echo "<br><br>";
		// echo "---------";

		// send email from admin
		return $CI->ps_mail->send_from_admin( $to, $subject, $msg );
	}
}

if ( !function_exists( 'send_shop_registeration_emails' )) {

	function send_shop_registeration_emails( $shop_id, $to_who = "", $subject = "" )
	{
		// get ci instance
		$CI =& get_instance();


		$super_admin_email = $CI->config->item( 'super_admin_email' );
		$super_admin_name = $CI->config->item( 'super_admin_name' );
		$sender_name = $CI->config->item( 'sender_name' );


		//for user shop
		$conds_shop['shop_id'] = $shop_id;

		//for shop
		$conds_shop_info['id'] = $shop_id;
		$conds_shop_info['no_publish_filter'] = 2;

		//to get shop information

		$shop_data = $CI->Shop->get_one_by($conds_shop_info);
		$shop_name = $shop_data->name;
		$shop_phone = $shop_data->about_phone1;
		$shop_address = $shop_data->address1;


		// get user email
		$user_shop =  $CI->User_shop->get_one_by($conds_shop);
		$conds_user['user_id'] = $user_shop->user_id;
		$user_email = $CI->User->get_one_by($conds_user)->user_email;
		$user_name = $CI->User->get_one_by($conds_user)->user_name;



		//Super Admin or User
		if($to_who == "super_admin") {
		
			$to = $super_admin_email;

			$msg = <<<EOL

		<p>Hi {$super_admin_name},</p>

<p>New shop has been registered. Please take a look more details at below :</p>

<p>

Shop Name : {$shop_name}<br/>
</p>

<p>
Shop Phone : {$shop_phone}<br/>
</p>

<p>
Shop Address : {$shop_address}<br/>
</p>

<p>
User Name : {$user_name}<br/>
</p>

<p>
User Email : {$user_email}<br/>
</p>



<p>
Best Regards,<br/>
{$sender_name}

EOL;

		} else if ($to_who == "user") {

			$to = $user_email;

			$msg = <<<EOL

		<p>Hi {$user_name},</p>

<p>Your shop has been registered. After we reviewed then we would get back to you for the result from approval process. Here some information for your shop :
</p>

<p>

Shop Name : {$shop_name}<br/>
</p>

<p>
Shop Phone : {$shop_phone}<br/>
</p>

<p>
Shop Address : {$shop_address}<br/>
</p>

<p>
User Name : {$user_name}<br/>
</p>

<p>
User Email : {$user_email}<br/>
</p>



<p>
Best Regards,<br/>
{$sender_name}

EOL;

		}


		


		
		
		// print_r($to); echo "<br><br>";
		// print_r($subject);   echo "<br><br>";
		// print_r($msg); echo "<br><br>";
		// echo "---------";

		// send email from admin
		return $CI->ps_mail->shop_registeration_send_from_admin( $to, $subject, $msg );
	}
}


if ( !function_exists( 'send_shop_approval_emails' )) {

	function send_shop_approval_emails( $shop_id, $to_who = "", $subject = "", $status )
	{
		// get ci instance
		$CI =& get_instance();

		$conds_shop['shop_id'] = $shop_id;
		$user_shop =  $CI->User_shop->get_one_by($conds_shop);
		$conds_user['user_id'] = $user_shop->user_id;
		//print_r($conds_user);die;
		$user_email = $CI->User->get_one_by($conds_user)->user_email;
		$user_name = $CI->User->get_one_by($conds_user)->user_name;

		$sender_name = $CI->config->item( 'sender_name' );

		$to = $user_email;

		if ( $status == 1 ) {

			$msg = <<<EOL

		<p>Hi {$user_name},</p>

<p>Congratulations! Your shop has been approved. So you can login with registered email and password. 
</p>

<p>
Best Regards,<br/>
{$sender_name}

EOL;

		} else {

			$msg = <<<EOL

		<p>Hi {$user_name},</p>

<p>Sorry! Your shop has been rejected. If you have any questions, please drop message to contact@ps.com 
</p>

<p>
Best Regards,<br/>
{$sender_name}

EOL;
		}


		
		
		// print_r($to); echo "<br><br>";
		// print_r($subject);   echo "<br><br>";
		// print_r($msg); echo "<br><br>";
		// echo "---------";

		// send email from admin
		return $CI->ps_mail->shop_approval_send_from_admin( $to, $subject, $msg );
	}
}

if ( !function_exists( 'send_user_register_email' )) {

  function send_user_register_email( $user_id, $subject = "" )
  {
    // get ci instance
    $CI =& get_instance();
    
    $user_info_obj = $CI->User->get_one($user_id);

    $user_name  = $user_info_obj->user_name;
    $user_email = $user_info_obj->user_email;
    $code = $user_info_obj->code;
    

    $to = $user_email;

		$sender_name = $CI->config->item( 'sender_name' );

    $msg = <<<EOL
<p>Hi {$user_name},</p>

<p>Your new User Account has been created. Welcome to Multi-Store. Please verified with the code at below to actived your account.</p>

<p>
Verified Code : {$code}<br/>
</p>


<p>
Best Regards,<br/>
{$sender_name}
</p>
EOL;
    
	require("PHPMailer/class.smtp.php");
	require("PHPMailer/class.phpmailer.php");

	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->Host = "smtp.gmail.com";
	$mail->Port = 465;
	$mail->SMTPAuth = true;
	$mail->SMTPSecure = "ssl";
	$mail->SMTPDebug = 1;
	$mail->From = $to;
	$mail->FromName = "BruFat";
	$mail->isHTML(true);
	$mail->Subject  = "Confirma tu registro";		
		
	$mail->Body = $msg;
	$mail->AddAddress($to, $user_name);
	$mail->SMTPAuth = true;
	$mail->Username = "esteticarubi20@gmail.com";
	$mail->Password = "Estetica@123";
	$mail->Send();

    

	// send email from admin
	$a = $CI->ps_mail->send_from_admin( $to, $subject, $msg );
    return $a;
  }
}

if ( !function_exists( 'send_contact_us_emails' )) {

  function send_contact_us_emails( $contact_id, $subject = "" )
  {
    // get ci instance  
    $CI =& get_instance();
    
    $contact_info_obj = $CI->Contact->get_one($contact_id);

    $contact_name  = $contact_info_obj->name;
    $contact_email = $contact_info_obj->email;
    $contact_phone = $contact_info_obj->phone;
    $contact_msg   = $contact_info_obj->message;
    

    $to = $CI->config->item( 'receive_email' );

	$sender_name = $CI->config->item( 'sender_name' );

    $msg = <<<EOL
<p>Hi Admin,</p>

<p>
Name : {$contact_name}<br/>
Email : {$contact_email}<br/>
Phone : {$contact_phone}<br/>
Message : {$contact_msg}<br/>
</p>


<p>
Best Regards,<br/>
{$sender_name}
</p>
EOL;
    
    
    

    // send email from admin
    return $CI->ps_mail->send_from_admin( $to, $subject, $msg );
  }
}