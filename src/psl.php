#!/usr/bin/php
<?php
/**
 * 
 * AXELS PROFILER FOR STDOUT
 * 
 * CLI app for profiling output from commandline tools
 * 
 */

// ----------------------------------------------------------------------
// CONFIG
// ----------------------------------------------------------------------

$VERSION = "0.5";

$iTimeout = 60;
$iWarn = 0.5;
$iCritical = 2;
$iRepeatHeader = 100;
$bForceExecution = false;
$bShowLines = false;
$bShowHeader = false;

// ----------------------------------------------------------------------
// INTERNAL VARS
// ----------------------------------------------------------------------

$iCounter = 0;
$iStart = microtime(true);
$iLastLine = $iStart;
$iProcessCheck = 0;

$sColorGood = "\033[32m";
$sColorWarn = "\033[33m";
$sColorBad = "\033[31m";
$sColorCli = "\033[34m";
$sColorReset = "\033[0m";

$iSlowest=0;
$iCountSlowest=5;
$aSlowest=[];

// ---MARK---INCLUDE---START---

require_once __DIR__ . '/process.class.php';

// ---MARK---INCLUDE---END---


// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------

function showHeader(){
    global $VERSION;
    echo "
    __________  _________.____     
    \______   \/   _____/|    |    
     |     ___/\_____  \ |    |    
     |    |    /        \|    |___ 
     |____|   /_______  /|_______ \  v$VERSION
                      \/         \/

";  
}

function showHelp()
{
    global $iTimeout, $iWarn, $iCritical, $iRepeatHeader, $iCountSlowest;
    echo "
    PROFILER FOR STDOUT LINES


This profiler listens to STDOUT and writes needed times for each line.
You can measure the time it takes to process each line and can finde bottlenecks.
Start your command and behind a pipe sign put the profiler.

This is free software.

Author: Axel Hahn
License: GNU GPL 3
Source: https://github.com/axelhahn/php-psl
Docs: TODO


SYNTAX:
  <your-command> | psl [options]


OPTIONS:
  -h, --help        show this help text

  -c, --critical    {float} critical threshold in seconds (default: $iCritical)
  -f, --force       Force execution of profiler if command in front of pipe 
                    wasn't detected
  -l, --lines       Show line number in front of output
  -t, --timeout     {float} timeout of measurement in seconds (default: $iTimeout)
  -r, --repeat      {integer} number of output lines when to repeat header
                    (default: off; suggestion: $iRepeatHeader)

  -s. --slowest     {int} set number of slowest items to show; default: $iCountSlowest
  -v, --version     Show version
  -w, --warn        {float} warn threshold in seconds (default: $iWarn)


EXAMPLES:
  <your-command> | psl -l -r=100
        Show line numbers and repeat header every 100 lines

  <your-command> | psl -w=0.1 -c=0.5
        Color time in yellow if delta > 0.1 seconds and red if delta > 0.5 seconds

";
}

function tableHeader()
{
    global $bShowLines, $bShowHeader, $sColorCli, $sColorReset;
    if($bShowHeader){
        echo $sColorCli
            . ($bShowLines ? "-----------+" : "--")."-----------------+---------------------------------------------------\n"
            . ($bShowLines ? "           |" : "  ")."   Time [sec]    |\n"
            . ($bShowLines ? "      Line |" : "  ")." Total |  Delta  | Output\n"
            . ($bShowLines ? "-----------+" : "--")."-------+---------+---------------------------------------------------\n"
            . $sColorReset
        ;
    }
}

function renderSlowest(){
    global $aSlowest, $iCountSlowest, $sColorCli, $sColorReset;
    if (count($aSlowest)==0){
        return;
    }
    $iCount=0;
    echo "{$sColorCli}$iCountSlowest Slowest items:\n\n"
        ."     [s]   Line   Output\n\n";
    foreach($aSlowest as $aItems){
        foreach ($aItems as $aItem){
            $iCount++;
            printf("%8s   %4d   %s \n", $aItem[1], $aItem[0], $aItem[2]);
        }
        if($iCount>=$iCountSlowest){
            break;
        }
    }
    echo "$sColorReset\n";
}
// ----------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------

if (PHP_OS !== 'Linux') {
    echo "{$sColorWarn}Warning: This tool was developed for Linux. You should abort.{$sColorReset}\n";
}
if ($argc > 1) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}


// ---------- handle cli parms



