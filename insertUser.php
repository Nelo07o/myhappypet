<?php

require('auth.php');
require_once('DB.php');

function insertUserIntoActito($token, $user)
{

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api3.actito.com/v4/entity/vetoquinol/table/test/profile',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_TIMEOUT => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($user),
        CURLOPT_HTTPHEADER => array(
            'allowUpdate: true',
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ),
    ));

    // $response = curl_exec($curl);

    curl_close($curl);
}
