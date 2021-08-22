<?php

date_default_timezone_set('Europe/Moscow');

function auth(string $operation, string $token = '') {
	if($operation = 'newtoken') {
		$link = 'https://supergird2012.amocrm.ru/oauth2/access_token';
		$data = [
			'client_id' => '32207fa9-8984-44d8-84b8-cac1ef5f0b47',
			'client_secret' => 'loLEfDvPH6yXW3wHW4UxWueh9EdPg1vDhfbNbsFYxPy7KPRrBNWEF3qxP6yRT8pu',
			'grant_type' => 'authorization_code',
			'code' => 'def5020041b6749965c7ca2195d777c6ee4a3d79dc511997f41385d9623dfa227aba904b229d6b74496ca050543af3dda3654292985bff2c4202735a4dd57a90c9b60b13f399f1c2665690d8913645b302e9210e810c6a932c96b93f34c056d1ae2569068b3e76653de8aeedf11f3b0aabc444d6a468496a40e28cba035e7a59bf386cecd17509c82e2cfadd539a8cda06378e75ce07dd16ebefeddc74f36fac7b775eae9dcf8bcc1c095e16e850a4187ec9f5bf34f937dc5707841a043e4db0b75431edd0fc255e9088d97c085700eb077cc5f2e734148d88d499305e481ed1523e44a23bc1b1fe57e34b66f0bdff9b4222f2e9aab6457d86965c032623f26be5771275fdf3e6113fa1d9b400a321c0cb9011025790cf2693730d3eff287e5a4e4df2dae6f1210987536851b4ec6d0c44a60026f7d3f88be1b2b1d5e376c649f98cf02b76547d6e38b02344d2ef74f7543c73bd107d7a35716f3add0155c6bd976e7f33ce6c982c4ec432131065649e0a412b16206081254d22d7a4b5520513fe7866f373a86126a411029ff17140c91107d61d4fe96c27d3cb4ae3bafee417cefd7b5edb38253818734e33e6c64911f43d2da5de6fb22c322e282595f8f933adf4e7826c2fea40cd29907b93a00961',
			'redirect_uri' => 'https://7ba4-188-187-12-220.ngrok.io/',
		];
	} elseif($operation = 'returntoken') {
		$link = 'https://supergird2012.amocrm.ru/oauth2/access_token';
		$data = [
			'client_id' => '32207fa9-8984-44d8-84b8-cac1ef5f0b47',
			'client_secret' => 'loLEfDvPH6yXW3wHW4UxWueh9EdPg1vDhfbNbsFYxPy7KPRrBNWEF3qxP6yRT8pu',
			'grant_type' => 'refresh_token',
			'refresh_token' => $token,
			'redirect_uri' => 'https://7ba4-188-187-12-220.ngrok.io/',
		];
	} else {
		die('Непредвиденная ошибка');
	}

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
	curl_setopt($curl, CURLOPT_URL, $link);
	curl_setopt($curl, CURLOPT_HTTPHEADER,['Content-Type:application/json']);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	$out = curl_exec($curl);
	$code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
	$response = json_decode($out, true);

	if($out && ($code == 200 || $code == 204)) {
		if(!is_dir('sec')) {
			mkdir('sec', 0777, true);
		}

		$fp = fopen('sec/.htaccess','w',0777);
		fwrite($fp, '<Files *>\r\nDeny from All\r\n</Files>');
		fclose($fp);

		/* записываем конечное время жизни токена */
		$response['endTokenTime'] = time() + $response['expires_in'];
		$response = json_encode($response);
		/* передаём значения наших токенов в файл */
		$fp = fopen('sec/token.json','w',0777);
		fwrite($fp, $response);
		fclose($fp);
		// $response = json_decode($response, true);
		
		$result = array(
			'status' => 'success',
			'data' => 'Авторизация выполнена успешно'
		);
	} elseif ($code < 200 || $code > 204) {
		$result = array(
			'status' => 'error',
			'data' => 'Ошибка №'.$code.' - '.$response['detail'].' - '.$response['hint']
		);
	}
	echo json_encode($result);
}

if(file_exists('sec/token.json')) {
	$content = json_decode(file_get_contents('sec/token.json'), true);
	if($content['endTokenTime'] <= time()) {
		auth('returntoken', $content['refresh_token']);
	}
} else {
	auth('newtoken');
}
?>