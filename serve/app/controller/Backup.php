<?php
namespace app\controller;
use app\controller\Acl;
class Backup extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        //数据完整性判断
        if(existFull($input,['page','limit'])){
            $dbInfo=config('database.connections.mysql');
    		$backup=new \org\BakSql($dbInfo['hostname'],$dbInfo['username'],$dbInfo['password'],$dbInfo['database']);
    		$list=$backup->get_filelist();
			//排除配置文件
			foreach ($list as $listKey=>$listVo){
				if(substr($listVo['name'],0,1)=='.'){
					unset($list[$listKey]);
				}
			}
            $count = count($list);//获取总条数
            $info =array_slice($list,$input['limit']*($input['page']-1),$input['limit']);//匹配分页数据
            $result=[
                'state'=>'success',
                'count'=>$count,
                'info'=>$info
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //备份
    public function backup(){
        $dbInfo=config('database.connections.mysql');
		$backup=new \org\BakSql($dbInfo['hostname'],$dbInfo['username'],$dbInfo['password'],$dbInfo['database']);
		$backup->backup();
		pushLog('备份数据');
		return json (['state'=>'success']);
    }
    //恢复
    public function restore(){
        $input=input('post.');
        if(existFull($input,['name'])){
            //恢复指定数据
            $dbInfo=config('database.connections.mysql');
            $backup=new \org\BakSql($dbInfo['hostname'],$dbInfo['username'],$dbInfo['password'],$dbInfo['database']);
            $backup->restore($input['name']);
            pushLog('恢复数据备份[ '.$input['name'].' ]');//日志信息
            $result=['state'=>'success'];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //删除
    public function del(){
        $input=input('post.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            $path=pathChange('static.backup');
            foreach ($input['parm'] as $infoVo) {
                //防止恶意请求
                if(strpos($infoVo,DIRECTORY_SEPARATOR)===false && strpos($infoVo,'..')===false){
                    @unlink($path.$infoVo);
                    pushLog('删除数据备份[ '.$infoVo.' ]');//日志信息
                }else{
                    return json(['state'=>'error','info'=>'传入参数错误!']);
                }
            }
            $result=['state'=>'success'];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
}