<?php

// ? Calling required files
require('auth.php');

// ? Initialization of a function that will create a webhook
function createWebHook($token)
{
    // ? Writing the json file which will contain the parameters expected by Actito
    $array = [
        "on" => "PROFILE_TABLE",
        "onElementId" => "1",
        "eventType" => "UPDATED_SUBSCRIPTION",
        "onFields" => ["Newsletter"],
        "targetUrl" => "https://loyalty.vetoquinol.com/webhook_profiles.php",
        "webhookPushType" => "ONE_BY_ONE",
        "isActive" => true,
    ];

    // ? Encoding the table and inserting the data into a file.json
    $json = json_encode($array, JSON_UNESCAPED_SLASHES);
    file_put_contents("file.json", $json);
    var_dump($json);


    $curl = curl_init();
    // ? cURL request that will post the files written previously
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api3.actito.com/v4/entity/vetoquinol/webhookSubscription',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_TIMEOUT => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_HTTPHEADER => array(

            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',

        ),
    ));

    $response = curl_exec($curl);
    var_dump($response);
    if (curl_errno($curl)) {
        echo curl_error($curl);
    }
    curl_close($curl);
}

// ? Calling the necessary functions
$token = getToken();
createWebHook($token);
