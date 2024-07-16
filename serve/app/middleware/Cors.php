<?php 
namespace app\middleware;
class Cors{
	//中间件跨域配置
	public function handle($request, \Closure $next){
		$origin=isset($_SERVER['HTTP_ORIGIN'])?$_SERVER['HTTP_ORIGIN']:$request->domain();
		header('Access-Control-Allow-Origin:'.$origin);
		header('access-control-allow-credentials:true');
		header('access-control-allow-methods:GET,POST,OPTIONS');
		header('access-control-allow-headers:Accept,X-PINGARUNER,CONTENT-TYPE,X-Requested-With,Token');
		return $request->isOptions()?response('Hello NodCloud',200):$next($request);
    }
}