<?php
/**
 * very incomplete class for process infos
 */

class process
{

    public int $pid;
    public int $ppid;
    public $cmd;

    // ----------------------------------------------------------------------
    // Constructor
    // ----------------------------------------------------------------------
    public function __construct(int $pid = 0)
    {
        $this->pid = $pid ?: getmypid();

        $this->ppid = $this->_parse_proc_status()['PPid'] ?? 0;
        $this->cmd = file_get_contents("/proc/{$this->pid}/cmdline") ?? '';

    }

    // ----------------------------------------------------------------------
    // protected methods
    // ----------------------------------------------------------------------

    /**
     * Parse /proc/<pid>/status and return its data as key value hash
     * @return array
     */
    protected function _parse_proc_status(): array
    {
        $aReturn = [];
        if (file_exists("/proc/{$this->pid}/status")) {
            foreach (explode("\n", file_get_contents("/proc/{$this->pid}/status")) as $line) {
                if (!strstr($line, ":")) {
                    continue;
                }
                list($key, $value) = explode(":", $line, 2);
                if (!$key) {
                    continue;
                }
                $aReturn[$key] = trim($value);
            }
        }
        return $aReturn;
    }

    // ----------------------------------------------------------------------
    // public methods
    // ----------------------------------------------------------------------

    /**
     * Check if the process of initially given pid still exists
     * @return bool
     */
    public function exists()
    {
        return ($this->_parse_proc_status()['Pid'] ?? 0) == $this->pid;
    }

}