<?php

namespace Modules\Scenarios\Models\_Abstracts;

use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use Uuid;
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getSearchField()
    {
        $staticObj = new static;
        if (empty($staticObj->search_fields)) {
            return $staticObj->fillable;
        }
        return $staticObj->search_fields;
    }

    public function getFilterFields()
    {
        $staticObj = new static;
        if (empty($staticObj->filter_fields)) {
            return $staticObj->fillable;
        }
        return $staticObj->filter_fields;
    }
}
