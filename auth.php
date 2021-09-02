<?php

date_default_timezone_set('Europe/Moscow');

function auth(string $operation, string $token = '') {
	if($operation = 'newtoken') {
		$link = 'https://yurytopgar.amocrm.ru/oauth2/access_token';
		$data = [
			'client_id' => '1e1173f5-2cd2-45d7-8fe0-36fc9a57482d',
			'client_secret' => 'sOdoQWOvhiAHDdtWDa4UvRdsUqA7hxbUvoCXdmUtyYz9iiplHTs9YC24a84IYaHX',
			'grant_type' => 'authorization_code',
			'code' => 'def5020082e7ab3adf3fe59ade25e699c7924e47032ed8aa462e3c7cf232cb9d221b01aa612b8b5591385e29ec8073c9fe0c0069a7d11e92f475192003fe38c378b87fc7dc17ac47d759ada75327a4532273c7a7b64a7c59495b04e731b30eec70f62d25e695fa743c48cf5f393e3608f1da13a59c203a1567e390d36c0f0b39bcb0f72721eaa771d03af6f8ce705303ee60d11fb5be80fca75829af5bb727a51610e3e99c72480a0774672d928479d23b86181ff7b4537c45223764c24234743d7d02a53dddbc22fc47543d62fa0689d760aa5528bcbeaed82b8609f686626864951603c2d365019097cc0685d9209a68a9db3394e85222c449b6f97157f8888ce8bbc3666d64a429feb3810df897d03762a6d1295d78e135f4539e61600a0bca3e257b635b0f54c6d667903d89d042d7a3a037c926090bf953201c1a4f74f58e12e7a8e8a32228e658322971cb922208c7f62f1854929c5908696548f1949afdfb8bd032b1e76f8a8f01594d31ff38fd1f99257544400abc84939ec0b849702ddedd8f11201dd2381494eb849fb44c557c9326ac457e16f3e156d348324e784a60651b4d16992b8127da75fc5e84cad2cfe9ab1d8fa10421bd33d75f08978b7444da0378f7c377c0ce455d900fa6e5',
			'redirect_uri' => 'https://2097-188-187-12-220.ngrok.io/',
		];
	} elseif($operation = 'returntoken') {
		$link = 'https://yurytopgar.amocrm.ru/oauth2/access_token';
		$data = [
			'client_id' => '1e1173f5-2cd2-45d7-8fe0-36fc9a57482d',
			'client_secret' => 'sOdoQWOvhiAHDdtWDa4UvRdsUqA7hxbUvoCXdmUtyYz9iiplHTs9YC24a84IYaHX',
			'grant_type' => 'refresh_token',
			'refresh_token' => $token,
			'redirect_uri' => 'https://2097-188-187-12-220.ngrok.io/',
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
		fwrite($fp, "<Files *>\r\nDeny from All\r\n</Files>");
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
			'data' => 'Ошибка №'.$code,
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