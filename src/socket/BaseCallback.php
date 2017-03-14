<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/4/8
 * Time: 下午5:43
 */

namespace base\socket;

use core\common\Globals;
use core\component\config\Config;
use core\component\task\TaskRoute;
use core\concurrent\Promise;

abstract class BaseCallback
{

    /**
     * @var \swoole_server
     */
    protected $server;
    protected $project_name;

    protected $pid_path;

    public function __construct()
    {
        $this->project_name = Config::getField('project', 'project_name');
        $this->pid_path   = Config::getField('project', 'pid_path');
    }

    public function onStart($server)
    {
        Globals::setProcessName($this->project_name . " server running master:" . $server->master_pid);
        if (!empty($this->pid_path)) {
            file_put_contents($this->pid_path . DIRECTORY_SEPARATOR . $this->project_name . '_master.pid', $server->master_pid);
        }
    }

    /**
     * @throws \Exception
     */
    public function onShutDown()
    {
        if (!empty($this->pid_path)) {
            $filename = $this->pid_path . DIRECTORY_SEPARATOR . $this->project_name . '_master.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
            $filename = $this->pid_path . DIRECTORY_SEPARATOR . $this->project_name . '_manager.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * @param $server
     * @throws \Exception
     * @desc 服务启动，设置进程名
     */
    public function onManagerStart($server)
    {
        Globals::setProcessName($this->project_name .' server manager:' . $server->manager_pid);
        if (!empty($this->pid_path)) {
            file_put_contents($this->pid_path . DIRECTORY_SEPARATOR . $this->project_name . '_manager.pid', $server->manager_pid);
        }
    }

    public function onManagerStop()
    {
        if (!empty($this->pid_path)) {
            $filename = $this->pid_path . DIRECTORY_SEPARATOR . $this->project_name . '_manager.pid';
            if (is_file($filename)) {
                unlink($filename);
            }
        }
    }

    public function doWorkerStart($server, $workerId)
    {
        $workNum = Config::getField('swoole', 'worker_num');
        if ($workerId >= $workNum) {
            Globals::setProcessName($this->project_name . " server tasker  num: ".($server->worker_id - $workNum)." pid " . $server->worker_pid);
        } else {
            Globals::setProcessName($this->project_name . " server worker  num: {$server->worker_id} pid " . $server->worker_pid);
        }
        Globals::$server = $server;

        $this->onWorkerStart($server, $workerId);
    }

    public function onWorkerStop($server, $workerId)
    {
        
    }

    public function setServer(\swoole_server $server)
    {
        $this->server = $server;
    }

    /**
     * @return \swoole_server
     */
    public function getServer()
    {
        return $this->server;
    }

    public function onTask(\swoole_server $server, $task_id, $from_id, $data)
    {
        $task_config = Config::getField('component', 'task');
        TaskRoute::onTask($task_config['task_path'], $data);
    }

    public function onFinish(\swoole_server $serv, $task_id, $data)
    {
        TaskRoute::onFinish();
    }

    public function doReceive(\swoole_server $server, int $fd, int $from_id, string $data)
    {
        Promise::co(function() use ($server, $fd, $data){
            yield $this->onRequest($server, $fd, $data);
        });
    }

    /**
     * @param \swoole_server $server
     * @param int $fd
     * @param string $data
     * @return mixed
     */
    abstract public function onRequest(\swoole_server $server, int $fd, string $data);


    /**
     * 进程初始化回调, 用于初始化全局变量
     * @param \swoole_websocket_server $server
     * @param $workerId
     */
    abstract public function onWorkerStart($server, $workerId);

}
