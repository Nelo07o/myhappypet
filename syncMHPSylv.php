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

    $array = ['attributes' => [
        'lastName' => [
            'name' => 'lastName',
            'value' => 'last-name',
        ],
    ]];

    // ! Renommer en importUsersIntoActito
    // insertUserIntoActito($t, $userData);
}
