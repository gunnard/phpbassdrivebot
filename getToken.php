<?php
$clientId = 'oiaj85u4lww35nv6eco0yrq5g3zibv';

$tokenAPI = 'https://id.twitch.tv/oauth2/token
	--data-urlencode
	?grant_type=refresh_token
	&refresh_token=eyJfaWQmNzMtNGCJ9%6VFV5LNrZFUj8oU231/3Aj
	&client_id=fooid
	&client_secret=barbazsecret';

$data = array (
	'client_id' => $clientId,
	'client_secret' => '1mb2v2fciec6b6z4hck8tkozvvhjf8',
	'grant_type' => 'client_credentials'
);

$post_data = json_encode($data);
$crl = curl_init('https://id.twitch.tv/oauth2/token');
curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($crl, CURLINFO_HEADER_OUT, true);
curl_setopt($crl, CURLOPT_POST, true);
curl_setopt($crl, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($crl, CURLOPT_HTTPHEADER, array(
	'Content-Type: application/json'
));
$result = curl_exec($crl);
$result = json_decode($result);
curl_close($crl);
var_dump($result);

