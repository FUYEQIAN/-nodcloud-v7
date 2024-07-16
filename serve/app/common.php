<?php
//产生随机令牌
function token(){
	$token='';
	$n='qwertyuioplkjhgfdsazxcvbnm1234567890QWERTYUIOPASDFGHJKLZXCVBNM';
	for ($i=0;$i<32;$i++){
		$token.=$n[mt_rand(0,strlen($n)-1)];
	}
	return $token;
}
//获取访问凭证
function getToken(){
    $header=request()->header('token');
    $parm=input('get.token');
    if(empty($header) && empty($parm)){
        $token='';
    }else if(!empty($header)){
        $token=$header;
    }else{
        $token=$parm;
    }
    return $token;
}
//获取登陆状态
function checkLogin() {
    $token=getToken();
    $cache=cache($token);
    if($cache==null){
        return false;
    }else{
        //重置缓存有效期
        cacheReset($token);
        return true;
    }
}
//重置缓存有效期
function cacheReset($key){
    $config=config();
    if($config['cache']['default']=='file'){
        //文件缓存
        $file=cache()->getCacheKey($key);
        touch($file);
        
    }
    return true;
}
//获取当前用户ID
function getUserID(){
    $cache=cache(getToken());
    if(empty($cache)){
        die('[ ERROR ] 获取用户失败!');
    }else{
        return $cache['user'];
    }
}
//判断字段存在且不为空
function existFull($arr,$keys){
    $state=true;
    foreach ($keys as $key) {
        if(!isset($arr[$key]) || empty($arr[$key]) || $arr[$key] === 'null'){
            $state=false;
            break;
        }
    }
    return $state;
} 
//扩展数据验证
function verify($data,$rule,$info=false){
    $validate = \think\facade\Validate::rule($rule);
    if($validate->check($data)){
        return true;
    }else{
        return $info?$validate->getError():false;
    }
}
//汉字转拼音
//$type[head:首字母|all:全拼音]
function zhToPy($text,$type='head'){
    $nod=new \org\ZhToPy();
    $result=$nod::encode($text,$type);
    return strtolower($result);//返回结果转小写
}
//寻找数组多层键名
//$source:键名数组 OR "键名1|键名2"
//如查找过程键名不存在返回空
function arraySeek($array,$source){
    $recode=$array;
    is_array($source)||($source=explode('|',$source));
    foreach ($source as $sourceVo) {
        if(is_array($recode) && isset($recode[$sourceVo])){
            $recode=$recode[$sourceVo];
        }else{
            $recode='';
            break;
        }
	}
	return $recode;
}
//数组搜索
function search($arr){
    $search=new \org\Search($arr);
    return $search;
}
//判断是否JSON数据
function isJson($string) {
    json_decode($string);
    return(json_last_error()==JSON_ERROR_NONE);
}
//返回数据子元素递归数据
function findSubData($arr,$id){
    $data=[];
    $search=search($arr,[['pid','=',$id]])->select();
    foreach ($search as $searchVo) {
        $subSearch=search($arr,[['pid','=',$searchVo['id']]])->select();
        $data[]=$searchVo;
        if(!empty($subSearch)){
            $data=array_merge($data,findSubData($arr,$searchVo['id']));//合并数据
        }
    }
    return $data;
}
//过滤XSS
function htmlpurifier($html) {
    $config = HTMLPurifier_Config::createDefault();
    $config->set('CSS.AllowTricky', true);
    $purifier = new \HTMLPurifier($config);
    $clean_html = $purifier->purify($html);
    return $clean_html;
}
//获取xlsx文件数据
function getXlsx($file){
	$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx')->setReadDataOnly(TRUE)->load($file)->getSheet(0)->toArray (null,false,false,true);
	//NULL转空白字符|拦截XSS
	array_walk_recursive($reader,function(&$vo){$vo=$vo===null?'':htmlpurifier($vo);});
	return $reader;
}
//路径转换
//$path='skin.upload.xlsx'
function pathChange($path=false){
    return $path==false?root_path():root_path().str_replace(".",DIRECTORY_SEPARATOR,$path).DIRECTORY_SEPARATOR;
}
//删除过期文件
//$path='skin.upload.xlsx'
//默认过期时间30秒
function delOverdueFile($path,$time=30){
    clearstatcache();//清除文件状态缓存 
	$path=pathChange($path);//路径转换
	$files=scandir($path);//获取文件目录
	$nowTime=time();//当前时间
	foreach ($files as $key=>$vo){
		if(substr($vo,0,1)!='.'){
			$filePath=$path.$vo;//文件路径
			if ($nowTime-filectime($filePath)>$time){
				@unlink($filePath);
			}
		}
	}
}
//删除目录|递归删除
function delDir($path){
    $path=pathChange($path);
    if(file_exists($path)){
        $list=listFile($path);
        //删除过期缓存
        foreach ($list['files'] as $file) {
            @unlink($file);
        }
        //删除空目录
        foreach ($list['dirs'] as $dir) {
            @rmdir($dir);
        }
    }
    
}
//删除目录|递归删除
function delCache(){
    $cache=pathChange('runtime.cache');
    if(file_exists($cache)){
        $list=listFile($cache);
        //删除过期缓存
        foreach ($list['files'] as $file) {
            $content = @file_get_contents($file);
            if($content!==false){
                $expire = (int)substr($content, 8, 12);
                if ($expire!=0 && time() - $expire > filemtime($file)){
                    @unlink($file);
                }
            }
        }
        //删除空目录
        foreach ($list['dirs'] as $dir) {
            @rmdir($dir);
        }
    }
}

