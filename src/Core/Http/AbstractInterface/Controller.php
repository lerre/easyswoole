<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2017/12/29
 * Time: 下午7:26
 */

namespace EasySwoole\Core\Http\AbstractInterface;



use EasySwoole\Core\Http\Message\Status;
use EasySwoole\Core\Http\Request;
use EasySwoole\Core\Http\Response;
use EasySwoole\Core\Utility\Validate\Rules;
use EasySwoole\Core\Utility\Validate\Validate;
use Swoole\Mysql\Exception;

abstract class Controller
{
    private $request;
    private $response;
    private $actionName;
    private static $forbidMethod = [
        'getActionName','onRequest','actionNotFound','afterAction','request','response','__call','__hook','onException'
    ];

    abstract function index();

    abstract function actionNotFound($action = null):void;

    abstract function afterAction($actionName):void;

    abstract function onException(\Exception $exception,$actionName):void;

    public function onRequest($action):?bool
    {
        return true;
    }

    public function getActionName():string
    {
        return $this->actionName;
    }

    public function resetAction(string $action):void
    {
        $this->actionName = $action;
    }

    public function __hook(string $actionName,Request $request,Response $response):void
    {
        $this->request = $request;
        $this->response = $response;
        $this->actionName = $actionName;
        //防止恶意调用
        if(in_array($actionName,self::$forbidMethod)){
            $response->withStatus(Status::CODE_INTERNAL_SERVER_ERROR);
            $response->end();
        }else{
            if($this->onRequest($actionName) !== false){
                $this->onRequest($actionName);
                //防止onRequest中   对actionName 进行修改
                $actionName = $this->actionName;
                if(method_exists($this,$actionName)){
                    $args = $this->request()->getRequestParam();
                    ksort($args);
                    try{
                        $this->$actionName();
                    }catch (Exception $exception){
                        $this->onException($exception,$actionName);
                    }
                    $this->afterAction($actionName);
                }else{
                    $this->actionNotFound($actionName);
                }
            }
        }
    }

    final public function request():Request
    {
        return $this->request;
    }

    final public function response():Response
    {
        return $this->response;
    }

    /*
     * 若不想用自带验证器，可以自己新建base控制器，重写validateParams方法
     */
    public function validateParams(Rules $rules)
    {
        $validate = new Validate();
        return $validate->validate($this->request()->getRequestParam(),$rules);
    }
}