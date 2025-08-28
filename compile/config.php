<?php
/**
 * 
 * APP SPECIFIC CONFIG FOR BUILD PROCESS
 * 
 */

return [
    'appname'=>"PLS",
    'php'=>[
        "version"=>"8.4.4",
        "libs" => [
            "readline" => true,
            "zlib" => false,
        ],
    ],
    "main" => "src/psl.php",
    "merge" => [
        "src/process.class.php",
    ],
];

