<?php
function get_all_contact($token, $page) {
	$subdomain = 'supergird2012'; //Поддомен нужного аккаунта
	$link = 'https://' . $subdomain . '.amocrm.ru/api/v4/contacts?page='.$page.'&limit=250'; //Формируем URL для запроса

	/** Формируем заголовки */
	$headers = [
		'Authorization: Bearer ' . $token
	];
	/**
	 * Нам необходимо инициировать запрос к серверу.
	 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
	 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
	 */
	$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
	/** Устанавливаем необходимые опции для сеанса cURL  */
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
	curl_setopt($curl,CURLOPT_URL, $link);
	curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl,CURLOPT_HEADER, false);
	curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
	$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
	$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);
	/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
	$code = (int)$code;
	$errors = [
		400 => 'Bad request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not found',
		500 => 'Internal server error',
		502 => 'Bad gateway',
		503 => 'Service unavailable',
	];

	try
	{
		/** Если код ответа не успешный - возвращаем сообщение об ошибке  */
		if ($code < 200 || $code > 204) {
			throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
		}
	}
	catch(\Exception $e)
	{
		die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
	}

	$response = json_decode($out, true);

	if($response) {
		get_all_contact_resp($response, $page);
	}
}

function get_all_contact_resp($response, $page) {

	// 
	// Нужна оптимизация, чтобы каждый раз не опрашивать всю БД и не создавать файл со всеми имеющимися контактами (при N > 1000 возможны большие задержки).
	// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
	// Функция исследует файл base_contracts в котором хранит все контакты AmoCRM и БД AmoCRM, из-за API невозможно выполнить фильтр на стороне Амо по их системным полям Телефона и Почты.
	// независимо от версии API, исследовались разные варианты GET-запросов к AmoCRM с попытками отфильтровать по полям.
	// Далее подсчитываем количество строк, берем в учет, что в ответе от AmoCRM может быть только 250 контактов, поэтому опрашиваем все страницы БД.
	// Затем собираем нужную нам информацию в файл.
	// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
	// Далее запускаем поиск по телефону или почте уже работая с файлом контактов на сервере.
	// 

	$last_page = false;
	$filename = "base_contracts.json";
	$count_str = count($response['_embedded']['contacts']); //количество контактов в ответе
	$count_file = count(file($filename)) - 4; //количество контактов в файле
	if($count_str < 250) {
		$last_page = true;
	}
	if($page == 1 && file_exists($filename)) {
		$new_filename = str_replace('.json', '', $filename).'_backup.json';
		copy($filename, $new_filename);
		unlink($filename);
	}
	$fp = fopen($filename,'a');
	if($page == 1) {
		fwrite($fp, "{\r\n	\"contacts\": [\r\n");
	}
	fclose($fp);
	for($i = 0; $i < $count_str; $i++) {
		$id_contact = $response['_embedded']['contacts'][$i]['id'];
		$name_contact = $response['_embedded']['contacts'][$i]['name'];

		//ищем телефон и почту в полях
		if(!empty($response['_embedded']['contacts'][$i]['custom_fields_values'])) {
			for($t = 0; $t < count($response['_embedded']['contacts'][$i]['custom_fields_values']); $t++) {
				if($response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['field_name'] == "Телефон") {
					if(!empty($response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'])) {
						$phone_contact = $response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'];
					} else {
						$phone_contact = null;
					}
				} else if ($response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['field_name'] == "Email") {
					if(!empty($response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'])) {
						$email_contact = $response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'];
					} else {
						$email_contact = null;
					}
				}
			}
		} else {
			$phone_contact = null;
			$email_contact = null;
		}

		$fp = fopen($filename,'a');
		$result = json_encode(array(
			'id_contact' => $id_contact,
			'name_contact' => $name_contact,
			'phone_contact' => $phone_contact,
			'email_contact' => $email_contact,
			), JSON_UNESCAPED_UNICODE, JSON_FORCE_OBJECT);
		if($i == $count_str - 1 && $last_page) {
			fwrite($fp, "		".$result."\r\n"); //закрываемся
		} else {
			fwrite($fp, "		".$result.",\r\n");
		}
	}
	if($count_str == 250) {
		$page = $page + 1;
		$content = json_decode(file_get_contents('token.json'), true);
		if(empty($_GET['name'])) {
			get_all_contact($content['access_token'], $page, 0);
		}
	} else {
		fwrite($fp, "	]\r\n}");
		fclose($fp);

		search_contact_in_json($_GET['email'], $_GET['phone']);
	}
}

function search_contact_in_json($email, $phone) {
	$content = array();
	$filename = "base_contracts.json";
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
		echo json_encode($data);
	} else {
		echo json_encode('Такого контакта нет, создать его?');
	}
}

function update_contact($token, $id, $name, $phone, $email) {
	//если все данные есть
	//отправил запрос в ТП, неизвесно почему контакты не обновляются, запрос PATCH, при POST контакты создаются как положено

	if(!empty($_GET['name']) && !empty($_GET['email']) && !empty($_GET['phone'])) {
		$set=array(
			array(
				"id"=>$id,
				"name"=>$name,
				"first_name"=>$name,
				"last_name"=>"",
				"updated_at"=>time(),
				"custom_fields_values"=>array(
					array(
						"field_id"=>633427,
						"values"=>array(
							array(
								"value"=>"111125@gmail.com",
								"enum_code"=>"WORK"
							)
						)
					),
					array(
						"field_id"=>633425,
						"values"=>array(
							array(
								"value"=>"242344234",
								"enum_code"=>"WORK"
							)
						)
					)
				),
				"request_id"=>"update"
			)
		);

		var_dump(json_encode($set));

		$subdomain = 'supergird2012'; //Поддомен нужного аккаунта
		$link = 'https://' . $subdomain . '.amocrm.ru/api/v4/contacts/'.$id; //Формируем URL для запроса

		/** Формируем заголовки */
		$headers = [
			'Authorization: Bearer ' . $token
		];
		/**
		 * Нам необходимо инициировать запрос к серверу.
		 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
		 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
		 */
		$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
		/** Устанавливаем необходимые опции для сеанса cURL  */
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
		curl_setopt($curl,CURLOPT_URL, $link);
		curl_setopt($curl,CURLOPT_SSLVERSION, 6);
		curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'PATCH');
		curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($set));
		curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl,CURLOPT_HEADER, false);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
		$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
		$code = (int)$code;
		$errors = [
			400 => 'Bad request',
			401 => 'Unauthorized',
			403 => 'Forbidden',
			404 => 'Not found',
			500 => 'Internal server error',
			502 => 'Bad gateway',
			503 => 'Service unavailable',
		];

		try
		{
			/** Если код ответа не успешный - возвращаем сообщение об ошибке  */
			if ($code < 200 || $code > 204) {
				throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
			}
		}
		catch(\Exception $e)
		{
			die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
		}

		$response = json_decode($out, true);
	}
}

