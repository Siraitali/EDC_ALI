<?php
class EdcPro
{
    // Fungsi private untuk download data dari URL
    private function download_edcpro($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Tambahkan user-agent supaya server menerima request
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
        // Jika server pakai HTTPS tapi sertifikat self-signed
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
            curl_close($ch);
            return [];
        }

        curl_close($ch);

        // Cek respons HTTP
        if ($httpcode != 200) {
            echo "HTTP Error: $httpcode";
            return [];
        }

        // Pisahkan baris
        $resultArray = str_getcsv($result, "\n");

        $dt = array();
        if (
            count($resultArray) > 1
            && strpos($result, 'A PHP Error was encountered') === false
            && strpos($result, 'A Database Error Occurred') === false
            && strpos($result, 'Not Authorized') === false
        ) {

            foreach ($resultArray as $k => $v) {
                // Auto deteksi delimiter (koma atau titik koma)
                $delimiter = (substr_count($v, ",") > substr_count($v, ";")) ? "," : ";";
                $dt[$k] = str_getcsv($v, $delimiter);
            }
        } else {
            echo "Data kosong atau ada error server!";
        }

        return $dt;
    }

    // Fungsi public untuk memanggil private function
    public function getData($url)
    {
        return $this->download_edcpro($url);
    }
}

// ----------------------
// Cara pakai class
// ----------------------
$edc = new EdcPro();
$data = $edc->getData("http://172.18.44.66/edcpro/index.php/detail/export?group_code=X");

// Cek hasil
echo "<pre>";
print_r($data);
echo "</pre>";
