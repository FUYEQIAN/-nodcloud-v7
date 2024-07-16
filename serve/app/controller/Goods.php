<?php
namespace app\controller ;
use app\controller\Acl;
use app\model\{Goods as Goodss,Attr,Sys};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Goods extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        $sql=fastSql($input,[
            [['name'=>'name|py'],'fullLike'],
            ['number','fullLike'],
            ['spec','fullLike'],
            ['brand','fullEq'],
            ['code','fullLike'],
            ['type','fullDec1'],
            ['data','fullLike']
        ]);//构造SQL
        //商品分类树结构查询
        if(existFull($input,['category'])){
            $sql[]=['category','in',findTreeArr('category',$input['category'],'id')];
        }
        $count = Goodss::where($sql)->count();//获取总条数
        $info = Goodss::with(['categoryData'])->where($sql)->page($input['page'],$input['limit'])->append(['extension'])->order(['id'=>'desc'])->select();//查询分页数据
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
            
            try {
                $input['py']=zhToPy($input['name']);//首拼信息
                empty($input['id'])?$this->validate($input,'app\validate\Goods'):$this->validate($input,'app\validate\Goods.update');
                
                //关联判断
                if(!empty($input['id'])){
                    $exist=moreTableFind([
                        ['table'=>'bor_info','where'=>[['goods','=',$input['id']]]],
                        ['table'=>'buy_info','where'=>[['goods','=',$input['id']]]],
                        ['table'=>'bre_info','where'=>[['goods','=',$input['id']]]],
                        ['table'=>'sor_info','where'=>[['goods','=',$input['id']]]],
                        ['table'=>'sell_info','where'=>[['goods','=',$input['id']]]],
                        ['table'=>'sre_info','where'=>[['goods','=',$input['id']]]],
                        ['table'=>'extry_info','where'=>[['goods','=',$input['id']]]],
                        ['table'=>'entry_info','where'=>[['goods','=',$input['id']]]],
                        ['table'=>'swap_info','where'=>[['goods','=',$input['id']]]]
                    ]);
                    if(!empty($exist)){
                        $goods=Db::name('goods')->where([['id','=',$input['id']]])->find();
                        if($input['unit'] != $goods['unit']){
                            throw new ValidateException('[ 单位 ] 存在数据关联,操作已撤销!');
                        }else if($input['type'] != $goods['type']){
                            throw new ValidateException('[ 类型 ] 存在数据关联,操作已撤销!');
                        }else if($input['serial'] != $goods['serial']){
                            throw new ValidateException('[ 序列号 ] 存在数据关联,操作已撤销!');
                        }else if($input['batch'] != $goods['batch']){
                            throw new ValidateException('[ 批次 ] 存在数据关联,操作已撤销!');
                        }else if($input['validity'] != $goods['validity']){
                            throw new ValidateException('[ 有效期 ] 存在数据关联,操作已撤销!');
                        }else{
                            $attr=Db::name('attr')->where([['pid','=',$input['id']]])->select()->toArray();
                            $column=[array_column($attr,'name'),array_column($input['attr'],'name')];
                            $diff=array_diff($column[0],$column[1]);
                            if(!empty($diff)){
                                throw new ValidateException('[ 辅助属性 ] 存在数据关联,操作已撤销!');
                            }
                        }
                    }
                }
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //验证ATTR
            if(empty($input['attr'])){
                //验证辅助属性
                foreach ($input['attr'] as $attrKey=>$attrVo) {
                    try {
                        $this->validate($attrVo,'app\validate\Attr');
                    } catch (ValidateException $e) {
                        return json(['state'=>'error','info'=>'辅助属性第'.($attrKey+1).'条'.$e->getError()]);
                        exit;
                    }
                }
            }
            
            //处理数据
            Db::startTrans();
            try {
                //GOODS数据
                if(empty($input['id'])){
                    //创建数据
                    $createInfo=Goodss::create($input);
                    $input['id']=$createInfo['id'];//转存主键
                    pushLog('新增商品[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Goodss::update($input);
                    pushLog('更新商品[ '.$input['name'].' ]');//日志
                }
                
                //ATTR数据
                Attr::where([['pid','=',$input['id']]])->delete();
                if(!empty($input['attr'])){
                    foreach ($input['attr'] as $attrKey=>$attrVo) {
                        unset($input['attr'][$attrKey]['id']);
                        $input['attr'][$attrKey]['pid']=$input['id'];
                    }
                    $attr = new Attr;
                    $attr->saveAll($input['attr']);
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
                'info'=>Goodss::with(['attr'])->where([['id','=',$input['id']]])->find()
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
                ['table'=>'sor_info','where'=>[['goods','in',$input['parm']]]],
                ['table'=>'sell_info','where'=>[['goods','in',$input['parm']]]],
                ['table'=>'sre_info','where'=>[['goods','in',$input['parm']]]],
                ['table'=>'bor_info','where'=>[['goods','in',$input['parm']]]],
                ['table'=>'buy_info','where'=>[['goods','in',$input['parm']]]],
                ['table'=>'bre_info','where'=>[['goods','in',$input['parm']]]],
                ['table'=>'extry_info','where'=>[['goods','in',$input['parm']]]],
                ['table'=>'entry_info','where'=>[['goods','in',$input['parm']]]],
                ['table'=>'swap_info','where'=>[['goods','in',$input['parm']]]],
            ]);
            if(empty($exist)){
                //逻辑处理
                $data=Db::name('goods')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
                Db::startTrans();
                try {
                    Db::name('goods')->where([['id','in',$input['parm']]])->delete();
                    Db::name('attr')->where([['pid','in',$input['parm']]])->delete();
                    Db::name('log')->insert(['time'=>time(),'user'=>getUserID(),'info'=>'删除商品[ '.implode(' | ',array_column($data,'name')).' ]']);
                    
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
                validate(['file'=>['fileSize'=>2*1024*1024,'fileExt'=>'png,gif,jpg,jpeg']])->check(['file'=>$file]);
                $fileInfo=Filesystem::disk('upload')->putFile('goods', $file, 'uniqid');
                $filePath=request()->domain().'/static/upload/'.$fileInfo;
                $result=['state'=>'success','info'=>$filePath];
            }catch(ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getMessage()]);
            }
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
						'py'=>zhToPy($dataVo['A']),
						'number'=>$dataVo['B'],
						'spec'=>$dataVo['C'],
						'category'=>$dataVo['D'],
						'brand'=>$dataVo['E'],
						'unit'=>$dataVo['F'],
						'buy'=>$dataVo['G'],
						'sell'=>$dataVo['H'],
						'retail'=>$dataVo['I'],
						'integral'=>$dataVo['J'],
						'code'=>$dataVo['K'],
						'location'=>$dataVo['L'],
						'stock'=>$dataVo['M'],
						'type'=>$dataVo['N']=='常规商品'?0:1,
						'data'=>$dataVo['O'],
						'alias'=>'',
						'imgs'=>[],
						'details'=>'',
						'units'=>[],
						'strategy'=>[],
						'serial'=>0,
						'batch'=>0,
						'protect'=>0,
						'threshold'=>0,
						'more'=>[]
					];
					try {
                        //数据合法性验证
                        $this->validate($record,'app\validate\Goods.imports');
                    } catch (ValidateException $e) {
                        return json(['state'=>'error','info'=>'模板文件第[ '.$dataKey.' ]行'.$e->getError()]);
                        exit;
                    }
                    $sql[]=$record;//加入SQL
                }
                //判断名称重复
                $nameColumn=array_column($sql,'name');
                $nameUnique=array_unique($nameColumn);
                $nameDiff=array_diff_assoc($nameColumn,$nameUnique);
                if(!empty($nameDiff)){
                    //返回错误信息
                    return json(['state'=>'error','info'=>'模板文件商品名称[ '.implode(' | ',$nameDiff).' ]重复!']);
                }
                //判断编号重复
                $numberColumn=array_column($sql,'number');
                $numberUnique=array_unique($numberColumn);
                $numberDiff=array_diff_assoc($numberColumn,$numberUnique);
                if(!empty($numberDiff)){
                    //返回错误信息
                    return json(['state'=>'error','info'=>'模板文件商品编号[ '.implode(' | ',$numberDiff).' ]重复!']);
                }
				//处理关联数据
				foreach($sql as $sqlKey=>$sqlVo){
					$sys=getSys(['unit','brand']);
					//商品类别
					if(empty($sqlVo['category'])){
					    return json(['state'=>'error','info'=>'模板文件第[ '.$sqlKey.' ]行商品类别不可为空!']);
					}else{
                        $find=Db::name('category')->where([['name','=',$sqlVo['category']]])->find();
                        if(empty($find)){
                            $insert=Db::name('category')->insertGetId([
                                'pid'=>0,
                                'name'=>$sqlVo['category'],
                                "sort" => 99,
                                "data" => "自动创建|商品导入"
                            ]);
                            $sql[$sqlKey]['category']=$insert;
                        }else{
                            $sql[$sqlKey]['category']=$find['id'];
                        }
					}
					//商品品牌
					if(!in_array($sqlVo['brand'],$sys['brand'])){
                        $sys['brand'][]=$sqlVo['brand'];
                        Sys::where([['name','=','brand']])->update(['info'=>json_encode($sys['brand'])]);
                    }
					//商品单位
					if($sqlVo['unit']=='多单位' || $sqlVo['unit']=='-1'){
					    return json(['state'=>'error','info'=>'模板文件第[ '.($sqlKey+1).' ]行商品单位[ 多单位 ]为保留字段!']);
					}else{
                        if(!in_array($sqlVo['unit'],$sys['unit'])){
                            $sys['unit'][]=$sqlVo['unit'];
                            Sys::where([['name','=','unit']])->update(['info'=>json_encode($sys['unit'])]);
                        }
					}
				}
				//新增数据
				$goods = new Goodss;
				$goods->saveAll($sql);
				pushLog('批量导入[ '.count($sql).' ]条商品数据');//日志
				$result=['state'=>'success','info'=>'成功导入'.count($sql).'行商品数据'];
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
            $info=Goodss::with(['categoryData','attr'])->where([['id','in',$input['parm']]])->append(['extension'])->order(['id'=>'desc'])->select()->toArray();//查询数据
            //字段数据二次处理
            foreach ($info as $infoKey=>$infoVo) {
                //图像赋值
                if(empty($infoVo['imgs'])){
                    $info[$infoKey]['imgs']='';
                }else{
                    $info[$infoKey]['imgs']=[
                        'type'=>'img',
                        'info'=>preg_replace("/(http|https):\/\/[^\/]*\//","",$infoVo['imgs'][0]['url'])
                    ];
                }
                //辅助属性赋值
                if(empty($infoVo['attr'])){
                    $info[$infoKey]['attr']='';
                }else{
                    $attrArr=[];
                    foreach ($infoVo['attr'] as $attrVo) {
                        $attrArr[]='属性名称:'.$attrVo['name'].' # 采购价格:'.$attrVo['buy'].' # 销售价格:'.$attrVo['sell'].' # 零售价格:'.$attrVo['retail'].' # 条形码:'.$attrVo['code'];
                    }
                    $info[$infoKey]['attr']=implode(chr(10),$attrArr);
                }
                //序列商品赋值
                $info[$infoKey]['serial']=$infoVo['serial']?'是':'否';
                //批次商品赋值
                $info[$infoKey]['batch']=$infoVo['batch']?'是':'否';
            }
            $field=[
                'imgs'=>'商品图像',
            	'name'=>'商品名称',
            	'number'=>'商品编号',
            	'spec'=>'规格型号',
            	'categoryData|name'=>'商品类别',
            	'brand'=>'商品品牌',
            	'extension|unit'=>'商品单位',
            	'buy'=>'采购价格',
            	'sell'=>'销售价格',
            	'retail'=>'零售价格',
            	'integral'=>'兑换积分',
            	'code'=>'商品条码',
            	'location'=>'商品货位',
            	'stock'=>'库存阈值',
            	'extension|type'=>'商品类型',
            	'attr'=>'辅助属性',
            	'data'=>'备注信息',
            	'alias'=>'零售名称',
            	'serial'=>'序列商品',
            	'batch'=>'批次商品',
            	'protect'=>'保质期',
            	'threshold'=>'预警阀值',
            ];
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'商品信息'];
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
            pushLog('导出商品信息');//日志
            buildExcel('商品信息',$excel);
        }else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}    
	}
}