function create_contact($token, $name, $phone, $email) {
		//если все данные есть
		//хорошо бы заносить имя и фамилию отдельно...пока так, вернемся к этому позже
		if(!empty($_GET['name']) && !empty($_GET['email']) && !empty($_GET['phone'])) {
			$set=array(
				array(
					"name"=>$name,
					"first_name"=>$name,
					"last_name"=>"",
					"updated_at"=>time(),
					"custom_fields_values"=>array(
						array(
							"field_id"=>633427,
							"values"=>array(
								array(
									"value"=>$email,
									"enum_code"=>"WORK"
								)
							)
						),
						array(
							"field_id"=>633425,
							"values"=>array(
								array(
									"value"=>$phone,
									"enum_code"=>"WORK"
								)
							)
						)
					),
					"request_id"=>"create"
				)
			);
	
			var_dump(json_encode($set));
	
			$subdomain = 'supergird2012'; //Поддомен нужного аккаунта
			$link = 'https://' . $subdomain . '.amocrm.ru/api/v4/contacts'; //Формируем URL для запроса
	
			/** Формируем заголовки */
			$headers = [
				'Authorization: Bearer ' . $token
			];
			/**
			 * Нам необходимо инициировать запрос к серверу.
			 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
			 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
			 */
			$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
			/** Устанавливаем необходимые опции для сеанса cURL  */
			curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
			curl_setopt($curl,CURLOPT_URL, $link);
			curl_setopt($curl,CURLOPT_SSLVERSION, 6);
			curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
			curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($set));
			curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl,CURLOPT_HEADER, false);
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
			$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
			$code = (int)$code;
			$errors = [
				400 => 'Bad request',
				401 => 'Unauthorized',
				403 => 'Forbidden',
				404 => 'Not found',
				500 => 'Internal server error',
				502 => 'Bad gateway',
				503 => 'Service unavailable',
			];
	
			try
			{
				/** Если код ответа не успешный - возвращаем сообщение об ошибке  */
				if ($code < 200 || $code > 204) {
					throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
				}
			}
			catch(\Exception $e)
			{
				die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
			}
	
			$response = json_decode($out, true);
		}
}

