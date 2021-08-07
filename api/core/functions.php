<?php

function ToJSON($array)
{
    return json_encode($array);
}

function LogMessage($message)
{

    $myfile = fopen("APIErrors.txt", "a") or die("Unable to open file!");
    fwrite($myfile, $message . PHP_EOL);
    fclose($myfile);

}

function DBDate()
{
    // ISO, eg. 2015-07-10.
    return date('Y-m-d');
}
