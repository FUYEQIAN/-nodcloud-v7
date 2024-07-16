<?php
namespace app\controller ;
use app\controller\Acl;
use app\model\Code as Codes;
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Code extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        $sql=fastSql($input,[
            ['name','fullLike'],
            ['info','fullLike'],
            ['type','fullDec1'],
            ['data','fullLike']
        ]);//构造SQL
        $count = Codes::where($sql)->count();//获取总条数
        $info = Codes::where($sql)->append(['extension'])->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select();//查询分页数据
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
                empty($input['id'])?$this->validate($input,'app\validate\Code'):$this->validate($input,'app\validate\Code.update');
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //处理数据
            Db::startTrans();
            try {
                if(empty($input['id'])){
                    //创建数据
                    Codes::create($input);
                    pushLog('新增条码[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Codes::update($input);
                    pushLog('更新条码[ '.$input['name'].' ]');//日志
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
                'info'=>Codes::where([['id','=',$input['id']]])->find()
            ];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //删除
    public function del(){
        $input=input('post.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            $data=Db::name('code')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            Db::startTrans();
            try {
                Db::name('code')->where([['id','in',$input['parm']]])->delete();
                Db::name('log')->insert(['time'=>time(),'user'=>getUserID(),'info'=>'删除条码信息[ '.implode(' | ',array_column($data,'name')).' ]']);
                
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
		delOverdueFile('static.upload.xlsx');//删除过期文件
		$file=request()->file('file');//获取上传文件
		if(empty($file)){
		    $result=['state'=>'error','info'=>'传入数据不完整!'];
		}else{
		    try{
                validate(['file'=>['fileSize'=>2*1024*1024,'fileExt'=>'xlsx']])->check(['file'=>$file]);
                $fileInfo = Filesystem::disk('upload')->putFile('xlsx', $file, 'uniqid');
                $filePath = pathChange('static.upload').$fileInfo;
                $data=getXlsx($filePath);
				unset($data[1]);//删除标题行
                $sql=[];//初始化SQL
		        foreach ($data as $dataKey=>$dataVo) {
					$record=[
						'name'=>$dataVo['A'],
						'info'=>$dataVo['B'],
						'type'=>$dataVo['C']=="条形码"?0:1,
						'data'=>$dataVo['D']
					];
                    //数据合法性验证
                    try {
                    	$this->validate($record,'app\validate\Code');
                    	$sql[]=$record;//加入SQL
                    } catch (ValidateException $e) {
                        //返回错误信息
                        return json(['state'=>'error','info'=>'模板文件第[ '.$dataKey.' ]行'.$e->getError()]);
                        exit;
                    }
                }
				//新增数据
				$code = new Codes;
				$code->saveAll($sql);
				$result=['state'=>'success','info'=>'成功导入'.count($sql).'行条码数据'];
            }catch(ValidateException $e) {
                $result=['state'=>'error','info'=>$e->getMessage()];//返回错误信息
            }
		}
		return json($result);
    }
    //导出
	public function exports(){
		$input=input('get.');
		if(existFull($input,['parm']) && is_array($input['parm'])){
            $info=Codes::where([['id','in',$input['parm']]])->append(['extension'])->order(['id'=>'desc'])->select()->toArray();//查询数据
            //生成并关联图像信息
            foreach ($info as $infoKey=>$infoVo) {
                if($infoVo['type']==0){
                    $info[$infoKey]['img']=[
                        'type'=>'img',
                        'info'=>txm($infoVo['info'],false)
                    ];
                }else if($infoVo['type']==1){
                    $info[$infoKey]['img']=[
                        'type'=>'img',
                        'info'=>ewm($infoVo['info'],false)
                    ];
                }else{
                    exit("Error");
                }
            }
            $field=[
            	'name'=>'条码名称',
            	'info'=>'条码内容',
            	'extension|type'=>'条码类型',
            	'img'=>'条码图像',
            	'data'=>'备注信息'
            ];
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'条码信息'];
            //表格数据
            $thead=array_values($field);//表格标题
            $tbody=[];//表格内容
            //构造表内数据
            foreach ($info as $infoVo) {
                $rowData=[];
                foreach (array_keys($field) as $fieldVo) {
                    $rowData[]=arraySeek($infoVo,$fieldVo);//多键名数据赋值
                }
            	$tbody[]=$rowData;//加入行数据
            }
            $excel[]=['type'=>'table','info'=>['thead'=>$thead,'tbody'=>$tbody]];//表格数据
            //统计数据
            $excel[]=['type'=>'node','info'=>['总数:'.count($info)]];
            //导出execl
            pushLog('导出条码信息');//日志
            buildExcel('条码信息',$excel);
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}    
	}
    //图像
    public function view(){
        $input=input('get.');
        if(existFull($input,['text','type']) && in_array($input['type'],['txm','ewm'])){
            if($input['type']=='txm'){
                //条形码
                txm($input['text']);
            }else if($input['type']=='ewm'){
                //二维条
                ewm($input['text']);
            }else{
                exit('error');
            }
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
}