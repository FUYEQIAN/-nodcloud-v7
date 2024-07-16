<?php
    //安装检测
    if (file_exists('install.lock')) {
        die('很抱歉，程序已经部署完成。如需重新部署，请删除install/install.lock文件。');
    }else{
        if(isset($_GET['act'])=='install'){
			install();
		}
    }
    //编码转换
    function strEnhtml($str) {
        return addslashes(htmlspecialchars(trim($str)));
    }
    //读取文件
    function fetchFile($file) {
        if (file_exists($file) and is_readable($file)) {
            if (function_exists('file_get_contents')) {
                $content = file_get_contents($file);
            } else {
                $fp = fopen($file, 'r');
                while (!feof($fp)) {
                    $content = fgets($fp, 1024);
                }
                fclose($fp);
            }
            return $content;
        }
    }
    //写入文件
    function writeFile($path, $str, $type = 'w+') {
        $of = fopen($path, $type);
        if ($of) {
            fwrite($of, iconv('utf-8', 'utf-8', $str));
            fclose($of);
            return true;
        } else {
            return false;
        }
    }
    //JSON返回
    function returnJson($info){
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($info));
    }
    function install() {
        $dbhost = isset($_POST['dbhost']) ? strEnhtml($_POST['dbhost']) :'';
        $dbname = isset($_POST['dbname']) ? strEnhtml($_POST['dbname']) :'';
        $dbuser = isset($_POST['dbuser']) ? strEnhtml($_POST['dbuser']) :'';
        $dbpwd = isset($_POST['dbpwd'])  ? strEnhtml($_POST['dbpwd'])  :'';
        (!$dbhost || !$dbname || !$dbuser) && returnJson(['state'=>'error','info'=>'配置信息填写不完全!']);
        $conn = @mysqli_connect($dbhost,$dbuser,$dbpwd);
        !$conn && returnJson(['state'=>'error','info'=>'连接数据库失败,请核实配置信息!']);
        mysqli_query($conn,"SET NAMES 'utf8', character_set_client=binary, sql_mode='', interactive_timeout=3600;");
        $list = mysqli_query($conn,'show Databases');
        while ($row = mysqli_fetch_array($list)) {
            $dbname_arr[] = $row['Database'];
        }
        if (!in_array($dbname,$dbname_arr)) {
            !mysqli_query($conn,'CREATE DATABASE '.$dbname.'') && returnJson(['state'=>'error','info'=>'创建数据库失败!']);
        }
        mysqli_select_db($conn,$dbname);
        $sql = fetchFile('mysql.sql');
        $sqlarr = explode(";\n",$sql);
        foreach ($sqlarr as $sql) {
            mysqli_query($conn,$sql);
        }
        //创建数据库连接文件
        $database = fetchFile('database.ini');
        $database = str_replace('{db_host}',$dbhost,$database);
        $database = str_replace('{db_user}',$dbuser,$database);
        $database = str_replace('{db_pwd}',$dbpwd,$database);
        $database = str_replace('{db_name}',$dbname,$database);
        if (!writeFile('../../config/database.php',$database)) {
            returnJson(['state'=>'error','info'=>'数据库连接文件创建失败!']);
        }
        writeFile('../install.lock','软件已正确安装，重新安装请删除本文件。');
        returnJson(['state'=>'success']);
    }
?>