foreach ($_GET as $key => $value) {
    switch ($key) {
        case "-h":
        case "--help":
            showHeader();
            showHelp();
            exit(0);

        case "-t":
        case "--timeout":
            $iTimeout = $value;
            break;

        case "-w":
        case "--warn":
            $iWarn = $value;
            break;

        case "-c":
        case "--critical":
            $iCritical = $value;
            break;

        case "-f":
        case "--force":
            $bForceExecution = true;
            break;

        case "-l":
        case "--lines":
            $bShowLines=true;
            break;

        case "-r":
        case "--repeat":
            $bShowHeader=true;
            $iRepeatHeader = (int) $value;
            break;

        case "-s":
        case "--slowest":
            $iCountSlowest = (int) $value;
            break;

        case "-v":
        case "--version":
            echo "$VERSION\n";
            exit(0);

        default:
            echo "Unknown option: '$key" . ($value ? "=$value" : "") . "'. Use -h to show help.\n";
            exit(1);
    }
}

// ---------- watch stdout

$oProcess = new process();

$ppid = $oProcess->ppid;

exec("ps -o pid,ppid,comm | grep $ppid", $outpsStart);
if (count($outpsStart) < 3 && !$bForceExecution) {
    echo "$sColorBad\nERROR: no sibling process was found.$sColorReset\n"
        . "Remark: This can happen if command exits within a fraction of a second.\n\n"
        . "  To measure execution times use syntax '<your-command> | psl'.\n"
        . "  You can force the execution using --force\n"
        . "  e.g. '<your-command> | psl --force --timeout=5'\n\n"
        . "  Run 'psl --help' for more information\n\n"
    ;
    exit(2);
}

stream_set_blocking(STDIN, FALSE);
stream_set_timeout(STDIN, 1);

tableHeader();

$bRun = true;
while ($bRun) {

    $line = fgets(STDIN); // reads one line from STDIN
    $iNow = microtime(true);

    // 
    $sWait = round($iNow - $iLastLine, 1);
    if(!strstr($sWait, ".")) {
        $sWait .= ".0";
    }
    // if($bShowLines) {
    //     echo "          ";
    // }
    printf("%15s\r", $sWait);


    if (strlen($line) > 0) {
        $iCounter++;
        $delta=sprintf("%1.3f", ($iNow - $iLastLine));
        if ($delta == "0.000") {
            $delta = "     ";
        } else {

            if($iSlowest < ($iNow - $iLastLine)) {
                $key=sprintf("%07.3f", $delta);
                $aSlowest[$key][]=[$iCounter, $delta, trim($line)];
                krsort($aSlowest);
                if(count(array_keys($aSlowest)) > $iCountSlowest) {
                    $aSlowest=array_slice($aSlowest, 0, $iCountSlowest);
                    $sLastKey=key($aSlowest);
                    $iSlowest=(float)array_key_last($aSlowest);
                }
            }

        }

        // insert extra table header
        if ($iCounter % $iRepeatHeader == 0) {
            tableHeader();
        }

        $sColor = $sColorGood;
        if ($delta > $iWarn) {
            $sColor = $sColorWarn;
        }
        if ($delta > $iCritical) {
            $sColor = $sColorBad;
        }

        if($bShowLines) {
            printf("%10s", $iCounter);
        }
        printf(" %8.3f   %-12s  %s\n", $iNow - $iStart, "{$sColor}{$delta}{$sColorReset}", trim($line));
        $iLastLine = $iNow;
    }

    // abort on timeout without output
    if ($iNow - $iLastLine > $iTimeout) {
        echo "                                                            \n";
        echo "{$sColorBad}Stopped: Timeout of $iTimeout sec was reached.$sColorReset\n";
        $bRun = false;
    }

    // detect process in front of pipe is still running
    if ($iNow - $iLastLine > 1 && $iNow - $iProcessCheck > 2) {
        $outpsNow = [];
        exec("ps -o pid,ppid,comm | grep $ppid", $outpsNow); // print_r($outpsNow);
        foreach ($outpsStart as $sLine) {
            if (!in_array($sLine, $outpsNow)) {
                echo "                                                            \n";
                // echo "{$sColorWarn}Sibling process was stopped:$sColorReset $sLine.\n\n";
                $bRun = false;
                break;
            }
        }
        $iProcessCheck = $iNow;
    }
}

echo "\n$sColorCli---------------------------------------------------------------------$sColorReset\n\n";

renderSlowest();

echo "Done.\n";

// ----------------------------------------------------------------------
// END
// ----------------------------------------------------------------------
