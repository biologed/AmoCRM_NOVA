<?php

/**
* Запрос в API
* @param string $token токен API
* @param string $operation имя операции которую выполняем
* @param int $id универсальный номер контакта присвоенный БД
* @param int $id_lead универсальный номер сделки присвоенный БД
* @param int $page страница в БД API используется, если в БД хранится более 250 значений (ограничение на выдачу API)
* @param array $set массив хранящий подготовленные параметры для передачи в API (создание\обновление контакта, связи, сделки)
* @param string $name имя контакта
* @access private
* @return array
*/

function &connect(string $operation, string $token, int $id, int $id_lead, int $page, array $set, string $name) {
	$headers = [
		'Authorization: Bearer ' . $token
	];

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');

	if($operation == 'get_all_contact') {  //запрос на получение всех контактов
		$link = 'https://supergird2012.amocrm.ru/api/v4/contacts?page='.$page.'&limit=250';
	} elseif($operation == 'update_contact') { //запрос на обновление контакта
		$link = 'https://supergird2012.amocrm.ru/api/v4/contacts/'.$id;
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST,'PATCH');
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($set));
	} elseif($operation == 'create_contact') { //запрос на создание контакта
		$link = 'https://supergird2012.amocrm.ru/api/v4/contacts';
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST,'POST');
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($set));
	} elseif($operation == 'create_lead') { //запрос на создание сделки
		$link = 'https://supergird2012.amocrm.ru/api/v4/leads';
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST,'POST');
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($set));
	} elseif($operation == 'create_link') { //запрос на создание связи
		$link = 'https://supergird2012.amocrm.ru/api/v4/leads/'.$id_lead.'/link';
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST,'POST');
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($set));
	} elseif($operation == 'get_contact') { //запрос на поиск контакта по имени
		$link = 'https://supergird2012.amocrm.ru/api/v4/contacts?filter[name]='.$name.'';
	}

	curl_setopt($curl, CURLOPT_URL, $link);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	$out = curl_exec($curl);
	$code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
	curl_close($curl);

	if($out && ($code == 200 || $code == 204)) {
		$response = array(
			'status' => 'success',
			'data' => json_decode($out, true)
		);
		
	} elseif ($code < 200 || $code > 204) {
		$response = array(
			'status' => 'error',
			'data' => 'Ошибка №'.$code
		);
	}

	return $response;
}

