<?php
namespace app\index\controller;

use think\facade\Cookie;
use think\facade\View;
use app\index\service\Nmysql;
use think\Config; 
use app\index\service\Mymongo;
/*
<option value="1">句子(juzi)</option>
<option value="2">标题(bt)</option>
<option value="3">图片(pic)</option>
<option value="4">小图(img)</option>
<option value="5">权重标题(wzmz)</option>
<option value="6">栏目名称(lanmu)</option>
<option value="7">关键词(keyword)</option>
<option value="8">吸引标题(zhon)</option>
<option value="9">关键词2(hou)</option>
<option value="10">外链(wailian)</option>
<option value="11">模板(moban)</option>
<option value="12">juzi2</option>
<option value="13">ditu</option>
 */
class Index extends Common
{
    const TableMap = ['1' => 'juzi', '2' => 'bt', '3' => 'pic', '4' => 'img', '5' => 'wzmz', '6' => 'lanmu', '7' => 'keyword', '8' => 'zhon', '9' => 'hou', '10' => 'wailian', '11' => 'moban', '12' => 'juzi2', '13' => 'bt_keyword', '14' => 'ditu'];
    const _K = '12345,.Abc33678';
    public function index()
    {
        return redirect('/index/subNews.html');
    }
    public function checkLogin()
    {
        $username = Cookie::get('username', '');
        return $username;
    }
    public function rand($len)
    {
        $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $string=time();
        $len = (int)$len;
        for($len=$len;$len>=1;$len--)
        {
            $position=rand()%strlen($chars);
            $position2=rand()%strlen($string);
            $string=substr_replace($string,substr($chars,$position,1),$position2,0);
        }
        return md5($string.microtime());
    }    
    public function doFile(){
        $file = request()->file('file_fr');
        $dirSep = '/';//DIRECTORY_SEPARATOR
        if ($file) {
            $name = $_FILES['file_fr']['name'];
            $extends = strrchr($name,'.');

            if(!in_array(trim($extends), ['.png','.jpg','.jpeg','.gif'])){
                echo json_encode(['error'=>'不正确的文件类型']);
            }
            // 移动到框架应用根目录/public/uploads/ 目录下
            $rand = $this->rand(20).$extends;
            $pName = $_SERVER['DOCUMENT_ROOT']. $dirSep . 'upload'.$dirSep.$rand;
            $info = $file->move($_SERVER['DOCUMENT_ROOT']. $dirSep . 'upload',$rand);
            //var_dump($pName);var_dump($info);exit;
            if($info&&file_exists($pName)){
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                echo json_encode(['uploaded' => $dirSep . 'ads'.$dirSep.$rand]);
            }else{
                echo json_encode(['error'=>'上传失败']);
            }  
        } else {
            echo json_encode(['error'=>'No files found for upload.']);
        }        
    }
    public function curl_post( $url, $postdata,$headers=[] ) {
 
       $header = array(
           'Accept: application/json',
        );
        $header = array_merge($header,$headers);
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 超时设置
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
     
        // 超时设置，以毫秒为单位
        // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);
     
        // 设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
     
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE );
     
        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        //执行命令
        $data = curl_exec($curl);
     
