<?php
/**
 * Created by PS_YWQ
 * User: Logmecn
 * Date: 2019/5/24-15:32
 */
namespace app\index\service;

use \Exception;
use \MongoDB;
use \MongoDB\Driver\Manager;
use \stdClass;

/**
 *
 */
class Mymongo
{
    public $pageNum = 53;
    public $showPage = 5;
    private $confArr = array();
    public static $curMongo = false;

    /**
     * Lite constructor.
     * @param array $conf 连接 MongoDB 的配置加载
     */
    public function __construct($conf)
    {
        $this->confArr = $conf;
        if(self::$curMongo == false){
            self::$curMongo = $this->connect();
        }
    }
    public function getManager(){
        return self::$curMongo;        
    }

    /**
     * @desc 连接到 MongoDB
     * @return bool|Manager
     */
    private function connect()
    {
        try {
            $connStr = "mongodb://" . $this->confArr['host'] . ":" . $this->confArr['port'] . "/" . $this->confArr['db_name'];
            $options = array(
                'username' => $this->confArr['username'],
                'password' => $this->confArr['password'],
//                'readPreference' => $this->confArr['read_preference'],  // 此参数已废弃
                'connectTimeoutMS' => intval($this->confArr['connect_timeout_ms']),
                'socketTimeoutMS' => intval($this->confArr['socket_timeout_ms']),
                'persist' => $this->confArr['persist'],
            );
            $mc = new \MongoDB\Driver\Manager($connStr);
            return $mc;
        } catch (Exception $e) {
            return false;
        }
    }
    public function getPageList($count=0,$curPage=1){
        $pageList = [];
        $pageNum = $this->pageNum;
        if($curPage<1){
            $curPage = 1;
        }
        $endPage = $curPage + $this->showPage - 1;
        $totalPage = ceil($count/$pageNum);
        if($endPage>$totalPage){
            $endPage = $totalPage;
        }
        if($curPage>1){
            $pageList[] = ['title'=>'首页','val'=>1];
            $pageList[] = ['title'=>'前一页','val'=>$curPage-1];
        }
        for($i=$curPage;$i<=$endPage;$i++){
            $pageList[] = ['title'=>$i,'val'=>$i];
        } 
        return $pageList;    
    }
    public function command($params) {

        try {
            $cmd = new MongoDB\Driver\Command($params);
            $result = self::$curMongo->executeCommand($this->confArr['db_name'], $cmd);
            return $result;
        } catch (Exception $e) {
            //记录错误
        }
        return false;
    } 
    public function count($query, $collection) {
        try {
            $cmd = array(
                'count' => $collection,
                'query' => $query,
            );
            $res = $this->command($cmd);
            $result = $res->toArray();
            return $result[0]->n;
        } catch (Exception $e) {
            //记录错误
        }
        return false;
    }       
}