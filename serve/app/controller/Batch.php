<?php
namespace app\controller ;
use app\controller\Acl;
use think\Model;
use app\model\{Goods,RoomInfo,BatchInfo};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Batch extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit']) && isset($input['warehouse']) && is_array($input['warehouse']) && isset($input['state']) && in_array($input['state'],[0,1])){
            //匹配仓库
            $warehouse = Db::name('warehouse')->where(empty($input['warehouse'])?sqlAuth('warehouse',[]):[['id','in',$input['warehouse']]])->order(['id'=>'desc'])->select()->toArray();
            //构造表头|集合
            $column=[];
            foreach ($warehouse as $warehouseVo) {
                $column[]=['id'=>$warehouseVo['id'],'key'=>'stock_'.$warehouseVo['id'],'name'=>$warehouseVo['name']];
            }
            //匹配商品
            $sql=fastSql($input,[
                [['name'=>'name|py'],'fullLike'],
                ['number','fullLike'],
                ['spec','fullLike'],
                ['brand','fullEq'],
                ['code','fullLike']
            ]);//构造SQL
            //商品类型
            $sql[]=['type','=',0];
            //辅助属性扩展查询
            $sqlOr=existFull($input,['code'])?[['id','in',array_column(Db::name('attr')->where([['code','=',$input['code']]])->select()->toArray(),'pid')]]:[];
            //商品分类树结构查询
            existFull($input,['category'])&&$sql[]=['category','in',findTreeArr('category',$input['category'],'id')];
            //批次查询
            $batchSql=fastSql($input,[
                [['batch'=>'number'],'fullLike'],
                ['time','fullTime']
            ]);//构造SQL
            $batchSql[]=['warehouse','in',array_column($warehouse,'id')];
            //查询操作-批次类型
            if(empty($input['state'])){
                $batch=Db::name('batch')->where($batchSql)->select()->toArray();
            }else{
                $batchSql[]=['time','<>',0];
                $batch=Db::name('batch')->alias('a')->join('goods b','a.goods = b.id')->where($batchSql)->whereRaw('a.time + (b.validity * 86400) >= :time - (b.threshold * 86400)',['time'=>strtotime(date('Y-m-d',time()))])->field('a.*')->select()->toArray();
            }
            //查询商品
            $sql[]=['id','in',array_column($batch,'goods')];
            //获取总条数
            $count = Goods::where($sql)->whereOr($sqlOr)->count();
            //查询分页数据
            $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->order(['id'=>'desc'])->page($input['page'],$input['limit'])->append(['extension'])->select()->toArray();
            //唯一标识|属性处理
            foreach ($info as $infoKey=>$infoVo) {
                $info[$infoKey]['key']=$infoVo['id'];
                foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                    $info[$infoKey]['attr'][$attrKey]['key']=$infoVo['id'].'.'.$attrVo['id'];
                    //属性处理
                    if(existFull($input,['code']) && !in_array($input['code'],[$infoVo['code'],$attrVo['code']])){
                        unset($info[$infoKey]['attr'][$attrKey]);
                    }
                }
                //重建索引
                $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
            }
            //库存集合[w:仓库|g:商品|a:属性|b:批次|t:生产日期]
            $gather=['g'=>[],'wg'=>[],'ga'=>[],'wga'=>[],'gb'=>[],'wgb'=>[],'gbt'=>[],'wgbt'=>[],'gab'=>[],'wgab'=>[],'gabt'=>[],'wgabt'=>[]];
            //查询库存数据-仓储
            $room=Db::name('room')->where([['warehouse','in',array_column($warehouse,'id')],['goods','in',array_column($info,'id')]])->select()->toArray();
            //构造库存数据
            foreach ($room as $roomVo) {
                //商品
                $g=md5_16($roomVo['goods']);
                $gather['g'][$g]=math()->chain($gather['g'][$g]??0)->add($roomVo['nums'])->done();
                //仓库|商品
                $wg=md5_16($roomVo['warehouse'].'&'.$roomVo['goods']);
                $gather['wg'][$wg]=math()->chain($gather['wg'][$wg]??0)->add($roomVo['nums'])->done();
                //判断属性
                if(!empty($roomVo['attr'])){
                    //商品|属性
                    $ga=md5_16($roomVo['goods'].'&'.$roomVo['attr']);
                    $gather['ga'][$ga]=math()->chain($gather['ga'][$ga]??0)->add($roomVo['nums'])->done();
                    //仓库|商品|属性
                    $wga=md5_16($roomVo['warehouse'].'&'.$roomVo['goods'].'&'.$roomVo['attr']);
                    $gather['wga'][$wga]=math()->chain($gather['wga'][$wga]??0)->add($roomVo['nums'])->done();
                }
            }
            
            //构造库存数据-批次
            foreach ($batch as $batchKey=>$batchVo) {
                //商品|批次
                $gb=md5_16($batchVo['goods'].'&'.$batchVo['number']);
                $gather['gb'][$gb]=math()->chain($gather['gb'][$gb]??0)->add($batchVo['nums'])->done();
                //仓库|商品|批次
                $wgb=md5_16($batchVo['warehouse'].'&'.$batchVo['goods'].'&'.$batchVo['number']);
                $gather['wgb'][$wgb]=math()->chain($gather['wgb'][$wgb]??0)->add($batchVo['nums'])->done();
                
                //匹配辅助属性
                $find=search($room)->where([['id','=',$batchVo['room']]])->find();
                if(empty($find['attr'])){
                    //转存数据
                    $batch[$batchKey]['attr']=null;
                    //生产日期
                    if(!empty($batchVo['time'])){
                        //商品|批次|生产日期
                        $gbt=md5_16($batchVo['goods'].'&'.$batchVo['id'].'&'.$batchVo['time']);
                        $gather['gbt'][$gbt]=math()->chain($gather['gbt'][$gbt]??0)->add($batchVo['nums'])->done();
                        //仓库|商品|批次|生产日期
                        $wgbt=md5_16($batchVo['warehouse'].'&'.$batchVo['goods'].'&'.$batchVo['id'].'&'.$batchVo['time']);
                        $gather['wgbt'][$wgbt]=math()->chain($gather['wgbt'][$wgbt]??0)->add($batchVo['nums'])->done();
                    }
                }else{
                    //转存数据
                    $batch[$batchKey]['attr']=$find['attr'];
                    //商品|属性|批次
                    $gab=md5_16($batchVo['goods'].'&'.$find['attr'].'&'.$batchVo['number']);
                    $gather['gab'][$gab]=math()->chain($gather['gab'][$gab]??0)->add($batchVo['nums'])->done();
                    //仓库|商品|属性|批次
                    $wgab=md5_16($batchVo['warehouse'].'&'.$batchVo['goods'].'&'.$find['attr'].'&'.$batchVo['number']);
                    $gather['wgab'][$wgab]=math()->chain($gather['wgab'][$wgab]??0)->add($batchVo['nums'])->done();
                    //生产日期
                    if(!empty($batchVo['time'])){
                        //商品|属性|批次|生产日期
                        $gabt=md5_16($batchVo['goods'].'&'.$find['attr'].'&'.$batchVo['id'].'&'.$batchVo['time']);
                        $gather['gabt'][$gabt]=math()->chain($gather['gabt'][$gabt]??0)->add($batchVo['nums'])->done();
                        //仓库|商品|属性|批次|生产日期
                        $wgabt=md5_16($batchVo['warehouse'].'&'.$batchVo['goods'].'&'.$find['attr'].'&'.$batchVo['id'].'&'.$batchVo['time']);
                        $gather['wgabt'][$wgabt]=math()->chain($gather['wgabt'][$wgabt]??0)->add($batchVo['nums'])->done();
                    }
                }
            }
            //数量匹配|库存处理
            foreach ($info as $infoKey=>$infoVo) {
                //商品
                $g=md5_16($infoVo['id']);
                $info[$infoKey]['summary']=isset($gather['g'][$g])?($infoVo['unit']=='-1'?unitSwitch($gather['g'][$g],$infoVo['units']):$gather['g'][$g]):0;
                //仓库|商品
                foreach ($column as $columnVo) {
                    $wg=md5_16($columnVo['id'].'&'.$infoVo['id']);
                    $info[$infoKey]['stock_'.$columnVo['id']]=isset($gather['wg'][$wg])?($infoVo['unit']=='-1'?unitSwitch($gather['wg'][$wg],$infoVo['units']):$gather['wg'][$wg]):0;
                }
                //匹配辅助属性
                foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                    //商品|属性
                    $ga=md5_16($infoVo['id'].'&'.$attrVo['name']);
                    $info[$infoKey]['attr'][$attrKey]['summary']=isset($gather['ga'][$ga])?($infoVo['unit']=='-1'?unitSwitch($gather['ga'][$ga],$infoVo['units']):$gather['ga'][$ga]):0;
                    //仓库|商品|属性
                    foreach ($column as $columnVo) {
                        $wga=md5_16($columnVo['id'].'&'.$infoVo['id'].'&'.$attrVo['name']);
                        $info[$infoKey]['attr'][$attrKey]['stock_'.$columnVo['id']]=isset($gather['wga'][$wga])?($infoVo['unit']=='-1'?unitSwitch($gather['wga'][$wga],$infoVo['units']):$gather['wga'][$wga]):0;
                    }
                }
                //重建索引
                $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
            }
            //数量匹配|批次处理
            foreach ($info as $infoKey=>$infoVo) {
                //数组改造
                if(empty($infoVo['attr'])){
                    $list=search($batch)->where([['goods','=',$infoVo['id']],['attr','=',null]])->select();
                    //去重匹配
                    foreach (assoc_unique($list,'number') as $listVo) {
                        $row=[
                            'name'=>$listVo['number']
                        ];
                        //商品|批次
                        $gb=md5_16($infoVo['id'].'&'.$listVo['number']);
                        $row['summary']=isset($gather['gb'][$gb])?($infoVo['unit']=='-1'?unitSwitch($gather['gb'][$gb],$infoVo['units']):$gather['gb'][$gb]):0;
                        //仓库|商品|批次
                        foreach ($column as $columnVo) {
                            $wgb=md5_16($columnVo['id'].'&'.$infoVo['id'].'&'.$listVo['number']);
                            $row['stock_'.$columnVo['id']]=isset($gather['wgb'][$wgb])?($infoVo['unit']=='-1'?unitSwitch($gather['wgb'][$wgb],$infoVo['units']):$gather['wgb'][$wgb]):0;
                        }
                        //生产日期处理
                        if(empty($listVo['time'])){
                            $row['batch']=$listVo['id'];
                        }else{
                            $sub=search($list)->where([['number','=',$listVo['number']]])->select();
                            foreach ($sub as $subVo) {
                                $tag=[
                                    'batch'=>$subVo['id'],
                                    'name'=>'-',
                                    'protect'=>$infoVo['protect'],
                                    'startTime'=>date('Y-m-d',$subVo['time']),
                                    'endTime'=>date('Y-m-d',$subVo['time']+($infoVo['protect']*86400))
                                ];
                                //商品|批次|生产日期
                                $gbt=md5_16($infoVo['id'].'&'.$subVo['id'].'&'.$subVo['time']);
                                $tag['summary']=isset($gather['gbt'][$gbt])?($infoVo['unit']=='-1'?unitSwitch($gather['gbt'][$gbt],$infoVo['units']):$gather['gbt'][$gbt]):0;
                                //仓库|商品|批次|生产日期
                                foreach ($column as $columnVo) {
                                    $wgbt=md5_16($columnVo['id'].'&'.$infoVo['id'].'&'.$subVo['id'].'&'.$subVo['time']);
                                    $tag['stock_'.$columnVo['id']]=isset($gather['wgbt'][$wgbt])?($infoVo['unit']=='-1'?unitSwitch($gather['wgbt'][$wgbt],$infoVo['units']):$gather['wgbt'][$wgbt]):0;
                                }
                                $tag['key']=$gbt;
                                $row['attr'][]=$tag;
                            }
                        }
                        $row['key']=$gb;
                        $info[$infoKey]['attr'][]=$row;
                    }
                }else{
                    //匹配数据
                    $list=search($batch)->where([['goods','=',$infoVo['id']],['attr','<>',null]])->select();
                    //循环属性
                    foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                        $select=search($list)->where([['attr','=',$attrVo['name']]])->select();
                        //去重匹配
                        foreach (assoc_unique($select,'number') as $selectVo) {
                            $row=[
                                'name'=>$selectVo['number']
                            ];
                            //商品|属性|批次
                            $gab=md5_16($infoVo['id'].'&'.$selectVo['attr'].'&'.$selectVo['number']);
                            $row['summary']=isset($gather['gab'][$gab])?($infoVo['unit']=='-1'?unitSwitch($gather['gab'][$gab],$infoVo['units']):$gather['gab'][$gab]):0;
                            //仓库|商品|属性|批次
                            foreach ($column as $columnVo) {
                                $wgab=md5_16($columnVo['id'].'&'.$infoVo['id'].$selectVo['attr'].'&'.$selectVo['number']);
                                $row['stock_'.$columnVo['id']]=isset($gather['wgab'][$wgab])?($infoVo['unit']=='-1'?unitSwitch($gather['wgab'][$wgab],$infoVo['units']):$gather['wgab'][$wgab]):0;
                            }
                            //生产日期处理
                            if(empty($selectVo['time'])){
                                $row['batch']=$selectVo['id'];
                            }else{
                                $sub=search($list)->where([['number','=',$selectVo['number']]])->select();
                                foreach ($sub as $subVo) {
                                    $tag=[
                                        'batch'=>$subVo['id'],
                                        'name'=>'-',
                                        'protect'=>$infoVo['protect'],
                                        'startTime'=>date('Y-m-d',$subVo['time']),
                                        'endTime'=>date('Y-m-d',$subVo['time']+($infoVo['protect']*86400))
                                    ];
                                    //商品|属性|批次|生产日期
                                    $gabt=md5_16($infoVo['id'].'&'.$selectVo['attr'].'&'.$subVo['id'].'&'.$subVo['time']);
                                    $tag['summary']=isset($gather['gabt'][$gabt])?($infoVo['unit']=='-1'?unitSwitch($gather['gabt'][$gabt],$infoVo['units']):$gather['gabt'][$gabt]):0;
                                    //仓库|商品|属性|批次|生产日期
                                    foreach ($column as $columnVo) {
                                        $wgabt=md5_16($columnVo['id'].'&'.$infoVo['id'].'&'.$selectVo['attr'].'&'.$subVo['id'].'&'.$subVo['time']);
                                        $tag['stock_'.$columnVo['id']]=isset($gather['wgabt'][$wgabt])?($infoVo['unit']=='-1'?unitSwitch($gather['wgabt'][$wgabt],$infoVo['units']):$gather['wgabt'][$wgabt]):0;
                                    }
                                    $tag['key']=$gabt;
                                    $row['attr'][]=$tag;
                                }
                            }
                            $row['key']=$gab;
                            $info[$infoKey]['attr'][$attrKey]['attr'][]=$row;
                        }
                        if(empty($select))unset($info[$infoKey]['attr'][$attrKey]);
                    }
                    $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
                }
            }
            $result=[
                'state'=>'success',
                'count'=>$count,
                'info'=>$info,
                'column'=>$column
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //导出
	public function exports(){
		$input=input('get.');
        existFull($input,['warehouse'])||$input['warehouse']=[];
		if(isset($input['warehouse']) && is_array($input['warehouse']) && isset($input['state']) && in_array($input['state'],[0,1])){
		    pushLog('导出批次列表');//日志
		    //匹配仓库
            $warehouse = Db::name('warehouse')->where(empty($input['warehouse'])?sqlAuth('warehouse',[]):[['id','in',explode(',',$input['warehouse'])]])->order(['id'=>'desc'])->select()->toArray();
            //构造表头
            $column=[];
            foreach ($warehouse as $warehouseVo) {
                $column['stock_'.$warehouseVo['id']]=$warehouseVo['name'];
            }
            //匹配商品
            $sql=fastSql($input,[
                [['name'=>'name|py'],'fullLike'],
                ['number','fullLike'],
                ['spec','fullLike'],
                ['brand','fullEq'],
                ['code','fullLike']
            ]);//构造SQL
            //商品类型
            $sql[]=['type','=',0];
            //辅助属性扩展查询
            $sqlOr=existFull($input,['code'])?[['id','in',array_column(Db::name('attr')->where([['code','=',$input['code']]])->select()->toArray(),'pid')]]:[];
            //商品分类树结构查询
            existFull($input,['category'])&&$sql[]=['category','in',findTreeArr('category',$input['category'],'id')];
            //批次查询
            $batchSql=fastSql($input,[
                [['batch'=>'number'],'fullLike'],
                ['time','fullTime']
            ]);//构造SQL
            $batchSql[]=['warehouse','in',array_column($warehouse,'id')];
            //查询操作-批次类型
            if(empty($input['state'])){
                $batch=Db::name('batch')->where($batchSql)->select()->toArray();
            }else{
                $batchSql[]=['time','<>',0];
                $batch=Db::name('batch')->alias('a')->join('goods b','a.goods = b.id')->where($batchSql)->whereRaw('a.time + (b.threshold * 86400) < :time',['time'=>strtotime(date('Y-m-d',time()))])->field('a.*')->select()->toArray();
            }
            //查询商品
            $sql[]=['id','in',array_column($batch,'goods')];
            //查询分页数据
            $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
            //属性处理
            foreach ($info as $infoKey=>$infoVo) {
                foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                    //属性处理
                    if(existFull($input,['code']) && !in_array($input['code'],[$infoVo['code'],$attrVo['code']])){
                        unset($info[$infoKey]['attr'][$attrKey]);
                    }
                }
                //重建索引
                $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
            }
            
            //库存集合[w:仓库|g:商品|a:属性|b:批次|t:生产日期]
            $gather=['g'=>[],'wg'=>[],'ga'=>[],'wga'=>[],'gb'=>[],'wgb'=>[],'gbt'=>[],'wgbt'=>[],'gab'=>[],'wgab'=>[],'gabt'=>[],'wgabt'=>[]];
            //查询库存数据-仓储
            $room=Db::name('room')->where([['warehouse','in',array_column($warehouse,'id')],['goods','in',array_column($info,'id')]])->select()->toArray();
            //构造库存数据
            foreach ($room as $roomVo) {
                //商品
                $g=md5_16($roomVo['goods']);
                $gather['g'][$g]=math()->chain($gather['g'][$g]??0)->add($roomVo['nums'])->done();
                //仓库|商品
                $wg=md5_16($roomVo['warehouse'].'&'.$roomVo['goods']);
                $gather['wg'][$wg]=math()->chain($gather['wg'][$wg]??0)->add($roomVo['nums'])->done();
                //判断属性
                if(!empty($roomVo['attr'])){
                    //商品|属性
                    $ga=md5_16($roomVo['goods'].'&'.$roomVo['attr']);
                    $gather['ga'][$ga]=math()->chain($gather['ga'][$ga]??0)->add($roomVo['nums'])->done();
                    //仓库|商品|属性
                    $wga=md5_16($roomVo['warehouse'].'&'.$roomVo['goods'].'&'.$roomVo['attr']);
                    $gather['wga'][$wga]=math()->chain($gather['wga'][$wga]??0)->add($roomVo['nums'])->done();
                }
            }
            
            //构造库存数据-批次
            foreach ($batch as $batchKey=>$batchVo) {
                //商品|批次
                $gb=md5_16($batchVo['goods'].'&'.$batchVo['number']);
                $gather['gb'][$gb]=math()->chain($gather['gb'][$gb]??0)->add($batchVo['nums'])->done();
                //仓库|商品|批次
                $wgb=md5_16($batchVo['warehouse'].'&'.$batchVo['goods'].'&'.$batchVo['number']);
                $gather['wgb'][$wgb]=math()->chain($gather['wgb'][$wgb]??0)->add($batchVo['nums'])->done();
                
                //匹配辅助属性
                $find=search($room)->where([['id','=',$batchVo['room']]])->find();
                if(empty($find['attr'])){
                    //转存数据
                    $batch[$batchKey]['attr']=null;
                    //生产日期
                    if(!empty($batchVo['time'])){
                        //商品|批次|生产日期
                        $gbt=md5_16($batchVo['goods'].'&'.$batchVo['id'].'&'.$batchVo['time']);
                        $gather['gbt'][$gbt]=math()->chain($gather['gbt'][$gbt]??0)->add($batchVo['nums'])->done();
                        //仓库|商品|批次|生产日期
                        $wgbt=md5_16($batchVo['warehouse'].'&'.$batchVo['goods'].'&'.$batchVo['id'].'&'.$batchVo['time']);
                        $gather['wgbt'][$wgbt]=math()->chain($gather['wgbt'][$wgbt]??0)->add($batchVo['nums'])->done();
                    }
                }else{
                    //转存数据
                    $batch[$batchKey]['attr']=$find['attr'];
                    //商品|属性|批次
                    $gab=md5_16($batchVo['goods'].'&'.$find['attr'].'&'.$batchVo['number']);
                    $gather['gab'][$gab]=math()->chain($gather['gab'][$gab]??0)->add($batchVo['nums'])->done();
                    //仓库|商品|属性|批次
                    $wgab=md5_16($batchVo['warehouse'].'&'.$batchVo['goods'].'&'.$find['attr'].'&'.$batchVo['number']);
                    $gather['wgab'][$wgab]=math()->chain($gather['wgab'][$wgab]??0)->add($batchVo['nums'])->done();
                    //生产日期
                    if(!empty($batchVo['time'])){
                        //商品|属性|批次|生产日期
                        $gabt=md5_16($batchVo['goods'].'&'.$find['attr'].'&'.$batchVo['id'].'&'.$batchVo['time']);
                        $gather['gabt'][$gabt]=math()->chain($gather['gabt'][$gabt]??0)->add($batchVo['nums'])->done();
                        //仓库|商品|属性|批次|生产日期
                        $wgabt=md5_16($batchVo['warehouse'].'&'.$batchVo['goods'].'&'.$find['attr'].'&'.$batchVo['id'].'&'.$batchVo['time']);
                        $gather['wgabt'][$wgabt]=math()->chain($gather['wgabt'][$wgabt]??0)->add($batchVo['nums'])->done();
                    }
                }
            }
            //数量匹配|库存处理
            foreach ($info as $infoKey=>$infoVo) {
                //商品
                $g=md5_16($infoVo['id']);
                $info[$infoKey]['summary']=isset($gather['g'][$g])?($infoVo['unit']=='-1'?unitSwitch($gather['g'][$g],$infoVo['units']):$gather['g'][$g]):0;
                //仓库|商品
                foreach ($warehouse as $warehouseVo) {
                    $wg=md5_16($warehouseVo['id'].'&'.$infoVo['id']);
                    $info[$infoKey]['stock_'.$warehouseVo['id']]=isset($gather['wg'][$wg])?($infoVo['unit']=='-1'?unitSwitch($gather['wg'][$wg],$infoVo['units']):$gather['wg'][$wg]):0;
                }
                //匹配辅助属性
                foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                    //商品|属性
                    $ga=md5_16($infoVo['id'].'&'.$attrVo['name']);
                    $info[$infoKey]['attr'][$attrKey]['summary']=isset($gather['ga'][$ga])?($infoVo['unit']=='-1'?unitSwitch($gather['ga'][$ga],$infoVo['units']):$gather['ga'][$ga]):0;
                    //仓库|商品|属性
                    foreach ($warehouse as $warehouseVo) {
                        $wga=md5_16($warehouseVo['id'].'&'.$infoVo['id'].'&'.$attrVo['name']);
                        $info[$infoKey]['attr'][$attrKey]['stock_'.$warehouseVo['id']]=isset($gather['wga'][$wga])?($infoVo['unit']=='-1'?unitSwitch($gather['wga'][$wga],$infoVo['units']):$gather['wga'][$wga]):0;
                    }
                }
                //重建索引
                $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
            }
            //数量匹配|批次处理
            foreach ($info as $infoKey=>$infoVo) {
                //数组改造
                if(empty($infoVo['attr'])){
                    $list=search($batch)->where([['goods','=',$infoVo['id']],['attr','=',null]])->select();
                    //去重匹配
                    foreach (assoc_unique($list,'number') as $listVo) {
                        $row=[
                            'name'=>$listVo['number']
                        ];
                        //商品|批次
                        $gb=md5_16($infoVo['id'].'&'.$listVo['number']);
                        $row['summary']=isset($gather['gb'][$gb])?($infoVo['unit']=='-1'?unitSwitch($gather['gb'][$gb],$infoVo['units']):$gather['gb'][$gb]):0;
                        //仓库|商品|批次
                        foreach ($warehouse as $columnVo) {
                            $wgb=md5_16($warehouseVo['id'].'&'.$infoVo['id'].'&'.$listVo['number']);
                            $row['stock_'.$warehouseVo['id']]=isset($gather['wgb'][$wgb])?($infoVo['unit']=='-1'?unitSwitch($gather['wgb'][$wgb],$infoVo['units']):$gather['wgb'][$wgb]):0;
                        }
                        //生产日期处理
                        if(!empty($listVo['time'])){
                            $sub=search($list)->where([['number','=',$listVo['number']]])->select();
                            foreach ($sub as $subVo) {
                                $tag=[
                                    'name'=>'-',
                                    'protect'=>$infoVo['protect'],
                                    'startTime'=>date('Y-m-d',$subVo['time']),
                                    'endTime'=>date('Y-m-d',$subVo['time']+($infoVo['protect']*86400))
                                ];
                                //商品|批次|生产日期
                                $gbt=md5_16($infoVo['id'].'&'.$subVo['id'].'&'.$subVo['time']);
                                $tag['summary']=isset($gather['gbt'][$gbt])?($infoVo['unit']=='-1'?unitSwitch($gather['gbt'][$gbt],$infoVo['units']):$gather['gbt'][$gbt]):0;
                                //仓库|商品|批次|生产日期
                                foreach ($warehouse as $warehouseVo) {
                                    $wgbt=md5_16($warehouseVo['id'].'&'.$infoVo['id'].'&'.$subVo['id'].'&'.$subVo['time']);
                                    $tag['stock_'.$warehouseVo['id']]=isset($gather['wgbt'][$wgbt])?($infoVo['unit']=='-1'?unitSwitch($gather['wgbt'][$wgbt],$infoVo['units']):$gather['wgbt'][$wgbt]):0;
                                }
                                $tag['key']=$gbt;
                                $row['attr'][]=$tag;
                            }
                        }
                        $row['key']=$gb;
                        $info[$infoKey]['attr'][]=$row;
                    }
                }else{
                    //匹配数据
                    $list=search($batch)->where([['goods','=',$infoVo['id']],['attr','<>',null]])->select();
                    //循环属性
                    foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                        $select=search($list)->where([['attr','=',$attrVo['name']]])->select();
                        //去重匹配
                        foreach (assoc_unique($select,'number') as $selectVo) {
                            $row=[
                                'name'=>$selectVo['number']
                            ];
                            //商品|属性|批次
                            $gab=md5_16($infoVo['id'].'&'.$selectVo['attr'].'&'.$selectVo['number']);
                            $row['summary']=isset($gather['gab'][$gab])?($infoVo['unit']=='-1'?unitSwitch($gather['gab'][$gab],$infoVo['units']):$gather['gab'][$gab]):0;
                            //仓库|商品|属性|批次
                            foreach ($warehouse as $warehouseVo) {
                                $wgab=md5_16($warehouseVo['id'].'&'.$infoVo['id'].$selectVo['attr'].'&'.$selectVo['number']);
                                $row['stock_'.$warehouseVo['id']]=isset($gather['wgab'][$wgab])?($infoVo['unit']=='-1'?unitSwitch($gather['wgab'][$wgab],$infoVo['units']):$gather['wgab'][$wgab]):0;
                            }
                            //生产日期处理
                            if(!empty($selectVo['time'])){
                                $sub=search($list)->where([['number','=',$selectVo['number']]])->select();
                                foreach ($sub as $subVo) {
                                    $tag=[
                                        'name'=>'-',
                                        'protect'=>$infoVo['protect'],
                                        'startTime'=>date('Y-m-d',$subVo['time']),
                                        'endTime'=>date('Y-m-d',$subVo['time']+($infoVo['protect']*86400))
                                    ];
                                    //商品|属性|批次|生产日期
                                    $gabt=md5_16($infoVo['id'].'&'.$selectVo['attr'].'&'.$subVo['id'].'&'.$subVo['time']);
                                    $tag['summary']=isset($gather['gabt'][$gabt])?($infoVo['unit']=='-1'?unitSwitch($gather['gabt'][$gabt],$infoVo['units']):$gather['gabt'][$gabt]):0;
                                    //仓库|商品|属性|批次|生产日期
                                    foreach ($warehouse as $warehouseVo) {
                                        $wgabt=md5_16($warehouseVo['id'].'&'.$infoVo['id'].'&'.$selectVo['attr'].'&'.$subVo['id'].'&'.$subVo['time']);
                                        $tag['stock_'.$warehouseVo['id']]=isset($gather['wgabt'][$wgabt])?($infoVo['unit']=='-1'?unitSwitch($gather['wgabt'][$wgabt],$infoVo['units']):$gather['wgabt'][$wgabt]):0;
                                    }
                                    $tag['key']=$gabt;
                                    $row['attr'][]=$tag;
                                }
                            }
                            $row['key']=$gab;
                            $info[$infoKey]['attr'][$attrKey]['attr'][]=$row;
                        }
                        if(empty($select))unset($info[$infoKey]['attr'][$attrKey]);
                    }
                    $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
                }
            }
		    //结构重组
		    $source=[];
		    foreach ($info as $infoVo) {
		        $source[]=$infoVo;
		        if(!empty($infoVo['attr'])){
		            foreach ($infoVo['attr'] as $attrVo) {
		                $attrVo['name']='|- '.$attrVo['name'];
		                $source[]=$attrVo;
		                if(existFull($attrVo,['attr'])){
        		            foreach ($attrVo['attr'] as $subVo) {
        		                $subVo['name']='|-- '.$subVo['name'];
        		                $source[]=$subVo;
        		            }
        		        }
		            }
		        }
		    }
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'批次列表'];
            //表格数据
            $field=array_merge(['name'=>'商品名称','summary'=>'库存数量'],$column,['protect'=>'保质期(天)','startTime'=>'生产日期','endTime'=>'过期日期','number'=>'商品编号','spec'=>'规格型号','categoryData|name'=>'商品分类','brand'=>'商品品牌','extension|unit'=>'商品单位','code'=>'商品条码','data'=>'商品备注']);
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
            //导出execl
            buildExcel('批次列表',$excel);
		}else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
	//详情列表
    public function detailRecord(){
        $input=input('post.');
        $sheet=['buy','bre','sell','sre','swapOut','swapEnter','entry','extry'];
        existFull($input,['type'])||$input['type']=$sheet;
        if(existFull($input,['page','limit','batch','warehouse']) && is_arrays($input,['batch','warehouse','type']) && arrayInArray($input['type'],$sheet)){
            //构造SQL|batch
            $sql=fastSql($input,[
                [['batch'=>'id'],'fullIn'],
                ['warehouse','fullIn']
            ]);
            //查询批次数据
            $batch=Db::name('batch')->where($sql)->field(['id','goods'])->select()->toArray();
            if(empty($batch)){
                $count=0;
                $info=[];
            }else{
                //构造SQL|BATCHINFO
                $infoSql=fastSql($input,[['type','fullIn']]);
                $infoSql[]=['pid','in',array_column($batch,'id')];
                //子查询SQL
                $existsSql=fastSql($input,[
                    ['number','fullLike'],
                    [['startTime'=>'time'],'startTime'],
                    [['endTime'=>'time'],'endTime']
                ]);
                $existsSql[]=['id','=',Db::raw('info.class')];
                $existsSql=frameScope($existsSql);
                //多源匹配
                $union=[];
                //数据关系表
                $table=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','swapOut'=>'swap','swapEnter'=>'swap','entry'=>'entry','extry'=>'extry'];
                foreach ($table as $k=>$v) {
                    //匹配类型|减少查询
                    if(in_array($k,$input['type'])){
                        $union[]=Db::name($v)->where([['info.type','=',$k]])->where(array_merge($existsSql,sqlAuth($v,[])))->limit(1)->buildSql();
                    }
                }
                //合并子查询
                $union=implode(' UNION ALL ',$union);
                $count=BatchInfo::alias('info')->where($infoSql)->whereExists($union)->count();
                $info=BatchInfo::with(['sourceData'=>['frameData']])->alias('info')->where($infoSql)->whereExists($union)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
                //处理多单位
                if(!empty($info)){
                    $goods=Db::name('goods')->where([['id','=',$batch[0]['goods']]])->find();
                    if($goods['unit']=='-1'){
                        foreach ($info as $infoKey=>$infoVo) {
                            $info[$infoKey]['nums']=unitSwitch($infoVo['nums'],json_decode($goods['units'],true));
                        }
                    }
                }
            }
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
	//详情导出
	public function detailExports(){
		$input=input('get.');
		$sheet=['buy','bre','sell','sre','swapOut','swapEnter','entry','extry'];
		existFull($input,['batch'])||$input['batch']=[];
		existFull($input,['type'])||$input['type']=$sheet;
		if(existFull($input,['batch','warehouse']) && is_arrays($input,['batch','warehouse','type']) && arrayInArray($input['type'],$sheet)){
            pushLog('导出批次详情');//日志
            //构造SQL|batch
            $sql=fastSql($input,[
                [['batch'=>'id'],'fullIn'],
                ['warehouse','fullIn']
            ]);
            //查询仓储数据
            $batch=Db::name('batch')->where($sql)->field(['id','goods','number'])->select()->toArray();
            if(empty($batch)){
                $source=[];
            }else{
                //构造SQL|BATCHINFO
                $infoSql=fastSql($input,[['type','fullIn']]);
                $infoSql[]=['pid','in',array_column($batch,'id')];
                //子查询SQL
                $existsSql=fastSql($input,[
                    ['number','fullLike'],
                    [['startTime'=>'time'],'startTime'],
                    [['endTime'=>'time'],'endTime']
                ]);
                $existsSql[]=['id','=',Db::raw('info.class')];
                $existsSql=frameScope($existsSql);
                //多源匹配
                $union=[];
                //数据关系表
                $table=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','swapOut'=>'swap','swapEnter'=>'swap','entry'=>'entry','extry'=>'extry'];
                foreach ($table as $k=>$v) {
                	//匹配类型|减少查询
                	if(in_array($k,$input['type'])){
                		$union[]=Db::name($v)->where([['info.type','=',$k]])->where(array_merge($existsSql,sqlAuth($v,[])))->limit(1)->buildSql();
                	}
                }
                //合并子查询
                $union=implode(' UNION ALL ',$union);
                $source=BatchInfo::with(['sourceData'=>['frameData']])->alias('info')->where($infoSql)->whereExists($union)->order(['id'=>'desc'])->append(['extension'])->select()->toArray();
                //处理多单位
                if(!empty($source)){
                    $goods=Db::name('goods')->where([['id','=',$batch[0]['goods']]])->find();
                    if($goods['unit']=='-1'){
                        foreach ($source as $sourceKey=>$sourceVo) {
                            $source[$sourceKey]['nums']=unitSwitch($sourceVo['nums'],json_decode($goods['units'],true));
                        }
                    }
                }
            }
            //开始构造导出数据
            $excel=[];//初始化导出数据
            //标题数据
            $excel[]=['type'=>'title','info'=>'批次详情'];
            //表格数据
            $field=[
                'sourceData|frameData|name'=>'所属组织',
                'sourceData|time'=>'操作时间',
                'extension|type'=>'单据类型',
                'sourceData|number'=>'单据编号',
                'extension|direction'=>'操作类型',
                'nums'=>'操作数量'
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
            //导出execl
            buildExcel('批次详情',$excel);
        }else{
		    return json(['state'=>'error','info'=>'传入数据不完整!']);
		}
	}
}