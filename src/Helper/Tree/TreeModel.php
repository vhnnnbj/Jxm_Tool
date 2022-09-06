<?php

namespace Jxm\Tool\Helper\Tree;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
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
        if (!$model) return false;
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
            $allNodes = array_column($allTrees->toArray(), null, 'id');
        } elseif ($allTrees instanceof Collection) {
            $allNodes = array_column($allTrees->toArray(), null, 'id');
        } else {
            $allNodes = $allTrees;
        }
        $allIds = [$this->id];
        do {
            $newAllIds = $allIds;
            $subDepartments = Arr::pluck(Arr::where($allNodes, function ($q) use ($allIds) {
                return in_array($q[$this->key_parent], $allIds);
            }), 'id');
            $allIds = array_unique(array_merge($newAllIds, $subDepartments));
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
        if (!$model) return [];
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
            $allNodes = array_column($allTrees->toArray(), null, 'id');
        } elseif ($allTrees instanceof Collection) {
            $allNodes = array_column($allTrees->toArray(), null, 'id');
        } else {
            $allNodes = $allTrees;
        }
        $allIds = [];
        $node = $allNodes[$this->id];
        $allIds[] = $this->id;
        while ($node[$this->key_parent] != 0 && $node[$this->key_parent] != $node['id']) {
            $node = $allNodes[$node[$this->key_parent]];
            $allIds[] = $node['id'];
        }
        return $allIds;
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
        if (!$model) return [];
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
        $use_static = false;
        if (is_null($allTrees)) {
            $allTrees = static::get(['id', $this->key_name, $this->key_parent]);
            $allNodes = array_column($allTrees->toArray(), null, 'id');
            $use_static = true;
        } elseif ($allTrees instanceof Collection) {
            $allNodes = array_column($allTrees->toArray(), null, 'id');
        } else {
            $allNodes = $allTrees;
        }
        $node = $allNodes[$this->id];
        while ($node[$this->key_parent] != 0 && $node[$this->key_parent] != $node['id']) {
            $node = $allNodes[$node[$this->key_parent]];
        }
        return $use_static ? (static::find($node['id'])) : (($allTrees instanceof Collection) ? $allTrees->where('id', $node['id'])->first() :
            $allTrees[$node['id']]);
    }

    /**
     * Notes: 获取所属公司
     * User: harden - 2021/9/28 下午2:15
     * @param null $allTrees
     * @param null $field
     * @return mixed
     */
    public static function getTopModel($model_id, $allTrees = null)
    {
        $model = static::whereId($model_id)->first();
        if (!$model) return null;
        return $model->getTop($allTrees);
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

    public static function dealArrayTrees(array|null $allNodes = null, int $node_id = null)
    {
        $tmp = new static();
        if (is_null($allNodes)) {
            $allNodes = self::getAllArrayInfoToRedis(self::RedisKey_All());
        }
        foreach ($allNodes as $key => $node) {
            $allNodes[$key]['children'] = [];
        }
        $done = [];
        do {
            $leafs = Arr::where($allNodes, function ($item) use ($allNodes, $tmp, $done) {
                return !in_array($item['id'], Arr::pluck($allNodes, $tmp->key_parent)) &&
                    !in_array($item['id'], $done);
            });
            foreach ($leafs as $leaf) {
                if ($leaf[$tmp->key_parent] != 0 && $leaf[$tmp->key_parent] != $leaf['id']) {
                    $allNodes[$leaf[$tmp->key_parent]]['children'][] = $leaf;
                    unset($allNodes[$leaf['id']]);
                }
                $done[] = $leaf['id'];
            }
        } while ($leafs);
        return array_values($allNodes);
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

    public static function getAllArray()
    {
        return array_column(self::getAllInfoToRedis(self::RedisKey_All())
            ->toArray(), null, 'id');
    }

    public static function getAllInfoToRedis($keyName, $relations = [], $relation_keys = [], $withCounts = [],
                                             $time = 2 * 3600, $deal_call = null): Collection
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

    public static function getAllArrayInfoToRedis($keyName, $relations = [], $relation_keys = [], $withCounts = [],
                                                  $time = 2 * 3600, $deal_call = null): array
    {
        $infos = self::getAllInfoToRedis($keyName, $relations = [], $relation_keys = [], $withCounts = [],
            $time = 2 * 3600, $deal_call = null);
        $array = array_column($infos->toArray(), null, 'id');
        return $array;
    }

    public static function clearCache()
    {
        Redis::del(self::RedisKey_All());
        Redis::del(self::RedisKey_Simple());
    }
    #endregion

}
