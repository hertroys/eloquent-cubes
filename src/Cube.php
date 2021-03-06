<?php

namespace Hertroys\Cubes;

use Hertroys\Aggregator\Aggregator;
use Illuminate\Database\Eloquent\Model;

class Cube extends Aggregator
{
    public $model;

    public $joinery;

    public function __construct(Model $model)
    {
        $this->joinery = new Joinery;

        $this->model = $model;

        parent::__construct($model->getConnection());

        $this->table($model->getTable());
    }

    public function getModel()
    {
        return $this->model;
    }

    public function get()
    {
        $this->joinery->compile($this);

        return parent::get();
    }

    public function toSql()
    {
        $this->joinery->compile($this);

        return parent::toSql();
    }

    public function getBindings()
    {
        $this->joinery->compile($this);

        return parent::getBindings();
    }

    public function joinTo($path, $cols = [], $type = Joinery::INNER)
    {
        $this->joinery->join($this, $path, $cols, $type);

        return $this;
    }

    public function leftJoinTo($path, $cols = [])
    {
        return $this->joinTo($path, $cols, Joinery::LEFT);
    }

    protected function joinColumn($path)
    {
        if (! strpos($path, '.')) return $path;

        $segments = explode('.', $path);

        $col = array_pop($segments);

        $this->joinTo(implode('.', $segments), [$col]);

        return $this->joinery->alias($path);
    }

    public function groupBy(...$groups)
    {
        foreach ($groups as $group) {
            parent::groupBy($this->joinColumn($group));
        }

        return $this;
    }

    public function where($path, $operator = null, $value = null, $boolean = 'and')
    {
        return parent::where($this->joinColumn($path), $operator, $value, $boolean);
    }

    public function whereNull($path, $boolean = 'and', $not = false)
    {
        return parent::whereNull($this->joinColumn($path), $boolean, $not);
    }

    public function whereIn($path, $values, $boolean = 'and', $not = false)
    {
        return parent::whereIn($this->joinColumn($path), $values, $boolean, $not);
    }

    public function whereBetween($path, array $values, $boolean = 'and', $not = false)
    {
        return parent::whereBetween($this->joinColumn($path), $values, $boolean, $not);
    }

    public function orderBy($path, $direction = 'asc')
    {
        return parent::orderBy($this->joinColumn($path), $direction);
    }
}
