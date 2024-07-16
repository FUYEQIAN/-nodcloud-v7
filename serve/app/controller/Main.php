<?php
namespace app\controller;
use app\controller\Acl;
use think\facade\Db;
use think\exception\ValidateException;
class Main extends Acl{
    //获取
    public function record(){
        //格子汇总
        $dayTime=strtotime(date('Y-m-d'));
        $group=[];
        foreach(['today','yesterday'] as $v){
            foreach(['sell','imy','ice'] as $t){
                $rowWhere=[['time','=',$v=='today'?$dayTime:$dayTime-86400],['examine','=',1]];
                if(in_array($t,['sell'])){
                    $group[$v][$t]=Db::name($t)->where(sqlAuth($t,$rowWhere))->fieldRaw('GROUP_CONCAT(`id`) as id,sum(actual) as actual')->select()->toArray()[0];
                }elseif($t=='imy'){
                    $group[$v][$t]=Db::name($t)->where(sqlAuth($t,$rowWhere))->fieldRaw('sum(total) as total')->select()->toArray()[0];
                }else{
                    $group[$v][$t]=Db::name($t)->where(sqlAuth($t,$rowWhere))->fieldRaw('sum(actual) as actual')->select()->toArray()[0];
                }
            }
        }
       
        $lattice['sve']=[
            'today'=>empty($group['today']['sell']['actual'])?0:floatval($group['today']['sell']['actual']),
            'yesterday'=>empty($group['yesterday']['sell']['actual'])?0:$group['yesterday']['sell']['actual'],
        ];
        $lattice['nos']=[
            'today'=>[
                'sell'=>empty($group['today']['sell']['id'])?[]:explode(',',$group['today']['sell']['id'])
            ],
            'yesterday'=>[
                'sell'=>empty($group['yesterday']['sell']['id'])?[]:explode(',',$group['yesterday']['sell']['id'])
            ]
        ];
        $lattice['spt']=[
            'today'=>Db::name('summary')->whereOr([
                [['type','=','sell'],['class','in',$lattice['nos']['today']['sell']]]
            ])->fieldRaw('sum(bct) as bct')->select()->toArray()[0]['bct'],
            'yesterday'=>Db::name('summary')->whereOr([
                [['type','=','sell'],['class','in',$lattice['nos']['yesterday']['sell']]]
            ])->fieldRaw('sum(bct) as bct')->select()->toArray()[0]['bct']
        ];
        $lattice['spt']=[
            'today'=>math()->chain($lattice['sve']['today'])->sub($lattice['spt']['today'])->done(),
            'yesterday'=>math()->chain($lattice['sve']['yesterday'])->sub($lattice['spt']['yesterday'])->done()
        ];
        $lattice['nos']=[
            'today'=>count(array_merge($lattice['nos']['today']['sell'])),
            'yesterday'=>count(array_merge($lattice['nos']['yesterday']['sell'])),
        ];
        $lattice['fund']=[
            'today'=>math()->chain($lattice['sve']['today'])->add($group['today']['imy']['total'])->add($group['today']['ice']['actual'])->done(),
            'yesterday'=>math()->chain($lattice['sve']['yesterday'])->add($group['yesterday']['imy']['total'])->add($group['yesterday']['ice']['actual'])->done(),
        ];
        $lattice['sve']['yesterday']=empty($lattice['sve']['yesterday'])?(empty($lattice['sve']['today'])?'0':'100'):math()->chain($lattice['sve']['today'])->div($lattice['sve']['yesterday'])->mul(100)->round(2)->done();
        $lattice['spt']['yesterday']=empty($lattice['spt']['yesterday'])?(empty($lattice['spt']['today'])?'0':'100'):math()->chain($lattice['spt']['today'])->div($lattice['spt']['yesterday'])->mul(100)->round(2)->done();
        $lattice['nos']['yesterday']=empty($lattice['nos']['yesterday'])?(empty($lattice['nos']['today'])?'0':'100'):math()->chain($lattice['nos']['today'])->div($lattice['nos']['yesterday'])->mul(100)->round(2)->done();
        $lattice['fund']['yesterday']=empty($lattice['fund']['yesterday'])?(empty($lattice['fund']['today'])?'0':'100'):math()->chain($lattice['fund']['today'])->div($lattice['fund']['yesterday'])->mul(100)->round(2)->done();


        
        //汇总信息
        $list=[
            'room'=>Db::name('room')->field(['id','nums'])->where(sqlAuth('room',[]))->select()->toArray(),
            'customer'=>Db::name('customer')->field(['id','balance'])->where(sqlAuth('customer',frameScope([])))->select()->toArray(),
            'supplier'=>Db::name('supplier')->field(['id','balance'])->where(sqlAuth('supplier',frameScope([])))->select()->toArray()
        ];
        $summary=[];
        $summary['goods']=Db::name('goods')->count();
        $summary['customer']=count($list['customer']);
        $summary['supplier']=count($list['supplier']);
        $summary['room']=mathArraySum(array_column($list['room'],'nums'));
        $summary['rwg'] = Db::name('room')->alias('room')->where([['id','in',array_column($list['room'],'id')]])->whereExists(
            Db::name('goods')->where([['id','=',Db::raw('room.goods')],['room.nums','<=',Db::raw('stock')]])->buildSql(false)
        )->count();
        $summary['bwg'] = Db::name('batch')->alias('batch')->where(sqlAuth('batch',[]))->whereExists(
            Db::name('goods')->where([['id','=',Db::raw('batch.goods')]])->whereRaw('batch.time + (threshold * 86400) < :time',['time'=>strtotime(date('Y-m-d',time()))])->buildSql(false)
        )->count();
        
        //资产数据
        $assets['account']=Db::name('account')->fieldRaw('(sum(initial)+sum(balance)) as money')->where(sqlAuth('account',frameScope([])))->select()[0]['money'];
        $sy=Db::name('summary')->fieldRaw('direction,sum(bct) as bct')->where(sqlAuth('summary'))->group(['direction'])->order('direction')->select()->toArray();
        $assets['rsy']=empty($sy)?0:(count($sy)==2?math()->chain($sy[1]['bct'])->sub($sy[0]['bct'])->done():($sy[0]['direction']==0?-$sy[0]['bct']:$sy[0]['bct']));
        $assets['cas']=mathArraySum(array_column($list['customer'],'balance'));
        $assets['sas']=mathArraySum(array_column($list['supplier'],'balance'));
        $assets['all']=math()->chain($assets['account'])->add($assets['rsy'])->add($assets['cas'])->sub($assets['sas'])->done();
        //位数处理
        foreach ($summary as $k=>$v){$summary[$k]=floatval($v);}
        foreach ($assets as $k=>$v){$assets[$k]=floatval($v);}
        
        //数据概括
        $fun=getSys('fun');
        $option=[];
        $deploy=[
            [  
                'title'=>['text'=>'','left'=>'center'],
                'xAxis'=>['type'=>'category','boundaryGap'=>false,'data'=>[]],
                'grid'=>['top'=>'12%','left'=>'1%','right'=>'1%','bottom'=>'0%','containLabel'=>true],
                'yAxis'=>['type'=>'value'],
                'series'=>[['data'=>[],'type'=>'line','areaStyle'=>[]]],
                'tooltip'=>['trigger'=>'axis','axisPointer'=>['type'=>'cross']]
            ],
            [
                'title'=>['text'=>'库存数据','left'=>'center'],
                'tooltip'=>['trigger'=>'item'],
                'legend'=>['orient'=>'vertical','left'=>'left'],
                'series'=>[['type'=>'pie','radius'=>'60%','data'=>[]]]
            ]
        ];
        $table=['buy'=>'采购单','bre'=>'采购退货单','sell'=>'销售单','sre'=>'销售退货单','imy'=>'收款单','omy'=>'付款单'];
        $where=[['time','>=',time()-($fun['days']*86400)],['time','<=',time()],['examine','=',1]];
        foreach($table as $k=>$v){
            $bill=Db::name($k)->fieldRaw('time,sum('.(in_array($k,['imy','omy'])?'total':'actual').') as actual')->where(sqlAuth($k,$where))->group('time')->select()->toArray();
            $xData=getOldDay($fun['days']);
            $yData=[];
            foreach($xData as $date){
                $t=strtotime($date);
                $find=search($bill)->where([['time','=',$t]])->find();
                $yData[]=empty($find)?0:$find['actual'];
            }
            $replica=$deploy[0];
            $replica['title']['text']=$v;
            $replica['xAxis']['data']=$xData;
            $replica['series'][0]['data']=$yData;
            $option[]=$replica;
        }
        //库存分布
        $pie=Db::name('room')->fieldRaw('warehouse,sum(nums) as nums')->where([['id','in',array_column($list['room'],'id')]])->group(['warehouse'])->select()->toArray();
        $wlt=Db::name('warehouse')->where([['id','in',array_column($pie,'warehouse')]])->select();
        $replica=$deploy[1];
        foreach($pie as $v){
            $w=search($wlt)->where([['id','=',$v['warehouse']]])->find();
            $replica['series']['0']['data'][]=['value'=>$v['nums'],'name'=>$w['name']];
        }
        $option[]=$replica;
        
        // 资金数据
        $fund=[
        	'xAxis'=>['type'=>'category','data'=>[]],
        	'grid'=>['top'=>'6%','left'=>'1%','right'=>'1%','bottom'=>'0%','containLabel'=>true],
        	'yAxis'=>['type'=>'value'],
        	'series'=>[['data'=>[],'type'=>'bar','itemStyle'=>(object)[]]],
        	'tooltip'=>['trigger'=>'axis','axisPointer'=>['type'=>'cross']]
        ];
        $fundData=Db::name('account')->fieldRaw('name,(initial+balance) as money')->where(sqlAuth('account',frameScope([])))->select()->toArray();
        foreach ($fundData as $v) {
            $fund['xAxis']['data'][]=$v['name'];
            $fund['series'][0]['data'][]=$v['money'];
        }
        if(empty($fund['xAxis']['data'])){
            $fund['xAxis']['data'][]='无数据';
            $fund['series'][0]['data'][]=0;
        }
        //负载监测
        $load=[];
        $cacheMaxSize=256;
        $load['cache']['size']=getDirSize(pathChange('runtime'));
        $load['cache']['rate']=round($load['cache']['size']*100/$cacheMaxSize,2);
        $mysqlMaxSize=256;
        $load['mysql']['size']=getMysqlSize();
        $load['mysql']['rate']=round($load['mysql']['size']*100/$mysqlMaxSize,2);
        
        //运行环境
        $run=[
            'os'=>PHP_OS,
            'soft'=>$_SERVER['SERVER_SOFTWARE'],
            'php'=>PHP_VERSION,
            'mysql'=>Db::query("select VERSION() as ver")[0]['ver'],
            'protocol'=>$_SERVER['SERVER_PROTOCOL']
        ];
        return json([
            'state'=>'success',
            'info'=>[
                'lattice'=>$lattice,
                'summary'=>$summary,
                'option'=>$option,
                'assets'=>$assets,
                'fund'=>$fund,
                'load'=>$load,
                'run'=>$run
            ]
        ]);
    }
}
