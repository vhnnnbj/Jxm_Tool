<?php


namespace Jxm\Tool\CommonClass;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

trait RecordTemplate
{
    use SoftDeletes;

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    abstract function editor(): BelongsTo;

    /**
     * Notes: 写日志
     * User: harden - 2021/9/14 下午2:48
     * @param $type
     * @param $subtype
     * @param $state
     * @param $param
     * @param $describe
     * @param null $editor_id
     * @param null $val_num
     * @param null $val_str
     * @param null $val_time
     * @return static
     */
    public static function makeRecord($type, $subtype, $state, $param, $describe,
                                      $editor_id = null, $val_num = null, $val_str = null, $val_time = null)
    {
        $record = new static([
            'type' => $type,
            'subtype' => $subtype,
            'state' => $state,
            'describe' => $describe,
            'editor_id' => $editor_id,
            'param' => is_array($param) ? json_encode($param) : $param,
            'val_num' => $val_num,
            'val_str' => $val_str,
            'val_time' => $val_time,
        ]);
        $record->save();
        return $record;
    }
}
