<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 16/4/8
 * Time: 下午4:29
 */

namespace base\socket;

use core\component\config\Config;

class SwooleServer
{
    private static $instance = null;

    /**
     * @return SwooleServer
     */
    public static function getInstance()
    {
        if(SwooleServer::$instance == null)
        {
            SwooleServer::$instance = new SwooleServer();
        }
        return SwooleServer::$instance;
    }
    
    protected function __construct()
    {

    }

    /**
     * @var \swoole_server
     */
    private $_server;
    /**
     * @var BaseCallback
     */
    private $_callback;


    public function init(array $config)
    {
        if(!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/swoole/swoole-src");
        }
        $this->_server = new \swoole_server($config['host'], $config['port'], SWOOLE_PROCESS);
        $this->_server->set(Config::get('swoole'));
        return $this;
    }

    public function setCallback($callback)
    {
        if( !( $callback instanceof BaseCallback ) )
        {
            throw new \Exception('client must object');
        }
        $this->_callback = $callback;
        $this->_callback->setServer($this->_server);
    }

    public function run()
    {
        $handlerArray = array(
            'onWorkerStop',
            'onWorkerError',

            'onConnect',
            'onClose',

            'onTask',
            'onFinish',

            'onManagerStart',
            'onManagerStop',
        );
        $this->_server->on('Start', array($this->_callback, 'onStart'));
        $this->_server->on('Shutdown', array($this->_callback, 'onShutdown'));
        $this->_server->on('WorkerStart', array($this->_callback, 'doWorkerStart'));
        $this->_server->on('Receive', array($this->_callback, 'onReceive'));

        foreach($handlerArray as $handler) {
            if(method_exists($this->_callback, $handler)) {
                $this->_server->on(\substr($handler, 2), array($this->_callback, $handler));
            }
        }

        $this->_server->start();
    }

    /**
     * @return \swoole_server
     */
    public function getServer()
    {
        return $this->_server;
    }

}
