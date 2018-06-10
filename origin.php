<?php
/**
 * Created by PhpStorm.
 * User: xing
 * Date: 2018/6/10
 * Time: 19:52
 */

/**
 * 获取给定时间段内的日期
 * @param $start int 开始时间戳
 * @param $end int 结束时间戳
 * @param $interval int 间隔
 * @param bool $obj 是否要原始datePeriod对象
 * @param string $format 日期格式化
 * @return array|DatePeriod
 * @throws Exception
 */
function periodDate($start, $end, $interval = 1, $obj = false, $format = 'Y-m-d')
{
    $start = date_create(date('Ymd', $start));
    $end = date_create(date('ymd', $end));
    $interval = new \DateInterval('P' . $interval . 'D');
    $daterange = new \DatePeriod($start, $interval, $end);
    $res = [];
    if ($obj == true) {
        return $daterange;
    }
    foreach ($daterange as $date) {
        $res[] = $date->format($format);
    }
    return $res;
}

/**
 * 生成下拉菜单option
 * @param $data
 * @param $selected string|int|array
 * @param string $k
 * @param string $v
 * @return string
 */
function optionsString($data, $selected = '', $k = 'id', $v = 'name')
{
    $string = '';
    foreach ($data as $index => $value) {
        $string .= '<option ';
        if (is_array($value)) {
            if ((!empty($selected) || $selected === '0' || $selected === 0) && ($selected == $value[$k])) {
                $string .= ' selected ';
            } elseif (is_array($selected) && $selected && in_array($value[$k], $selected)) {
                $string .= ' selected ';
            }
            $string .= ' value="' . $value[$k] . '">' . $value[$v] . '</option>';
        } else {
            if ((!empty($selected) || $selected === '0' || $selected === 0) && ($selected == $index)) {
                $string .= ' selected';
            } elseif (is_array($selected) && $selected && in_array($value, $selected)) {
                $string .= ' selected ';
            }
            $string .= ' value="' . $index . '">' . $value . '</option>';
        }

    }
    return $string;
}

/**
 * 计算两点地理坐标之间的距离
 * @param  float $longitude1 起点经度
 * @param  float $latitude1 起点纬度
 * @param  float $longitude2 终点经度
 * @param  float $latitude2 终点纬度
 * @param  Int $unit 单位 1:米 2:公里
 * @param  Int $decimal 精度 保留小数位数
 * @return float|string
 */
function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
{

    if (!$latitude1 || !$latitude2 || !$longitude2 || !$longitude1 || !is_numeric($latitude1) || !is_numeric($latitude2) || !is_numeric($longitude2) || !is_numeric($longitude1)) {
        return '未知距离';
    }

    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $PI = 3.1415926;

    $radLat1 = $latitude1 * $PI / 180.0;
    $radLat2 = $latitude2 * $PI / 180.0;

    $radLng1 = $longitude1 * $PI / 180.0;
    $radLng2 = $longitude2 * $PI / 180.0;

    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;

    $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $distance = $distance * $EARTH_RADIUS * 1000;

    if ($unit == 2) {
        $distance = $distance / 1000;
    }

    return round($distance, $decimal);

}

/**
 * 二维数组指定字段简单排序
 * @param $data array
 * @param $field string 排序的字段
 * @param int $sort SORT_ASC=4,SORT_DESC=3
 */
function arrayMultiSort(&$data, $field, $sort = SORT_ASC)
{
    $tmp = [];
    foreach ($data as $v) {
        $tmp[] = $v[$field];
    }
    array_multisort($tmp, $sort, $data);
}

/**
 * 检测联系电话(座机/手机)格式
 * @param $telephone
 * @return bool
 */
function checkTelephone($telephone)
{
    if (!preg_match('/^(0[0-9]{2,3}-)?([2-9][0-9]{6,7})+(-[0-9]{1,4})?$/', $telephone) && !preg_match('/^1\d{10}$/', $telephone)) {
        return false;
    }
    return true;
}


/**
 * 检测远程文件是否存在
 * @param $url
 * @return bool
 */
