<?php

header('HTTP/1.1 200 No Content');

$curlHandle = curl_init('http://api.amoscato.com/stream');
$filePointer = fopen('stream.json', 'w');

curl_setopt($curlHandle, CURLOPT_FILE, $filePointer);
curl_setopt($curlHandle, CURLOPT_HEADER, 0);

curl_exec($curlHandle);

curl_close($curlHandle);
fclose($filePointer);
