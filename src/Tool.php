<?php


namespace Jxm\Tool;

use Jxm\Tool\ErrorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Tool
{
    protected static $error_msg;
    const JSON = 'Json';

    /**
     * Notes: 格式化返回结果
     * User: harden - 2021/8/24 下午6:21
     * @param Request $request
     * @param bool|array|null $result 结果数据
     * @param int $code 结果状态码
     * @param string $msg 消息
     * @param int $type 返回类型 1:object,2:array,3:json,4:xml
     * @return false|mixed|string
     */
    public static function formatOutput(Request $request, $result, $code = ErrorCode::ERR_OK, $msg = '操作成功', $type = 3)
    {
        if (is_bool($result)) {
            $result = [
                'code' => $result ? 0 : ErrorCode::ERR_UNKNOW,
                'msg' => $result ? '操作成功' : '操作失败',
            ];
        } elseif (is_null($result)) {
            $result = [
                'code' => 0,
                'msg' => '操作成功',
            ];
        }
        if (Arr::has($result, 'code')) {
            $code = $result['code'];
        }
        return self::commonDataInput($code, $result, $type, Arr::has($result, 'msg') ? $result['msg'] : $msg);
    }


    /**
     * 统一的接口输入
     * @param integer $code 错误代码
     * @param string $data 返回数据
     * @param integer $type 返回类型 1:object,2:array,3:json,4:xml
     * @param string $msg 自定义返回信息
     * @return mixed
     */
    private static function commonDataInput(int $code, $data = '', $type = 3, $msg = '')
    {
        $type = self::getRealType($type);

        $return = [];

        $code = empty($type) ? 5 : $code;
        $is_legal = is_int($code) ? true : false;
        if (!$is_legal) {
            $code = 5;
        } else {
            $ver = 'beta001';
            $return['code'] = $code;
            $return['msg'] = $msg;
            $return['version'] = $ver ?: date("Ymd") . '001';
            $return['data'] = $data;
        }

        if (1 == $type) {//对象
            dump((object)($return));
        } elseif (2 == $type) {//数组
            dump($return);
        } elseif (3 == $type) {//默认json格式
//         Tool::setReturnLog($return);
            return response()->json($return);
        } elseif (4 == $type) {//xml
            self::xmlEncode($code, $msg, $data);
        } else {
            return response()->json($return);
        }
    }

    /**
     * 按xml方式输出通信数据
     * @param integer $code 状态码
     * @param string $message 提示信息
     * @param array $data 数据
     * @return string
     */
    public static function xmlEncode($code, $message, $data = array())
    {
        if (!is_numeric($code)) {
            return '';
        }

        $result = array(
            'code' => $code,
            'msg' => $message,
            'data' => $data,
        );

        header("Content-Type:text/xml");
        // date_default_timezone_set(PRC);
        $xml = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $xml .= "<root>\n";

        $xml .= self::xmlToEncode($result);

        $xml .= "</root>";
        echo $xml;
    }

    public static function getRealType($type = self::JSON)
    {
        if (is_numeric($type)) {
            return $type;
        }
        $type = ucwords(strtolower($type));
        $type_arr = ['Object' => 1, 'Array' => 2, 'Json' => 3, 'Xml' => 4];
        $real_type = isset($type_arr[$type]) ? $type_arr[$type] : '';
        return $real_type;
    }
}
