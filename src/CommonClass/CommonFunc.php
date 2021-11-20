<?php

/**
 * 匹配模块路由
 * @param string $module 模块名
 * @param string $controller 控制器名，不带controller
 * @param int $type 1,普通api接口，2，hprose服务
 */
function routeModuleHelper(string $module, string $controller, $type = 1)
{
    $folders = scandir(base_path() . '/Modules');
    foreach ($folders as $folder) {
        if (strtolower($folder) == strtolower($module)) {
            $module = $folder;
            break;
        }
    }
    if ($type == 1) {
        $files = scandir(base_path() . '/Modules/' . $module . '/Http/Controllers');
        foreach ($files as $file) {
            if (strtolower($file) == (strtolower($controller) . 'controller.php')) {
                $class = 'Modules\\' . $module . '\\Http\\Controllers\\' . $file;
                //echo $class;
                break;
            }
        }
    } elseif ($type == 2) {
        $files = scandir(base_path() . '/Modules/' . $module . '/Services');
        foreach ($files as $file) {
            if (strtolower($file) == (strtolower($controller) . 'service.php')) {
                $class = 'Modules\\' . $module . '\\Services\\' . $file;
                //echo $class;
                break;
            }
        }
    } else {
        abort(404);
    }
    $class = substr($class, 0, strlen($class) - 4);
    return $class;
}

/**
 * 匹配App/Http接口路由
 * @param $controller 控制器名，不带controller
 * @param bool $is_api 是否API调用接口
 * @param int $type 1,普通api接口，2，hprose服务
 */
function routeHelper($controller, $is_api = false, $type = 1)
{
    if ($type == 1) {
        $files = scandir(base_path() . '/app/Http/Controllers' . ($is_api ? '/Api' : ''));
        foreach ($files as $file) {
            if (strtolower($file) == (strtolower($controller) . 'controller.php')) {
                $class = 'App\\Http\\Controllers\\' . ($is_api ? 'Api\\' : '') . $file;
                //echo $class;
                break;
            }
        }
    } elseif ($type == 2) {
        $files = scandir(base_path() . '/app/Http/Services');
        foreach ($files as $file) {
            if (strtolower($file) == (strtolower($controller) . 'service.php')) {
                $class = 'App\\Http\\Services\\' . $file;
                //echo $class;
                break;
            }
        }
    } else {
        abort(404);
    }
    $class = substr($class, 0, strlen($class) - 4);
    return $class;
}

function makeErrorMsg(string $msg, int $code, $object = null)
{
    return [
        'code' => $code,
        'msg' => $msg,
        'object' => $object,
    ];
}

/**
 * Notes: 发企业微信消息
 * User: harden
 * Date: 2020/12/18
 * Time:10:14
 * @param $msg
 * @param string|null $url
 * @param null $type
 * @return mixed
 */
function sendWebHookMsg($msg, string $url = null, $type = 1)
{
    switch ($type) {
        case 1:
            $url = $url ?: env('WEBHOOK_PRODUCTALARM');
            break;
        case 2:
            $url = $url ?: env('ERROR_ALARM');
            break;
    }
//    echo $url . "\r\n" . $msg . "\r\n";
    if ($url) {
        $client = new \GuzzleHttp\Client();
        $client->request('POST', $url, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'msgtype' => 'text',
                'text' => [
                    'content' => $msg,
                ],
            ],
            'timeout' => 20,
        ]);
    }
}

/**
 * 判断文本是否序列化
 * @param $data
 * @return bool
 */
function is_serialized($data)
{
    // if it isn't a string, it isn't serialized
    if (!is_string($data))
        return false;
    $data = trim($data);
    if ('N;' == $data)
        return true;
    if (!preg_match('/^([adObis]):/', $data, $badions))
        return false;
    switch ($badions[1]) {
        case 'a' :
        case 'O' :
        case 's' :
            if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                return true;
            break;
        case 'b' :
        case 'i' :
        case 'd' :
            if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                return true;
            break;
    }
    return false;
}

/**
 * 时间日期格式化
 * @param $datetime
 * @return false|string
 */
function DatetimeFormat($datetime)
{
    if (is_null($datetime)) return "";
    if (gmdate("Y", strtotime($datetime) == date("Y")))
        return date("m月d日 H:i", strtotime($datetime));
    else
        return date("y年m月d日 H:i", strtotime($datetime));
}

/**
 * Notes: 身份证号隐藏
 * User: harden
 * Date: 2020/10/16
 * Time:16:55
 * @param string $identity
 * @return string
 */
function hideIdentity(string $identity)
{
    if (strlen($identity) > 7)
        return Str::substr($identity, 0, 5) . '****' . Str::substr($identity, Str::length($identity) - 6);
    else
        return $identity;
}

/**
 * Notes: 手机号隐藏
 * User: harden
 * Date: 2020/10/16
 * Time:16:55
 * @param string $phone
 * @return string
 */
function hidePhoneNumber(string $phone)
{
    if (strlen($phone) > 7)
        return Str::substr($phone, 0, 3) . '****' . Str::substr($phone, Str::length($phone) - 4);
    else
        return $phone;
}

/**
 * Notes: 微信号隐藏
 * User: harden
 * Date: 2020/10/16
 * Time:16:55
 * @param string $wxid
 * @return string
 */
function hideWxid(string $wxid)
{
    if (!preg_match('/^[-_a-zA-Z0-9]{6,20}$/', $wxid)) {
        return '微信号格式错误:' . $wxid;
    }
    if (strlen($wxid) > 5)
        return substr($wxid, 0, 2) . '****' . substr($wxid, strlen($wxid) - 3);
    elseif (strlen($wxid) > 2)
        return substr('*****', 0, strlen($wxid) - 2) . substr($wxid, strlen($wxid) - 2);
    else
        return $wxid;
}

/**
 * Notes: 隐藏实名
 * User: harden - 2021/8/25 下午12:01
 * @param string $name
 * @return string
 */
function hideName(string $name)
{
//    return $name;
    if (mb_strlen($name) >= 3)
        return mb_substr($name, 0, 1) . '*' . mb_substr($name, mb_strlen($name) - 1);
    else
        return '*' . mb_substr($name, mb_strlen($name) - 1);
}

// obj 转 query string
function json2querystr($obj, $notEncode = false)
{
    ksort($obj);
    $arr = array();
    if (!is_array($obj)) {
        throw new \Exception($obj + " must be a array");
    }
    foreach ($obj as $key => $val) {
        array_push($arr, $key . '=' . ($notEncode ? $val : rawurlencode($val)));
    }
    return join('&', $arr);
}

function formatRecordTableColumn(\Illuminate\Database\Schema\Blueprint $table)
{
    $table->nullableUuidMorphs('model', 'model');
    $table->unsignedTinyInteger('type')->comment('记录类型')->index();
    $table->string('subtype')->comment('记录子类型')->index();
    $table->unsignedSmallInteger('state')->nullable()->comment('可用标志状态');
    $table->text('param')->nullable()->comment('记录参数');
    $table->string('describe')->nullable()->comment('记录简述');
    $table->unsignedBigInteger('editor_id')->nullable()->comment('编辑人ID');
    $table->decimal('val_num', 10, 2)->nullable()->comment('备用数值');
    $table->string('val_str', 4096)->nullable()->comment('备用字符串');
    $table->dateTime('val_time')->nullable()->comment('备用时间');
    $table->softDeletes();
}

const RecordFillable = [
    'model_id',
    'model_type',
    'type',
    'subtype',
    'state',
    'describe',
    'editor_id',
    'param',
    'val_num',
    'val_str',
    'val_time',
];
