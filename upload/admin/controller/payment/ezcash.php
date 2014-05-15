<?php
class ControllerPaymentEzcash extends Controller {
	private $error = array();

	public function index() {
		$this->language->load('payment/ezcash'); //loading the language for the module

		$this->document->setTitle($this->language->get('heading_title')); // setting the heading title in admin

		$this->load->model('setting/setting'); // loading oc settings

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {// processsing post requests
			$this->model_setting_setting->editSetting('ezcash', $this->request->post); 

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_all_zones'] = $this->language->get('text_all_zones');
		$this->data['text_yes'] = $this->language->get('text_yes');
		$this->data['text_no'] = $this->language->get('text_no');

		$this->data['entry_merchant'] = $this->language->get('entry_merchant');
		$this->data['entry_password'] = $this->language->get('entry_password');
		$this->data['entry_pvt_key'] = $this->language->get('entry_pvt_key');
		$this->data['entry_test'] = $this->language->get('entry_test');
		$this->data['entry_total'] = $this->language->get('entry_total');
		$this->data['entry_order_status'] = $this->language->get('entry_order_status');
		$this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['text_test_mode'] = $this->language->get('text_test_mode');
		$this->data['text_live_mode'] = $this->language->get('text_live_mode');
		$this->data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$this->data['entry_test_order'] = $this->language->get('entry_test_order');
		$this->data['entry_test_order_note'] = $this->language->get('entry_test_order_note');		
		
		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		if (isset($this->error['merchant'])) {
			$this->data['error_merchant'] = $this->error['merchant'];
		} else {
			$this->data['error_merchant'] = '';
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('payment/ezcash', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['action'] = $this->url->link('payment/ezcash', 'token=' . $this->session->data['token'], 'SSL');

		$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');
		
		// processing data from admin form
		if (isset($this->request->post['ezcash_merchant'])) {
			$this->data['ezcash_merchant'] = $this->request->post['ezcash_merchant'];
		} else {
			$this->data['ezcash_merchant'] = $this->config->get('ezcash_merchant');
		}

		if (isset($this->request->post['ezcash_pvt_key'])) {
			$this->data['ezcash_pvt_key'] = $this->request->post['ezcash_pvt_key'];
		} else {
			$this->data['ezcash_pvt_key'] = $this->config->get('ezcash_pvt_key');
		}

		if (isset($this->request->post['ezcash_password'])) {
			$this->data['ezcash_password'] = $this->request->post['ezcash_password'];
		} else {
			$this->data['ezcash_password'] = $this->config->get('ezcash_password');
		}
		
		if (isset($this->request->post['ezcash_test'])) {
			$this->data['ezcash_test'] = $this->request->post['ezcash_test'];
		} else {
			$this->data['ezcash_test'] = $this->config->get('ezcash_test');
		}

		if (isset($this->request->post['ezcash_test_order'])) {
			$this->data['ezcash_test_order'] = $this->request->post['ezcash_test_order'];
		} else {
			$this->data['ezcash_test_order'] = $this->config->get('ezcash_test_order');
		}

		if (isset($this->request->post['ezcash_total'])) {
			$this->data['ezcash_total'] = $this->request->post['ezcash_total'];
		} else {
			$this->data['ezcash_total'] = $this->config->get('ezcash_total');
		}

		if (isset($this->request->post['ezcash_order_status_id'])) {
			$this->data['ezcash_order_status_id'] = $this->request->post['ezcash_order_status_id'];
		} else {
			$this->data['ezcash_order_status_id'] = $this->config->get('ezcash_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['ezcash_geo_zone_id'])) {
			$this->data['ezcash_geo_zone_id'] = $this->request->post['ezcash_geo_zone_id'];
		} else {
			$this->data['ezcash_geo_zone_id'] = $this->config->get('ezcash_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['ezcash_status'])) {
			$this->data['ezcash_status'] = $this->request->post['ezcash_status'];
		} else {
			$this->data['ezcash_status'] = $this->config->get('ezcash_status');
		}

		if (isset($this->request->post['ezcash_sort_order'])) {
			$this->data['ezcash_sort_order'] = $this->request->post['ezcash_sort_order'];
		} else {
			$this->data['ezcash_sort_order'] = $this->config->get('ezcash_sort_order');
		}

		$this->template = 'payment/ezcash.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/ezcash')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['ezcash_merchant']) {
			$this->error['merchant'] = $this->language->get('error_merchant');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
}
?>
