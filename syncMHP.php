<?php

// ? Calling required files
require('auth.php');
require_once('DB.php');

// ? Initialization of a function that will synchronize the local MHP database and that of Actito
function syncMyHappyPet($t, $d)
{

    $db = connectToDB('myhappypet');
    $stmt = $db->prepare("SELECT * FROM user ORDER BY id ASC");
    $stmt->execute(array());
    while ($row = $stmt->fetch()) {

        $userData = new stdClass();

        $lastName = new stdClass();
        $lastName->name = "lastName";
        $lastName->value = $row['last_name'];

        $firstName = new stdClass();
        $firstName->name = "firstName";
        $firstName->value = $row['first_name'];

        $emailAddress = new stdClass();
        $emailAddress->name = "emailAddress";
        $emailAddress->value = $row['email_address'];

        $uploadedPaperCard = new stdClass();
        $uploadedPaperCard->name = "uploadedPaperCard";
        $uploadedPaperCard->value = $row['has_uploaded_paper_card'];

        $creationMoment = new stdClass();
        $creationMoment->name = "creationMoment";
        $creationMoment->value = $row['created_on'];

        $updateMoment = new stdClass();
        $updateMoment->name = "updateMoment";
        $updateMoment->value = $row['modified_on'];

        $referralCode = new stdClass();
        $referralCode->name = "referralCode";
        $referralCode->value = $row['referral_code'];

        $country = '';
        $language = '';

        switch ($row['app_country_code_id']) {
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
                return;
        }

        $adressCountry = new stdClass();
        $adressCountry->name = "adressCountry";
        $adressCountry->value = $country;
        $motherLanguage = new stdClass();
        $motherLanguage->name = "motherLanguage";
        $motherLanguage->value = $language;

        $userData->attributes = array($lastName, $firstName, $emailAddress, $motherLanguage, $uploadedPaperCard, $creationMoment, $updateMoment, $referralCode);

        insertUserIntoActito($t, $userData);
    }
}
