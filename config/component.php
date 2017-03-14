<?php
/**
 * Created by PhpStorm.
 * User: lidanyang
 * Date: 17/3/4
 * Time: 14:56
 */

/**
 * 各组件相关自定义配置
 */
return [
    'component' => [
        /**
         * 日志组件
         */
        'log' => [
            'open_log'      => true,            // 是否开启日志
            'adapter'       => 'Debug',         // 日志模块
            'log_level'     => 1,               // 日志记录等级

            // 各个模块的定制化配置
            'path'          => '/var/log/',     // 日志文件存储路径
        ],

        /**
         * 异步任务组件
         */
        'task' => [
            'open_task'     => true,            // 是否开启异步任务模块
            'task_path'     => '/task/',    // 任务文件路径
        ]
    ]
];