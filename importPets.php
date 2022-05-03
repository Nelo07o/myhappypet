<?php

require('auth.php');
require_once('DB.php');
require('addLogEvents.php');

date_default_timezone_set('Europe/Paris');

function readLastImportDate()
{
    $infosJSON = 'infosJSONPets.json';
    if (file_exists($infosJSON)) {
        $decodeData = json_decode(file_get_contents($infosJSON), true);
        return $decodeData['lastImportSuccess'];
    } else {
        return 0;
    }
}

function checkImportSuccess($token)
{
    $infosJSON = 'infosJSONPets.json';
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
    var_dump($response);
    curl_close($curl);

    $decodeData = json_decode($response, true);

    $withErrors = $decodeData['withErrors'];
    $rowsInError = $decodeData['rowsInError'];

    if ($withErrors == false && $rowsInError == 0) {
        $decodeJSON['lastImportSuccess'] = $decodeJSON['lastImportQuery'];
        file_put_contents($infosJSON, json_encode($decodeJSON));
    }
}

function buildAndZIPCSVPets($lastImportSuccessDate)
{
    $db = connectToDB('myhappypet');

    $customTablePets = [];

    $row = $db->query('SELECT pet.*, user.email_address FROM pet INNER JOIN user ON user.id = pet.user_id AND pet.modified_on >= ' . $lastImportSuccessDate . ' limit 2');

    while ($rows = $row->fetch(PDO::FETCH_ASSOC)) {

        $birthDate = date("d/m/Y", ($rows['birth_date'] / 1000));

        $customTablePets[] = [$rows['id'], $rows['name'], $rows['type'], $birthDate, $rows['breed'], $rows['gender'], $rows['neutered'], $rows['email_address'],];
    }

    if (count($customTablePets) == 0) return false;

    $fichier_csv = fopen("pets.csv", "w+");

    fprintf($fichier_csv, chr(0xEF) . chr(0xBB) . chr(0xBF));

    $header = ['petAppId', 'petName', 'petType', 'petBirthDate', 'petBreed', 'petGender', 'petNeutered', 'emailAddress',];
    fputcsv($fichier_csv, $header, ";");

    foreach ($customTablePets as $lignes) {
        fputcsv($fichier_csv, $lignes, ";");
    }
    fclose($fichier_csv);

    $zip = new ZipArchive();

    $zipFileName = "pets.zip";
    if (file_exists($zipFileName)) {
        unlink($zipFileName);
    }

    if ($zip->open($zipFileName, ZIPARCHIVE::CREATE) != TRUE) {
        addLogEventPets('Error - zip creation');
        return false;
    }

    $resultAddFile = $zip->addFile('pets.csv', 'pets.csv');

    if ($resultAddFile == false) {
        addLogEventPets('Error - zip csv');
        $zip->close();
        return false;
    }
    $zip->close();
    unlink('pets.csv');
    addLogEventPets('Sucess - creation CSV file');
    return true;
}

function importCSV($token, $actitoCustomTableName)
{
    $date = date(round(microtime(true) * 1000));

    $zipFileName = 'pets.zip';
    $paramFileName = 'parametersPets.json';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api3.actito.com/v4/entity/vetoquinol/customTable/' . $actitoCustomTableName . '/import',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_TIMEOUT => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'inputFile' => new CURLFile($zipFileName, 'application/zip'),
            'parameters' => new CURLFile($paramFileName, 'application/json'),
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token,
            'Content-Type: multipart/form-data',
        ),
    ));
    $response = curl_exec($curl);
    $result = json_decode($response, true);
    var_dump($result);
    curl_close($curl);

    $savedData = [
        'lastImportId' => 0,
        'lastImportQuery' => 0,
        'lastImportSuccess' => 0,
    ];

    $infosJSON = 'infosJSONPets.json';

    if (file_exists($infosJSON)) {
        $savedData = json_decode(file_get_contents($infosJSON), true);
    }

    if (is_array($result) && array_key_exists('id', $result)) {
        addLogEventPets('Success - import id =' . $result['id']);
        $savedData['lastImportId'] = $result['id'];
        $savedData['lastImportQuery'] = $date;
        var_dump($savedData);
        file_put_contents($infosJSON, json_encode($savedData));
        unlink('pets.zip');
    } else {
        addLogEventPets('Error - import failed');
    }
}

function import()
{
    $token = getToken();
    checkImportSuccess($token);

    $lastImportSuccessDate = readLastImportDate();

    if (buildAndZIPCSVPets($lastImportSuccessDate)) {
        importCSV($token, 'TESTpets');
    }
}

import();

// $token = getToken();
// buildAndZIPCSVPets(0);
