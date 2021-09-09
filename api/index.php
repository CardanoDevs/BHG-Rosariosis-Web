<?php

//http_response_code(404);

echo "<pre>";
print_r(json_decode('{"Authorization":"Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJBUFBfVkVSU0lPTiI6IjEuMCIsIkRFVklDRV9JRCI6IjEyMzQ1MTIzIiwiVVNFUiI6eyJVU0VSTkFNRSI6Ik1yLkxDb29wZXIiLCJVU0VSX0lEIjoiNjQ5IiwiRklSU1RfTkFNRSI6IkxheWxhIiwiTEFTVF9OQU1FIjoiQ29vcGVyIiwiVElUTEUiOm51bGwsIlNDSE9PTF9JRCI6IjEiLCJQUk9GSUxFIjoic3R1ZGVudCJ9fQ._A6rqQ5qoQrHCsee6q37qXbURoYw1XTK26nwOVFnACo","Accept-Encoding":"identity","User-Agent":"Dalvik\\/2.1.0 (Linux; U; Android 7.1.2; SM-G988N Build\\/NRD90M)","Host":"10.10.12.188","Connection":"Keep-Alive"}'));
exit;

// JWT Verification system
// Version checking system