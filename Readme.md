# Axels Profiler for STDOUT

CLI app for profiling output from commandline tools.
It shows the delta time that it takes to produce each line.

This is free software.

Author: Axel Hahn \
Source: <https://github.com/axelhahn/php-psl> \
License: GNU GPL 3 \
Docs: TODO

## Requirements

- PHP 8.x
- Linux

## Usage

```txt
<your-command> | ./src/psl.php [options]
```

```txt
    __________  _________.____     
    \______   \/   _____/|    |    
     |     ___/\_____  \ |    |    
     |    |    /        \|    |___ 
     |____|   /_______  /|_______ \  v0.3
                      \/         \/


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

  -c, --critical    {float} critical threshold in seconds (default: 2)
  -f, --force       Force execution of profiler if command in front of pipe 
                    wasn't detected
  -l, --lines       Show line number in front of output
  -t, --timeout     {float} timeout of measurement in seconds (default: 60)
  -r, --repeat      {integer} number of output lines when to repeat header
                    (default: off; suggestion: 100)
  -v, --version     Show version
  -w, --warn        {float} warn threshold in seconds (default: 0.5)


EXAMPLES:
  <your-command> | psl -l -r=100
        Show line numbers and repeat header every 100 lines

  <your-command> | psl -w=0.1 -c=0.5
        Color time in yellow if delta > 0.1 seconds and red if delta > 0.5 seconds

```

## Screenshot

![alt text](image.png)

## TODO

- Docs