function url_exists($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    //不下载
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    //设置超时
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code == 200) {
        return true;
    }
    return false;
}

/**
 * 纯文本转为html
 * @param $text
 * @return mixed|string
 */
function textToHtml($text)
{
    $str = trim($text); // 取得字串同时去掉头尾空格和空回车
    //$str=str_replace("<br>","",$str); // 去掉<br>标签
    $str = "<p>" . trim($str); // 在文本头加入<p>
    $str = str_replace(PHP_EOL, "</p>" . PHP_EOL . "<p>", $str); // 用p标签取代换行符
    $str .= "</p>\n"; // 文本尾加入</p>
    $str = str_replace("<p></p>", "", $str); // 去除空段落
    $str = str_replace(PHP_EOL, "", $str); // 去掉空行并连成一行
    $str = str_replace("</p>", "</p>", $str); //整理html代码
    return $str;
}

/**
 * 格式化属性
 * @param $data
 * @param $rule
 */
function formatAttribute(&$data, $rule)
{
    if ($data && $rule) {
        $arr = [
            'string' => 'strval',
            'int' => 'intval',
            'date' => function ($param) {
                return date('Y-m-d', $param);
            },
            'datetime' => function ($param) {
                return date('Y-m-d H:i', $param);
            },
            'float' => 'floatval',
            'callable' => 'call_user_func_array'
        ];
        foreach ($data as $k => &$v) {
            foreach ($rule as $vv) {
                if (in_array($k, $vv[1], true) && isset($arr[$vv[0]])) {
                    if ($vv[0] != 'callable') {
                        $v = call_user_func_array($arr[$vv[0]], [$v]);
                    }

                } elseif (in_array($k, array_keys($vv[1]), true)) {
                    foreach ($vv[1] as $key => $call) {
                        if ($key == $k && is_callable($call)) {
                            $v = call_user_func_array($call, (array)$v);
                        }
                    }
                }
            }
        }
    }
}

/**
 * 获取银行卡部分信息
 * @param $bank_number
 * @return array
 */
