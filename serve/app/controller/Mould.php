<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Mould as Moulds;
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Mould extends Acl{
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                ['name','fullLike'],
                ['key','fullLike']
            ]);//构造SQL
            $count = Moulds::where($sql)->count();//获取总条数
            $info = Moulds::where($sql)->page($input['page'],$input['limit'])->order(['key'=>'asc','sort'=>'asc'])->select();//查询分页数据
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
            //验证数据
            try {
                empty($input['id'])?$this->validate($input,'app\validate\Mould'):$this->validate($input,'app\validate\Mould.update');
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //处理数据
            Db::startTrans();
            try {
                if(empty($input['id'])){
                    //创建数据
                    Moulds::create($input);
                    pushLog('新增模板[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Moulds::update($input);
                    pushLog('更新模板[ '.$input['name'].' ]');//日志
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
                'info'=>Moulds::where([['id','=',$input['id']]])->find()
            ];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //复制
    public function copy(){
        $input=input('post.');
        if(existFull($input,['id'])){
            $find=Moulds::where([['id','=',$input['id']]])->find()->toArray();
            pushLog('复制模板[ '.$find['name'].' ]');//日志
            unset($find['id']);//移除主键
            $find['name']=$find['name'].'|复制';
            Moulds::create($find);
            $result=['state'=>'success'];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //删除
    public function del(){
        $input=input('post.');
        if(existFull($input,['id'])){
            $find=Db::name('mould')->where([['id','=',$input['id']]])->find();
            Db::startTrans();
            try {
                Db::name('mould')->where([['id','=',$input['id']]])->delete();
                pushLog('删除模板[ '.$find['name'].' ]');//日志
                
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
    //导入
    public function import(){
		delOverdueFile('static.upload.mould');//删除过期文件
		$file=request()->file('file');//获取上传文件
		if(empty($file)){
		    $result=['state'=>'error','info'=>'传入数据不完整!'];
		}else{
		    try{
                validate(['file'=>['fileSize'=>1024*1024,'fileExt'=>'md']])->check(['file'=>$file]);
                $fileInfo = Filesystem::disk('upload')->putFile('mould', $file, 'uniqid');
                $filePath = pathChange('static.upload').$fileInfo;
                $json=json_decode(file_get_contents($filePath),true);
                if(existFull($json,['name','key','source','code','data'])){
                    $createInfo=Moulds::create($json);
                    pushLog('导入模板[ '.$createInfo['name'].' ]');//日志
                    $result=['state'=>'success'];
                }else{
                    $result=['state'=>'error','info'=>'模板文件参数不正确!'];
                }
            }catch(ValidateException $e) {
                $result=['state'=>'error','info'=>$e->getMessage()];//返回错误信息
            }
		}
		return json($result);
    }
    //导出
	public function exports(){
	    delOverdueFile('static.file.mould');//删除过期文件
		$input=input('get.');
		if(existFull($input,['parm']) && is_array($input['parm'])){
		    pushLog('导出打印模板');//日志
            $info=Moulds::where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();//查询数据
            $zipFile=[];
            foreach ($info as $infoVo) {
                unset($infoVo['id']);
                $fileName = str_replace(['/','\\',':','*','"','<','>','|','?'],'_',$infoVo['name']);
                $filePath = pathChange('static.file.mould').$fileName.'.md';
                file_put_contents($filePath,json_encode($infoVo));
                $zipFile[]=$filePath;
            }
            buildZip('模板文件',$zipFile);
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}    
	}
	
	// --- 场景函数 ---
	
	//获取|ID
    public function find(){
        $input=input('post.');
        if(existFull($input,['id'])){
            $mould=Moulds::where([['id','=',$input['id']]])->find();
            $result=[
                'state'=>'success',
                'info'=>$mould
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //获取|KEY
    public function select(){
        $input=input('post.');
        if(existFull($input,['key'])){
            $moulds=Moulds::where([['key','in',$input['key']]])->order(['sort'=>'asc'])->select();
            $result=[
                'state'=>'success',
                'info'=>$moulds
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //更新|模板编辑
    public function update(){
        $input=input('post.');
        if(existFull($input,['id','code'])){
            Moulds::where([['id','=',$input['id']]])->update(['code'=>$input['code']]);
            $result=['state'=>'success',];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
}