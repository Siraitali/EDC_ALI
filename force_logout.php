<?php
// force_logout.php

$baseUrl = "https://wpe.bri.co.id:8080";

// Lokasi cookie
$cookieFile = __DIR__ . "/cookie.txt";

// Hapus cookie lama kalau ada
if (file_exists($cookieFile)) {
    unlink($cookieFile);
    echo "Cookie lama dihapus.\n";
}

// Endpoint logout
$logoutUrls = [
    "/api/guest/logout",
    "/api/logout"
];

foreach ($logoutUrls as $logout) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . $logout,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Accept: application/json",
            "User-Agent: Mozilla/5.0"
        ],
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Logout URL: $logout\n";
    echo "HTTP: $httpcode\n";
    echo "Response: $response\n\n";
}

echo "Proses logout selesai. Silakan coba login ulang.\n";
