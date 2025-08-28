<?php
// ----------------------------------------------------------------------
// SHARED FUNCTIONS FOR INSTALLER AND BUILDER
// ----------------------------------------------------------------------

$aCol=[
    "reset" => "\e[0m",
    "red" => "\e[31m",
    "green" => "\e[32m",
    "yellow" => "\e[33m",
    "blue" => "\e[34m",
    "magenta" => "\e[35m",
    "cyan" => "\e[36m",
    "light_gray" => "\e[37m",
    "dark_gray" => "\e[90m",
    "light_red" => "\e[91m",
    "light_green" => "\e[92m",
    "light_yellow" => "\e[93m",
    "light_blue" => "\e[94m",
    "light_magenta" => "\e[95m",
];

function _h1(string $s): void
{
    global $aCol;
    echo "\n$aCol[magenta]>>>>>>>>>> $s$aCol[reset]\n";
    
    /*
    echo "\n   __".str_repeat("_", strlen($s))."\n";
    echo "__/ ".str_repeat(" ", strlen($s))." \___".str_repeat("_", 70-strlen($s))."\n";
    echo "    $s\n";
    echo str_repeat(".", 79)."\n\n";
    */
}
function _abort(string $sMessage, int $iExitcode = 1): never
{
    global $aCol;
    echo "❌ $aCol[red]$sMessage$aCol[reset]\n";
    exit($iExitcode);
}

function _ok(string $sMessage=""): void
{
    echo "✅ OK $sMessage\n";
}
function _skip(string $sMessage=""): void
{
    echo "🔹 SKIP: $sMessage\n";
}

function _chdir(string $sDir): void
{
    global $aCol;
    if (!is_dir($sDir)) {
        _abort("Directory '$sDir' not found.");
    }
    chdir($sDir);
    echo "$aCol[cyan]dir # " . getcwd() . "$aCol[reset]\n";
}

/**
 * Execute shell command and abort if it fails
 * @param string $cmd
 * 
 * @return int
 */
function _exec(string $cmd, bool $bAbortOnError=true, $bForceExec=false): int
{
    global $aCol;
    echo "$aCol[blue]cmd > $cmd$aCol[reset]\n";
    $iStart=microtime(true);

    if($bForceExec) {
        exec("$cmd 2>&1", $aOut, $rc);
        $iEnd=microtime(true);
        if (!count($aOut)) {
            $aOut = ["-- no output --"];
        }
        echo implode("\n", $aOut) . "\n";
    } else {

        // show output in real-time
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $process = proc_open($cmd, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            // Close stdin pipe
            fclose($pipes[0]);
        
            // Read stdout and stderr in real-time
            while ($output = fgets($pipes[1])) {
                echo "     | $output";
                flush();
            }
            fclose($pipes[1]);
        
            while ($error = fgets($pipes[2])) {
                echo "    2| $error";
                flush();
            }
            fclose($pipes[2]);
        
            // Close the process
            $rc=proc_close($process);
            $iEnd=microtime(true);
        } else {
            if($bAbortOnError){
                _abort("Unable to execute command.");            
            }
            _skip("Unable to execute command.");
        }
    }

    $sTime= "... ⏱️ Time: " . round($iEnd-$iStart, 3) . "s\n";
    if ($rc != 0) {
        echo "rc=$rc $sTime";
        if($bAbortOnError){
            _abort("Command failed. Aborting.", $rc);
        }
        _skip("Unable to execute command.");
        
    }
    _ok($sTime);
    return $rc;
}

function _mkdir(string $sMyDir): void
{
    if (!is_dir($sMyDir)) {
        echo "DIR > '$sMyDir' ";
        if (!mkdir($sMyDir, 0755, true)) {
            _abort("mkdir('$sMyDir') failed.");
        }
        _ok();
        echo "\n";
    } else {
        _skip("mkdir: already exists: '$sMyDir'");
    }

}

function _rm(string $sFileObj): void
{
    if (is_dir($sFileObj)) 
    {
        _exec("rm -rf \"$sFileObj\"");
    } else if (file_exists($sFileObj)) {
        if (!unlink($sFileObj)) {
            _abort("unlink('$sFileObj') failed.");
        }
        _ok("File was deleted");
    } else {
        _skip("rm '$sFileObj' - does not exist");
    }
}