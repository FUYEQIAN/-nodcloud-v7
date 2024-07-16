<?php
namespace app\controller;
use app\controller\Acl;
use app\model\{Goods,Serial,Batch,Cost};
use think\facade\{Db,Filesystem};
use think\exception\ValidateException;
class Service extends Acl {
    //基础数据
    public function store() {
        $tree = new \org\Tree();
        //获取用户权限架构数据
        $userFrame = getUserAuth('frame');
        if($userFrame=='all'){
            $frame=$tree::hTree(Db::name('frame')->select()->toArray(),-1);
        }else{
            $frame=Db::name('frame')->where([['id','in',$userFrame]])->select()->toArray();
            //追加子数据
            foreach ($frame as $frameKey=>$frameVo) {
                $frame[$frameKey]['sub']=[];
            }
        }
        //获取用户权限菜单
        $menu = $tree::hTree(getRootMemu());
        //获取全局字段配置
        $fields = getFields();
        //获取用户权限数据
        $root = getUserRoot();
        //获取商品类别数据
        $category = $tree::hTree(Db::name('category')->order(['sort'=>'asc'])->select()->toArray(),-1);
        //获取仓库数据
        $warehouse=Db::name('warehouse')->where(sqlAuth('warehouse',[]))->field(['id','name'])->order(['id'=>'desc'])->select();
        //获取资金账户
        $account=Db::name('account')->where(sqlAuth('account',[]))->field(['id','name'])->order(['id'=>'desc'])->select();
        //获取收支类别
        $ietList=Db::name('iet')->order(['sort'=>'asc'])->select()->toArray();
        $iet=['in'=>search($ietList)->where([['type','=',0]])->select(),'out'=>search($ietList)->where([['type','=',1]])->select()];
        //获取常用功能
        $often = Db::name('often')->where([['user','=',getUserId()]])->field(['name','key'])->select();
        //获取系统参数
        $sys = getSys(['name','icp','notice','brand','unit','crCategory','crGrade','srCategory','fun','logistics']);
        //返回数据
        return json(['state' => 'success','info' => [
            'frame' => $frame,
            'menu' => $menu,
            'fields' => $fields,
            'root'=>$root,
            'category' => $category,
            'warehouse' => $warehouse,
            'account' => $account,
            'iet' => $iet,
            'often' => $often,
            'sys' => $sys
        ]]);
    }
    //获取|组织数据
    public function getFrame(){
        $cache=cache(getToken());
        $result = ['state' => 'success','info'=>isset($cache['frame'])?$cache['frame']:[]];
        return json($result);
    }
    //保存|组织数据
    public function saveFrame(){
        $input=input('post.');
        if(existFull($input,['parm']) || is_array($input['parm'])){
            $token=getToken();
            $cache=cache($token);
            $cache['frame']=$input['parm'];
            cache($token,$cache);
            $result = ['state' => 'success'];
        }else{
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //获取|商品列表
    public function goodsRecord(){
        $input = input('post.');
        if (existFull($input,['page','limit'])) {
            $sql=fastSql($input,[
                [['mate'=>'name|py|number|spec'],'fullLike'],
                ['code','fullEq'],
                ['brand','fullEq'],
                ['type','fullDec1'],
                ['data','fullLike']
            ]);
            //辅助属性扩展查询
            $sqlOr=existFull($input,['code'])?[['id','in',array_column(Db::name('attr')->where([['code','=',$input['code']]])->select()->toArray(),'pid')]]:[];
            //商品分类树结构查询
            existFull($input,['category'])&&$sql[]=['category','in',findTreeArr('category',$input['category'],'id')];
            //获取总条数
            $count = Goods::where($sql)->whereOr($sqlOr)->count();
            //查询分页数据
            $info = Goods::with(['categoryData','attr'])->where($sql)->whereOr($sqlOr)->append(['extension'])->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
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
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
            //返回数据
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //商品|扫码接口
    public function goodsScan(){
        $input = input('post.');
        if (existFull($input,['code'])) {
            $sql=[['code','=',$input['code']]];
            $sqlOr=[['id','in',array_column(Db::name('attr')->where([['code','=',$input['code']]])->select()->toArray(),'pid')]];
            //查询数据
            $info = Goods::with(['attr'])->where($sql)->whereOr($sqlOr)->order(['id'=>'desc'])->select()->toArray();
            //处理|辅助属性条形码
            foreach ($info as $infoKey=>$infoVo) {
                //匹配|有辅助属性|主条形码不同
                if(!empty($infoVo['attr']) && $infoVo['code']!=$input['code']){
                    foreach ($infoVo['attr'] as $attrKey=>$attrVo) {
                        if($attrVo['code']!=$input['code']){
                            unset($info[$infoKey]['attr'][$attrKey]);
                        }
                    }
                    //重建索引
                    $info[$infoKey]['attr']=array_values($info[$infoKey]['attr']);
                }
            }
            $result = [
                'state' => 'success',
                'info' => $info
            ];
            //返回数据
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //获取|库存信息
    public function goodsDepot(){
        $input = input('post.');
        if (existFull($input,['page','limit','goods']) && isset($input['attr'])){
            //查询数据
            $warehouseSql=sqlAuth('warehouse',[]);
            $count=Db::name('warehouse')->where($warehouseSql)->count();
            $warehouse=Db::name('warehouse')->where($warehouseSql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //匹配数据
            $room=Db::name('room')->where([['warehouse','in',array_column($warehouse,'id')],['goods','=',$input['goods']],['attr','=',$input['attr']]])->select()->toArray();
            //构造数据
            $info=[];
            foreach ($warehouse as $warehouseVo) {
                $item=['warehouse'=>$warehouseVo['id']];
                $item['name']=$warehouseVo['name'];
                //仓储查询
                $roomFind=search($room)->where([['warehouse','=',$warehouseVo['id']]])->find();
                //记录赋值
                $item['nums']=empty($roomFind)?0:floatval($roomFind['nums']);
                //记录转存
                $info[]=$item;
            }
            //数据处理|单位转换
            $goods=Db::name('goods')->where([['id','=',$input['goods']]])->find();
            if($goods['unit']=='-1'){
                foreach ($info as $infoKey=>$infoVo) {
                    $info[$infoKey]['nums']=unitSwitch($infoVo['nums'],json_decode($goods['units'],true));
                }
            }
            //返回数据
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //商品|最近价格
    public function recentPrice(){
        $input = input('post.');
        if(existFull($input,['model','source','goods']) && in_array($input['model'],['bor','buy','bre','sor','sell','sre']) && isset($input['attr']) && isset($input['unit'])){
            $model=$input['model'];
            //构造CLASS条件
            $sql=[['examine','=',1]];
            //场景匹配
            in_array($model,['bor','buy','bre'])&&$sql[]=['supplier','=',$input['source']];//供应商
            in_array($model,['sor','sell','sre'])&&$sql[]=['customer','=',$input['source']];//客户
            //查询CLASS数据
            $sql=sqlAuth($model,$sql);//数据鉴权
            $class=Db::name($model)->where($sql)->field(['id'])->order(['id'=>'desc'])->select()->toArray();
            if(empty($class)){
                $result = ['state' => 'success','info' => 0];
            }else{
                //查询INFO数据
                $parm=[
                    ['pid','in',array_column($class,'id')],
                    ['goods','=',$input['goods']],
                    ['attr','=',$input['attr']],
                    ['unit','=',$input['unit']]
                ];
                $info=Db::name($model.'_info')->where($parm)->order(['pid'=>'desc'])->find();
                if(empty($info)){
                    $result = ['state' => 'success','info' => 0];
                }else{
                    $result = ['state' => 'success','info' => floatval($info['price'])];
                }
            }
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //商品序列号
    public function getSerial(){
        $input = input('post.');
        if(existFull($input,['page','limit','warehouse','goods']) && isset($input['attr']) && isset($input['batch']) && isset($input['mfd']) && isset($input['state'])){
            $room=Db::name('room')->where([['warehouse','=',$input['warehouse']],['goods','=',$input['goods']],['attr','=',$input['attr']]])->find();
            if(empty($room)){
                $result = ['state' => 'success','count' => 0,'info' => []];
            }else{
                if(empty($input['batch'])){
                    $batch=['id'=>0];
                }else{
                    $batch=Db::name('batch')->where([['room','=',$room['id']],['number','=',$input['batch']],['time','=',empty($input['mfd'])?0:strtotime($input['mfd'])]])->find();
                    if(empty($batch)){
                        return json(['state' => 'success','count' => 0,'info' => []]);
                        exit;
                    }
                }
                $count=Serial::where([['room','=',$room['id']],['batch','=',$batch['id']],['state','=',$input['state']]])->count();
                $info=Serial::where([['room','=',$room['id']],['batch','=',$batch['id']],['state','=',$input['state']]])->append(['extension'])->page($input['page'],$input['limit'])->order(['id'=>'asc'])->select();
                $result = ['state' => 'success','count' => $count,'info' => $info];
            }
        }else{
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //商品批次号
    public function getBatch(){
        $input = input('post.');
        if(existFull($input,['page','limit','warehouse','goods']) && isset($input['attr'])){
            //匹配仓储
            $room=Db::name('room')->where([['warehouse','=',$input['warehouse']],['goods','=',$input['goods']],['attr','=',$input['attr']]])->find();
            if(empty($room)){
                $result = ['state' => 'success','count' => 0,'info' => []];
            }else{
                //匹配批次号
                $sql=fastSql($input,[
                    ['number','fullLike'],
                    [['startTime'=>'time'],'startTime'],
                    [['endTime'=>'time'],'endTime']
                ]);//构造SQL
                $sql[]=['room','=',$room['id']];
                $count=Batch::where($sql)->count();
                $info=Batch::where($sql)->page($input['page'],$input['limit'])->order(['id'=>'asc'])->select()->toArray();
                //数据处理|单位转换
                $goods=Db::name('goods')->where([['id','=',$input['goods']]])->find();
                if($goods['unit']=='-1'){
                    foreach ($info as $infoKey=>$infoVo) {
                        $info[$infoKey]['nums']=unitSwitch($infoVo['nums'],json_decode($goods['units'],true));
                    }
                }
                $result = ['state' => 'success','count' => $count,'info' => $info];
            }
        }else{
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //单据列表
    public function billRecord(){
        $input = input('post.');
        if (existFull($input,['page','limit','mold']) && in_array($input['mold'],['imy','omy','buy','bre','sell','sre','ice','oce'])) {
            if(in_array($input['mold'],['omy','buy','bre','oce'])){
                $sql=fastSql($input,[
                    ['supplier','fullEq'],
                    ['number','fullLike'],
                    [['startTime'=>'time'],'startTime'],
                    [['endTime'=>'time'],'endTime'],
                    ['user','fullEq'],
                    ['data','fullLike']
                ]);
            }else{
                $sql=fastSql($input,[
                    ['customer','fullEq'],
                    ['number','fullLike'],
                    [['startTime'=>'time'],'startTime'],
                    [['endTime'=>'time'],'endTime'],
                    ['user','fullEq'],
                    ['data','fullLike']
                ]);
            }
            $sql[]=['examine','=',1];
            $sql[]=existFull($input,['nucleus'])?['nucleus','=',$input['nucleus']-1]:['nucleus','in',[0,1]];
            $sql=sqlAuth($input['mold'],$sql);//数据鉴权
            $table=['imy'=>'\app\model\Imy','omy'=>'\app\model\Omy','buy'=>'\app\model\Buy','bre'=>'\app\model\Bre','sell'=>'\app\model\Sell','sre'=>'\app\model\Sre','ice'=>'\app\model\Ice','oce'=>'\app\model\Oce'];
            $count = $table[$input['mold']]::where($sql)->count();//获取总条数
            $info = $table[$input['mold']]::with(['frameData','userData'])->where($sql)->append(['extension'])->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();//查询分页数据
            //数据处理
            foreach ($info as $key=>$vo) {
                in_array($input['mold'],['buy','bre','sell','sre','ice','oce'])&&$info[$key]['total']=$vo['actual'];
            }
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
            //返回数据
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //匹配|下拉接口
    public function getScene() {
        $input = input('post.');
        if (existFull($input,['id','scene'])) {
            $find=Db::name($input['scene'])->where([['id','=',$input['id']]])->find();
            $result=['state' => 'success','info' => $find];
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //用户角色|下拉接口
    public function roleRecord() {
        $input = input('post.');
        if (existFull($input,['page','limit'])) {
            //构造SQL
            $sql = fastSql($input,[
                [['query'=>'name|py'],'fullLike']
            ]);
            $count = Db::name('role')->where($sql)->count();
            //获取总条数
            $info = Db::name('role')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //查询分页数据
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
            //返回数据
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //用户数据|下拉接口
    public function userRecord() {
        $input = input('post.');
        if (existFull($input,['page','limit'])) {
            //构造SQL
            $sql = fastSql($input,[
                [['query'=>'name|py'],'fullLike']
            ]);
            isset($input['noAuth']) || ($sql = sqlAuth('user',$sql));
            //数据鉴权
            $count = Db::name('user')->where($sql)->count();
            //获取总条数
            $info = Db::name('user')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //查询分页数据
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
            //返回数据
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //人员数据|下拉接口
    public function peopleRecord() {
        $input = input('post.');
        if (existFull($input,['page','limit'])) {
            //构造SQL
            $sql = fastSql($input,[
                [['query'=>'name|py'],'fullLike']
            ]);
            isset($input['noAuth']) || ($sql = sqlAuth('people',$sql));
            //数据鉴权
            $count = Db::name('people')->where($sql)->count();
            //获取总条数
            $info = Db::name('people')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //查询分页数据
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
            //返回数据
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //仓库数据|下拉接口
    public function warehouseRecord() {
        $input = input('post.');
        if (existFull($input,['page','limit'])) {
            //构造SQL
            $sql = fastSql($input,[
                [['query'=>'name|py'],'fullLike']
            ]);
            $sql = sqlAuth('warehouse',$sql);//数据鉴权
            $count = Db::name('warehouse')->where($sql)->count();
            //获取总条数
            $info = Db::name('warehouse')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //查询分页数据
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
            //返回数据
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //供应商数据|下拉接口
    public function supplierRecord() {
        $input = input('post.');
        if (existFull($input,['page','limit'])) {
            //构造SQL
            $sql = fastSql($input,[
                [['query'=>'name|py'],'fullLike']
            ]);
            $sql = sqlAuth('supplier',$sql);//数据鉴权
            $count = Db::name('supplier')->where($sql)->count();
            //获取总条数
            $info = Db::name('supplier')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //查询分页数据
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
            //返回数据
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //客户数据|下拉接口
    public function customerRecord() {
        $input = input('post.');
        if (existFull($input,['page','limit'])) {
            //构造SQL
            $sql = fastSql($input,[
                [['query'=>'name|py|contacts'],'fullLike']
            ]);
            $sql = sqlAuth('customer',$sql);//数据鉴权
            $count = Db::name('customer')->where($sql)->count();
            //获取总条数
            $info = Db::name('customer')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //查询分页数据
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
            //返回数据
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //资金账户|下拉接口
    public function accountRecord() {
        $input = input('post.');
        if (existFull($input,['page','limit'])) {
            //构造SQL
            $sql = fastSql($input,[
                [['query'=>'name'],'fullLike']
            ]);
            $sql = sqlAuth('account',$sql);//数据鉴权
            $count = Db::name('account')->where($sql)->count();
            //获取总条数
            $info = Db::name('account')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //查询分页数据
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //收支类别|下拉接口
    public function ietRecord() {
        $input = input('post.');
        if (existFull($input,['page','limit'])) {
            //构造SQL
            $sql = fastSql($input,[
                [['query'=>'name'],'fullLike']
            ]);
            $count = Db::name('iet')->where($sql)->count();
            //获取总条数
            $info = Db::name('iet')->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select()->toArray();
            //查询分页数据
            $result = [
                'state' => 'success',
                'count' => $count,
                'info' => $info
            ];
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //费用详情|数据接口
    public function getCost() {
        $input = input('post.');
        if (existFull($input,['cost'])) {
            $info=Cost::with(['sourceData','ietData'])->where([['id','=',$input['cost']]])->append(['extension'])->find()->toArray();
            $info['uat']=math()->chain($info['money'])->sub($info['settle'])->done();
            $result = ['state' => 'success','info' => $info];
        } else {
            $result = ['state' => 'error','info' => '传入参数不完整!'];
        }
        return json($result);
    }
    //零售配置
    public function getDeploy(){
        $deploy=getFrameDeploy();
        if(!empty($deploy)){
            //安全处理-隐藏接口配置
            $deploy['wechat']=[
                'enable'=>$deploy['wechat']['enable'],
                'account'=>$deploy['wechat']['account']
            ];
            $deploy['ali']=[
                'enable'=>$deploy['ali']['enable'],
                'account'=>$deploy['ali']['account']
            ];
        }
        return json(['state'=>'success','info'=>$deploy]);
    }
    //扩展字段文件上传
    public function fieldUpload() {
        $file = request()->file('file');
        //获取上传文件
        if (empty($file)) {
            $result = ['state' => 'error','info' => '传入数据不完整!'];
        } else {
            //文件限制5MB
            try{
                validate(['file'=>['fileSize'=>5*1024*1024,'fileExt'=>'png,gif,jpg,jpeg,txt,doc,docx,rtf,xls,xlsx,ppt,pptx,pdf,zip,rar']])->check(['file'=>$file]);
                $fileInfo=Filesystem::disk('upload')->putFile('field', $file, 'uniqid');
                $filePath=request()->domain().'/static/upload/'.$fileInfo;
                $result=['state'=>'success','info'=>$filePath];
            }catch(ValidateException $e) {
                $result=['state' => 'error','info' => $e->getMessage()];
            }
        }
        return json($result);
    }
    //编辑器图像上传
    public function editorUpload(){
        $files=request()->file('images');//获取上传文件
        if(empty($files)){
		    $result=['state'=>'error','info'=>'传入数据不完整!'];
		}else{
            //文件限制2MB
            foreach ($files as $file) {
                try{
                    validate(['file'=>['fileSize'=>2*1024*1024,'fileExt'=>'png,gif,jpg,jpeg']])->check(['file'=>$file]);
                }catch(ValidateException $e) {
                    return json(['state'=>'error','info'=>$e->getMessage()]);
                    exit;
                }
            }
            foreach ($files as $file) {
                $fileInfo=Filesystem::disk('upload')->putFile('editor', $file, 'uniqid');
                $filePath=request()->domain().'/static/upload/'.$fileInfo;
                $data[]=$filePath;
            }
            $result=['state'=>'success','info'=>$data];
		}
		return json($result);
    }
	//获取版本信息
	public function getUpgrade(){
		$ask = json_decode(curl('https://www.nodcloud.com/api/service/version',false,['product' => config('soft.product'),'edition' => config('soft.edition'),'version' => config('soft.version')],'GET'),true);
		if(empty($ask)){
		    $resule = ['state' => 'success','info'=>['ver'=>config('soft.version'),'new'=>config('soft.version'),'url'=>'']];
		}else{
		    if($ask['state']=='success'){
		        $resule = ['state' => 'success','info'=>['ver'=>config('soft.version'),'new'=>$ask['info']['version'],'url'=>$ask['info']['url']]];
    		}elseif($ask['state']=='warning'){
    			$resule = ['state' => 'error','info' => $ask['message']];
    		}else{
    			$resule = ['state' => 'error','info' => '版本服务系统异常!'];
    		}
		}
		return json($resule);
	}
    //清空缓存文件
    public function clachCache(){
        delCache();
        delDir('runtime.log');
        delDir('runtime.session');
        return json(['state'=>'success']);
    }
    //退出
    public function out(){
        pushLog('退出登录');
        cache(getToken(),NULL);
        return json(['state' => 'success']);
    }
}