function bankCard($bank_number)
{
    $url = 'https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?_input_charset=utf-8&cardNo=' . $bank_number . '&cardBinCheck=true';
    $list = [

        "SRCB" => "深圳农村商业银行",
        "BGB" => "广西北部湾银行",
        "SHRCB" => "上海农村商业银行",
        "BJBANK" => "北京银行",
        "WHCCB" => "威海市商业银行",
        "BOZK" => "周口银行",
        "KORLABANK" => "库尔勒市商业银行",
        "SPABANK" => "平安银行",
        "SDEB" => "顺德农商银行",
        "HURCB" => "湖北省农村信用社",
        "WRCB" => "无锡农村商业银行",
        "BOCY" => "朝阳银行",
        "CZBANK" => "浙商银行",
        "HDBANK" => "邯郸银行",
        "BOC" => "中国银行",
        "BOD" => "东莞银行",
        "CCB" => "中国建设银行",
        "ZYCBANK" => "遵义市商业银行",
        "SXCB" => "绍兴银行",
        "GZRCU" => "贵州省农村信用社",
        "ZJKCCB" => "张家口市商业银行",
        "BOJZ" => "锦州银行",
        "BOP" => "平顶山银行",
        "HKB" => "汉口银行",
        "SPDB" => "上海浦东发展银行",
        "NXRCU" => "宁夏黄河农村商业银行",
        "NYNB" => "广东南粤银行",
        "GRCB" => "广州农商银行",
        "BOSZ" => "苏州银行",
        "HZCB" => "杭州银行",
        "HSBK" => "衡水银行",
        "HBC" => "湖北银行",
        "JXBANK" => "嘉兴银行",
        "HRXJB" => "华融湘江银行",
        "BODD" => "丹东银行",
        "AYCB" => "安阳银行",
        "EGBANK" => "恒丰银行",
        "CDB" => "国家开发银行",
        "TCRCB" => "江苏太仓农村商业银行",
        "NJCB" => "南京银行",
        "ZZBANK" => "郑州银行",
        "DYCB" => "德阳商业银行",
        "YBCCB" => "宜宾市商业银行",
        "SCRCU" => "四川省农村信用",
        "KLB" => "昆仑银行",
        "LSBANK" => "莱商银行",
        "YDRCB" => "尧都农商行",
        "CCQTGB" => "重庆三峡银行",
        "FDB" => "富滇银行",
        "JSRCU" => "江苏省农村信用联合社",
        "JNBANK" => "济宁银行",
        "CMB" => "招商银行",
        "JINCHB" => "晋城银行JCBANK",
        "FXCB" => "阜新银行",
        "WHRCB" => "武汉农村商业银行",
        "HBYCBANK" => "湖北银行宜昌分行",
        "TZCB" => "台州银行",
        "TACCB" => "泰安市商业银行",
        "XCYH" => "许昌银行",
        "CEB" => "中国光大银行",
        "NXBANK" => "宁夏银行",
        "HSBANK" => "徽商银行",
        "JJBANK" => "九江银行",
        "NHQS" => "农信银清算中心",
        "MTBANK" => "浙江民泰商业银行",
        "LANGFB" => "廊坊银行",
        "ASCB" => "鞍山银行",
        "KSRB" => "昆山农村商业银行",
        "YXCCB" => "玉溪市商业银行",
        "DLB" => "大连银行",
        "DRCBCL" => "东莞农村商业银行",
        "GCB" => "广州银行",
        "NBBANK" => "宁波银行",
        "BOYK" => "营口银行",
        "SXRCCU" => "陕西信合",
        "GLBANK" => "桂林银行",
        "BOQH" => "青海银行",
        "CDRCB" => "成都农商银行",
        "QDCCB" => "青岛银行",
        "HKBEA" => "东亚银行",
        "HBHSBANK" => "湖北银行黄石分行",
        "WZCB" => "温州银行",
        "TRCB" => "天津农商银行",
        "QLBANK" => "齐鲁银行",
        "GDRCC" => "广东省农村信用社联合社",
        "ZJTLCB" => "浙江泰隆商业银行",
        "GZB" => "赣州银行",
        "GYCB" => "贵阳市商业银行",
        "CQBANK" => "重庆银行",
        "DAQINGB" => "龙江银行",
        "CGNB" => "南充市商业银行",
        "SCCB" => "三门峡银行",
        "CSRCB" => "常熟农村商业银行",
        "SHBANK" => "上海银行",
        "JLBANK" => "吉林银行",
        "CZRCB" => "常州农村信用联社",
        "BANKWF" => "潍坊银行",
        "ZRCBANK" => "张家港农村商业银行",
        "FJHXBC" => "福建海峡银行",
        "ZJNX" => "浙江省农村信用社联合社",
        "LZYH" => "兰州银行",
        "JSB" => "晋商银行",
        "BOHAIB" => "渤海银行",
        "CZCB" => "浙江稠州商业银行",
        "YQCCB" => "阳泉银行",
        "SJBANK" => "盛京银行",
        "XABANK" => "西安银行",
        "BSB" => "包商银行",
        "JSBANK" => "江苏银行",
        "FSCB" => "抚顺银行",
        "HNRCU" => "河南省农村信用",
        "COMM" => "交通银行",
        "XTB" => "邢台银行",
        "CITIC" => "中信银行",
        "HXBANK" => "华夏银行",
        "HNRCC" => "湖南省农村信用社",
        "DYCCB" => "东营市商业银行",
        "ORBANK" => "鄂尔多斯银行",
        "BJRCB" => "北京农村商业银行",
        "XYBANK" => "信阳银行",
        "ZGCCB" => "自贡市商业银行",
        "CDCB" => "成都银行",
        "HANABANK" => "韩亚银行",
        "CMBC" => "中国民生银行",
        "LYBANK" => "洛阳银行",
        "GDB" => "广东发展银行",
        "ZBCB" => "齐商银行",
        "CBKF" => "开封市商业银行",
        "H3CB" => "内蒙古银行",
        "CIB" => "兴业银行",
        "CRCBANK" => "重庆农村商业银行",
        "SZSBK" => "石嘴山银行",
        "DZBANK" => "德州银行",
        "SRBANK" => "上饶银行",
        "LSCCB" => "乐山市商业银行",
        "JXRCU" => "江西省农村信用",
        "ICBC" => "中国工商银行",
        "JZBANK" => "晋中市商业银行",
        "HZCCB" => "湖州市商业银行",
        "NHB" => "南海农村信用联社",
        "XXBANK" => "新乡银行",
        "JRCB" => "江苏江阴农村商业银行",
        "YNRCC" => "云南省农村信用社",
        "ABC" => "中国农业银行",
        "GXRCU" => "广西省农村信用",
        "PSBC" => "中国邮政储蓄银行",
        "BZMD" => "驻马店银行",
        "ARCU" => "安徽省农村信用社",
        "GSRCU" => "甘肃省农村信用",
        "LYCB" => "辽阳市商业银行",
        "JLRCU" => "吉林农信",
        "URMQCCB" => "乌鲁木齐市商业银行",
        "XLBANK" => "中山小榄村镇银行",
        "CSCB" => "长沙银行",
        "JHBANK" => "金华银行",
        "BHB" => "河北银行",
        "NBYZ" => "鄞州银行",
        "LSBC" => "临商银行",
        "BOCD" => "承德银行",
        "SDRCU" => "山东农信",
        "NCB" => "南昌银行",
        "TCCB" => "天津银行",
        "WJRCB" => "吴江农商银行",
        "CBBQS" => "城市商业银行资金清算中心",
        "HBRCU" => "河北省农村信用社"
    ];
    $temp = file_get_contents($url);
    $arr = json_decode($temp, true);
    $res['bank_name'] = $list[$arr['bank']] ? $list[$arr['bank']] : '未知银行';
    $res['bank_code'] = $arr['bank'] ? $arr['bank'] : '';
    $res['type'] = $arr['cardType'] ? $arr['cardType'] : '';
    $type_arr = ['DC' => '储蓄卡', 'CC' => '信用卡', 'SCC' => '准贷记卡', 'PC' => '预付费卡'];
    $res['type_name'] = $type_arr[$arr['cardType']] ? $type_arr[$arr['cardType']] : '未知类型';
    $res['bank_logo'] = 'https://apimg.alipay.com/combo.png?d=cashier&t=' . $res['bank_code'];
    $res['bank_card'] = $arr['key'] ? $arr['key'] : '';
    return $res;
}

