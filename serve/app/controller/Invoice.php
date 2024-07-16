<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Invoice as Invoices;
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Invoice extends Acl{
    //购销发票
    public function record(){
        $input=input('post.');
        $sheet=['buy','bre','sell','sre'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(existFull($input,['page','limit']) && is_arrays($input,['invoice','mold']) && arrayInArray($input['mold'],$sheet)){
            //基础语句
            $base=fastSql($input,[
                [['number'=>'class.number'],'fullLike'],
                ['invoice','fullIn'],
                [['startTime'=>'class.time'],'startTime'],
                [['endTime'=>'class.time'],'endTime'],
            ]);
            $base[]=['examine','=',1];
            $base[]=['invoice','<>',3];
            $base=frameScope($base);
            //匹配语句
            $sql=[];
            foreach ($input['mold'] as $mold) {
                if(in_array($mold,['buy','bre'])){
                    $sql[$mold]=array_merge($base,fastSql($input,[['supplier','fullEq']]));
                }else{
                    $sql[$mold]=array_merge($base,fastSql($input,[['customer','fullEq']]));
                }
                $sql[$mold]=sqlAuth($mold,$sql[$mold]);//数据鉴权
            }
            //构造查询
            $union=[];
            foreach ($input['mold'] as $mold) {
                $union[]=Db::name($mold)->alias('class')->where($sql[$mold])->leftJoin(['is_invoice'=>'invoice'],'class.id=invoice.class and invoice.type="'.$mold.'"')->fieldRaw('"'.$mold.'" as mold,class.id,class.time,sum(invoice.money) as iat')->group('class.id')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $count=DB::query('SELECT COUNT(*) as count FROM ('.$union.') as nodcloud')[0]["count"];
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud ORDER BY `time` DESC LIMIT '.pageCalc($input['page'],$input['limit'],'str'));
            //匹配数据
            $list=[];
            foreach ($input['mold'] as $mold) {
                $gather=search($record)->where([['mold','=',$mold]])->select();
                $db="app\\model\\".ucfirst($mold);
                if(in_array($mold,['buy','bre'])){
                    $list[$mold]=$db::with(['frameData','supplierData'])->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray();
                }else{
                    $list[$mold]=$db::with(['frameData','customerData'])->where([['id','in',array_column($gather,'id')]])->append(['extension'])->select()->toArray();
                }
            }
            $data=[];
            foreach ($record as $recordVo) {
                $mold=$recordVo['mold'];
                $row=search($list[$mold])->where([['id','=',$recordVo['id']]])->find();
                $row['mold']=$mold;
                $row['name']=['buy'=>'采购单','bre'=>'采购退货单','sell'=>'销售单','sre'=>'销售退货单'][$mold];
                $row['current']=in_array($mold,['buy','bre'])?$row['supplierData']:$row['customerData'];
                $row['iat']=floatval($recordVo['iat']);
                $row['ani']=math()->chain($row['actual'])->sub($row['iat'])->done();
                $row['money']="";
                $data[]=$row;
            }
            $result=[
                'state'=>'success',
                'count'=>$count,
                'info'=>$data
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //开具发票
    public function save(){
        $input=input('post.');
        if(existFull($input,['data'])&& is_array($input['data'])){
            //验证数据
            foreach ($input['data'] as $key=>$vo) {
                try {
                    $this->validate($vo,'app\validate\Invoice');
                } catch (ValidateException $e) {
                    return json(['state'=>'error','info'=>'选中数据第'.($key+1).'条'.$e->getError()]);
                    exit;
                }
            }
            //单据匹配
            $gather=[];
            $tab=['buy','bre','sell','sre'];
            foreach ($input['data'] as $vo) {
                in_array($vo['type'],$tab)&&$gather[$vo['type']][]=$vo['class'];
            }
            $union=[];
            foreach ($gather as $mold=>$vo) {
                $union[]=Db::name($mold)->alias('class')->where([['class.id','in',$vo]])->leftJoin(['is_invoice'=>'invoice'],'class.id=invoice.class and invoice.type="'.$mold.'"')->fieldRaw('"'.$mold.'" as mold,class.id,class.actual as actual, sum(invoice.money) as iat')->group('class.id')->buildSql();
            }
            $union=implode(' UNION ALL ',$union);
            $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
            //验证数据
            foreach ($input['data'] as $key=>$vo) {
                $find=search($record)->where([['mold','=',$vo['type']],['id','=',$vo['class']]])->find();
                $ani=math()->chain($find['actual'])->sub($find['iat'])->done();
                if(bccomp($vo['money'],$ani)==1){
                    return json(['state'=>'error','info'=>'选中数据第'.($key+1).'条发票金额超出未开票金额!']);
                    exit;
                }else{
                    $input['data'][$key]['ani']=$ani;
                }
            }
            //处理数据
            Db::startTrans();
            try {
                $bill=[];
                foreach ($input['data'] as $key=>$vo) {
                    //发票状态
                    $bill[$vo['type']]['class'][]=[
                        'id'=>$vo['class'],
                        'invoice'=>bccomp($vo['money'],$vo['ani'])==0?2:1
                    ];
                    //发票记录
                    $bill[$vo['type']]['record'][]=['type'=>$vo['type'],'source'=>$vo['class'],'time'=>time(),'user'=>getUserID(),'info'=>'开具发票[ '.floatval($vo['money']).' ]'];
                    unset($input['data'][$key]['iat']);
                }
                //更新单据状态
                foreach ($bill as $mold=>$vo) {
                    Db::name($mold)->duplicate(['invoice'=>Db::raw('VALUES(`invoice`)')])->insertAll($vo['class']);
                    Db::name('record')->insertAll($vo['record']);
                }
                //添加发票记录
                $model = new \app\model\Invoice;
                $model->saveAll($input['data']);
                pushLog('开具购销发票');//日志
                
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
    //上传
    public function upload(){
		$file = request()->file('file');
        //获取上传文件
        if (empty($file)) {
            $result = ['state' => 'error','info' => '传入数据不完整!'];
        } else {
            //文件限制5MB
            try{
                validate(['file'=>['fileSize'=>5*1024*1024,'fileExt'=>'png,gif,jpg,jpeg,txt,doc,docx,rtf,xls,xlsx,ppt,pptx,pdf,zip,rar']])->check(['file'=>$file]);
                $fileInfo=Filesystem::disk('upload')->putFile('bor', $file, 'uniqid');
                $filePath=request()->domain().'/static/upload/'.$fileInfo;
                $result=['state'=>'success','info'=>$filePath];
            }catch(ValidateException $e) {
                $result = ['state'=>'error','info'=>$e->getMessage()];
            }
        }
        return json($result);
    }
    //购销发票-导出
    public function exports(){
        $input=input('get.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            pushLog('导出购销发票');//日志
            $parm=[];
            $tab=['buy','bre','sell','sre'];
            foreach($input['parm'] as $vo){
                if(in_array($vo['mold'],$tab)){
                    $parm[$vo['mold']][]=$vo['id'];
                }
            }
            //匹配数据
            $list=[]; 
            foreach ($parm as $mold=>$vo) {
                $db="app\\model\\".ucfirst($mold);
                if(in_array($mold,['buy','bre'])){
                    $list[$mold]=$db::with(['frameData','supplierData'])->alias('class')->where([['class.id','in',$vo]])->leftJoin(['is_invoice'=>'invoice'],'class.id=invoice.class and invoice.type="'.$mold.'"')->fieldRaw('class.*,sum(invoice.money) as iat')->group('class.id')->append(['extension'])->select()->toArray();
                }else{
                    $list[$mold]=$db::with(['frameData','customerData'])->alias('class')->where([['class.id','in',$vo]])->leftJoin(['is_invoice'=>'invoice'],'class.id=invoice.class and invoice.type="'.$mold.'"')->fieldRaw('class.*,sum(invoice.money) as iat')->group('class.id')->append(['extension'])->select()->toArray();
                }
            }
            $data=[];
            foreach ($input['parm'] as $vo) {
                $mold=$vo['mold'];
                $row=search($list[$mold])->where([['id','=',$vo['id']]])->find();
                $row['name']=['buy'=>'采购单','bre'=>'采购退货单','sell'=>'销售单','sre'=>'销售退货单'][$mold];
                $row['current']=in_array($mold,['buy','bre'])?$row['supplierData']:$row['customerData'];
                $row['iat']=floatval($row['iat']);
                $row['ani']=math()->chain($row['actual'])->sub($row['iat'])->done();
                $data[]=$row;
            }
            $source=$data;
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'购销发票'];
            //表格数据 
            $field=[
                'name'=>'单据类型',
                'frameData|name'=>'所属组织',
                'current|name'=>'往来单位',
                'time'=>'单据时间',
                'number'=>'单据编号',
                'extension|invoice'=>'发票状态',
                'actual'=>'单据金额',
                'iat'=>'已开票金额',
                'ani'=>'未开票金额'
            ];
            $thead=array_values($field);//表格标题
            $tbody=[];//表格内容
            //构造表内数据
            foreach ($source as $sourceVo) {
                $rowData=[];
                foreach (array_keys($field) as $fieldVo) {
                    $rowData[]=arraySeek($sourceVo,$fieldVo);//多键名数据赋值
                }
            	$tbody[]=$rowData;//加入行数据
            }
            $excel[]=['type'=>'table','info'=>['thead'=>$thead,'tbody'=>$tbody]];//表格数据
            $excel[]=['type'=>'node','info'=>[
                '总数:'.count($source),
                '单据总金额:'.mathArraySum(array_column($source,'actual')),
                '已开票总金额:'.mathArraySum(array_column($source,'iat')),
                '未开票总金额:'.mathArraySum(array_column($source,'ani'))
            ]];
            //导出execl
            buildExcel('购销发票',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
	//报表数据
	public function form(){
	    $input=input('post.');
	    $sheet=['buy','bre','sell','sre'];
        existFull($input,['mold'])||$input['mold']=$sheet;
        if(existFull($input,['page','limit']) && arrayInArray($input['mold'],$sheet)){
            $sql=[];
            //构造SQL|INVOICE
            $sql['invoice']=fastSql($input,[
                [['mold'=>'type'],'fullIn'],
                [['inr'=>'number'],'fullLike'],
                ['title','fullLike'],
            ]);
            
            //构造SQL|CLASS|基础语句
            $sql['base']=fastSql($input,[
                ['number','fullLike'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime'],
            ]);
            $sql['base']=frameScope($sql['base']);
            //数据表
            foreach ($input['mold'] as $t) {
                if(in_array($t,['buy','bre'])){
                    $sql[$t]=array_merge($sql['base'],[['id','=',Db::raw('invoice.class')]],fastSql($input,[['supplier','fullEq']]));
                }else{
                    $sql[$t]=array_merge($sql['base'],[['id','=',Db::raw('invoice.class')]],fastSql($input,[['customer','fullEq']]));
                }
                $sql[$t]=sqlAuth($t,$sql[$t]);//数据鉴权
            }
            //多源匹配
            $union=[];
            foreach ($input['mold'] as $t) {
                //匹配类型|减少查询
                if(in_array($t,$input['mold'])){
                    $union[]=Db::name($t)->where([
                        ['invoice.type','=',$t],
                    ])->where(empty($sql[$t])?[]:[$sql[$t]])->limit(1)->buildSql();
                }
            }
            //合并子查询
            $union=implode(' UNION ALL ',$union);
            $count=Invoices::alias('invoice')->where($sql['invoice'])->whereExists($union)->count();
            $info=Invoices::with(['sourceData'=>['frameData']])->alias('invoice')->where($sql['invoice'])->whereExists($union)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            
            //匹配供应商|客户
            $current=[
                'supplier'=>Db::name('supplier')->where([['id','in',arrayColumns(search($info)->where([['type','in',['buy','bre']]])->select(),['sourceData','supplier'])]])->select()->toArray(),
                'customer'=>Db::name('customer')->where([['id','in',arrayColumns(search($info)->where([['type','in',['sell','sre']]])->select(),['sourceData','customer'])]])->select()->toArray(),
            ];
            $data=[];
            foreach ($info as $infoVo) {
                $row=$infoVo;
                $mold=$infoVo['type'];
                $row['name']=['buy'=>'采购单','bre'=>'采购退货单','sell'=>'销售单','sre'=>'销售退货单'][$mold];
                if(in_array($mold,['buy','bre'])){
                    $row['current']=search($current['supplier'])->where([['id','=',$row['sourceData']['supplier']]])->find();
                }else{
                    $row['current']=search($current['customer'])->where([['id','=',$row['sourceData']['customer']]])->find();
                }
                $data[]=$row;
            }
            $result=[
                'state'=>'success',
                'count'=>$count,
                'info'=>$data
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
	}
	//删除
    public function del(){
        $input=input('post.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            $data=Db::name('invoice')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            Db::startTrans();
            try {
                $gather=[];
                $record=[];
                //匹配数据
                foreach ($data as $key=>$vo) {
                    $gather[$vo['type']][]=$vo['class'];
                    $record[]=['type'=>$vo['type'],'source'=>$vo['class'],'time'=>time(),'user'=>getUserID(),'info'=>'删除发票[ '.floatval($vo['money']).' ]'];
                }
                //插入单据记录
                Db::name('record')->insertAll($record);
                //删除发票记录
                Db::name('invoice')->where([['id','in',$input['parm']]])->delete();
                //匹配发票记录
                $union=[];
                foreach ($gather as $mold=>$vo) {
                    $union[]=Db::name($mold)->alias('class')->where([['class.id','in',$vo]])->leftJoin(['is_invoice'=>'invoice'],'class.id=invoice.class and invoice.type="'.$mold.'"')->fieldRaw('"'.$mold.'" as mold,class.id,count(invoice.id) as length')->group('class.id')->buildSql();
                }
                $union=implode(' UNION ALL ',$union);
                $list=Db::query('SELECT * FROM  ('.$union.') as nodcloud');
                //更新单据状态
                $bill=[];
                foreach($list as $vo){
                    $bill[$vo['mold']][]=['id'=>$vo['id'],'invoice'=>empty($vo['length'])?0:1];
                }
                foreach($bill as $mold=>$vo){
                    Db::name($mold)->duplicate(['invoice'=>Db::raw('VALUES(`invoice`)')])->insertAll($vo);
                    
                }
                pushLog('删除购销发票');//日志
                
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
    //购销发票报表-导出
    public function formExports(){
        $input=input('get.');
		if(existFull($input,['parm']) && is_array($input['parm'])){
            pushLog('导出购销发票报表');//日志
            $info=Invoices::with(['sourceData'=>['frameData']])->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            //匹配供应商|客户
            $current=[
                'supplier'=>Db::name('supplier')->where([['id','in',arrayColumns(search($info)->where([['type','in',['buy','bre']]])->select(),['sourceData','supplier'])]])->select()->toArray(),
                'customer'=>Db::name('customer')->where([['id','in',arrayColumns(search($info)->where([['type','in',['sell','sre']]])->select(),['sourceData','customer'])]])->select()->toArray(),
            ];
            $data=[];
            foreach ($info as $infoVo) {
                $row=$infoVo;
                $mold=$infoVo['type'];
                $row['name']=['buy'=>'采购单','bre'=>'采购退货单','sell'=>'销售单','sre'=>'销售退货单'][$mold];
                if(in_array($mold,['buy','bre'])){
                    $row['current']=search($current['supplier'])->where([['id','=',$row['sourceData']['supplier']]])->find();
                }else{
                    $row['current']=search($current['customer'])->where([['id','=',$row['sourceData']['customer']]])->find();
                }
                $data[]=$row;
            }
            $source=$data;
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'购销发票报表'];
            //表格数据 
            $field=[
                'name'=>'单据类型',
                'sourceData|frameData|name'=>'所属组织',
                'current|name'=>'往来单位',
                'sourceData|time'=>'单据时间',
                'sourceData|number'=>'单据编号',
                'sourceData|actual'=>'单据金额',
                'time'=>'开票时间',
                'number'=>'发票号码',
                'title'=>'发票抬头',
                'money'=>'发票金额',
                'data'=>'备注信息'
            ];
            $thead=array_values($field);//表格标题
            $tbody=[];//表格内容
            //构造表内数据
            foreach ($source as $sourceVo) {
                $rowData=[];
                foreach (array_keys($field) as $fieldVo) {
                    $rowData[]=arraySeek($sourceVo,$fieldVo);//多键名数据赋值
                }
            	$tbody[]=$rowData;//加入行数据
            }
            $excel[]=['type'=>'table','info'=>['thead'=>$thead,'tbody'=>$tbody]];//表格数据
            $excel[]=['type'=>'node','info'=>[
                '总数:'.count($source),
                '单据总金额:'.mathArraySum(arrayColumns($source,['sourceData','actual'])),
                '发票总金额:'.mathArraySum(array_column($source,'money'))
            ]];
            //导出execl
            buildExcel('购销发票报表',$excel);
        }else{
            return json(['state'=>'error','info'=>'传入参数不完整!']);
        }
    }
}
