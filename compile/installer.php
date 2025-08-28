#!/bin/env php
<?php
/*

    I N S T A L L E R

*/

require __DIR__."/config.php";
require __DIR__."/inc_vars.php";
require __DIR__."/inc_functions.php";


$spcUrl="https://dl.static-php.dev/static-php-cli/spc-bin/nightly/spc-$myos-$myarchitecture$myosextension";
$doneBuild="__done_build-micro__.txt";

/*
Filename                                          Last Modified            Size      Download Count

spc-linux-aarch64                                 2025-02-15 17:30:46      3.4M      451
spc-linux-aarch64.tar.gz                          2025-02-15 17:30:46      3.3M      54
spc-linux-x86_64                                  2025-02-15 17:30:46      3.5M      937
spc-linux-x86_64.tar.gz                           2025-02-15 17:30:46      3.3M      282
spc-macos-aarch64                                 2025-02-15 17:31:40      7.8M      669
spc-macos-aarch64.tar.gz                          2025-02-15 17:31:38      3.8M      211
spc-macos-x86_64                                  2025-02-15 17:31:46      7.8M      463
spc-macos-x86_64.tar.gz                           2025-02-15 17:31:45      3.9M      89
spc-windows-x64.exe                               2025-02-15 17:30:46      3.9M      551

*/

// ----------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------
echo "
  \e[1m$PHP_APP\e[0m

  I N S T A L L E R   *   U P D A T E R

...............................................................................
";

// ----------------------------------------------------------------------

if ($argc > 1) {
    parse_str(implode('&', array_slice($argv, 1)), $ARGS);
}

if(isset($ARGS['-h']) || isset($ARGS['--help'])){
    echo "
    - Get spc binary
    - Installations with spc doctor
    - Download PHP and libs with spc
    - Build Micro sfx with spc

...............................................................................

 ✨ \e[1mSYNTAX:\e[0m

    ./installer.php [OPTIONS]

 🔷 \e[1mOPTIONS:\e[0m

    -h, --help     Show this help
    -r, --reset    Reset; delete created folders of installer and build

";
    exit(0);
}

_h1("Init");
_chdir("$approot");

if(isset($ARGS['-r']) || isset($ARGS['--reset'])){
    _h1("Reset data");
    _rm($dirBuild);
    _rm($dirExternal);
    _rm($dirPackages);
}

// ----------------------------------------------------------------------
_h1("Create directories");

_mkdir($dirExternal);
_mkdir("$dirExternal/bin");
_mkdir($dirBuild);


// ----------------------------------------------------------------------
// _h1("Generate include file with all available checks...");
// _exec("ln -s $selfdir/$dirExternal/appmonitor/public_html/client $selfdir/src");



// ----------------------------------------------------------------------
_h1("Get / update spc");
_chdir("$approot/$dirExternal/bin");
if(!file_exists($SPC)){
    _exec("wget -O $SPC \"$spcUrl\"", true, ($myos == "windows"));
} else {
    _skip("download of spc.");
}

if (PHP_OS == "Linux") {
    _exec("chmod +x $SPC");
}


// ----------------------------------------------------------------------
_h1("Spc - prepare environment");
_chdir("$approot/$dirBuild");

$bDoBild=true;
$sDoneData="PHP version $php_version\nExtensions: $PHP_LIBS";
if(file_exists($doneBuild)){

    $sDone=file_get_contents($doneBuild);
    if (strstr($sDone, $sDoneData)>=0){
        $bDoBild=false;
    }
}

if ($bDoBild){    
    _exec("$SPC --no-interaction doctor");

    echo "💡 Hint: this can take a minute on a fresh install ...\n";
    _exec($cmdSpcDownload);

    echo "💡 Hint: this can take 4 minutes on a fresh install or less on module changes ...\n";
    _exec($cmdSpcBuild);
    file_put_contents("$doneBuild", date("Y-m-d H:i:s") . "\n$sDoneData\n");
} else {
    _skip("Micro already built - php $php_version - extensions \"$PHP_LIBS\"");
}

_h1("Done. You can run 'build.php' to compile the binary 🎉");
echo "\n";

// ----------------------------------------------------------------------
