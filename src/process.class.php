<?php
/**
 * very incomplete class for process infos
 */

class process {

    public int $pid;
    public int $ppid;
    public $cmd;

    public function __construct(int $pid=0) {
        $this->pid = $pid ?: getmypid();

        $this->ppid = $this->_parse_proc_status()['PPid']??0;
        $this->cmd = file_get_contents("/proc/{$this->pid}/cmdline")??'';

    }

    protected function _parse_proc_status():array 
    {
        $aReturn = [];
        if(file_exists("/proc/{$this->pid}/status")) {
            foreach (explode("\n", file_get_contents("/proc/{$this->pid}/status")) as $line) {
                if(!strstr($line, ":")) {
                    continue;
                }
                list($key, $value) = explode(":", $line, 2);
                if(!$key) {
                    continue;
                }
                $aReturn[$key] = trim($value);
            }
        }
        return $aReturn;
    }

    public function exists(){
        return ($this->_parse_proc_status()['Pid']??0) == $this->pid;
    }

}