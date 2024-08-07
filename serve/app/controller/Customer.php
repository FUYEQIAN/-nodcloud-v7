<?php
namespace app\controller ;
use app\controller\Acl;
use app\model\{Customer as Customers,Sys};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Customer extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        $sql=fastSql($input,[
            [['name'=>'name|py'],'fullLike'],
            ['number','fullLike'],
            ['category','fullEq'],
            ['grade','fullEq'],
            ['contacts','fullLike'],
            [['tel'=>'contacts'],'fullLike'],
            ['user','fullEq'],
            ['data','fullLike']
        ]);//构造SQL
        $sql=frameScope($sql);//组织数据
        $sql=sqlAuth('customer',$sql);//数据鉴权
        $count = Customers::where($sql)->count();//获取总条数
        $info = Customers::with(['frameData','userData'])->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select();//查询分页数据
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
                //排除balance字段|防止更新应收款余额
                unset($input['balance']);
                $input['py']=zhToPy($input['name']);//首拼信息
                empty($input['id'])?$this->validate($input,'app\validate\Customer'):$this->validate($input,'app\validate\Customer.update');
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //处理数据
            Db::startTrans();
            try {
                if(empty($input['id'])){
                    //创建数据
                    Customers::create($input);
                    pushLog('新增客户[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Customers::update($input);
                    pushLog('更新客户[ '.$input['name'].' ]');//日志
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
                'info'=>Customers::where([['id','=',$input['id']]])->find()
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
            $exists=[
                ['table'=>'sor','where'=>[['customer','in',$input['parm']]]],
                ['table'=>'sell','where'=>[['customer','in',$input['parm']]]],
                ['table'=>'sre','where'=>[['customer','in',$input['parm']]]],
                ['table'=>'extry','where'=>[['customer','in',$input['parm']]]],
                ['table'=>'imy','where'=>[['customer','in',$input['parm']]]],
                ['table'=>'bill','where'=>[['customer','in',$input['parm']]]],
                ['table'=>'ice','where'=>[['customer','in',$input['parm']]]]
            ];
            $exist=moreTableFind($exists);
            if(empty($exist)){
                //逻辑处理
                $data=Db::name('customer')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
                Db::startTrans();
                try {
                    Db::name('customer')->where([['id','in',$input['parm']]])->delete();
                    Db::name('log')->insert(['time'=>time(),'user'=>getUserID(),'info'=>'删除客户[ '.implode(' | ',array_column($data,'name')).' ]']);
                    
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
				unset($data[1]);//删除标题行
				unset($data[2]);//删除列名行
                $sql=[];//初始化SQL
                $frame=Db::name('frame')->where([['name','in',array_column($data,'C')]])->select()->toArray();
                foreach ($data as $dataKey=>$dataVo) {
					$record=[
						'name'=>$dataVo['A'],
						'py'=>zhToPy($dataVo['A']),
						'number'=>$dataVo['B'],
						'frame'=>$dataVo['C'],
						'user'=>getUserID(),
						'category'=>$dataVo['D'],
						'grade'=>$dataVo['E'],
						'bank'=>$dataVo['F'],
						'account'=>$dataVo['G'],
						'tax'=>$dataVo['H'],
						'data'=>$dataVo['I'],
						'contacts'=>(empty($dataVo['J'])&&empty($dataVo['K']))?[]:[['main'=>true,'name'=>$dataVo['J'],'tel'=>$dataVo['K'],'add'=>$dataVo['L'],'data'=>$dataVo['M']]],
						'more'=>[]
					];
					
					//所属组织匹配
    				$frameFind=search($frame)->where([['name','=',$record['frame']]])->find();
    				if(empty($frameFind)){
    				    throw new ValidateException('模板文件第'.$dataKey.'行所属组织[ '.$record['frame'].' ]未匹配!');
    				}else{
    				    $record['frame']=$frameFind['id'];
    				}
					try {
					    //数据合法性验证
                        $this->validate($record,'app\validate\Customer');
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
                    return json(['state'=>'error','info'=>'模板文件客户编号[ '.implode(' | ',$diff).' ]重复!']);
                }
				//处理关联数据
				foreach($sql as $sqlKey=>$sqlVo){
					$sys=getSys(['crCategory','crGrade']);
					//客户类别
					if(!in_array($sqlVo['category'],$sys['crCategory'])){
						$sys['crCategory'][]=$sqlVo['category'];
						Sys::where([['name','=','crCategory']])->update(['info'=>json_encode($sys['crCategory'])]);
					}
					//客户等级
					if(!in_array($sqlVo['grade'],$sys['crGrade'])){
						$sys['crGrade'][]=$sqlVo['grade'];
						Sys::where([['name','=','crGrade']])->update(['info'=>json_encode($sys['crGrade'])]);
					}
				}
				//新增数据
				$customer = new Customers;
				$customer->saveAll($sql);
				pushLog('批量导入[ '.count($sql).' ]条客户数据');//日志
				$result=['state'=>'success','info'=>'成功导入'.count($sql).'行客户数据'];
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
            $info=Customers::with(['frameData','userData'])->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();//查询数据
            foreach ($info as $infoKey=>$infoVo) {
                $contactsArr=[];
                foreach ($infoVo['contacts'] as $contactsVo) {
                    $contactsArr[]=$contactsVo['name'].' | '.$contactsVo['tel'].' | '.$contactsVo['add'].' | '.$contactsVo['data'];
                }
                $info[$infoKey]['contacts']=implode(chr(10),$contactsArr);
            }
            $field=[
            	'name'=>'客户名称',
            	'number'=>'客户编号',
            	'category'=>'客户类别',
            	'grade'=>'客户等级',
            	'bank'=>'开户银行',
            	'account'=>'银行账号',
            	'tax'=>'纳税号码',
            	'balance'=>'应收款余额',
            	'frameData|name'=>'所属组织',
            	'userData|name'=>'所属用户',
            	'data'=>'备注信息',
            	'contacts'=>'联系资料'
            ];
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'客户信息'];
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
            pushLog('导出客户信息');//日志
            buildExcel('客户信息',$excel);
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
}