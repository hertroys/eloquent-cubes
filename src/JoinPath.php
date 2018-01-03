<?php

namespace Hertroys\Cubes;

class JoinPath
{
    public $models = [];

    public $type;

    public $query;

    protected $cols = [];

    public function __construct($from, $path, $type = Joinery::INNER)
    {
        $this->models[] = $from;
        $this->type = $type;

        $this->query = $from->newQuery();
        $this->route($from, $path);
    }

    public function route($cursor, $path)
    {
        foreach ($path as $step) {
            $cursor = $this->next($cursor->$step());
            $this->models[] = $cursor;
        }
    }

    protected function next($relation)
    {
        $next = $relation->getRelated();

        $this->query->{$this->type}(
            $next->getTable(),
            $relation->getQualifiedForeignKey(), '=',
            $relation->getQualifiedOwnerKeyName()
        );

        return $next;
    }

    public function addColumns($cols = [])
    {
        array_walk($cols, [$this, 'addColumn']);
    }

    public function addColumn($col)
    {
        if (! in_array($col, $this->cols)) {
            $this->query->addSelect($this->to()->getTable().'.'.$col);
            $this->cols[] = $col;
        }
    }

    public function from()
    {
        return reset($this->models);
    }

    public function to()
    {
        return end($this->models);
    }
}
