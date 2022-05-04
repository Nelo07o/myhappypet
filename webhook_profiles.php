<?php

// ? Calling required files
require('auth.php');
require_once('DB.php');

// ? Receive and decode json file
$file = 'file.log';
$data = file_get_contents($file);
$decodeData = json_decode($data, true);
header('Status: 200');

/*
$data = file_get_contents('php://input');
$decodeData = json_decode($data, true);
header('Status: 200');
*/

//? Initialization of variables with the data we need
$profileId = $decodeData['data']['profileId'];
$newsLetter = (int)$decodeData['data']['Newsletter'];

// ? Initialization of a function that will update the user
function updateUser($token, $profileId, $newsLetter)
{
    $curl = curl_init();
    // ? cURL request that fetches the profile corresponding to the data received
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api3.actito.com/v4/entity/vetoquinol/table/test/profile/' . $profileId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    // ? Profile table decoding
    $responseDecode = json_decode($response, true);

    $emailAddress = '';

    // ? Traverse the array until you find the emailAddress field
    for ($i = 0; $i < count($responseDecode['attributes']); $i++) {
        if ($responseDecode['attributes'][$i]['name'] == 'emailAddress') {
            $emailAddress = $responseDecode['attributes'][$i]['value'];
            break;
        }
    }

    // ? SQL query that will update the corresponding profile
    $db = connectToDB('myhappypet');
    $stmt = $db->prepare('UPDATE user SET emails_optin = ? WHERE email_address LIKE ?');
    $stmt->execute(array($newsLetter, $emailAddress));
}

// ? Calling the necessary functions
$token = getToken();
updateUser($token, $profileId, $newsLetter);
