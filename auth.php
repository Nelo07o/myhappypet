<?php

function getToken()
{
    $file = 'APIkey.txt';
    $key = file_get_contents($file);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api3.actito.com/auth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array('Authorization:' . $key),
    ));

    $response = curl_exec($curl);
    // echo curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);
    $array = json_decode($response, true);
    return $array["accessToken"];
}