function get_contact($token, $name = '') {
	$subdomain = 'supergird2012'; //Поддомен нужного аккаунта
	if(!empty($name)) {
		$link = 'https://' . $subdomain . '.amocrm.ru/api/v4/contacts?filter[name]='.$name.''; //Формируем URL для запроса
	}

	/** Формируем заголовки */
	$headers = [
		'Authorization: Bearer ' . $token
	];
	/**
	 * Нам необходимо инициировать запрос к серверу.
	 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
	 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
	 */
	$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
	/** Устанавливаем необходимые опции для сеанса cURL  */
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
	curl_setopt($curl,CURLOPT_URL, $link);
	curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl,CURLOPT_HEADER, false);
	curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
	$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
	$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);
	/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
	$code = (int)$code;
	$errors = [
		400 => 'Bad request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not found',
		500 => 'Internal server error',
		502 => 'Bad gateway',
		503 => 'Service unavailable',
	];

	try
	{
		/** Если код ответа не успешный - возвращаем сообщение об ошибке  */
		if ($code < 200 || $code > 204) {
			throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
		}
	}
	catch(\Exception $e)
	{
		die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
	}

	$response = json_decode($out, true);
	
	if($response) {
		$count_str = count($response['_embedded']['contacts']); //количество контактов в ответе
		$data = array();
		for($i = 0; $i < $count_str; $i++) {
			$id_contact = $response['_embedded']['contacts'][$i]['id'];
			$name_contact = $response['_embedded']['contacts'][$i]['name'];

			//ищем телефон и почту в полях
			if(!empty($response['_embedded']['contacts'][$i]['custom_fields_values'])) {
				for($t = 0; $t < count($response['_embedded']['contacts'][$i]['custom_fields_values']); $t++) {
					if($response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['field_name'] == "Телефон") {
						if(!empty($response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'])) {
							$phone_contact = $response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'];
						} else {
							$phone_contact = null;
						}
					} else if ($response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['field_name'] == "Email") {
						if(!empty($response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'])) {
							$email_contact = $response['_embedded']['contacts'][$i]['custom_fields_values'][$t]['values'][0]['value'];
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
		echo json_encode($data);
	} else {
		if(empty($_GET['phone']) || empty($_GET['email'])) {
			echo json_encode('Такого контакта нет, для поиска по другим параметрам оставьте поле пустым');
		} else {
			echo json_encode('Такого контакта нет, хотите создать контакт?');
		}
	}
}

if(file_exists('token.json')) {
	$content = json_decode(file_get_contents('token.json'), true);

	if(!empty($_GET['update'])) {
		update_contact($content['access_token'], $_GET['id'], $_GET['name'], $_GET['phone'], $_GET['email']);
	} else if(!empty($_GET['create'])) {
		create_contact($content['access_token'], $_GET['name'], $_GET['phone'], $_GET['email']);
	} else {
		if(empty($_GET['name'])) {
			get_all_contact($content['access_token'], 1);
		} else {
			get_contact($content['access_token'], $_GET['name']);
		}
	}
}
?>