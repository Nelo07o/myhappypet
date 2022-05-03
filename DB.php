<?php

function connectToDB($dbName)
{
    try {
        $host = 'localhost';
        $user = 'myhappypet';
        $pwd = 'myhappypet';
        $db = new PDO('mysql:host=' . $host . ';dbname=' . $dbName, $user, $pwd);

        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;

    }
    catch (PDOException $e) {
        print_r($e);
        return null;
    }
}

?>
