<?php

// ? Function that will save the date and id of CSV imports
function addLogEventUsers($event)
{
    $time = date("d/m/Y H:i:s");
    $time = "[" . $time . "] ";

    $event = $time . "\n" . $event . "\n" . "\n";

    file_put_contents("importUsers.log", $event, FILE_APPEND);
}

function addLogEventPets($event)
{
    $time = date("d/m/Y H:i:s");
    $time = "[" . $time . "] ";

    $event = $time . "\n" . $event . "\n" . "\n";

    file_put_contents("importPets.log", $event, FILE_APPEND);
}
