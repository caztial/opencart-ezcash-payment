<?php
class ControllerPaymentEzcash extends Controller {
private $test_order_id=1; // in live mode
	protected function index() {
		$this->data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		if($this->config->get('ezcash_test')=='t'){
			$this->test_order_id=$this->config->get('ezcash_test_order');
		}
		$this->data['merchant'] = $this->config->get('ezcash_merchant');
		$this->data['trans_id'] = $this->session->data['order_id'];
		$this->data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

		if ($this->config->get('ezcash_password')) {
			$this->data['digest'] = md5($this->session->data['order_id'] . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . $this->config->get('ezcash_password'));
		} else {
			$this->data['digest'] = '';
		}
		if ($this->config->get('ezcash_password')) {
            $this->data['callback'] = $this->url->link('payment/ezcash/callback', '', 'SSL');
			$mcode = $this->data['merchant']; //merchant code
			$tid = $this->data['trans_id']*$this->test_order_id; // transaction id
			$tamount = $this->data['amount']; //transaction amount
			$rurl =$this->data['callback'];
			$sensitiveData = $mcode.'|'.$tid.'|'.$tamount.'|'.$rurl; // query string
			$publicKey = $this->config->get('ezcash_password');
			$pkey = wordwrap($this->config->get('ezcash_password'), 65, "\n", true);
			$publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
$pkey
-----END PUBLIC KEY-----
EOD;
			$encrypted = '';
			if (!openssl_public_encrypt($sensitiveData, $encrypted, $publicKey))
			echo 'Failed to encrypt data check your public key';
			$invoice = base64_encode($encrypted); // encoded encrypted query string
			$this->data['merchantInvoice']=$invoice;
			
		}else{
			$this->data['merchantInvoice']="Error encryption";
		}		

		$this->data['bill_name'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
		$this->data['bill_addr_1'] = $order_info['payment_address_1'];
		$this->data['bill_addr_2'] = $order_info['payment_address_2'];
		$this->data['bill_city'] = $order_info['payment_city'];
		$this->data['bill_state'] = $order_info['payment_zone'];
		$this->data['bill_post_code'] = $order_info['payment_postcode'];
		$this->data['bill_country'] = $order_info['payment_country'];
		$this->data['bill_tel'] = $order_info['telephone'];
		$this->data['bill_email'] = $order_info['email'];

		if ($this->cart->hasShipping()) {
			$this->data['ship_name'] = $order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'];
			$this->data['ship_addr_1'] = $order_info['shipping_address_1'];
			$this->data['ship_addr_2'] = $order_info['shipping_address_2'];
			$this->data['ship_city'] = $order_info['shipping_city'];
			$this->data['ship_state'] = $order_info['shipping_zone'];
			$this->data['ship_post_code'] = $order_info['shipping_postcode'];
			$this->data['ship_country'] = $order_info['shipping_country'];
		} else {
			$this->data['ship_name'] = '';
			$this->data['ship_addr_1'] = '';
			$this->data['ship_addr_2'] = '';
			$this->data['ship_city'] = '';
			$this->data['ship_state'] = '';
			$this->data['ship_post_code'] = '';
			$this->data['ship_country'] = '';
		}

		$this->data['currency'] = $this->currency->getCode();
		$this->data['callback'] = $this->url->link('payment/ezcash/callback', '', 'SSL');

		switch ($this->config->get('ezcash_test')) {
			case 'live':
				$status = 'live';
				break;
			case 'successful':
			default:
				$status = 'true';
				break;
			case 'fail':
				$status = 'false';
				break;
		}

		$this->data['options'] = 'test_status=' . $status . ',dups=false,cb_post=false';

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/ezcash.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/ezcash.tpl';
		} else {
			$this->template = 'default/template/payment/ezcash.tpl';
		}

		$this->render();
	}

	public function callback() {
		if($this->config->get('ezcash_test')=='t'){
			$this->test_order_id=$this->config->get('ezcash_test_order');
		}
		$decrypted = '';
		$encrypted = $_POST['merchantReciept'];
		$pkey = wordwrap($this->config->get('ezcash_pvt_key'), 65, "\n", true);
		$privateKey = <<<EOD
-----BEGIN PRIVATE KEY-----
$pkey
-----END PRIVATE KEY-----
EOD;
		$encrypted = base64_decode($encrypted); //decode the encrypted query string
		if (!openssl_private_decrypt($encrypted, $decrypted, $privateKey)){
			$status = false;
			die('Failed to decrypt data check your private key');
		}
		$status = true;
		$response=explode("|",$decrypted);

		$order_id=(int)$response[0]/$this->test_order_id;
		$statusCode=(int)$response[1];	
		$statusDescription=$response[2];
		$transactionAmount=$response[3];
		$merchantCode=$response[4];
		if (isset($response[5])) {$walletReferenceId=$response[5];}
		

		$this->load->model('checkout/order'); // loading the checkout order

		$order_info = $this->model_checkout_order->getOrder($order_id);
		if ($order_info) {
			$this->language->load('payment/ezcash');

			$this->data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

			if (!isset($this->request->server['HTTPS']) || ($this->request->server['HTTPS'] != 'on')) {
				$this->data['base'] = HTTP_SERVER;
			} else {
				$this->data['base'] = HTTPS_SERVER;
			}

			$this->data['language'] = $this->language->get('code');
			$this->data['direction'] = $this->language->get('direction');

			$this->data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

			$this->data['text_response'] = $this->language->get('text_response');
			$this->data['text_success'] = $this->language->get('text_success');
			$this->data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->url->link('checkout/success'));
			$this->data['text_failure'] = $this->language->get('text_failure');
			$this->data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/cart'));

			if ((isset($statusCode) && $statusCode == 2 && $status) || $this->config->get('ezcash_test')=='t') {
				$this->load->model('checkout/order');

				$this->model_checkout_order->confirm($order_id, $this->config->get('config_order_status_id'));

				$message = '';

				if (isset($statusCode)) {
					$message .= 'Status Code: ' . $statusCode . "\n";
				}

				if (isset($statusDescription)) {
					$message .= 'Status Description: ' . $statusDescription . "\n";
				}

				if (isset($transactionAmount)) {
					$message .= 'Transaction Amount: ' . $transactionAmount . "\n";
				}

				if (isset($merchantCode)) {
					$message .= 'Merchant Code: ' . $merchantCode . "\n";
				}

				if (isset($walletReferenceId)) {
					$message .= 'Wallet ReferenceId ' . $walletReferenceId . "\n";
				}

				$this->model_checkout_order->update($order_id, $this->config->get('ezcash_order_status_id'), $message, false);

				$this->data['continue'] = $this->url->link('checkout/success');

				if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/ezcash_success.tpl')) {
					$this->template = $this->config->get('config_template') . '/template/payment/ezcash_success.tpl';
				} else {
					$this->template = 'default/template/payment/ezcash_success.tpl';
				}

				$this->children = array(
					'common/column_left',
					'common/column_right',
					'common/content_top',
					'common/content_bottom',
					'common/footer',
					'common/header'
				);

				$this->response->setOutput($this->render());
			} else {
				$this->data['continue'] = $this->url->link('checkout/cart');

				if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/ezcash_failure.tpl')) {
					$this->template = $this->config->get('config_template') . '/template/payment/ezcash_failure.tpl';
				} else {
					$this->template = 'default/template/payment/ezcash_failure.tpl';
				}

				$this->children = array(
					'common/column_left',
					'common/column_right',
					'common/content_top',
					'common/content_bottom',
					'common/footer',
					'common/header'
				);

				$this->response->setOutput($this->render());
			}
		}
	}
}
?>
