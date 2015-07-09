<?php
/**
 * Created by PhpStorm.
 * User: m_xuelu
 * Date: 2015/7/7
 * Time: 20:33
 */
function run(){
    header('Content-Type:text/html;charset=utf-8');
    date_default_timezone_set('PRC');
    //下载图片保存地址，最后要有/
    define('PATH_DOWNLOAD', './download/');
    if (!file_exists(PATH_DOWNLOAD)) {
        mkdir(PATH_DOWNLOAD);
    }
    set_time_limit(0);
    $city_url = "http://wd.koudai.com/wd/cate/getList?param={%22userID%22:%22337474108%22}&callback=jsonpcallback_1436277342180_5235922697465867&ver=2015070700014";
    //验证curl模块
    if (!function_exists('curl_init')) {
        echo "执行失败!请先安装curl扩展!\n";
        exit();
    }
    //获取城市目录
    $city = get_mulu($city_url);
    echo "获取城市：".$city['status']."  耗时：".$city['time']."\n";
    //解析目录jsonp数据
    $city = get_items($city['res']);
    //开始循环城市
    foreach($city['result'] as $city){
        $city_dir = PATH_DOWNLOAD.$city['cate_name']."/";
        save_dir(get_gbk($city_dir));
        echo "开始处理 ".$city['cate_name']."  城市ID：".$city['cate_id']."\n";
        $url = "http://wd.koudai.com/wd/cate/getItems?param={%22userID%22:%22337474108%22,%22cate_id%22:%22".$city['cate_id']."%22,%22limitStart%22:0,%22limitNum%22:10}&callback=jsonpcallback_1436278044036_6557131321169436&ver=2015070700014";
        $mulu = get_mulu($url);
        echo "--获取目录状态：".$mulu['status']."，耗时：".$mulu['time']."\n";
        //解析目录jsonp数据
        $items = get_items($mulu['res']);
        foreach($items['result'] as $item){

            //保存目录
//            echo var_dump(str_replace(" ","",$item['itemName']))."\n";continue;
            $name = str_replace(" ","",$item['itemName']);
            $name = str_replace("/","",$name);
            $name = str_replace(".","",$name);
            $name = str_replace("\r","",$name);
            $name = str_replace("\n","",$name);
            $girl_dir = $city_dir.$name."/";
            save_dir(get_gbk($girl_dir));
            //解析二级页面
            $second_url = "http://weidian.com/wd/item/getPubInfo?param={%22itemID%22:".$item['itemID'].",%22page%22:1}&callback=jsonpcallback_1436279264909_6875134997535497&ver=2015070700014";
            $senond_mulu = get_mulu($second_url);
//        echo "二级目录解析完成！\n";
//        echo "开始下载图片...\n";
            $s_items = get_items($senond_mulu['res']);
            echo "----二级目录：".$item['itemName']."  图片数量：".count($s_items['result']['Imgs'])."\n";
            echo "----开始下载...\n";
            $index = 1;
            foreach($s_items['result']['Imgs'] as $pic){
                //对地址进行处理
                $pic_url = get_pic_url($pic);
                //写入图片文件
                save_file($pic_url,$girl_dir,$index++);
            }
            unset($url);
        }
    }
}

function get_mulu($url){
    //初始化curl
    $curl = curl_init($url);
    //curl超时 30s
    curl_setopt($curl, CURLOPT_TIMEOUT, '30');
    //user-agent头
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30");
    //返回文件流
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    //关闭头文件数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //禁止服务端验证
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    //转码结果
    $res = iconv('GBK', 'UTF-8', curl_exec($curl));
    //HTTP 状态码
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    //耗时
    $total_time = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
    curl_close($curl);
    return ['status'=>$http_status,'time'=>$total_time,'res'=>$res];
}

function save_dir($dir){
    //建立文件夹，存在的话不用建立
    if (!file_exists($dir)) {
        @mkdir($dir,'0777');
    }
}

function get_items($jsonp){
    $start_index = strpos($jsonp,"{"); //开始位置
    $items = substr($jsonp,$start_index,-1);
    return json_decode(mb_convert_encoding($items, 'UTF-8', 'ASCII,UTF-8,ISO-8859-1'),true);
}

function get_pic_url($pic){
    $end_index = strpos($pic,"?"); //结束位置
    $p_url = substr($pic,0,$end_index);
    return mb_convert_encoding($p_url, 'UTF-8', 'ASCII,UTF-8,ISO-8859-1');
}

function save_file($pic_url,$girl_dir,$index){
    echo $pic_url."\n";
    ob_start();//打开输出
    readfile($pic_url);//输出图片文件
    $img = ob_get_contents();//得到浏览器输出
    ob_end_clean();//清除输出并关闭
    $filename = $girl_dir.$index.".jpg";
    $fp2 = fopen(get_gbk($filename), "a");
    fwrite($fp2, $img);//向当前目录写入图片文件，并重新命名
    fclose($fp2);
}

function get_gbk($str){
    return mb_convert_encoding($str, 'GBK', 'ASCII,UTF-8,ISO-8859-1');
}
run();

