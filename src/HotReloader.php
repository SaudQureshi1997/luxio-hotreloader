<?php

namespace Luxio\Utils;

use RuntimeException;
use Swoole\Process;

class HotReloader
{
    protected $fd;
    protected $wds;
    protected $dirs;

    /**
     * name of directories to be watched
     *
     * @param array $dirs
     */
    public function __construct(array $dirs)
    {
        $this->dirs = $dirs;
        $this->fd = \inotify_init();

        foreach ($this->dirs as $dir) {
            if (!file_exists($dir)) {
                throw new RuntimeException("$dir not found");
            }
            $this->addWatcher($dir);
        }
    }

    /**
     * watch the provided swoole server(TCP, UDP, HTTP, etc)
     *
     * @param object $server
     * @return void
     */
    public function watch($server)
    {
        $process = new Process(function ($process) use ($server) {

            print("Hot reloading enabled...\n");
            while (true) {
                $events = \inotify_read($this->fd);
                if (count($events)) {
                    $server->reload();
                }
            }
        });

        $server->addProcess($process);
    }

    /**
     * add an inotify watcher to all the files in a directory
     *
     * @param string $dir_path
     * @return void
     */
    public function addWatcher(string $dir_path)
    {
        $rdi = new \RecursiveDirectoryIterator($dir_path);

        $rii = new \RecursiveIteratorIterator($rdi);

        foreach ($rii as $splFileInfo) {
            $file_name = $splFileInfo->getPathName();

            // Skip hidden files and directories.
            if ($file_name[0] === '.') {
                continue;
            }

            if (!$splFileInfo->isDir()) {
                if ($wd = \inotify_add_watch($this->fd, $file_name, IN_CREATE | IN_DELETE | IN_MODIFY | IN_MOVE)) {
                    $this->wds[] = $wd;
                }
            }
        }
    }

    /**
     * destroy/remove all the inotify watchers and release inotify resource/instance
     */
    public function __destruct()
    {
        foreach ($this->wds as $wd) {
            \inotify_rm_watch($this->fd, $wd);
        }

        fclose($this->fd);
    }
}
