<?php

namespace Hertroys\Cubes;

class Joinery
{
    public $joins = [];

    const INNER = 'join';
    const LEFT  = 'leftJoin';
    const RIGHT = 'rightJoin';
    const CROSS = 'crossJoin';

    public function join($cube, $path, $cols = [], $type = self::INNER)
    {
        $alias = $this->alias($path);

        if (! array_key_exists($alias, $this->joins)) {
            $this->joins[$alias] = $this->setup($cube, $path, $type);
        }

        $this->joins[$alias]->addColumns($this->aliasColumns($cols, $alias));
    }

    public function compile($cube)
    {
        foreach ($this->joins as $alias => $join) {
            $this->compileJoin($cube, $join, $alias);
        }
    }

    protected function setup($cube, $path, $type)
    {
        $steps = explode('.', head(explode(' as ', $path)));
        $glue = $cube->model->{array_shift($steps)}();

        $join = new JoinPath($glue->getRelated(), $steps, $type);
        $join->glue = $glue;
        return $join;
    }

    protected function compileJoin($cube, $join, $alias)
    {
        $join->query->addSelect(
            $join->from()->getQualifiedKeyName().' as '.$alias.'_joinkey'
        );

        $cube->query->{$join->type}(
            app('db')->raw("({$join->query->toSql()}) as {$cube->wrap($alias)}"),
            $join->glue->getQualifiedForeignKey(), '=', "$alias.{$alias}_joinkey"
        );
    }

    public function alias($path)
    {
        return str_replace('.', '_', last(explode(' as ', $path)));
    }

    protected function aliasColumns($cols = [], $prefix)
    {
        $aliased = [];
        foreach ($cols as $col) {
            $aliased[] = $this->aliasColumn($col, $prefix);
        }
        return $aliased;
    }

    protected function aliasColumn($col, $prefix)
    {
        return strpos($col, ' as ') ? $col : $col.' as '.$prefix.'_'.$col;
    }
}
