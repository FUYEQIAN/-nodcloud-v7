<?php
namespace app\controller ;
use app\controller\Acl;
use app\model\{People as Peoples,Sys};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class People extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        $sql=fastSql($input,[
            [['name'=>'name|py'],'fullLike'],
            ['number','fullLike'],
            ['sex','fullDec1'],
            ['tel','fullLike'],
            ['add','fullLike'],
            ['card','fullLike'],
            ['data','fullLike']
        ]);//构造SQL
        $sql=frameScope($sql);//组织数据
        $sql=sqlAuth('people',$sql);//数据鉴权
        $count = Peoples::where($sql)->count();//获取总条数
        $info = Peoples::with(['frameData'])->append(['extension'])->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select();//查询分页数据
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
            //构造|验证
            try {
                $input['py']=zhToPy($input['name']);//首拼信息
                empty($input['id'])?$this->validate($input,'app\validate\People'):$this->validate($input,'app\validate\People.update');
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //处理数据
            Db::startTrans();
            try {
                if(empty($input['id'])){
                    //创建数据
                    Peoples::create($input);
                    pushLog('新增人员[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Peoples::update($input);
                    pushLog('更新人员[ '.$input['name'].' ]');//日志
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
                'info'=>Peoples::where([['id','=',$input['id']]])->find()
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
            //关联判断
            $exist=moreTableFind([
                ['table'=>'allot','where'=>[['people','in',$input['parm']]]],
                ['table'=>'barter','where'=>[['people','in',$input['parm']]]],
                ['table'=>'bill','where'=>[['people','in',$input['parm']]]],
                ['table'=>'bor','where'=>[['people','in',$input['parm']]]],
                ['table'=>'bre','where'=>[['people','in',$input['parm']]]],
                ['table'=>'buy','where'=>[['people','in',$input['parm']]]],
                ['table'=>'entry','where'=>[['people','in',$input['parm']]]],
                ['table'=>'extry','where'=>[['people','in',$input['parm']]]],
                ['table'=>'ice','where'=>[['people','in',$input['parm']]]],
                ['table'=>'oce','where'=>[['people','in',$input['parm']]]],
                ['table'=>'imy','where'=>[['people','in',$input['parm']]]],
                ['table'=>'omy','where'=>[['people','in',$input['parm']]]],
                ['table'=>'sell','where'=>[['people','in',$input['parm']]]],
                ['table'=>'sor','where'=>[['people','in',$input['parm']]]],
                ['table'=>'sre','where'=>[['people','in',$input['parm']]]],
                ['table'=>'swap','where'=>[['people','in',$input['parm']]]]
            ]);
            if(empty($exist)){
                //逻辑处理
                $data=Db::name('people')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
                Db::startTrans();
                try {
                    Db::name('people')->where([['id','in',$input['parm']]])->delete();
                    Db::name('log')->insert(['time'=>time(),'user'=>getUserID(),'info'=>'删除人员[ '.implode(' | ',array_column($data,'name')).' ]']);
                    
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
				unset($data[1]);//删除列名行
                $sql=[];//初始化SQL
                $frame=Db::name('frame')->where([['name','in',array_column($data,'C')]])->select()->toArray();
                foreach ($data as $dataKey=>$dataVo) {
					$record=[
						'name'=>$dataVo['A'],
						'py'=>zhToPy($dataVo['A']),
						'number'=>$dataVo['B'],
						'frame'=>$dataVo['C'],
						'sex'=>$dataVo['D'],
						'tel'=>$dataVo['E'],
						'add'=>$dataVo['F'],
						'card'=>$dataVo['G'],
						'data'=>$dataVo['H'],
						'more'=>[]
					];
					//所属组织匹配
    				$frameFind=search($frame)->where([['name','=',$record['frame']]])->find();
    				if(empty($frameFind)){
    				    throw new ValidateException('模板文件第'.$dataKey.'行所属组织[ '.$record['frame'].' ]未匹配!');
    				}else{
    				    $record['frame']=$frameFind['id'];
    				}
    				//人员性别匹配
    				if(in_array($record['sex'],['男','女'])){
    				    $record['sex']=$record['sex']=='男'?1:0;
    				}else{
    				    throw new ValidateException('模板文件第'.$dataKey.'行人员性别[ '.$record['sex'].' ]未匹配!');
    				}
					try {
					    //数据合法性验证
                        $this->validate($record,'app\validate\People');
                        $sql[]=$record;//加入SQL
                    } catch (ValidateException $e) {
                        return json(['state'=>'error','info'=>'模板文件第[ '.$dataKey.' ]行'.$e->getError()]);
                        exit;
                    }
                }
                //判断编号重复
                $column=array_column($sql,'number');
                $unique=array_unique($column);
                $diff=array_diff_assoc($column,$unique);
                if(!empty($diff)){
                    //返回错误信息
                    return json(['state'=>'error','info'=>'模板文件人员编号[ '.implode(' | ',$diff).' ]重复!']);
                }
				//新增数据
				$customer = new Peoples;
				$customer->saveAll($sql);
				pushLog('批量导入[ '.count($sql).' ]条人员数据');//日志
				$result=['state'=>'success','info'=>'成功导入'.count($sql).'行人员数据'];
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
            $info=Peoples::with(['frameData'])->append(['extension'])->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();//查询数据
            $field=[
            	'name'=>'人员名称',
            	'number'=>'人员编号',
            	'frameData|name'=>'所属组织',
            	'extension|sex'=>'人员性别',
            	'tel'=>'联系电话',
            	'add'=>'联系地址',
            	'card'=>'身份证号',
            	'data'=>'备注信息'
            ];
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'人员信息'];
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
            pushLog('导出人员信息');//日志
            buildExcel('人员信息',$excel);
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
}