        // 显示错误信息
        if (curl_error($curl)) {
            return false;
        } else {
            return json_decode($data,true);
            // 打印返回的内容
            curl_close($curl);
        }
    }
    public function test(){
        $myMongo = new Mymongo(config('database.connections.mongo'));
        $manager = $myMongo->getManager();
        $filter = ['_id' => ['$gt' => 0]];
        $options = [
            'sort' => ['_id' => -1],
        ];        
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $manager->executeQuery('redio.re_ads', $query);  
        foreach ($cursor as $document) {
            print_r((array)$document);
        } 
    }
    public function subNews()
    {
        if (!$this->checkLogin()) {
            return redirect('/index/login.html');
        }
        if (!empty($_POST)) {
            $paths = (string) trim($_POST['paths']);
            if (empty($paths)) {
                return ['status' => 1];
            }
            $nonce = mt_rand(1,30000);// 
            $result = $this->curl_post( 'http://127.0.0.1:8885/api', ['ads'=>$paths,'device'=>(int)$_POST['device'],'type'=>(int)$_POST['type']],["nonce:{$nonce}","token:".md5($nonce."..,".self::_K)]);

            $status = 0;

            return ['status' => $result['status']];
        }

        return View::fetch();
    }
    public function myList()
    {
        $pageNum = 1;
        if (!$this->checkLogin()) {
            return redirect('/index/login.html');
        }
        $page = $_GET['p'] ?? 1;
        if($page<1){
            $page = 1;
        }
        $skip = ($page - 1)*1;

        $myMongo = new Mymongo(config('database.connections.mongo'));
        $manager = $myMongo->getManager();
        $filter = ['status' => 0];
        $options = [
            'sort' => ['_id' => -1],
            'limit'=>1,
            'skip'=>$skip
        ];        
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $manager->executeQuery('redio.re_ads', $query);
        $list = [];  
        foreach ($cursor as $document) {
            $list[] = (array)$document;
        }
        $result = 0;
        $count = $myMongo->count(['status' => 0], 're_ads');

        $pageList = $myMongo->getPageList($count,$page);
        /*
        $type   = $title   = '';
        $data[] = ['id', '>', 0];
        $list   = \think\facade\Db::connect('db2')->name('map')->where($data)->order('id', 'desc')->paginate(['list_rows' => 50]);
        */
        View::assign('list', $list);
        View::assign('pageList', $pageList);
        View::assign('curPage', $page);


        return View::fetch();
    }

    public function del(){
        $id = (int)$_POST['id'];
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->update(
            ['_id' => $id],
            ['$set' => ['status' => 1]],
            ['multi' => false, 'upsert' => false]
        );
        $myMongo = new Mymongo(config('database.connections.mongo'));
        $manager = $myMongo->getManager();
        $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
        $result = $manager->executeBulkWrite('redio.re_ads', $bulk, $writeConcern);   
        return 1;
    }
    public function hostf()
    {
        return View::fetch();
    }
    public function addHost()
    {
        $dbInfo = config('database.connections.db2');
        $info = [
            'host'=>$dbInfo['hostname'],
            'port'=>$dbInfo['hostport'],
            'user'=>$dbInfo['username'],
            'passwd'=>$dbInfo['password'],
            'dbname'=>$dbInfo['database']
        ];
        $nmysql = new Nmysql($info);
        $url = trim($_GET['url']);
        $res = $nmysql->field(array('id'))
            ->where(array('url'=>"'".$url."'"))
            ->limit(1)
            ->select('host_map');
        if(!empty($res)||!empty(current($res))){
            return ['status'=>0];
        }
        $res = $this->add($nmysql,$url);
        if($res){
            return ['status'=>0];
        }else{
            return ['status'=>1];
        }

    }   

    public function add($mysql,$url){
        $mysql->startTrans();
        try{
            $tableId = $mysql->insert("host_map",['url'=>$url],1);var_dump($tableId);
            if(empty($tableId)){
                $mysql->rollback();
                return false;
            }
            $oldTable = "host_visiter_samp";
            $newTable = "host_visiter{$tableId}_1";
            $sql = "create table {$newTable} like {$oldTable}";
            $res=$mysql->doSql($sql);
            $sql = "select * from information_schema.tables where table_name ='".$newTable."'";
            $res=$mysql->doSql($sql);
            if(empty($res)){
                $mysql->where(['id'=>$tableId])->delete("host_map");
                $mysql->rollback();
                return false;
            }
            $tableId = $mysql->insert("host_valid_table",['name'=>"visiter{$tableId}"],1);
            if(empty($tableId)){
                $this->mysql->rollback();
                return false;
            }
            $mysql->commit();        
            return true;
        }catch(\Exception $e){
            $mysql->rollback();
            return false;
        }       
    }     
    public function dellogin()
    {
        if (!$this->checkLogin()) {
            return redirect('/index/login.html');
        }
        Cookie::delete('username');
        return redirect('/index/login.html');
    }
}
