<?php
namespace Owl\DataMapper;

/**
 * @example
 * class MyMapper extends \Owl\DataMapper\DB\Mapper {
 *     use \Owl\DataMapper\CacheMapper;
 *
 *     protected function getCache($id) {
 *         // ...
 *     }
 *
 *     protected function deleteCache($id) {
 *         // ...
 *     }
 *
 *     protected function saveCache($id, array $record) {
 *         // ...
 *     }
 * }
 *
 * class MyData extends \Owl\DataMapper\Data {
 *     static protected $mapper = 'MyMapper';
 *
 *     static protected $mapper_options = [
 *         'service' => 'my.db',
 *         'collection' => 'tablename',
 *         'cache_policy' => [
 *             'insert' => false,       // create cache after insert, default disable
 *             'update' => false,       // update cache after update, default disable
 *         ],
 *     ];
 *
 *     static protected $attributes = [
 *         // ...
 *     ];
 * }
 */
trait CacheMapper {
    /**
     * @param mixed $id
     * @return array|false
     */
    abstract protected function getCache($id);

    /**
     * @param mixed $id
     * @return boolean
     */
    abstract protected function deleteCache($id);

    /**
     * @param mixed $id
     * @param array $record
     * @return boolean
     */
    abstract protected function saveCache($id, array $record);

    /**
     * create cache after save new data, if cache policy set
     *
     * @param \Owl\DataMapper\Data $data
     * @return void
     */
    protected function __afterInsert(\Owl\DataMapper\Data $data) {
        $policy = $this->getCachePolicy();

        if ($policy['insert']) {
            $id = $data->id();
            $record = $this->unpack($data);
            $record = $this->normalizeCacheRecord($record);

            $this->saveCache($id, $record);
        }

        parent::__afterInsert($data);
    }

    /**
     * delete or update cache after save data
     *
     * @param \Owl\DataMapper\Data $data
     * @return void
     */
    protected function __afterUpdate(\Owl\DataMapper\Data $data) {
        $policy = $this->getCachePolicy();

        if ($policy['update']) {
            $id = $data->id();
            $record = $this->unpack($data);
            $record = $this->normalizeCacheRecord($record);

            $this->saveCache($id, $record);
        } else {
            $this->deleteCache($data->id());
        }

        parent::__afterUpdate($data);
    }

    /**
     * delete cache after delete data
     *
     * @param \Owl\DataMapper\Data $data
     * @return void
     */
    protected function __afterDelete(\Owl\DataMapper\Data $data) {
        $this->deleteCache($data->id());

        parent::__afterDelete($data);
    }

    /**
     * delete cache before refresh data
     *
     * @param \Owl\DataMapper\Data $data
     * @return \Owl\DataMapper\Data
     */
    public function refresh(\Owl\DataMapper\Data $data) {
        $this->deleteCache($data->id());

        return parent::refresh($data);
    }

    /**
     * 获得缓存策略配置
     *
     * @return integer
     */
    protected function getCachePolicy() {
        $defaults = [
            'insert' => false,
            'update' => false,
        ];

        if (!$this->hasOption('cache_policy')) {
            return $defaults;
        }


        $policy = $this->getOption('cache_policy');

        if (is_array($policy)) {
            return array_merge($defaults, $policy);
        } else {
            if (DEBUG) {
                throw new \Exception('Invalid cache policy setting');
            }

            return $defaults;
        }
    }

    /**
     * return record from cache if cache is created, or save data into cache
     *
     * @param mixed $id
     * @param \Owl\Service $service
     * @param string $collection
     * @return array
     */
    protected function doFind($id, \Owl\Service $service = null, $collection = null) {
        if ($record = $this->getCache($id)) {
            return $record;
        }

        if (!$record = parent::doFind($id, $service, $collection)) {
            return $record;
        }

        $record = $this->normalizeCacheRecord($record);
        $this->saveCache($id, $record);

        return $record;
    }

    /**
     * remove NULL value from record
     *
     * @param array $record
     * @return array
     */
    protected function normalizeCacheRecord(array $record) {
        // 值为NULL的字段不用缓存
        foreach ($record as $key => $val) {
            if ($val === null) {
                unset($record[$key]);
            }
        }

        return $record;
    }
}