//计算多维数组最多数组数量
function calArrMaxCount($arr,$max=0){
    //对多维数组进行循环
    foreach ($arr as $vo) {
        if(is_array($vo)){
            $count=count($vo);
            //判断是否多维数组
            if ($count==count($vo,1)) {
                $count > $max&&($max=$count);
            }else{
                $max=CalArrMaxCount($vo,$max);
            }
        }
    }
    return $max;
}
//生成EXCEL
function buildExcel($fileName,$data,$down=true){
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $cellName=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];//列标识
    $shell=$spreadsheet->getActiveSheet(0);//工作簿
	$shell->setTitle('NODCLOUD.COM');//工作簿名称
    $shell->getDefaultColumnDimension()->setWidth(20);//设置默认行宽
    $shell->getDefaultRowDimension()->setRowHeight(16);//设置默认行高
    //循环加入数据
    $rowNums=1;//初始化行数
    $maxCell=calArrMaxCount($data);//获取多维数组最多数组数量
    //循环增加数据
    foreach ($data as $dataVo) {
        //判断数据类型
        if($dataVo['type']=='title'){
            //标题行
            $cellNums=0;//初始化列数
            $shell->mergeCells($cellName[$cellNums].$rowNums.':'.$cellName[$maxCell-1].$rowNums);//合并单元格
            $shell->setCellValue($cellName[$cellNums].$rowNums,$dataVo['info'])->getStyle ($cellName[$cellNums].$rowNums)->applyFromArray ([
                'font'=>['bold'=>true,'size'=>12],
                'alignment'=>['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ]);//设置内容|居中|粗体|12号
            $shell->getRowDimension($rowNums)->setRowHeight(26);//设置行高
            $rowNums++;//自增行数
        }elseif($dataVo['type']=='node'){
            //节点行
            $cellNums=0;//初始化列数
            $shell->getStyle($cellName[$cellNums].$rowNums.':'.$cellName[$maxCell-1].$rowNums)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('e7e6e6');//设置背景颜色
            foreach ($dataVo['info'] as $data_info) {
                $shell->setCellValue ($cellName[$cellNums].$rowNums,$data_info);
                $cellNums++;//自增列数
            }
			$shell->getRowDimension($rowNums)->setRowHeight(16);//设置行高
            $rowNums++;//自增行数
        }elseif($dataVo['type']=='table'){
            //表格数据
            //处理表头数据
            $cellNums=0;//初始化列数
            foreach ($dataVo['info']['thead'] as $theadVo) {
                $shell->setCellValue($cellName[$cellNums].$rowNums,$theadVo)->getStyle($cellName[$cellNums].$rowNums)->applyFromArray ([
                    'font'=>['bold'=>true],
                    'alignment'=>['horizontal'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                ]);//设置内容|居中|粗体;
                $cellNums++;//自增列数
            }
			$shell->getRowDimension($rowNums)->setRowHeight(16);//设置标题行高
            $rowNums++;//自增行数
            //处理表格数据
            foreach ($dataVo['info']['tbody'] as $tbodyVo) {
                $cellNums=0;//初始化列数
                $RowHeight=16;
                foreach ($tbodyVo as $tbodyVal) {
                    if(is_array($tbodyVal)){
                        if($tbodyVal['type']=='img'){
                            //图像
                            $drawing=new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                            $drawing->setPath($tbodyVal['info']);//设置图像路径
                            $drawing->setOffsetX(22);//设置X偏移距离
							$drawing->setOffsetY(3);//设置Y偏移距离
							$drawing->setWidth(100);//设置图像宽度
							$drawing->setCoordinates($cellName[$cellNums].$rowNums)->setWorksheet($shell);//设置内容
							$imgInfo=getimagesize($tbodyVal['info']);//读取图像信息
							$ImgHeight=($imgInfo[1]/($imgInfo[0]/100))*0.75;//计算行高|按照宽度缩放比例缩放
							$RowHeight=$ImgHeight+4.5;//增加Y偏移1.5倍关系
                        }
                    }else{
                        $shell->setCellValueExplicit($cellName[$cellNums].$rowNums,$tbodyVal,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);//设置内容并指定文本格式
                        $shell->getStyle($cellName[$cellNums].$rowNums)->getAlignment()->setWrapText(true);//设置多行文本
                    }
                    $cellNums++;//自增列数
                }
				$shell->getRowDimension($rowNums)->setRowHeight($RowHeight);//设置数据行高
                $rowNums++;//自增行数
            }
        }
	}
	//设置边框
	$shell->getStyle('A1:'.$cellName[$maxCell-1].($rowNums-1))->applyFromArray (['borders'=>['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],'alignment'=>['vertical'=>\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]]);
	//输出文件
	ob_get_contents()&&(ob_end_clean());//清除缓冲区,避免乱码
    $writer=\PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet,'Xlsx');
    if($down){
        header('Content-type:application/vnd.ms-excel');
        header("Content-Disposition:attachment;filename=$fileName.xlsx");
        $writer->save('php://output');
        exit;
    }else{
        delOverdueFile('static.file.xlsx');//删除过期文件
        $filePath=pathChange('static.file.xlsx').$fileName.'.xlsx';
        $writer->save($filePath);
        return $filePath;
    }
}
//生成压缩文件
function buildZip($fileName,$files,$down=true){
    delOverdueFile('static.file.zip');//删除过期文件
    empty($files)&&(die('[ 文件数据为空 ]'));//空数据检验
    $filePath=pathChange('static.file.zip').$fileName.'.zip';
    $zip=new ZipArchive();
    if ($zip->open($filePath,ZIPARCHIVE::CREATE)!==TRUE) {
        exit('创建压缩文件失败!');
    }
    foreach ($files as $file) {
        $zip->addFile($file,basename($file));
    }
    $zip->close();
    if($down){
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename='.basename($filePath)); //文件名
        header("Content-Type: application/zip"); //zip格式的
        header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
        header('Content-Length: '.filesize($filePath)); //告诉浏览器，文件大小
        @readfile($filePath);//输出文件;
        exit;
    }else{
        return $filePath;
    }
}
//curl请求
function curl($url,$header,$data,$method='POST'){
    $ch=curl_init();
	if($method == 'GET'){
        $url = $url.'?'.http_build_query($data);
    }
	if($method == 'POST'){
        curl_setopt($ch, CURLOPT_POST,TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    }
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch,CURLOPT_HEADER,FALSE);
	if($header!=false){
		curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
	}
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
    $result=curl_exec($ch);
    curl_close($ch);
    return $result;
}
//获取文件夹大小  
function getDirSize($dir){
    static $sizeResult = 0;
    $handle = opendir($dir);
    while (false!==($FolderOrFile = readdir($handle)))  {   
        if($FolderOrFile != "." && $FolderOrFile != "..")   {   
            if(is_dir("$dir/$FolderOrFile")){   
                $sizeResult += getDirSize("$dir/$FolderOrFile");   
            }else{   
                $sizeResult += filesize("$dir/$FolderOrFile");   
            }  
        }      
    }  
    closedir($handle);  
    return round(($sizeResult/1048576),2);
}
//获取数据库大小
function getMysqlSize(){
    $dbInfo=config('database.connections.mysql');
    $tabs = think\facade\Db::query("SHOW TABLE STATUS FROM `".$dbInfo['database']."`");
    $size = 0;
    foreach($tabs as $vo) {
        $size += $vo["Data_length"] + $vo["Index_length"];
    }
    //转换为M
    return round(($size/1048576),2);
}

//生成条形码
//$type[true:直接输出|false:保存文件]
function  txm($text,$type=true){
    delOverdueFile('static.code.txm');//删除过期文件
	$font = new BarcodeBakery\Common\BCGFontFile(pathChange('static.code.font').'Arial.ttf', 18);
    $colorFront = new BarcodeBakery\Common\BCGColor(0, 0, 0);
    $colorBack = new BarcodeBakery\Common\BCGColor(255, 255, 255);
    $code = new BarcodeBakery\Barcode\BCGcode128();
    $code->setScale(2);
    $code->setThickness(30);
    $code->setForegroundColor($colorFront);
    $code->setBackgroundColor($colorBack);
    $code->setFont($font);
    $code->setStart(null);
    $code->setTilde(true);
    $code->parse($text);
    if ($type){
        header('Content-Type: image/png');
        $drawing = new BarcodeBakery\Common\BCGDrawing('',$colorBack);
        $drawing->setBarcode($code);
        $drawing->draw();
        $drawing->finish(BarcodeBakery\Common\BCGDrawing::IMG_FORMAT_PNG);
        exit;
	}else {
	    $filePath=pathChange('static.code.txm').uniqid().'.png';
		$drawing = new BarcodeBakery\Common\BCGDrawing($filePath, $colorBack);
        $drawing->setBarcode($code);
        $drawing->draw();
        $drawing->finish(BarcodeBakery\Common\BCGDrawing::IMG_FORMAT_PNG);
		return $filePath;
	}
}
//生成二维码
//$type[true:直接输出|false:返回文件地址]
function ewm($text,$type=true){
    delOverdueFile('static.code.ewm');//删除过期文件
    $qrCode = new Endroid\QrCode\QrCode($text);
    if($type){
        header('Content-Type: '.$qrCode->getContentType());
        echo $qrCode->writeString();
        exit;
    }else{
        $filePath=pathChange('static.code.ewm').uniqid().'.png';
        $qrCode->writeFile($filePath);
        return $filePath;
    }
}

//高精度运算
function math($digit=6){
    $math=new \org\Math($digit);
    return $math;
}
//数组求和
function mathArraySum($arr){
    $sum=0;
    foreach ($arr as $vo) {
        $sum=math()->chain($sum)->add($vo)->done();
    }
    return $sum;
}
//四舍五入处理位数
function roundFloat($number,$digit){
    return floatval(round($number,$digit));
}

//MD5|16位
function md5_16($str){
    return substr(md5($str),8,16);
}
//计算分页
function PageCalc($page,$limit,$type='array'){
    $state=$limit*($page-1);
    $end=$limit;
    return $type=='array'?[$state,$end]:$state.','.$end;
}
//二维数组指定字段去重
function assoc_unique($arr,$key){
    $record=[];
    foreach($arr as $k => $v) {
        if(in_array($v[$key],$record)){
            unset($arr[$k]);
        }else{
            $record[]=$v[$key];
        }
    }
    sort($arr);
    return $arr;
}
//数据排序
function arraySort(&$arr,$field,$sort=SORT_ASC){
    $fields = [];
    foreach ($arr as $vo) {
        $fields[] = $vo[$field];
    }
    array_multisort($fields,$sort,$arr);
}
//数组包含数组
function arrayInArray($arr1,$arr2){
    foreach ($arr1 as $vo) {
        if(!in_array($vo,$arr2)){
            return false;
        }
    }
    return true;
}
//多重提取数组列
function arrayColumns($arr,$fields){
    foreach ($fields as $field) {
        $arr=array_column($arr,$field);
    }
    return $arr;
}
//获取路径的文件夹和文件
function listFile($path,$state=true){
    static $record=['dirs'=>[],'files'=>[]];
    $state&&$record=['dirs'=>[],'files'=>[]];
    foreach (new \FilesystemIterator($path) as $item) {
        if ($item->isDir() && !$item->isLink()) {
            $record['dirs'][]=$item->getPathname();
            listFile($item->getPathname(),false);
        } else {
            $record['files'][]=$item->getPathname();
        }
    }
    return $record;
}
//判断数组中指定键是否为数组
function is_arrays($arr,$fields){
    foreach ($fields as $field) {
        if(!isset($arr[$field]) || !is_array($arr[$field])){
            return false;
        }
    }
    return true;
}
//获取指定天数
function getOldDay($day=7){
    $time=strtotime(date('Y-m-d',time()));//获取今天开始时间戳
    $tmp_time_arr=[];
    for ($i = 0; $i < $day; $i++) {
        array_push($tmp_time_arr,date('Y-m-d',$time-($i*86400)));
    }
    return array_reverse($tmp_time_arr);//返回反转的数组
}
function uuid($prefix=''){
    $chars = md5(uniqid(mt_rand(), true));
    $uuid  = substr($chars,0,8) . '-';
    $uuid .= substr($chars,8,4) . '-';
    $uuid .= substr($chars,12,4) . '-';
    $uuid .= substr($chars,16,4) . '-';
    $uuid .= substr($chars,20,12);
    return $prefix . $uuid;
}
//[---------- 数据相关函数 ----------]
//DB助手函数
function db($model){
    return think\facade\Db::name($model);
}
//获取系统配置
//传入数组分类返回|传入字符返回内容
function getSys($name = []) {
    if(empty($name)){
        $sql=[];
    }else{
        $sql=is_array($name)?[['name','in',$name]]:[['name','=',$name]];
    }
    $sys = db('sys')->where($sql)->field(['name','info'])->select()->toArray();
    //数据二次处理|JSON对象转换
    foreach ($sys as $sysKey => $sysVo) {
        isJson($sysVo['info']) && ($sys[$sysKey]['info'] = json_decode($sysVo['info'],true));
    }
    if (empty($sys)) {
        $result = false;
    } else {
        if(is_array($name)){
            //分类返回
            $result = [];
            foreach ($sys as $sysVo) {
                $result[$sysVo['name']] = $sysVo['info'];
            }
        }else{
            //返回内容
            $result = $sys[0]['info'];
        }
    }
    return $result;
}
//写入日志
function pushLog($info,$user=false) {
    db('log')->insert([
        'time' => time(),
        'user' => empty($user)?getUserID():$user,
        'info' => $info
    ]);
}
//获取结账日期
function getPeriod(){
    $row=db('period')->order(['id'=>'desc'])->find();
    return empty($row)?0:$row['date'];
}

//快捷获取SQL
//$arr:数组数据|$config:配置项
//['field','Eq']|[['field','a|b|c'],'eq']
//eq:等同查询|fullEq:不为空等于数据|noNullEq:不为NULL等同数据|fullDec1:不为空内容减1|fullTime:不为空转时间戳
//md5:MD5加密|like:包含查询|fullLike:不为空包含查询
//fullIn:不为空包含查询(传入数组)|fullDivisionIn:不为空分割集合包含查询
//startTime和endTime:扩展时间字段查询
function fastSql($arr,$config) {
    $sql = [];
    foreach ($config as $item){
        $key=is_array($item[0])?key($item[0]):$item[0];
        $field=is_array($item[0])?$item[0][key($item[0])]:$item[0];
        if(array_key_exists($key,$arr)){
            $val=$arr[$key];
            $val==='null'&&($val=null);
            $condition=$item[1];
            //判断条件
            if ($condition == 'eq') {
                //等同查询
                $sql[] = [$field,'=',$val];
            } elseif ($condition == 'fullEq') {
                //不为空等于数据
                empty($val) || ($sql[] = [$field,'=',$val]);
            } elseif ($condition == 'noNullEq') {
                //不为NULL等于数据
                $val===null || ($sql[] = [$field,'=',$val]);
            } elseif ($condition == 'fullDec1') {
                //不为空内容减1
                empty($val) || ($sql[] = [$field,'=',$val-1]);
            } else if ($condition == 'md5') {
                //md5加密
                $sql[] = [$field,'=',md5($val)];
            } elseif ($condition == 'like') {
                //包含查询
                $sql[] = [$field,'like','%'.$val.'%'];
            } elseif ($condition == 'fullLike') {
                //不为空包含查询
                empty($val) || ($sql[] = [$field,'like','%'.$val.'%']);
            }elseif ($condition == 'fullTime') {
                //不为空转时间戳
                empty($val) || ($sql[] = [$field,'=',strtotime($val)]);
            }elseif ($condition == 'fullIn') {
                //不为空包含查询
                empty($val) || ($sql[] = [$field,'in',$val]);
            } elseif ($condition == 'fullDivisionIn') {
                //不为空分割集合查询
                empty($val) || ($sql[] = [$field,'in',explode(",",$val)]);
            } elseif ($condition == 'startTime') {
                //开始时间
                $start=strtotime($val);
                empty($start)||($sql[] = [$field,'>=',$start]);
            } elseif ($condition == 'endTime') {
                //结束时间
                $end=strtotime($val);
                empty($end)||($sql[] = [$field,'<=',$end+86399]);
            }else{
                die('[ ERROR ]未匹配条件!');
            }
        }
    }
    return array_unique($sql,SORT_REGULAR);
}
//递归获取指定ID树状数组结构
function findTreeArr($mode,$data,$field = false) {
    $tree = [];
    //判断是否初次执行
    if (!is_array($data)) {
        $first = db($mode)->where([['id','=',$data]])->find();
        //查询首次数据
        $tree[] = $first;
        //加入首次数据
        $data = [$first];
        //修正数据数组类型
    }
    $gather = array_column($data,'id');
    //获取集合数据
    $arr = db($mode)->where([['pid','in',$gather]])->select()->toArray();
    //查询子数据
    if (!empty($arr)) {
        $tree = array_merge($tree,$arr,findTreeArr($mode,$arr));
        //合并数据
    }
    return $field == false?$tree:array_column($tree,$field);
}
//多表多条件find查询
//$arr[['table'=>'plug','where'=>[['only','=',1]]]]
function moreTableFind($arr) {
    $result = false;
    //默认未找到
    foreach ($arr as $vo) {
        $find = db($vo['table'])->where($vo['where'])->find();
        if (!empty($find)) {
            $result = true;
            //找到数据
            break;
        }
    }
    return $result;
}
//SQL条件转换|数组条件转索引数组
function parmToIndex($parm){
    $sql=[];
    foreach ($parm as $parmKey=>$parmVo) {
        $sql[]=[$parmKey,'=',$parmVo];
    }
    return $sql;
}
//多单位分析|转换基数
function unitRadix($unit,$data){
    $radix=1;
    $state=false;
    array_unshift($data,['name'=>$data[0]['source'],'nums'=>1]);
    $data=array_reverse($data);
    foreach ($data as $dataVo) {
        if($state || $dataVo['name']==$unit){
            $state=true;
            $radix=math()->chain($radix)->mul($dataVo['nums'])->done();
        }
    }
    if($state){
        return $radix;
    }else{
        return false;
    }
}
//多单位分析|单位转换
function unitSwitch($nums,$data){
    //1 构造数据
    $record=[];
    foreach ($data as $dataVo) {
        if(abs($nums)<$dataVo['nums']){
            //1.1 小于归零
            $record[]=['name'=>$dataVo['source'],'nums'=>$nums];
            $nums=0;
        }else{
            //1.2 取余
            $mod=math()->chain($nums)->mod($dataVo['nums'])->done();
            $record[]=['name'=>$dataVo['source'],'nums'=>abs($mod)];
            //1.3 递减数量
            $nums=math()->chain($nums)->sub($mod)->div($dataVo['nums'])->done();
        }
    }
    //2 追加数据
    $end=end($data);
    $record[]=['name'=>$end['name'],'nums'=>$nums];
    //3 结构数据
    $text='';
    foreach (array_reverse($record) as $recordVo) {
        if(!empty($recordVo['nums'])){
            $text.=$recordVo['nums'].$recordVo['name'];
        }
    }
    $text==''&&$text=0;
    return $text;
}
//获取用户信息
function userInfo($id,$field = false) {
    $user = db('user')->where([['id','=',$id]])->find();
    return $field == false?$user:$user[$field];
}
//获取授权数据范围
function getUserAuth($type){
    $user=userInfo(getUserID());
    if($user['role']==0){
        $result='all';
    }else{
        $role=db('role')->where([['id','=',$user['role']]])->find();
        $auth=json_decode($role['auth'],true);
        if($type=='frame'){
            if(count($auth['frame'])==1 && $auth['frame'][0]==-2){
                $result='all';
            }else if(count($auth['frame'])==1 && $auth['frame'][0]==-1){
                $result=[$user['frame']];
            }else{
                $result=$auth['frame'];
            }
        }elseif($type=='customer'){
            if($auth['customer']=='all'){
                $result='all';
            }else if($auth['customer']=='userId'){
                $user=getUserAuth('user');
                if($user=='all'){
                    $result='all';
                }else{
                    $result=array_column(db('customer')->where([['user','in',$user]])->field(['id'])->select()->toArray(),'id');
                }
            }else{
                $frame=getUserAuth('frame');
                if($frame=='all'){
                    $result='all';
                }else{
                    $result=array_column(db('customer')->where([['frame','in',$frame]])->field(['id'])->select()->toArray(),'id');
                }
            }
        }elseif($type=='supplier'){
            if($auth['supplier']=='all'){
                $result='all';
            }else if($auth['supplier']=='userId'){
                $user=getUserAuth('user');
                if($user=='all'){
                    $result='all';
                }else{
                    $result=array_column(db('supplier')->where([['user','in',$user]])->field(['id'])->select()->toArray(),'id');
                }
            }else{
                $frame=getUserAuth('frame');
                if($frame=='all'){
                    $result='all';
                }else{
                    $result=array_column(db('supplier')->where([['frame','in',$frame]])->field(['id'])->select()->toArray(),'id');
                }
            }
        }elseif($type=='warehouse'){
            if($auth['warehouse']=='all'){
                $result='all';
            }else if($auth['warehouse']=='userFrame'){
                $result=[$user['frame']];
            }else{
                $frame=getUserAuth('frame');
                if($frame=='all'){
                    $result='all';
                }else{
                    $result=array_column(db('warehouse')->where([['frame','in',$frame]])->field(['id'])->select()->toArray(),'id');
                }
            }
        }elseif($type=='account'){
            if($auth['account']=='all'){
                $result='all';
            }else if($auth['account']=='userFrame'){
                $result=[$user['frame']];
            }else{
                $frame=getUserAuth('frame');
                if($frame=='all'){
                    $result='all';
                }else{
                    $result=array_column(db('account')->where([['frame','in',$frame]])->field(['id'])->select()->toArray(),'id');
                }
            }
        }elseif($type=='user'){
            if($auth['user']=='all'){
                $result='all';
            }else if($auth['user']=='userId'){
                $result=[getUserID()];
            }else{
                $frame=getUserAuth('frame');
                if($frame=='all'){
                    $result='all';
                }else{
                    $result=array_column(db('user')->where([['frame','in',$frame]])->field(['id'])->select()->toArray(),'id');
                }
            }
        }elseif($type=='people'){
            if($auth['people']=='all'){
                $result='all';
            }else if($auth['people']=='userFrame'){
                $result=[$user['frame']];
            }else{
                $frame=getUserAuth('frame');
                if($frame=='all'){
                    $result='all';
                }else{
                    $result=array_column(db('people')->where([['frame','in',$frame]])->field(['id'])->select()->toArray(),'id');
                }
            }
        }else{
            $result = false;
        }
    }
    return $result;
}
//组织数据范围
function frameScope($sql,$field='frame'){
    $cache=cache(getToken());
    if(empty($cache['frame'])){
        $frame=getUserAuth('frame');
        if($frame!='all'){
            $sql[]=[$field,'in',$frame];
        }
    }else{
        $sql[]=[$field,'in',$cache['frame']];
    }
    return $sql;
}
//SQL数据鉴权
function sqlAuth($model,$sql=[]){
    $tab=[
        'user'=>['id'=>'user','frame'=>'frame'],
        'log'=>['user'=>'user'],
        'customer'=>['id'=>'customer','frame'=>'frame','user'=>'user'],
        'supplier'=>['id'=>'supplier','frame'=>'frame','user'=>'user'],
        'warehouse'=>['id'=>'warehouse','frame'=>'frame'],
        'account'=>['id'=>'account','frame'=>'frame'],
        'people'=>['frame'=>'frame'],
        'bor'=>['frame'=>'frame','supplier'=>'supplier','people'=>'people','user'=>'user'],
        'buy'=>['frame'=>'frame','supplier'=>'supplier','people'=>'people','user'=>'user'],
        'bre'=>['frame'=>'frame','supplier'=>'supplier','people'=>'people','user'=>'user'],
        'sor'=>['frame'=>'frame','customer'=>'customer','people'=>'people','user'=>'user'],
        'sell'=>['frame'=>'frame','customer'=>'customer','people'=>'people','user'=>'user'],
        'sre'=>['frame'=>'frame','customer'=>'customer','people'=>'people','user'=>'user'],
        'swap'=>['frame'=>'frame','people'=>'people','user'=>'user'],
        'entry'=>['frame'=>'frame','people'=>'people','user'=>'user'],
        'extry'=>['frame'=>'frame','people'=>'people','user'=>'user'],
        'imy'=>['frame'=>'frame','customer'=>'customer','people'=>'people','user'=>'user'],
        'omy'=>['frame'=>'frame','supplier'=>'supplier','people'=>'people','user'=>'user'],
        'bill'=>['frame'=>'frame','people'=>'people','user'=>'user'],
        'allot'=>['frame'=>'frame','people'=>'people','user'=>'user'],
        'ice'=>['frame'=>'frame','customer'=>'customer','people'=>'people','user'=>'user'],
        'oce'=>['frame'=>'frame','supplier'=>'supplier','people'=>'people','user'=>'user'],
        'summary'=>['warehouse'=>'warehouse'],
        'room'=>['warehouse'=>'warehouse'],
        'batch'=>['warehouse'=>'warehouse'],
        'period'=>['user'=>'user']
    ];
    //扩展数据
    $extend=[
        'entry'=>['customer'=>[0]],
        'extry'=>['supplier'=>[0]],
        'ice'=>['customer'=>[0]],
        'oce'=>['supplier'=>[0]]
    ];
    $user = userInfo(getUserID());
    //排除管理员
    if($user['role']!=0){
        $role=db('role')->where([['id','=',$user['role']]])->find();
        $auth=json_decode($role['auth'],true);
        foreach ($tab[$model] as $tabKey => $tabVo) {
            //排除存在SQL键名
            if(!in_array($tabKey,array_column($sql,0))){
                $auth=getUserAuth($tabVo);
                if($auth!='all'){
                    //填入扩展
                    isset($extend[$model][$tabKey])&&$auth=array_merge($auth,$extend[$model][$tabKey]);
                    //赋值语句
                    $sql[] = [$tabKey,'in',$auth];
                }
            }
        }
    }
    return $sql;
}
//获取扩展字段配置
function getFields() {
    $data=[];
    $field = db('field')->select()->toArray();
    foreach ($field as $fieldVo) {
        $data[$fieldVo['key']] = json_decode($fieldVo['fields'],true);
    }
    return $data;
}
//获取用户权限数据
function getUserRoot() {
    $user=userInfo(getUserID());
    if ($user['role'] == 0) {
        return 'all';
    } else {
        $role = db('role')->where([['id','=',$user['role']]])->find();
        $root = json_decode($role['root'],true);
        //初始化权限数据
        foreach ($root as $rootVo) {
            $data[$rootVo['module']] = $rootVo['data'];
        }
        return $data;
    }
}
//获取组织零售单配置
function getFrameDeploy(){
    $userFrame=userInfo(getUserID(),'frame');
    $frame=db('frame')->where([['id','=',$userFrame]])->find();
    $deploy=db('deploy')->where([['frame','=',$frame['id']]])->find();
    while ($frame['pid']!=-1 && empty($deploy)){
        $frame=db('frame')->where([['id','=',$frame['pid']]])->find();
        $deploy=db('deploy')->where([['frame','=',$frame['id']]])->find();
    }
    return empty($deploy)?null:json_decode($deploy['source'],true);
}

//获用户权限菜单数据
function getRootMemu() {
    $root = getUserRoot();
    if ($root == 'all') {
        $menu= db('menu')->order('sort asc')->select()->toArray();
    } else {
        $menu = db('menu')->where([['root','<>','admin'],['id','>',0]])->order('sort asc')->select()->toArray();
        //1.做数据标识
        foreach ($menu as $menuKey=>$menuVo) {
            $voRoot=explode('|',$menuVo['root']);
            //[权限为空|常规上级菜单|权限存在且为真]
            $menu[$menuKey]['check']=(empty($menuVo['root']) || $menuVo['resource']=='#group' || (isset($root[$voRoot[0]][count($voRoot)==1?'see':$voRoot[1]])&&$root[$voRoot[0]][count($voRoot)==1?'see':$voRoot[1]]=='true'))?1:0;
        }
        //2.处理附属菜单
        foreach (search($menu)->where([['type','=',1],['check','=',1]])->select() as $menuVo) {
            $pidData=search($menu)->where([['id','=',$menuVo['pid']],['check','=',false]],true)->find();
            empty($pidData)||$menu[$pidData['rowKey']]['check']=-1;
        }
        //3.保留可访问菜单数据
        $menu=search($menu)->where([['check','in',[1,-1]]])->select();
        //4.处理空集菜单
        $group=search($menu)->where([['resource','=','#group']],true)->select();
        foreach ($group as $menuVo) {
            $tree = new \org\Tree();
            $v=$tree::vTree($menu,$menuVo['id']);
            if(empty($v)){
                unset($menu[$menuVo['rowKey']]);
            }else{
                $find=search($v)->where([['resource','<>','#group']])->find();
                if(empty($find)){
                    unset($menu[$menuVo['rowKey']]);
                }
            }
        }
    }
    return $menu;
}