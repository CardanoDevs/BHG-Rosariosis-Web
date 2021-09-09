<?php

include_once 'core/functions.php';

if (isset($_GET['key']) && $_GET['key'] == '12345') {
    $schools = [
        [
            'Name' => 'Karachi Public School',
            'BaseURL' => 'https://stratusarchives.com/assignment',
        ],
        [
            'Name' => 'MAJU',
            'BaseURL' => 'https://stratusarchives.com/assignment',
        ],
        [
            'Name' => 'PAF KIET',
            'BaseURL' => 'https://stratusarchives.com/assignment',
        ],
        [
            'Name' => 'FAST',
            'BaseURL' => 'https://stratusarchives.com/assignment',
        ],
        [
            'Name' => 'NUCES',
            'BaseURL' => 'https://stratusarchives.com/assignment',
        ],
        [
            'Name' => 'LUMS',
            'BaseURL' => 'https://stratusarchives.com/assignment',
        ],
        [
            'Name' => 'DADABHOY',
            'BaseURL' => 'https://stratusarchives.com/assignment',
        ],
        [
            'Name' => 'Virtual University',
            'BaseURL' => 'https://stratusarchives.com/assignment',
        ],
        [
            'Name' => 'Virtual University 2',
            'BaseURL' => 'https://stratusarchives.com/assignment',
        ],
        [
            'Name' => 'Virtual University 3',
            'BaseURL' => 'https://stratusarchives.com/assignment2',
        ],
        [
            'Name' => 'Tabernacle Baptist Christian Academy',
            'BaseURL' => 'https://gs4ed.com/                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ',
        ],
        [
            'Name' => 'Local Test School',
            'BaseURL' => 'http://10.10.12.188'
        ],
    ];
    echo ToJSON([
        'Schools' => $schools
    ]);
} else {
    http_response_code(404);
}