<?php

namespace Jxm\Tool;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ErrorCode
{
    const ERR_OK = 0;

    const ERR_PARAMWRONG = 1;

    const ERR_PARAMNOTFULL = 2;

    const ERR_NODATA = 3;

    const ERR_DATAEXIST = 4;

//非法操作
    const ERR_ILLEGAL = 5;

    const ERR_UNKNOW = 6;

    const ERR_REQUISITE = 7;

    const ERR_USERDEFINED = 8;

    const ERR_TIMED_OUT = 9;

    const ERR_UNLOGIN = 10;

//过期（机构有效期）
    const ERR_OVERDUE = 11;

    //订单查验错误
    const ERR_ORDERCHECKFAIL = 20;

    const NEED_REFRESH = 99;

//需要重新登陆 (学员卡注销后调用)
    const NEED_RELOGIN = 100;


//用户等待中(比如支付中)
    const USER_WAITING = 199;

//设置成家长身份
    const NEED_SET_PARENTS = 101;

//设置成老师身份
    const NEED_SET_TEACHER = 102;

//需要重新获取openID
    const NEED_GET_OPENID = 119;
}
