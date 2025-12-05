<?php
$url = 'http://172.18.44.66/edcpro/index.php/main/kanwil_implementor?kanwil=X';
$json = file_get_contents($url);
$data = json_decode($json, true); // true → jadi array asosiatif

echo '<pre>';
print_r($data);  // Tampilkan array dengan rapi
echo '</pre>';
