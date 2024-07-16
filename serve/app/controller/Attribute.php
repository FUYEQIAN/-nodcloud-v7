<?php
namespace app\controller ;
use app\controller\Acl;
use app\model\Attribute as Attributes;
use think\facade\Db;
use think\exception\ValidateException;
class Attribute extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        $sql=fastSql($input,[
            ['name','fullLike'],
            ['data','fullLike']
        ]);//构造SQL
        $count = Attributes::where($sql)->count();//获取总条数
        $info = Attributes::where($sql)->page($input['page'],$input['limit'])->order(['sort'=>'asc'])->select();//查询分页数据
        $result=[
            'state'=>'success',
            'count'=>$count,
            'info'=>$info
        ];//返回数据
        return json($result);
    }
    //新增|更新
    public function save(){
        $input=input('post.');
        if(isset($input['id'])){
            //验证数据
            try {
                empty($input['id'])?$this->validate($input,'app\validate\Attribute'):$this->validate($input,'app\validate\Attribute.update');
                //关联判断
                if(!empty($input['id'])){
                    $column=[array_column($input['info'],'name'),array_column(Db::name('attribute_info')->where([['pid','=',$input['id']]])->select()->toArray(),'name')];
                    $diff=array_diff($column[1],$column[0]);
                    if(!empty($diff)){
                        $whereOr=[];
                        foreach($diff as $v){$whereOr[]=['name','like','%'.$v.'%'];}
                        $attr=Db::name('attr')->whereOr($whereOr)->select()->toArray();
                        if(!empty($attr)){
                            $column[]=array_column($attr,'name');
                            $columns=[];
                            foreach($column[2] as $v){$columns=array_merge($columns,explode("|",$v));}
                            foreach($column[2] as $v){
                                if(in_array($v,$columns)){throw new ValidateException('[ '.$v.' ] 存在数据关联，操作已撤销!');}
                            }
                        }
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
                    $createInfo=Attributes::create($input);
                    $input['id']=$createInfo['id'];//转存主键
                    pushLog('新增辅助属性[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Attributes::update($input);
                    pushLog('更新辅助属性[ '.$input['name'].' ]');//日志
                }
                
                //INFO数据
                Db::name('attribute_info')->where([['pid','=',$input['id']]])->delete();
                foreach ($input['info'] as $k=>$v) {
                    $input['info'][$k]['pid']=$input['id'];
                }
                Db::name('attribute_info')->insertAll($input['info']);
                
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
                'info'=>Attributes::with(['info'])->where([['id','=',$input['id']]])->find()
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
            $find=Db::name('attribute')->where([['id','=',$input['id']]])->find();
            $info=Db::name('attribute_info')->where([['pid','=',$input['id']]])->select()->toArray();
            
            //关联判断
            $list=array_column($info,'name');
            $whereOr=[];
            foreach($list as $v){
                $whereOr[]=['name','like','%'.$v.'%'];
            }
            $attr=Db::name('attr')->whereOr($whereOr)->select()->toArray();
            if(!empty($attr)){
                $column=array_column($attr,'name');
                $columns=[];
                foreach($column as $v){
                    $columns=array_merge($columns,explode("|",$v));
                }
                foreach($list as $v){
                    if(in_array($v,$columns)){
                        return json(['state'=>'error','info'=>'存在数据关联,删除失败!']);
                    }
                }
            }
            
            Db::startTrans();
            try {
                Db::name('attribute')->where([['id','=',$input['id']]])->delete();
                Db::name('attribute_info')->where([['pid','=',$input['id']]])->delete();
                pushLog('删除辅助属性[ '.$find['name'].' ]');//日志
                
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
    //全部
    public function select(){
        $info = Attributes::with('info')->order(['sort'=>'asc'])->select();
        $result=['state'=>'success','info'=>$info];
        return json($result);
    }
}