/**
 * 简单发送post请求
 * @param string $url 请求地址
 * @param array $post_data post键值对数据
 * @return string
 */
function send_post($url, $post_data = [])
{

    $data = http_build_query($post_data);
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => $data,
            'timeout' => 30 // 超时时间（单位:s）
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    return $result;
}

/**
 * 简单设置关联数组
 * @param $data
 * @param string $index
 * @param array $kv ['k'=>$key,'v'=>$val]
 * @return array
 */
function arrayIndex($data, $index = 'id', $kv = [])
{
    $res = [];
    foreach ($data as $v) {
        if ($kv) {
            if (isset($kv['k']) && isset($kv['v'])) {
                $res[$v[$kv['k']]] = $v[$kv['v']];
            } else {
                $res[$v[$kv[0]]] = $v[$kv[1]];
            }

        } else {
            $res[$v[$index]] = $v;
        }
    }
    return $res;
}

/**
 * 格式化浮点数保留小数点位数
 * @param string|array $data
 * @param int $decimal 保留位数
 * @param $symbol bool 是否显示正负号
 * @return string|array
 */
function floatingFormate($data, $decimal = 2, $symbol = false)
{
    $format = '%.' . $decimal . 'f';
    if ($symbol) {
        $format = '%+.' . $decimal . 'f';
    }
    if (is_array($data)) {
        $data = [];
        foreach ($data as &$datum) {
            $datum = sprintf($format, $datum);
        }
    } else {
        $data = sprintf($format, $data);
    }
    return $data;
}
