<?php
class ControllerModuleSamson extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('module/samson');
		$this->document->setTitle($this->language->get('heading_title'));


		$this->load->model('setting/setting');  
		$this->load->model('module/samson');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { 
			$this->model_setting_setting->editSetting('samson', $this->request->post);
			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}
		
		//Проверка подключение по API

		$this->model_module_samson->installDB(); 
		$current_key = $this->model_module_samson->getKey();
		$curl = curl_init('https://api.samsonopt.ru/v1/category/?api_key='.$current_key);
		$arHeaderList = array();
		$arHeaderList[] = 'Accept: application/json';
		$arHeaderList[] = 'User-Agent: string';
		curl_setopt($curl, CURLOPT_HTTPHEADER, $arHeaderList);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$authRes = curl_exec($curl);
		curl_close($curl);
		
		$data['token'] = $this->session->data['token'];
		$data['current_key'] = (!empty($current_key) ? $current_key : $this->language->get('auth_error'));
		$data['authRes'] = $authRes;
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_response'] = $this->language->get('text_response');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_set_percent'] = $this->language->get('text_set_percent');
		$data['text_auth_ok']    = $this->language->get('auth_ok');
		$data['text_auth_error']    = $this->language->get('auth_error');
		$data['text_check'] = $this->language->get('text_check');
		$data['api_key'] = $this->language->get('api_key');
		$data['percent'] = $this->model_module_samson->getPercent();

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('module/samson', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('module/samson', 'token=' . $this->session->data['token'], 'SSL');
		$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
		

       	$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('module/samson.tpl', $data));
	}

	public function keychecked(){

		$this->load->model('module/samson');
		$data['auth_ok']  = $this->language->get('auth_ok');
		$data['auth_error'] = $this->language->get('auth_error');
		$current_key = $this->request->post['key'];

		

		if ($current_key) {
			$curl = curl_init('https://api.samsonopt.ru/v1/category/?api_key=' . $current_key);
			$arHeaderList = array();
			$arHeaderList[] = 'Accept: application/json';
			$arHeaderList[] = 'User-Agent: string';
			curl_setopt($curl, CURLOPT_HTTPHEADER, $arHeaderList);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			$data['result'] = curl_exec($curl);
			curl_close($curl);
			if ($data['result']){
				$this->model_module_samson->setKey($current_key);
				$data['answer'] = "ok";
			} else {
				$data['answer'] = "error";
			}
		}

		$data_json = json_encode($data);
		$this->response->setOutput($data_json);
	}

	public function changeprice(){

		$this->load->model('module/samson');
		$current_key = $this->model_module_samson->getKey();
		$this->model_module_samson->setPercent((int)$this->request->post['percent']);

		if ($current_key) {
			$curl = curl_init('https://api.samsonopt.ru/v1/assortment/?api_key=' . $current_key);
			$arHeaderList = array();
			$arHeaderList[] = 'Accept: application/json';
			$arHeaderList[] = 'User-Agent: string';
			curl_setopt($curl, CURLOPT_HTTPHEADER, $arHeaderList);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			$res = curl_exec($curl);
			curl_close($curl);
		}
		$decodeRes = json_decode($res, true);
		$newPrice = array();
		for($i = 0; $i <= count($decodeRes['data'])-1; $i++){

				$newPrice += [ $decodeRes['data'][$i]['sku'] => (float)$this->getPrice($decodeRes['data'][$i]['price_list'][0]['value']) ];

			}
		

		//получаем пары данных из базы.
		$result = (array) $this->model_module_samson->getSkuPrice();
			$existsPrice = array();
			for($i = 0; $i <= count($result['rows'])-1; $i++){

				$existsPrice += [ $result['rows'][$i]['sku'] => (float)$result['rows'][$i]['price'] ]; 
			}

		//ищим совпадения
		$price = array();
		$sku = array();
		foreach ($existsPrice as $key => $value){
			if ( array_key_exists($key, $newPrice)){
				if($existsPrice[$key] != $newPrice[$key]){
				array_push($sku, $key);
				array_push($price, $newPrice[$key]);
				}
			}
		}
		if (!empty($sku)){
			$this->model_module_samson->setSkuPrice($sku, $price);
		}
	}

    protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/samson')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
	
	protected function getPrice($number) {
		$percent = $this->model_module_samson->getPercent() /100;
		if ($percent <= 0){
			$percent = 0;
		} else {
			$res = ((float)$number * $percent) + $number;
			$float = $res - floor($res);
			$float = round($float, 1) * 10;
				if($float != 5){
					$float = ($float < 5 ? 0.5 : 1);
				} else {
					$float = 0.5;
				} 
			$res = floor($res);
			$price = $res + $float;
		}

		return $price;
	}
}
