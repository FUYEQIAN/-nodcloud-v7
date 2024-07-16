<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Menu as Menus;
use think\facade\Db;
use think\exception\ValidateException;
class Menu extends Acl{
    //获取
    public function record(){
        $tree=new \org\Tree();
        $menu=$tree::hTree(Menus::order(['sort'=>'asc'])->append(['extension'])->select());
        return json(['state'=>'success','info'=>$menu]);
    }
    //新增|更新
    public function save(){
        $input=input('post.');
        if(isset($input['id'])){
            //验证数据
            try {
                if(empty($input['id'])){
                    $this->validate($input,'app\validate\Menu');
                }else{
                    $this->validate($input,'app\validate\Menu.update');
                    //所属不可等于或包含当前
                    if(in_array($input['pid'],findTreeArr('menu',$input['id'],'id'))){
                        throw new ValidateException('所属菜单选择不正确!');
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
                    Menus::create($input);
                    pushLog('新增菜单[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Menus::update($input);
                    pushLog('更新菜单[ '.$input['name'].' ]');//日志
                }
                
                //修改上级架构菜单地址|#group
                if($input['type']!=1){
                    Menus::where([['id','=',$input['pid']]])->update(['resource'=>'#group']);
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
                'info'=>Menus::where([['id','=',$input['id']]])->find()
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
            $subFind=Db::name('menu')->where([['pid','=',$input['id']]])->find();
            if(empty($subFind)){
                $find=Db::name('menu')->where([['id','=',$input['id']]])->find();
                Db::startTrans();
                try {
                    Db::name('menu')->where([['id','=',$input['id']]])->delete();
                    pushLog('删除菜单[ '.$find['name'].' ]');//日志
                    
                    Db::commit();
                    $result=['state'=>'success'];
                } catch (\Exception $e) {
                	Db::rollback();
                	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
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
