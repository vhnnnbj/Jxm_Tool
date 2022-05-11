<?php

namespace Jxm\Tool\Helper\Tree;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

trait TreeModel
{
    public $key_parent = 'parent_id';
    public $key_name = 'name';

    #region Relations
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, $this->key_parent);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, $this->key_parent);
    }

    #endregion

    #region Functions
    public function isTop()
    {
        return $this[$this->key_parent] == 0;
    }

    public static function checkTop($model_id)
    {
        $model = static::whereId($model_id)->first();
        return $model->isTop();
    }

    /**
     * Notes: 获取子类型汇总id
     * User: harden - 2021/9/28 下午2:15
     * @param null $allTrees
     * @param null $field
     * @return array
     */
    public function getAllChildrenIds($allTrees = null)
    {
        if (is_null($allTrees)) {
            $allTrees = static::get(['id', $this->key_parent]);
        }
        $allIds = [$this->id];
        do {
            $newAllIds = $allIds;
            $subDepartments = $allTrees->whereIn($this->key_parent, $allIds)->pluck('id');
            $allIds = array_unique(array_merge($newAllIds, $subDepartments->toArray()));
        } while (sizeof($newAllIds) != sizeof($allIds));
        return $allIds;
    }

    /**
     * Notes: static获取子类型汇总id
     * User: harden - 2021/9/28 下午2:15
     * @param null $allTrees
     * @param null $field
     * @return array
     */
    public static function getAllSubIds($model_id, $allTrees = null)
    {
        $model = static::whereId($model_id)->first();
        return $model->getAllChildrenIds($allTrees);
    }

    /**
     * Notes: 获取上级部门id
     * User: harden - 2021/9/28 下午2:15
     * @param null $allTrees
     * @param null $field
     * @return array
     */
    public function getUpGrades($allTrees = null)
    {
        if (is_null($allTrees)) {
            $allTrees = static::get(['id', $this->key_parent]);
        }
        $allIds = new Collection();
        $node = $allTrees->where('id', $this->id)->first();
        $allIds->add($this->id);
        while ($node[$this->key_parent] != 0) {
            $node = $allTrees->where('id', $node[$this->key_parent])->first();
            $allIds->add($node->id);
        }
        return $allIds->unique()->toArray();
    }

    /**
     * Notes: 获取上级部门id
     * User: harden - 2021/9/28 下午2:15
     * @param null $allTrees
     * @param null $field
     * @return array
     */
    public static function getAllUpGrades($model_id, $allTrees = null)
    {
        $model = static::whereId($model_id)->first();
        return $model->getUpGrades($allTrees);
    }

    /**
     * Notes: 获取所属公司
     * User: harden - 2021/9/28 下午2:15
     * @param null $allTrees
     * @param null $field
     * @return mixed
     */
    public function getTop($allTrees = null)
    {
        if (is_null($allTrees))
            $allTrees = static::get(['id', $this->key_name, $this->key_parent]);
        $node = $allTrees->where('id', $this->id)->first();
        while ($node[$this->key_parent] != 0 && $node[$this->key_parent] != $node->id) {
            $node = $allTrees->where('id', $node[$this->key_parent])->first();
        }
        return static::find($node->id);
    }

    /**
     * Notes: 获取所属公司
     * User: harden - 2021/9/28 下午2:15
     * @param null $allTrees
     * @param null $field
     * @return mixed
     */
    public function getTopModel($model_id, $allTrees = null)
    {
        $model = static::whereId($model_id)->first();
        return $model->getTope($allTrees);
    }
    #endregion

    #region static Functions
    public static function dealTrees($allModels, $node_id = null)
    {
        $tmp = new static();
        if ($node_id) {
            $tree = $allModels->where('id', $node_id)->first();
            $children = $allModels->where($tmp->key_parent, $node_id);
            $result = [];
            foreach ($children as $child) {
                $result[] = self::dealTrees($allModels, $child->id);
            }
            $tree->children = $result;
            return $tree;
        } else {
//            $trees = new Collection();
            $tops = $allModels->whereNotIn($tmp->key_parent, $allModels->pluck('id'))
                ->values();
            $tops->transform(function ($item) use ($allModels, $tmp) {
                $result = [];
                $children = $allModels->where($tmp->key_parent, $item->id);
                foreach ($children as $child) {
                    $result[] = self::dealTrees($allModels, $child->id);
                }
                $item->children = $result;
                return $item;
            });
            return $tops;
        }
    }

    public static function RedisKey_All()
    {
        return null;
    }

    public static function RedisKey_Simple(): string
    {
        return self::RedisKey_All();
    }

    public static function getAll()
    {
        return self::getAllInfoToRedis(self::RedisKey_All());
    }

    public static function getAllInfoToRedis($keyName, $relations = [], $relation_keys = [], $withCounts = [],
                                             $time = 2 * 3600, $deal_call = null)
    {
        $infos = null;
        if ($keyName)
            $infos = Redis::get($keyName);
        if (!$infos) {
            $infos = self::with($relations)->withCount($withCounts)->get();
            if ($deal_call) {
                $deal_call($infos);
            }
            if ($keyName)
                Redis::setex($keyName, 2 * 3600, json_encode($infos->toArray()));
        } else {
            $cached_infos = json_decode($infos, true);
            $infos = new Collection();
            foreach ($cached_infos as $info) {
                $item = new static($info);
                $item->id = $info['id'];
                foreach ($relation_keys as $key) {
                    $item->setRelation($key, collect($info[$key]));
                }
//                $item->editor = collect($info['editor']);
                foreach ($withCounts as $key) {
                    $item[$key . '_count'] = $info[$key . '_count'];
                }
                $infos->add($item);
            }
        }
        return $infos;
    }

    public static function clearCache()
    {
        Redis::del(self::RedisKey_All());
        Redis::del(self::RedisKey_Simple());
    }
    #endregion

}
