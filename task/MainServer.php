<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/6/15
 * Time: 上午11:03
 */

namespace app\server;

use base\Entrance;
use base\socket\BaseCallback;
use core\component\config\Config;
use core\component\pool\PoolManager;
use core\component\task\AsyncTask;

class MainServer extends BaseCallback
{

    /**
     * @var \swoole_server
     */
    private $server;

    public function onWorkerStart($server, $workerId)
    {
        // 加载配置
        Config::load(Entrance::$rootPath . '/config');
        // 初始化连接池
        if($server->taskworker)
        {
            PoolManager::getInstance()->init('mysql_master');
            PoolManager::getInstance()->init('redis_master');
        }
        $this->server = $server;
    }

    /**
     * @param \swoole_server $server
     * @param int $fd
     * @param string $data
     * @return mixed
     */
    public function onRequest(\swoole_server $server, int $fd, string $data)
    {
        $data = substr($data,0,4);
        if(empty($data) || !isset($data['task']))
        {
            $this->send($fd, [
                'code' => -1
            ]);
            return;
        }
        $task = new AsyncTask($data['task']);
        $action = $data['action'];
        $args = $data['args'];

        $result = yield call_user_func_array([$task, $action], $args);
        $this->send($fd, [
            'code' => 0,
            'data' => $result
        ]);
    }

    private function send($fd, array $data)
    {
        $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->server->send($fd, pack('N' , strlen($data)) . $data);
    }

}
