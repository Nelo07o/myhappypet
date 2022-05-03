<?php

// ? Calling required files
require('auth.php');
require_once('DB.php');
require('addLogEvents.php');

// ? Function that defines a time zone
date_default_timezone_set('Europe/Paris');

function readLastImportDate()
{
    $infosJSON = 'infosJSONUsers.json';
    if (file_exists($infosJSON)) {
        $decodeData = json_decode(file_get_contents($infosJSON), true);
        return $decodeData['lastImportSuccess'];
    } else {
        return 0;
    }
}

// ? Function that will check the status of the last import
function checkImportSuccess($token)
{
    $infosJSON = 'infosJSONUsers.json';
    if (!file_exists($infosJSON)) {
        return;
    }

    $fileImport = file_get_contents($infosJSON);
    $decodeJSON = json_decode($fileImport, true);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api3.actito.com/v4/entity/vetoquinol/import/' . $decodeJSON['lastImportId'] . '/result',
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

    $decodeData = json_decode($response, true);

    $withErrors = $decodeData['withErrors'];

    // ? Check if the withErrors value is false
    if ($withErrors == false) {
        $decodeJSON['lastImportSuccess'] = $decodeJSON['lastImportQuery'];
        file_put_contents($infosJSON, json_encode($decodeJSON));
    }
}

// ? Function that will generate a CSV file
function buildAndZIPCSVUser($lastImportSuccessDate)
{
    // ? Connection to the MyHappyPet local database 
    $db = connectToDB('myhappypet');

    $tableUser = [];
    // ? SQL query that will select all users who have been modified recently
    $row = $db->query('SELECT * FROM user WHERE `modified_on` >= ' . $lastImportSuccessDate . ' limit 2');
    // ? Loop that reads the user table
    while ($rows = $row->fetch(PDO::FETCH_ASSOC)) {

        $country = '';
        $language = '';
        // ? Conversion of the app_country_code_id field to the format expected by Actito
        switch ($rows['app_country_code_id']) {
            case 1:
                $country = 'BE';
                $language = 'FR';
                break;
            case 2:
                $country = 'BE';
                $language = 'NL';
                break;
            case 3:
                $country = 'SE';
                $language = 'SV';
                break;
            case 4:
                $country = 'NL';
                $language = 'NL';
                break;
            case 5:
                $country = 'FR';
                $language = 'FR';
                break;
            default:
                $country = 'FR';
                $language = 'FR';
                break;
        }

        $tableUser[] = [$rows['last_name'], $rows['first_name'], $rows['email_address'], $language, $country, $rows['has_uploaded_paper_card'], $rows['referral_code'],];
    }
    if (count($tableUser) == 0) return false;

    // ? Writing the CSV file with the data previously recovered
    $fichier_csv = fopen("users.csv", "w+");
    // ? Allows character encoding in utf-8
    fprintf($fichier_csv, chr(0xEF) . chr(0xBB) . chr(0xBF));
    // ? Header generation
    $header = ['lastName', 'firstName', 'emailAddress', 'motherLanguage', 'addressCountry', 'uploadedPaperCard', 'referralCode'];
    fputcsv($fichier_csv, $header, ";");
    // ? Browse the table and insert data into the file
    foreach ($tableUser as $lignes) {
        fputcsv($fichier_csv, $lignes, ";");
    }
    fclose($fichier_csv);
    // ? ZIP CSV file
    $zip = new ZipArchive();

    $zipFileName = "users.zip";
    // ? Check if the file exists
    if (file_exists($zipFileName)) {
        // ? Delete the file
        unlink($zipFileName);
    }
    // ? Opening the zip archive or creating it if it does not exist
    if ($zip->open($zipFileName, ZIPARCHIVE::CREATE) != TRUE) {
        addLogEventUsers('Error - zip creation');
        return false;
    }
    // ? Add CSV file in zip
    $resultAddFile = $zip->addFile('users.csv', 'users.csv');
    if ($resultAddFile == false) {
        addLogEventUsers('Error - zip csv');
        $zip->close();
        return false;
    }
    // ? ZIP closure
    $zip->close();
    // ? Delete the CSV file
    unlink('users.csv');
    addLogEventUsers('Sucess - creation CSV file');
    return true;
}

// ? Function that will allow the import of the ZIP file
function importCSV($token, $actitoTableName)
{
    // ? Function that generates a date in milliseconds
    $date = date(round(microtime(true) * 1000));
    // $dateImport = 'dateImport.txt';

    $zipFileName = 'users.zip';
    $paramFileName = 'parametersUsers.json';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api3.actito.com/v4/entity/vetoquinol/table/' . $actitoTableName . '/import',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_TIMEOUT => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            // ? Importing the ZIP file ( MIME type of the resource )
            'inputFile' => new CURLFile($zipFileName, 'application/zip'),
            // ? Importing the parameters file (  MIME type of the resource )
            'parameters' => new CURLFile($paramFileName, 'application/json'),
        ),
        CURLOPT_HTTPHEADER => array(
            // ? Use of authentication token
            'Authorization: Bearer ' . $token,
            // ? Indicate the MIME type of the resource
            'Content-Type: multipart/form-data',
        ),
    ));
    // ? Execution of the request
    $response = curl_exec($curl);
    $result = json_decode($response, true);
    var_dump($result);
    // ? Closing the request
    curl_close($curl);

    $savedData = [
        'lastImportId' => 0,
        'lastImportQuery' => 0,
        'lastImportSuccess' => 0,
    ];

    $infosJSON = 'infosJSONUsers.json';

    if (file_exists($infosJSON)) {
        $savedData = json_decode(file_get_contents($infosJSON), true);
    }

    // ? Saving information in a log file
    if (is_array($result) && array_key_exists('id', $result)) {
        addLogEventUsers('Success - import id =' . $result['id']);
        $savedData['lastImportId'] = $result['id'];
        $savedData['lastImportQuery'] = $date;
        file_put_contents($infosJSON, json_encode($savedData));
        unlink('users.zip');
    } else {
        addLogEventUsers('Error - import failed' . $response . '');
    }
}

function import()
{
    $token = getToken();
    checkImportSuccess($token);

    $lastImportSuccessDate = readLastImportDate();

    if (buildAndZIPCSVUser($lastImportSuccessDate)) {
        importCSV($token, 'test');
    }
}

// ? Calling import 
import();
