<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Frame as Frames;
use think\facade\Db;
use think\exception\ValidateException;
class Frame extends Acl{
    //列表
    public function record(){
        $tree=new \org\Tree();
        $frame=$tree::hTree(Frames::order(['sort'=>'asc'])->select());
        return json(['state'=>'success','info'=>$frame]);
    }
    //新增|更新
    public function save(){
        $input=input('post.');
        if(isset($input['id'])){
            //验证数据
            try {
                if(empty($input['id'])){
                    $this->validate($input,'app\validate\Frame');
                }else{
                    $this->validate($input,'app\validate\Frame.update');
                    //所属不可等于或包含当前
                    if(in_array($input['pid'],findTreeArr('frame',$input['id'],'id'))){
                        throw new ValidateException('所属组织选择不正确!');
                    }
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
                    Frames::create($input);
                    pushLog('新增组织机构[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Frames::update($input);
                    pushLog('更新组织机构[ '.$input['name'].' ]');//日志
                }
                
                Db::commit();
            	$result=['state'=>'success'];
            } catch (\Exception $e) {
            	Db::rollback();
            	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
            }
        }else {
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
                'info'=>Frames::where([['id','=',$input['id']]])->find()
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
                $subFind=Db::name('frame')->where([['pid','=',$input['id']]])->find();
                if(empty($subFind)){
                     //关联判断
                    $exist=moreTableFind([
                        ['table'=>'account','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'allot','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'bill','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'bor','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'bre','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'buy','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'customer','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'entry','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'extry','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'ice','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'imy','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'oce','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'omy','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'people','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'sell','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'sor','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'sre','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'supplier','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'swap','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'user','where'=>[['frame','=',$input['id']]]],
                        ['table'=>'warehouse','where'=>[['frame','=',$input['id']]]],
                    ]);
                    if(empty($exist)){
                        //逻辑处理
                        $find=Db::name('frame')->where([['id','=',$input['id']]])->find();
                        Db::startTrans();
                        try {
                            Db::name('frame')->where([['id','=',$input['id']]])->delete();
                            pushLog('删除组织机构[ '.$find['name'].' ]');//日志
                            
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
                    $result=['state'=>'error','info'=>'存在子数据,删除失败!'];
                }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    
}
