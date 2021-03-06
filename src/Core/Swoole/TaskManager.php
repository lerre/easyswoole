<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2017/12/7
 * Time: 下午10:35
 */

namespace EasySwoole\Core\Swoole;


use EasySwoole\Core\Component\SuperClosure;

class TaskManager
{
    public static  function async($task,$finishCallback = null,$taskWorkerId = -1)
    {
        if($task instanceof \Closure){
            $task = new SuperClosure($task);
        }
        return ServerManager::getInstance()->getServer()->task($task,$taskWorkerId,$finishCallback);
    }

    public static  function sync($task,$timeout = 0.5,$taskWorkerId = -1)
    {
        if($task instanceof \Closure){
            $task = new SuperClosure($task);
        }
        return ServerManager::getInstance()->getServer()->taskwait($task,$timeout,$taskWorkerId);
    }

    public static  function barrier(array $taskList,$timeout = 0.5)
    {
        $temp =[];
        $map = [];
        $result = [];
        foreach ($taskList as $name => $task){
            $temp[] = $task;
            $map[] = $name;
        }
        if(!empty($temp)){
            $ret = ServerManager::getInstance()->getServer()->taskWaitMulti($temp,$timeout);
            if(!empty($ret)){
                //极端情况下  所有任务都超时
                foreach ($ret as $index => $result){
                    $result[$map[$index]] = $result;
                }
            }
        }
        return $result;
    }
}