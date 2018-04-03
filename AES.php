<?php
//AES 加密算法
function encrypt($input, $key) {
	$key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
	$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB); //获得加密算法的分组大小
	//var_dump($size);exit;
	$input = pkcs5_pad($input, $size);
	$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, ''); //打开算法和模式对应的模块
	$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND); //初始化向量
	mcrypt_generic_init($td, $key, $iv);
	$data = mcrypt_generic($td, $input);
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	$data = base64_encode($data);
	return $data;
}
function pkcs5_pad ($text, $blocksize) {
	$pad = $blocksize - (strlen($text) % $blocksize);
	return $text . str_repeat(chr($pad), $pad);
}
function decrypt($sStr, $sKey) {
	$key = substr(openssl_digest(openssl_digest($sKey, 'sha1', true), 'sha1', true), 0, 16);
	$decrypted= mcrypt_decrypt(
			MCRYPT_RIJNDAEL_128,
			$key,
			base64_decode($sStr),
			MCRYPT_MODE_ECB
	);
	$dec_s = strlen($decrypted);
	$padding = ord($decrypted[$dec_s-1]);
	$decrypted = substr($decrypted, 0, -$padding);
	return $decrypted;
}

$str="xxxxxxxxxxxx";
$key="gTrUYb";
$en=encrypt($str,$key);
$de=decrypt('e99T663RP77lCYhAQKrwD9mjXxYj4J+ZPSANfKBnL6M=',$key);
var_dump($en,$de);exit;
