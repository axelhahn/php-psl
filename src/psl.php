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

$VERSION = "0.2";
$iTimeout = 60;
$iWarn = 0.5;
$iCritical = 2;
$iRepeatHeader = 100;
$bForceExecution = false;

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

// ---MARK---INCLUDE---START---

require_once __DIR__ . '/process.class.php';

// ---MARK---INCLUDE---END---


// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------

function showHelp()
{
    global $iTimeout, $iWarn, $iCritical, $iRepeatHeader, $VERSION;
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

  -w, --warn        {float} warn threshold in seconds (default: $iWarn)
  -c, --critical    {float} critical threshold in seconds (default: $iCritical)

  -f, --force       Force execution of profiler if command in front of pipe 
                    wasn't detected
  -t, --timeout     {float} timeout in seconds (default: $iTimeout)

  -r, --repeat      {integer} number of output lines when to repeat header
                    (default: $iRepeatHeader)

";
}

function tableHeader()
{
    global $sColorCli, $sColorReset;
    echo $sColorCli
        . "-----------+-----------------+---------------------------------------------------\n"
        . "           |   time [sec]    |\n"
        . "      line | total |  delta  | output\n"
        . "-----------+-------+---------+---------------------------------------------------\n"
        . $sColorReset
    ;
}

function getPPID($s): int
{
    preg_match('/(\d+).*(\d+).*/', $s, $matches);
    print_r($matches);
    return $matches[1];
}

// ----------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------

if (PHP_OS !== 'Linux') {
    echo "{$sColorWarn}Warning: This tool was developed for Linux. You should abort.{$sColorReset}\n";
}

echo "
    __________  _________.____     
    \______   \/   _____/|    |    
     |     ___/\_____  \ |    |    
     |    |    /        \|    |___ 
     |____|   /_______  /|_______ \  v$VERSION
                      \/         \/

";
// ---------- handle cli parms


if ($argc > 1) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

foreach ($_GET as $key => $value) {
    switch ($key) {
        case "-h":
        case "--help":
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

        case "-r":
        case "--repeat":
            $iRepeatHeader = (int) $value;
            break;

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
    echo "$sColorBad\nERROR: no sibling process was found.$sColorReset\n\n"
        . "  To measure execution times use syntax '<your-command> | psl'.\n"
        . "  If the detection was wrong you can force the execution using --force\n"
        . "  e.g. '<your-command> | psl --force --timeout=5'\n\n"
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
    printf("%26s\r", $sWait);


    if (strlen($line) > 0) {
        $iCounter++;
        $delta = round($iNow - $iLastLine, 3);
        if ($delta == "0.000") {
            $delta = "     ";
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
        if ($delta > "0") {
            $delta = sprintf("%5.3F", $delta);
        }

        printf("%10s   %-7.3f   %-8s   %s\n", $iCounter, $iNow - $iStart, "{$sColor}{$delta}{$sColorReset}", trim($line));
        $iLastLine = $iNow;
    }

    // abort on timeout without output
    if ($iNow - $iLastLine > $iTimeout) {
        echo "                                                            \n";
        echo "{$sColorBad}Stopped: Timeout of $iTimeout sec was reached.$sColorReset\n";
        $bRun = false;
    }
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

echo "Done.\n";
// ----------------------------------------------------------------------
// END
// ----------------------------------------------------------------------
