<?php
namespace app\controller;
use app\controller\Acl;
use think\facade\Db;
class Summary extends Acl {
    
    public function __construct(){}
    
    //初始化
    public function init() {
        $period=getPeriod();
        //查询条数
        $count=Db::name('room_info')->where([['time','>',$period]])->count();
        //计价方式
        $fun=getSys('fun');
        $info=[
            'valuation'=>['base'=>'基础计价','ma'=>'移动平均','fifo'=>'先进先出'][$fun['valuation']],
            'branch'=>['总仓核算','分仓核算'][$fun['branch']],
            'rule'=>['def'=>'结存结余','attr'=>'辅助属性','batch'=>'批次日期','aab'=>'属性批次'][$fun['rule']],
        ];
        //初始结账
        $summary=Db::name('summary')->where([['time','>',$period]])->field(['id'])->select()->toArray();
        $fifo=Db::name('fifo')->where([['out','in',array_column($summary,'id')]])->select()->toArray();
        $relation=[];
        foreach ($fifo as $v) {
            $relation[]=['id'=>$v['in'],'handle'=>$v['handle']];
        }
        if(!empty($relation)){
            Db::name('summary')->duplicate(['handle'=>Db::raw('handle - VALUES(`handle`)')])->insertAll($relation);
            Db::name('fifo')->where([['id','in',array_column($fifo,'id')]])->delete();
        }
        Db::name('summary')->where([['id','in',array_column($summary,'id')]])->delete();
        pushLog('执行数据校准');//日志
        //返回数据
        $result=[
            'state'=>'success',
            'count'=>$count,
            'info'=>$info
        ];
        return json($result);
    }
    //收发处理
    public function note($type,$class,$mold){
        //场景判断
        if($mold){
            //记录
            $info=Db::name('room_info')->where([['type','=',$type],['class','=',$class]])->order(['time','id'])->field(['id'])->select()->toArray();
            $this->handle(array_column($info,'id'));
        }else{
            //清除
            $summary=Db::name('summary')->where([['type','=',$type],['class','=',$class]])->field(['id'])->select()->toArray();
            $fifo=Db::name('fifo')->where([['out','in',array_column($summary,'id')]])->select()->toArray();
            $relation=[];
            foreach ($fifo as $v) {
                $relation[]=['id'=>$v['in'],'handle'=>$v['handle']];
            }
            if(!empty($relation)){
                Db::name('summary')->duplicate(['handle'=>Db::raw('handle - VALUES(`handle`)')])->insertAll($relation);
                Db::name('fifo')->where([['id','in',array_column($fifo,'id')]])->delete();
            }
            Db::name('summary')->where([['id','in',array_column($summary,'id')]])->delete();
        }
    }
    //轮询数据
    public function poll() {
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            Db::startTrans();
            try {
                $period=getPeriod();
                $info=Db::name('room_info')->where([['time','>',$period]])->page($input['page'],$input['limit'])->order(['time','id'])->field(['id'])->select()->toArray();
                $this->handle(array_column($info,'id'));
                
            	Db::commit();
            	$result=['state'=>'success'];
            } catch (\Exception $e) {
                dd($e);
            	Db::rollback();
            	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //收发记录
    public function handle($arr){
        if(empty($arr)) return;
        //查询记录
        $info = Db::name('room_info')->where([['id','in',$arr]])->order(['time','id'])->select()->toArray();
        //匹配单据
        $union=[];
        $tab=['buy'=>'buy','bre'=>'bre','sell'=>'sell','sre'=>'sre','swapOut'=>'swap','swapEnter'=>'swap','entry'=>'entry','extry'=>'extry'];
        foreach ($tab as $t=>$m) {
            $gather=search($info)->where([['type','=',$t]])->select();
            if(!empty($gather)){
                $union[]=Db::name($m.'_info')->where([['id','in',array_column($gather,'info')]])->fieldRaw('"'.$t.'" as mold,id,goods,attr,'.($t=='swapEnter'?'storehouse as warehouse':'warehouse').',batch,mfd,serial')->buildSql();
            }
        }
        //合并子查询
        $union=implode(' UNION ALL ',$union);
        $record=DB::query('SELECT * FROM ('.$union.') as nodcloud');
        //构造数据
        $summary=[];
        //exist结存|balance结余
        //[0,0,0,0]=[商品总仓|商品分仓|规则总仓|规则分仓]
        $def=['exist'=>[0,0,0,0],'balance'=>[0,0,0,0]];
        foreach ($info as $vo) {
            $row=search($record)->where([['mold','=',$vo['type']],['id','=',$vo['info']]])->find();
            $summary[]=[
                'pid'=>$vo['id'],
                'type'=>$vo['type'],
                'class'=>$vo['class'],
                'info'=>$vo['info'],
                'time'=>$vo['time'],
                'goods'=>$row['goods'],
                'attr'=>$row['attr'],
                'warehouse'=>$row['warehouse'],
                'batch'=>$row['batch'],
                'mfd'=>$row['mfd'],
                'serial'=>$row['serial'],
                'direction'=>$vo['direction'],
                'price'=>$vo['price'],
                'nums'=>$vo['nums'],
                'uct'=>0,
                'bct'=>0,
                'exist'=>json_encode($def['exist']),
                'balance'=>json_encode($def['balance']),
                'handle'=>0
            ];
        }
        Db::name('summary')->insertAll($summary);
        $summary=Db::name('summary')->where([['pid','in',array_column($summary,'pid')]])->order(['id'])->select()->toArray();
        //处理数据
        $fun=getSys('fun');
        $goods=Db::name('goods')->where([['id','in',array_column($summary,'goods')]])->field(['id','buy'])->select()->toArray();
        $attr=Db::name('attr')->where([['pid','in',array_column($summary,'goods')]])->field(['pid','name','buy'])->select()->toArray();
        foreach ($summary as $vo) {
            $sql=[
                [['id','<',$vo['id']],['goods','=',$vo['goods']]],
                [['warehouse','=',$vo['warehouse']]]
            ];
            //规则语句
            if($fun['rule']=='def'){
                $sql[]=[];
            }else if($fun['rule']=='attr'){
                $sql[]=[['attr','=',$vo['attr']]];
            }else if($fun['rule']=='batch'){
                $sql[]=[['batch','=',$vo['batch']],['mfd','=',$vo['mfd']]];
            }else{
                $sql[]=[['attr','=',$vo['attr']],['batch','=',$vo['batch']],['mfd','=',$vo['mfd']]];
            }
            //[商品总仓|商品分仓|规则总仓|规则分仓]
            $senten=[$sql[0],array_merge($sql[0],$sql[1]),array_merge($sql[0],$sql[2]),array_merge($sql[0],$sql[1],$sql[2])];
            $first=[];
            $first[]=Db::name('summary')->where($senten[0])->order(['id'=>'DESC'])->find();
            $first[]=Db::name('summary')->where($senten[1])->order(['id'=>'DESC'])->find();
            $first[]=Db::name('summary')->where($senten[2])->order(['id'=>'DESC'])->find();
            $first[]=Db::name('summary')->where($senten[3])->order(['id'=>'DESC'])->find();
            //默认值
            foreach ($first as $k=>$v) {
                if(empty($v)){
                    $first[$k]=$def;
                }else{
                    $first[$k]=['exist'=>json_decode($v['exist']),'balance'=>json_decode($v['balance'])];
                }
            }
            //数据处理
            $g=search($goods)->where([['id','=',$vo['goods']]])->find();
            $a=search($attr)->where([['pid','=',$vo['goods']],['name','=',$vo['attr']]])->find();
            $buy=empty($a)?$g['buy']:$a['buy'];
            //序列判断
            $serial=json_decode($vo['serial']);
            if(empty($serial)){
                //计价方法
                if($fun['valuation']=='base'){
                    //基础计价法
                    if(in_array($vo['type'],['buy','bre','swapOut','swapEnter','entry','extry'])){
                        $uct=$vo['price'];
                    }else{
                        $uct=$buy;
                    }
                }else if($fun['valuation']=='ma'){
                    //移动平均法
                    if(in_array($vo['type'],['buy','bre','swapOut','swapEnter','entry','extry'])){
                        $uct=$vo['price'];
                    }else{
                        //[空|负]库存取采购价
                        //正常库存取结余除结存
                        if(empty($fun['branch'])){
                            $uct=$first[2]['exist'][2]<=0?$buy:math()->chain($first[2]['balance'][2])->div($first[2]['exist'][2])->done();
                        }else{
                            $uct=$first[3]['exist'][3]<=0?$buy:math()->chain($first[3]['balance'][3])->div($first[3]['exist'][3])->round(2)->done();
                        }
                    }
                }else{
                    //先进先出法
                    if(in_array($vo['type'],['buy','swapEnter','entry'])){
                        $uct=$vo['price'];
                    }else if(in_array($vo['type'],['sre','vre'])){
                        $uct=$buy;
                    }else{
                        $where=[
                            ['id','<',$vo['id']],
                            ['goods','=',$vo['goods']],
                            ['attr','=',$vo['attr']],
                            ['batch','=',$vo['batch']],
                            ['mfd','=',$vo['mfd']],
                            ['direction','=',1],
                            [DB::raw('nums'),'<>',DB::raw('handle')]
                        ];
                        empty($fun['branch'])&&$where[]=['warehouse','=',$vo['warehouse']];
                        $build=DB::name('summary')->where($where)->fieldRaw('id,uct,(nums - handle) as usable,(@sum := @sum + (nums - handle)) as sum')->order('id')->buildSql(false);
                        $build=str_replace("WHERE","CROSS JOIN ( SELECT @sum := 0 ) t WHERE",$build);
                        $list=DB::query('SELECT * FROM ('.$build.') as nodcloud WHERE sum < '.$vo['nums'].' or (sum >= '.$vo['nums'].' and sum - usable < '.$vo['nums'].');');
                        if(empty($list)){
                            //[无入库]取采购价
                            $uct=$buy;
                        }else{
                            $uct=0;
                            $knot=$vo['nums'];
                            $relation=[];
                            foreach ($list as $v) {
                                if($knot<=$v['usable']){
                                    $relation[]=['id'=>$v['id'],'handle'=>$knot];
                                    $calc=math()->chain($knot)->mul($v['uct'])->done();
                                    $uct=math()->chain($uct)->add($calc)->done();
                                    break;
                                }else{
                                    $relation[]=['id'=>$v['id'],'handle'=>$v['usable']];
                                    $calc=math()->chain($v['usable'])->mul($v['uct'])->done();
                                    $uct=math()->chain($uct)->add($calc)->done();
                                    $knot=math()->chain($knot)->sub($v['usable'])->done();
                                }
                            }
                            $uct=math()->chain($uct)->div($vo['nums'])->done();
                            Db::name('summary')->duplicate(['handle'=>Db::raw('handle + VALUES(`handle`)')])->insertAll($relation);
                            $fifo=[];
                            foreach ($relation as $v) {
                                $fifo[]=['out'=>$vo['id'],'in'=>$v['id'],'handle'=>$v['handle']];
                            }
                            Db::name('fifo')->insertAll($fifo);
                        }
                    }
                }
            }else{
                //序列产品
                if(in_array($vo['type'],['buy','swapEnter','entry'])){
                    //[无入库]取采购价
                    $uct=$vo['price'];
                }else{
                    $uct=0;
                    foreach ($serial as $v) {
                        $row=DB::name('summary')->where([
                            ['id','<',$vo['id']],
                            ['goods','=',$vo['goods']],
                            ['serial','like','%"'.$v.'"%']
                        ])->field(['uct'])->order(['id'=>'DESC'])->find();
                        $uct=math()->chain($uct)->add(empty($row)?$buy:$row['uct'])->done();
                    }
                    $uct=math()->chain($uct)->div($vo['nums'])->done();
                }
                
                
            }
            //综合处理
            $uct=math()->chain($uct)->round($fun['digit']['money'])->done();
            $exist=[
            	empty($vo['direction'])?math()->chain($first[0]['exist'][0])->sub($vo['nums'])->done():math()->chain($first[0]['exist'][0])->add($vo['nums'])->done(),
            	empty($vo['direction'])?math()->chain($first[1]['exist'][1])->sub($vo['nums'])->done():math()->chain($first[1]['exist'][1])->add($vo['nums'])->done(),
            	empty($vo['direction'])?math()->chain($first[2]['exist'][2])->sub($vo['nums'])->done():math()->chain($first[2]['exist'][2])->add($vo['nums'])->done(),
            	empty($vo['direction'])?math()->chain($first[3]['exist'][3])->sub($vo['nums'])->done():math()->chain($first[3]['exist'][3])->add($vo['nums'])->done()
            ];
            $bct=math()->chain($uct)->mul($vo['nums'])->done();
            $balance=[
            	empty($vo['direction'])?math()->chain($first[0]['balance'][0])->sub($bct)->done():math()->chain($first[0]['balance'][0])->add($bct)->done(),
            	empty($vo['direction'])?math()->chain($first[1]['balance'][1])->sub($bct)->done():math()->chain($first[1]['balance'][1])->add($bct)->done(),
            	empty($vo['direction'])?math()->chain($first[2]['balance'][2])->sub($bct)->done():math()->chain($first[2]['balance'][2])->add($bct)->done(),
            	empty($vo['direction'])?math()->chain($first[3]['balance'][3])->sub($bct)->done():math()->chain($first[3]['balance'][3])->add($bct)->done()
            ];
            foreach ($exist as $k=>$v){
                $v==0&&$balance[$k]=0;
            }
            $exist=json_encode($exist);
            $balance=json_encode($balance);
            Db::name('summary')->where([['id','=',$vo['id']]])->update(['uct'=>$uct,'bct'=>$bct,'exist'=>$exist,'balance'=>$balance]);
        }
    }
}