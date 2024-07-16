<?php
namespace app\controller;
use app\controller\Acl;
use app\model\User as Users;
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class User extends Acl{
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                [['name'=>'name|py'],'fullLike'],
                ['user','fullLike'],
                ['tel','fullLike'],
                ['data','fullLike']
            ]);//构造SQL
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('user',$sql);//数据鉴权
            $count = Users::where($sql)->count();//获取总条数
            $info = Users::with(['frameData','roleData'])->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select();//查询分页数据
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
    //新增|更新
    public function save(){
        $input=input('post.');
        if(isset($input['id'])){
            
            //构造|验证
            try {
                $input['py']=zhToPy($input['name']);//首拼信息
                if(empty($input['id'])){
                    $this->validate($input,'app\validate\User');
                }else{
                    //设置TOKEN失效
                    $input['token']="";
                    //判断密码是否修改|留空不修改密码
                    if(isset($input['pwd']) && empty($input['pwd'])){
                        unset($input['pwd']);
                    }
                    $this->validate($input,'app\validate\User.update');
                }
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //处理数据
            Db::startTrans();
            try {
                if(empty($input['id'])){
                    //创建数据
                    Users::create($input);
                    pushLog('新增用户[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Users::update($input);
                    pushLog('更新用户[ '.$input['name'].' ]');//日志
                }
                
                Db::commit();
            	$result=['state'=>'success'];
            } catch (\Exception $e) {
            	Db::rollback();
            	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //获取
    public function get(){
        $input=input('post.');
        if(existFull($input,['id'])){
            $result=[
                'state'=>'success',
                'info'=>Users::where([['id','=',$input['id']]])->find()
            ];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //删除
    public function del(){
        $input=input('post.');
        if(existFull($input,['id'])){
            //关联判断
            $exist=moreTableFind([
                ['table'=>'allot','where'=>[['user','=',$input['id']]]],
                ['table'=>'barter','where'=>[['user','=',$input['id']]]],
                ['table'=>'bill','where'=>[['user','=',$input['id']]]],
                ['table'=>'bor','where'=>[['user','=',$input['id']]]],
                ['table'=>'bre','where'=>[['user','=',$input['id']]]],
                ['table'=>'buy','where'=>[['user','=',$input['id']]]],
                ['table'=>'entry','where'=>[['user','=',$input['id']]]],
                ['table'=>'extry','where'=>[['user','=',$input['id']]]],
                ['table'=>'ice','where'=>[['user','=',$input['id']]]],
                ['table'=>'imy','where'=>[['user','=',$input['id']]]],
                ['table'=>'oce','where'=>[['user','=',$input['id']]]],
                ['table'=>'omy','where'=>[['user','=',$input['id']]]],
                ['table'=>'period','where'=>[['user','=',$input['id']]]],
                ['table'=>'sell','where'=>[['user','=',$input['id']]]],
                ['table'=>'sor','where'=>[['user','=',$input['id']]]],
                ['table'=>'sre','where'=>[['user','=',$input['id']]]],
                ['table'=>'swap','where'=>[['user','=',$input['id']]]],
                ['table'=>'customer','where'=>[['user','=',$input['id']]]],
                ['table'=>'often','where'=>[['user','=',$input['id']]]],
                ['table'=>'record','where'=>[['user','=',$input['id']]]],
                ['table'=>'supplier','where'=>[['user','=',$input['id']]]],
            ]);
            if(empty($exist)){
                //逻辑处理
                $find=Db::name('user')->where([['id','=',$input['id']]])->find();
                Db::startTrans();
                try {
                    Db::name('user')->where([['id','=',$input['id']]])->delete();
                    Db::name('log')->where([['user','=',$input['id']]])->delete();
                    pushLog('删除用户[ '.$find['name'].' ]');//日志
                    
                	Db::commit();
                	$result=['state'=>'success'];
                } catch (\Exception $e) {
                	Db::rollback();
                	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
                }
            }else{
                $result=['state'=>'error','info'=>'存在数据关联,删除失败!'];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //上传
    public function upload(){
		$file=request()->file('file');//获取上传文件
		if (empty($file)){
		    $result=['state'=>'error','info'=>'传入数据不完整!'];
		}else{
		    try{
                validate(['file'=>['fileSize'=>1*1024*1024,'fileExt'=>'png,gif,jpg,jpeg']])->check(['file'=>$file]);
                $fileInfo=Filesystem::disk('upload')->putFile('user', $file, 'uniqid');
                $filePath=request()->domain().'/static/upload/'.$fileInfo;
                $result=['state'=>'success','info'=>$filePath];
            }catch(ValidateException $e) {
                $result=['state'=>'error','info'=>$e->getMessage()];
            }
		}
		return json($result);
    }
}
