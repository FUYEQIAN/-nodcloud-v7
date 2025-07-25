<?php
/*
BY:NODCLOUD.COM
*/
namespace org;
class BakSql {
    private $config=[];
    private $handler;
    private $tables = array();//需要备份的表
    private $begin; //开始时间
    private $error;//错误信息
    public function __construct($hostname,$username,$password,$database,$path='static'.DIRECTORY_SEPARATOR.'backup'.DIRECTORY_SEPARATOR) {
        $config['hostname']=$hostname; //服务器地址
        $config['username']=$username; //用户名
        $config['password']=$password; //密码
        $config['database']=$database; //数据库名
        $config['type']='mysql'; //数据库类型
        $config['hostport']='3306'; //数据库端口
        $config['charset']='utf8'; //数据库端口
        $config["sqlbakname"]='ERP_'.date("YmdHis",time()).".sql";//默认保存文件
        $config['path']=$path; //默认目录
        if (!file_exists($path)){
            mkdir($path,0755,true); //如文件路径不存在,则按照路径创建多个文件夹
        }
        $this->config = $config;
        $this->begin = microtime(true);
        header("Content-type: text/html;charset=utf-8");
        $this->connect();
    }
    //首次进行pdo连接
    private function connect() {
        try{
           $this->handler =new \PDO("{$this->config['type']}:host={$this->config['hostname']};port={$this->config['hostport']};dbname={$this->config['database']};",
                $this->config['username'],
                $this->config['password'], 
                array(
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']};",
                    \PDO::ATTR_ERRMODE =>  \PDO::ERRMODE_EXCEPTION, 
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                )); 
        }catch (PDOException $e) {
            die ("Error!: " . $e->getMessage() . "<br/>");
        }
    }
     /**
     * 查询
     * @param string $sql
     * @return mixed
     */
    private function query($sql = '')
    {
        $stmt = $this->handler->query($sql);
        $stmt->setFetchMode(\PDO::FETCH_NUM);
        $list = $stmt->fetchAll();
        return $list;
    }
     /**
     * 获取全部表
     * @param string $dbName
     * @return array
     */
    private function get_dbname($dbName = '*') {
         $sql = 'SHOW TABLES';
        $list = $this->query($sql);
        $tables = array();
        foreach ($list as $value)
        {   
            $tables[] = $value[0];
        }
        return $tables;        
    }
    /**
     * 获取表定义语句
     * @param string $table
     * @return mixed
     */
    private function get_dbhead($table = '')
    {
        $sql = "SHOW CREATE TABLE `{$table}`";
        $ddl = $this->query($sql)[0][1] . ';';
        return $ddl;
    }
    /**
     * 获取表数据
     * @param string $table
     * @return mixed
     */
    private function get_dbdata($table = '')
    {
        $sql = "SHOW COLUMNS FROM `{$table}`";
        $list = $this->query($sql);
        //字段
        $columns = '';
        //需要返回的SQL
        $query = '';
        foreach ($list as $value)
        {
            $columns .= "`{$value[0]}`,";
        }
        $columns = substr($columns, 0, -1);
        $data = $this->query("SELECT * FROM `{$table}`");
        foreach ($data as $value)
        {
            $dataSql = '';
            foreach ($value as $v)
            {
                $dataSql .= "'".addslashes($v)."',";
            }
            $dataSql = substr($dataSql, 0, -1);
            $query .= "INSERT INTO `{$table}` ({$columns}) VALUES ({$dataSql});\r\n";
        }
        return $query;
    }
    /**
     * 写入文件
     * @param array $tables
     * @param array $ddl
     * @param array $data
     */
    private function writeToFile($tables = array(), $ddl = array(), $data = array())
    {
        $str = "/*\r\nMySQL Database Backup Tools - NODCLOUD.COM\r\n";
        $str .= "Server:{$this->config['hostname']}:{$this->config['hostport']}\r\n";
        $str .= "Database:{$this->config['database']}\r\n";
        $str .= "Data:" . date('Y-m-d H:i:s', time()) . "\r\n*/\r\n";
        $str .= "SET NAMES utf8;\r\n";
        $str .= "SET FOREIGN_KEY_CHECKS=0;\r\n";
        $str .= "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\r\n";
        $i = 0;
        foreach ($tables as $table)
        {
            $str .= "-- ----------------------------\r\n";
            $str .= "-- Table structure for {$table}\r\n";
            $str .= "-- ----------------------------\r\n";
            $str .= "DROP TABLE IF EXISTS `{$table}`;\r\n";
            $str .= $ddl[$i] . "\r\n";
            $str .= "-- ----------------------------\r\n";
            $str .= "-- Records of {$table}\r\n";
            $str .= "-- ----------------------------\r\n";
            $str .= $data[$i] . "\r\n";
            $i++;
        }
        if(!file_exists($this->config['path'])){mkdir($this->config['path']);}
        return file_put_contents($this->config['path'].$this->config['sqlbakname'], $str) ? 'success' : 'error';
    }
    /**
     * 设置要备份的表
     * @param array $tables
     */
    private function setTables($tables = array())
    {
        if (!empty($tables) && is_array($tables))
        {
            //备份指定表
            $this->tables = $tables;
        }
        else
        {
            //备份全部表
            $this->tables = $this->get_dbname();
        }
    }
    /**
     * 备份
     * @param array $tables
     * @return bool
     */
    public function backup($tables = array())
    {
        //存储表定义语句的数组
        $ddl = array();
        //存储数据的数组
        $data = array();
        $this->setTables($tables);
        if (!empty($this->tables))
        {
            foreach ($this->tables as $table)
            {
                $ddl[] = $this->get_dbhead($table);
                $data[] = $this->get_dbdata($table);
            }
            
            //开始写入
            return $this->writeToFile($this->tables, $ddl, $data);
        }
        else
        {
            $this->error = '数据库中没有表!';
            return false;
        }
    }
    /**
     * 错误信息
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }
    //还原数据库
    public function restore($filename = '')
    {
        $path=$this->config['path'].$filename;
        if (!file_exists($path))
        {
            $this->error('SQL文件不存在!');
            return false;
        }
        else
        {
            $sql = $this->parseSQL($path);
            try
            {
                $this->handler->exec($sql);
                return 'success';
            }
            catch (PDOException $e)
            {
                $this->error = $e->getMessage();
                return false;
            }
        }
    }
 
    /**
     * 解析SQL文件为SQL语句数组
     * @param string $path
     * @return array|mixed|string
     */
    private function parseSQL($path = '')
    {
        $sql = file_get_contents($path);
        $sql = explode("\r\n", $sql);
        //先消除--注释
        $sql = array_filter($sql, function ($data)
        {
            if (empty($data) || preg_match('/^--.*/', $data))
            {
                return false;
            }
            else
            {
                return true;
            }
        });
        $sql = implode('', $sql);
        //删除/**/注释
        $sql = preg_replace('/\/\*.*\*\//', '', $sql);
        return $sql;
    }
    /**
     * 下载备份
     * @param string $fileName
     * @return array|mixed|string
     */
    public function downloadFile($fileName) {
        $fileName=$this->config['path'].$fileName;
        if (file_exists($fileName)){
            ob_end_clean();
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Length: ' . filesize($fileName));
            header('Content-Disposition: attachment; filename=' . basename($fileName));
            readfile($fileName);
        }else{
            $this->error="文件有错误！";
        }

    }
    /**
     * 获取文件是时间
     * @param string $file
     * @return string
     */
    private function getfiletime($file){
        $path=$this->config['path'].$file;
        $a = filemtime($path);
        $time = date("Y-m-d H:i:s", $a);
        return $time;
    }
    /**
     * 获取文件是大小
     * @param string $file
     * @return string
     */
    private function getfilesize($file){
        $perms=stat($this->config['path'].$file);
        $size = $perms['size'];
        $a    = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pos  = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        return round($size, 2). $a[$pos];
    }
    
    /**
     * 获取文件列表
     * @param string $Order 级别
     * @return array
     */
    public function get_filelist($Order = 0) {
        $FilePath=$this->config['path'];
        $FilePath = opendir($FilePath);
        $FileAndFolderAyy=array();
        $i=1;
        while (false !== ($filename = readdir($FilePath))) {
            if ($filename!="." && $filename!=".."){
                $i++;
                $FileAndFolderAyy[$i]['name'] = $filename;
                $FileAndFolderAyy[$i]['time'] = $this->getfiletime($filename);
                $FileAndFolderAyy[$i]['size'] = $this->getfilesize($filename);
            }
        }
        $Order == 0 ? sort($FileAndFolderAyy) : rsort($FileAndFolderAyy);
        return array_reverse($FileAndFolderAyy);//返回反转数组
    }
    public function delfilename($filename){
        $path=$this->config['path'].$filename;          
        if (@unlink($path)) {return 'success';}
    }
}
?>