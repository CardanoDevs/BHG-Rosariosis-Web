<?php

use \Firebase\JWT\JWT;

function IsStudent()
{
    $user = GetAuthData();
    return ($user->USER->PROFILE == 'student');
}

function IsParent()
{
    $user = GetAuthData();
    return ($user->USER->PROFILE == 'parent');
}

function GetStudentIdsFromJWT()
{

    $user = GetAuthData();

    if ($user) {

        $students = (array)$user->USER->STUDENTS;

        if ($students) {

            $student_ids = array_map(function ($item) {
                return $item->STUDENT_ID;
            }, $students);

            return $student_ids;

        } else {
            return [];
        }

    }

    return null;

}

function GetAuthData()
{

    $token = GetBearerToken();

    return VerifyJWT($token);

}

function CreateJWT($payload)
{

    global $jwt_secret;

    $jwt = JWT::encode($payload, $jwt_secret);

    return $jwt;

}

function VerifyJWT($jwt)
{
    global $jwt_secret, $jwt_algo;

    try {
        $decoded = JWT::decode($jwt, $jwt_secret, $jwt_algo);
        return $decoded;
    } catch (Exception $e) {
        return null;
    }

}

function PerformInitialAuthChecks()
{

    $token = GetBearerToken();

    if ($token == null) {
        http_response_code(401);
        echo ToJSON([
            'ERROR' => 'Please login to continue',
            'STATE' => 1,
        ]);
        exit();
    } else {

        $tokenResponse = VerifyJWT($token);

        if ($tokenResponse == null) {
            http_response_code(401);
            echo ToJSON([
                'ERROR' => 'Please login to continue',
                'STATE' => 2,
            ]);
            exit();
        }

    }

}

function GetBearerToken()
{
    $headers = GetAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function GetAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
//        echo 'CASE 1';
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
//        echo 'CASE 2';
    } else { 
        if( !function_exists('apache_request_headers') ) {
            function apache_request_headers() {
                $arh = array();
                $rx_http = '/\AHTTP_/';
                foreach($_SERVER as $key => $val) {
                    if( preg_match($rx_http, $key) ) {
                        $arh_key = preg_replace($rx_http, '', $key);
                        $rx_matches = array();
                        // do some nasty string manipulations to restore the original letter case
                        // this should work in most cases
                        $rx_matches = explode('_', strtolower($arh_key));
                        if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                                foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                                $arh_key = implode('-', $rx_matches);
                        }
                        $arh[$arh_key] = $val;
                    }
                }
                if(isset($_SERVER['CONTENT_TYPE'])) $arh['Content-Type'] = $_SERVER['CONTENT_TYPE'];
                if(isset($_SERVER['CONTENT_LENGTH'])) $arh['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
                return( $arh );
            }
        }
//        echo 'CASE 3';
        $requestHeaders = apache_request_headers();
        error_log(json_encode($requestHeaders));
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
//        print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
//    else{
//        echo 'CASE 4';
//    }
    return $headers;
}