/**
* Ищем контакт по его имени в БД
* для создания связи между ними
* @param string $token токен API
* @param int $name имя контакта
* @access private
* @return array
*/
function get_contact(string $token, string $name) {
	// проверяем чтобы поле с именем было заполнено, а поле почты и телефона пустые
	if(!empty($_GET['name'])) {
		$response = &connect('get_contact', $token, 0, 0, 0, [], $name); //инициируем запрос в API
		if($response['status'] == 'success') {
			$count_str = count($response['data']['_embedded']['contacts']); //количество контактов в ответе
			$data = array();
			for($i = 0; $i < $count_str; $i++) {
				$id_contact = $response['data']['_embedded']['contacts'][$i]['id'];
				$name_contact = $response['data']['_embedded']['contacts'][$i]['name'];

				//ищем телефон и почту в полях
				if(!empty($response['data']['_embedded']['contacts'][$i]['custom_fields_values'])) {
					for($t = 0; $t < count($response['data']['_embedded']['contacts'][$i]['custom_fields_values']); $t++) {
						if($response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['field_name'] == 'Телефон') {
							if(!empty($response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'])) {
								$phone_contact = $response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'];
							} else {
								$phone_contact = null;
							}
						} else if ($response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['field_name'] == 'Email') {
							if(!empty($response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'])) {
								$email_contact = $response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'];
							} else {
								$email_contact = null;
							}
						}
					}
				} else {
					$phone_contact = null;
					$email_contact = null;
				}

				$data[] = array(
					'id_contact' => $id_contact,
					'name_contact' => $name_contact,
					'phone_contact' => $phone_contact,
					'email_contact' => $email_contact,
					);
			}

			$result = array(
				'status' => 'success',
				'data' => json_encode($data),
			);
		} elseif ($response['status'] == 'error') {
			$result = array(
				'status' => 'error',
				'data' => $response['data'],
			);
		} else {
			if(empty($_GET['phone']) && empty($_GET['email'])) {
				$result = array(
					'status' => 'error',
					'data' => 'Такого контакта нет, для поиска по другим параметрам оставьте поле пустым',
				);
			} else {
				$result = array(
					'status' => 'fail',
					'data' => 'Такого контакта нет, хотите создать контакт?',
				);
			}
		}
	} else {
		$result = array(
			'status' => 'error',
			'data' => 'Поле с именем контакта должно быть заполнено',
		);
	}
	
	echo json_encode($result);
}

/**
* Создаем контакт в БД
* @param string $token токен API
* @param string $name имя контакта
* @param string $phone телефон контакта
* @param string $email почта контакта
* @access private
* @return array if error
*/
function create_contact(string $token, string $name, string $phone, string $email) {
	// проверяем чтобы все поля были заполнены
	if(!empty($_GET['name']) && !empty($_GET['email']) && !empty($_GET['phone'])) {
		$set=array(
			array(
				'name'=>$name,
				'first_name'=>$name,
				'last_name'=>'',
				'updated_at'=>time(),
				'custom_fields_values'=>array(
					array(
						'field_id'=>633427,
						'values'=>array(
							array(
								'value'=>$email,
								'enum_code'=>'WORK'
							)
						)
					),
					array(
						'field_id'=>633425,
						'values'=>array(
							array(
								'value'=>$phone,
								'enum_code'=>'WORK'
							)
						)
					)
				),
				'request_id'=>'create'
			)
		);

		$response = &connect('create_contact', $token, 0, 0, 0, $set, ''); //инициируем запрос в API
		if($response['status'] == 'success') {
			$id_contact = $response['data']['_embedded']['contacts'][0]['id'];
			create_lead($token, $name, $id_contact); //создаем сделку
		} else if ($response['status'] == 'error') {
			$result = array(
				'status' => 'error',
				'data' => $response['data']
			);
			echo json_encode($result);
		} else {
			$result = array(
				'status' => 'error',
				'data' => 'Ошибка! Контакт не был создан'
			);
			echo json_encode($result);
		}
	} else {
		$result = array(
			'status' => 'fail',
			'data' => 'Все поля должны быть заполнены',
		);
		echo json_encode($result);
	}
}

/**
* Создаем сделку и передаем данные об ID сделки и контакта в функцию create_link
* для создания связи между ними
* @param string $token токен API
* @param string $name имя сделки
* @param int $id_contact универсальный номер контакта
* @access private
* @return array if error
*/
function create_lead(string $token, string $name_lead, int $id_contact) {
	$set=array(
		array(
			'name'=>$name_lead,
			'price'=>0,
			'request_id'=>'create_lead'
		)
	);
	$response = &connect('create_lead', $token, 0, 0, 0, $set, ''); //инициируем запрос в API

	if($response['status'] == 'success') {
		$id_lead = $response['data']['_embedded']['leads'][0]['id'];
		create_link($token, $id_lead, $id_contact); //создаем связь между сделкой и контактом
	} else if ($response['status'] == 'error') {
		$result = array(
			'status' => 'error',
			'data' => $response['data']
		);
		echo json_encode($result);
	} else {
		$result = array(
			'status' => 'error',
			'data' => 'Ошибка! Сделка не была создана'
		);
		echo json_encode($result);
	}
}

/**
* Создаем связь между сделкой и контактом
* для создания связи между ними
* @param string $token токен API
* @param int $id_lead универсальный номер сделки
* @param int $id_contact универсальный номер контакта
* @access private
* @return array
*/
function create_link(string $token, int $id_lead, int $id_contact) {
	$set=array(
		array(
			'to_entity_id'=>$id_contact,
			'to_entity_type'=>'contacts',
			'metadata'=>array(
				'is_main'=>true,
			)
		)
	);

	$response = &connect('create_link', $token, 0, $id_lead, 0, $set, ''); //инициируем запрос в API
	if($response['status'] == 'success') {
		$result = array(
			'status' => 'success',
			'data' => 'Контакт c ID: '.$id_contact.' сделка c ID: '.$id_lead.' и связь между ними успешно созданы'
		);
	} else if ($response['status'] == 'error') {
		$result = array(
			'status' => 'error',
			'data' => $response['data']
		);
	} else {
		$result = array(
			'status' => 'error',
			'data' => 'Ошибка! Сделка не была создана'
		);
	}

	echo json_encode($result);
}

/**
* функция работы с файлом base_contracts.json
* Функция исследует файл base_contracts в котором хранит все контакты AmoCRM и БД AmoCRM, 
* из-за API невозможно выполнить фильтр на стороне Амо по их системным полям Телефона и Почты.
* независимо от версии API, исследовались разные варианты GET-запросов к AmoCRM с попытками отфильтровать по полям, в т.ч. на 3 и 4 версиях.
* Далее подсчитываем количество строк, берем в учет, что в ответе от AmoCRM может быть только 250 контактов, поэтому опрашиваем все страницы БД.
* Затем собираем нужную нам информацию в файл.
* Далее запускаем поиск по телефону или почте уже работая с файлом контактов на сервере.
* @param string $token токен API
* @param int $page страница в БД API используется, если в БД хранится более 250 значений (ограничение на выдачу API)
* @access private
* @return array if error
*/
function get_all_contact(string $token, int $page) {
	$response = &connect('get_all_contact', $token, 0, 0, $page, [], ''); //инициируем запрос в API

	if($response['status'] == 'success') {
		$last_page = false;
		$count_str = count($response['data']['_embedded']['contacts']); //количество контактов в ответе
		// $count_file = count(file($filename)); //количество контактов в файле
		if(!is_dir('sec')) {
			mkdir('sec', 0777, true);
		}

		$filename = 'sec/base_contracts.json';
		$fp = fopen('sec/.htaccess','w',0777);
		fwrite($fp, '<Files *>\r\nDeny from All\r\n</Files>');
		fclose($fp);

		if(file_exists($filename) && $page == 1) {
			$new_filename = str_replace('.json', '', $filename).'_backup.json';
			copy($filename, $new_filename);
			unlink($filename);
		}	

		if($count_str < 250) {
			$last_page = true;
		}

		$fp = fopen($filename,'a',0777);
		if($page == 1) {
			fwrite($fp, '{\r\n	\'contacts\': [\r\n');
		}
		for($i = 0; $i < $count_str; $i++) {
			$id_contact = $response['data']['_embedded']['contacts'][$i]['id'];
			$name_contact = $response['data']['_embedded']['contacts'][$i]['name'];

			//ищем телефон и почту в полях
			if(!empty($response['data']['_embedded']['contacts'][$i]['custom_fields_values'])) {
				for($t = 0; $t < count($response['data']['_embedded']['contacts'][$i]['custom_fields_values']); $t++) {
					if($response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['field_name'] == 'Телефон') {
						if(!empty($response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'])) {
							$phone_contact = $response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'];
						} else {
							$phone_contact = null;
						}
					} else if ($response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['field_name'] == 'Email') {
						if(!empty($response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'])) {
							$email_contact = $response['data']['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'];
						} else {
							$email_contact = null;
						}
					}
				}
			} else {
				$phone_contact = null;
				$email_contact = null;
			}
			$set = json_encode(array(
				'id_contact' => $id_contact,
				'name_contact' => $name_contact,
				'phone_contact' => $phone_contact,
				'email_contact' => $email_contact,
				), JSON_UNESCAPED_UNICODE, JSON_FORCE_OBJECT);
			if($i == $count_str - 1 && $last_page) {
				fwrite($fp, '		'.$set.'\r\n'); //закрываемся
			} else {
				fwrite($fp, '		'.$set.',\r\n');
			}
		}
		if($count_str == 250) {
			$page++;
			get_all_contact($token, $page);
		} else {
			fwrite($fp, '	]\r\n}');
			fclose($fp);

			search_contact_in_json($filename, $_GET['email'], $_GET['phone']);
		}
	} else if ($response['status'] == 'error') {
		$result = array(
			'status' => 'error',
			'data' => $response['data']
		);
	} else {
		$result = array(
			'status' => 'error',
			'data' => 'Ошибка! Невозможно получить список контактов'
		);
	}
}

/**
* Ищем контакт в файле полученным из БД
* @param string $filename имя ранее созданого файла
* @param string $email почта контакта
* @param string $phone телефон контакта
* @access private
* @return array
*/
function search_contact_in_json(string $filename, string $email, string $phone) { //ищем контакты в файле
	$content = array();
	$json = json_decode(file_get_contents($filename), true);

	for($i = 0; $i < count($json['contacts']); $i++) {
		if(!empty($_GET['email']) && !empty($_GET['phone'])) {
			if ($json['contacts'][$i]['phone_contact'] == $phone && $json['contacts'][$i]['email_contact'] == $email) {
					$content[] = $json['contacts'][$i];
				}
		} else if (!empty($_GET['email']) || !empty($_GET['phone'])) {
			if (($json['contacts'][$i]['phone_contact'] == $phone && $json['contacts'][$i]['phone_contact'] != null) || 
				($json['contacts'][$i]['email_contact'] == $email && $json['contacts'][$i]['email_contact'] != null)) {
				$content[] = $json['contacts'][$i];
			}
		}
	}

	if($content) {
		$data = array();
		for($i = 0; $i < count($content); $i++) {
			$id_contact = $content[$i]['id_contact'];
			$name_contact = $content[$i]['name_contact'];
			$phone_contact = $content[$i]['phone_contact'];
			$email_contact = $content[$i]['email_contact'];
			$data[] = array(
				'id_contact' => $id_contact,
				'name_contact' => $name_contact,
				'phone_contact' => $phone_contact,
				'email_contact' => $email_contact,
				);
		}
		$result = array(
			'status' => 'success',
			'data' => json_encode($data)
		);
	} else {
		$result = array(
			'status' => 'fail',
			'data' => 'Такого контакта нет, хотите создать контакт?'
		);
	}
	echo json_encode($result);
}

/**
* Обновляем данные контакта в БД
* @param string $token токен API
* @param int $id универсальный номер контакта присвоенный БД
* @param string $name имя контакта
* @param string $phone телефон контакта
* @param string $email почта контакта
* @access private
* @return array
*/
function update_contact(string $token, int $id, string $name, string $phone, string $email) {
	if(!empty($_GET['name']) && !empty($_GET['email']) && !empty($_GET['phone'])) {
		$set=array(
			array(
				'id'=>$id,
				'first_name'=>$name.'1',
				'last_name'=>'',
				'custom_fields_values'=>array(
					array(
						'field_id'=>669985,
						'field_name'=>'pfafasd',
						'values'=>array(
							array(
								'value'=>'11111111'
							)
						)
					)
				),
				'request_id'=>'update'
			)
		);
		$response = &connect('update_contact', $token, $id, 0, 0, $set, $name); //инициируем запрос в API
		if($response['status'] == 'success') {
			$result = array(
				'status' => 'success',
				'data' => $response
			);
		} else if ($response['status'] == 'error') {
			$result = array(
				'status' => 'error',
				'data' => $response['data']
			);
		} else {
			$result = array(
				'status' => 'error',
				'data' => 'Ошибка! Сделка не была создана'
			);
		}
		echo json_encode($result);
	}
}

/**
* Точка входа
* Обязательно проверяем наличие токена, полученного при авторизации
* GET update - точка входа в функцию обновления контакта
* GET create - точка входа в функцию создания контакта
* GET create и update вызываются Ajax запросом
* @access public
*/
if(file_exists('sec/token.json')) {
	$content = json_decode(file_get_contents('sec/token.json'), true);

	if(!empty($_GET['update'])) {
		update_contact($content['access_token'], $_GET['id'], $_GET['name'], $_GET['phone'], $_GET['email']);
	} 
	else if(!empty($_GET['create'])) {
		create_contact($content['access_token'], $_GET['name'], $_GET['phone'], $_GET['email']);
	} else {
		if(empty($_GET['name'])) {
			get_all_contact($content['access_token'], 1);
		} else {
			get_contact($content['access_token'], $_GET['name']);
		}
	}
} else {
	$result = array(
		'status' => 'error',
		'data' => 'Ошибка авторизации проверте файл с токеном'
	);
	
	echo json_encode($result);
}
?>