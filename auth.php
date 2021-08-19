<?php

include_once('lib.php');
date_default_timezone_set("Europe/Moscow");

function grantNewToken() {
	$subdomain = 'supergird2012'; //Поддомен нужного аккаунта
	$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса
	
	/** Соберем данные для запроса */
	$data = [
		'client_id' => '32207fa9-8984-44d8-84b8-cac1ef5f0b47',
		'client_secret' => 'loLEfDvPH6yXW3wHW4UxWueh9EdPg1vDhfbNbsFYxPy7KPRrBNWEF3qxP6yRT8pu',
		'grant_type' => 'authorization_code',
		'code' => 'def502002a10494be6b546277098dac36015489c7fcef4a838d09210c6561d7379ac2ac0419019e040df5ff9d41da2eb7473f30ebea01cfbf35ebd3acc6a82858e3a06ce55d167488bedd35380a00e49d6371f969265c01345e702457bd78b382122f4d8832ea5da8ce3b8d994af8c953473fdf7ff17280a982a69e78e006d2669378fbf8cd1d3fd717354e6523e001d6afe2f30f37eb92b0dc1edd6f687ef6c0446a4073d03f937aa6ee906e173116c20218034efc57e65199ba1d83e2ba9d5fd3a460db803d951b3d9f9d422c7bf2549ddaa071d74b30deec8be7d5614408ef1e329a660cedb9bf440f12913ec9380cd9931757f9a9f6af1ef86776c6474fffe4aaed3966ee51ee1b2ddb4b5ebc7d6d446d41cdde123fceb26128373e59581b1988b9614645940dd983753af7d40658a8917fb2762416cce87b7e12fb36a6fc57038e01e17fcbe6dfd8ec3fb67963e0aca9df2b3295490457fff132e4d285d38ce457041c605b7aee5e8e8f6036cfd23a24f32b5ed13409c841cda36184fada586b702b28c560baa1c72bd253b3e353b5e4fec6919ba247e7bf390e9e5538ccd856debd2920a2169ffff716994d6614bab6d62ca35dc53b07f189311ee43efa430080487f22dc3fb',
		'redirect_uri' => 'https://7cb54e18d608.ngrok.io/',
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
	curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
	curl_setopt($curl,CURLOPT_HEADER, false);
	curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
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
	
	/**
	 * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
	 * нам придётся перевести ответ в формат, понятный PHP
	 */
	
	$response = json_decode($out, true);

	if($response) {

		/* записываем конечное время жизни токена */
		$response["endTokenTime"] = time() + $response["expires_in"];

		$responseJSON = json_encode($response);

		/* передаём значения наших токенов в файл */
		$filename = "token.json";
		$f = fopen($filename,'w');
		fwrite($f, $responseJSON);
		fclose($f);

		$response = json_decode($responseJSON, true);

		return $response;
	}
	else {
		return false;
	}
}

if(file_exists('token.json')) {
	$content = json_decode(file_get_contents('token.json'), true);
	if($content['endTokenTime'] <= time()) {
		returnNewToken($content['refresh_token']);
	}
} else {
	grantNewToken();
}


function returnNewToken($token) {

	$subdomain = 'supergird2012'; //Поддомен нужного аккаунта
	$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

	/** Соберем данные для запроса */
	$data = [
		'client_id' => '32207fa9-8984-44d8-84b8-cac1ef5f0b47',
		'client_secret' => 'loLEfDvPH6yXW3wHW4UxWueh9EdPg1vDhfbNbsFYxPy7KPRrBNWEF3qxP6yRT8pu',
		'grant_type' => 'refresh_token',
		'refresh_token' => $token,
		'redirect_uri' => 'https://7cb54e18d608.ngrok.io/',
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
	curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
	curl_setopt($curl,CURLOPT_HEADER, false);
	curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
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

	/**
	 * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
	 * нам придётся перевести ответ в формат, понятный PHP
	 */

	$response = json_decode($out, true);

	if($response) {

		/* записываем конечное время жизни токена */
		$response["endTokenTime"] = time() + $response["expires_in"];

		$responseJSON = json_encode($response);

		/* передаём значения наших токенов в файл */
		$filename = "token.json";
		$f = fopen($filename,'w');
		fwrite($f, $responseJSON);
		fclose($f);

		$response = json_decode($responseJSON, true);

		return $response;
	}
	else {
		return false;
	}
}
?>