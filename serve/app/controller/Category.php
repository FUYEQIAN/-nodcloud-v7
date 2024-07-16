<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Category as Categorys;
use think\facade\Db;
use think\exception\ValidateException;
class Category extends Acl{
    //列表
    public function record(){
        $tree=new \org\Tree();
        $category=$tree::hTree(Categorys::order(['sort'=>'asc'])->select());
        return json(['state'=>'success','info'=>$category]);
    }
    //新增|更新
    public function save(){
        $input=input('post.');
        if(isset($input['id'])){
            //验证数据
            try {
                if(empty($input['id'])){
                    $this->validate($input,'app\validate\Category');
                }else{
                    $this->validate($input,'app\validate\Category.update');
                    //所属不可等于或包含当前
                    if(in_array($input['pid'],findTreeArr('category',$input['id'],'id'))){
                        throw new ValidateException('所属类别选择不正确!');
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
                    Categorys::create($input);
                    pushLog('新增商品类别[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Categorys::update($input);
                    pushLog('更新商品类别[ '.$input['name'].' ]');//日志
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
                'info'=>Categorys::where([['id','=',$input['id']]])->find()
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
            $subFind=Db::name('category')->where([['pid','=',$input['id']]])->find();
            if(empty($subFind)){
                //关联判断
                $exist=moreTableFind([
                	['table'=>'goods','where'=>[['category','=',$input['id']]]],
                ]);
                if(empty($exist)){
                //逻辑处理
                    $find=Db::name('category')->where([['id','=',$input['id']]])->find();
                    Db::startTrans();
                    try {
                        Db::name('category')->where([['id','=',$input['id']]])->delete();
                        pushLog('删除商品类别[ '.$find['name'].' ]');//日志
                        
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
