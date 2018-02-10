# Eloquent Cubes

Eloquent cubes simplify querying for measures and filtering/grouping/ordering by dimensions when using the [Eloquent ORM](https://laravel.com/docs/eloquent). The Cube extends from the [laravel-aggregate-builder](https://github.com/hertroys/laravel-aggregate-builder).

```
$salesCube = new \Hertroys\Cubes\Cube(new \App\OrderLine);

$salesCube->sum('subtotal as sales')
    ->whereBetween('order.created_at', ['2017-01-01', '2017-12-31'])
    ->groupBy('product.subgroup.group_id as product_group_id')->get();
```

Output:
```
Illuminate\Support\Collection Object
(
    [items:protected] => Array
        (
            [0] => stdClass Object
                (
                    [sales] => 956.65
                    [product_group_id] => 1
                )

            [1] => stdClass Object
                (
                    [sales] => 68.10
                    [product_group_id] => 2
                )

        )

)
```

## Joins
### Relation paths
The Laravel query builder joins like so:
```
\DB::table('order_lines')->join('products', 'order_lines.product_id', '=', 'products.id')->get();
```

When using Eloquent models, these join conditions may be extracted from the relationships, which are defined as methods on the model classes.

```
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderLine extends Model
{
    public function product()
    {
        return $this->belongsTo('App\Product', 'product_id', 'id');
    }
}

```

The method names can be chained to form a *relation path* from some model to another which can be used, i.a., to eager load:
`\App\OrderLine::get()->load('product.subgroup.group');`

This yields (provided that the `subgroup` and `group` relations are defined):
```
1. select * from `order_lines`
2. select * from `products` where `products`.`id` in ('1', '2', '3', '4', '5', '6')
3. select * from `product_subgroups` where `product_subgroups`.`id` in ('1', '2', '3')
4. select * from `product_groups` where `product_groups`.`id` in ('1', '2')
```

### joinTo
Eloquent cubes use these relation paths to perform joins: `$salesCube->joinTo('product.subgroup.group')->toSql();`

```
select * from
    `order_lines`
    inner join (
        select `products`.`id` as `product_subgroup_group_joinkey` from
            `products`
            inner join `product_subgroups` on
                `products`.`subgroup_id` = `product_subgroups`.`id`
            inner join `product_groups` on
                `product_subgroups`.`group_id` = `product_groups`.`id`
    ) as `product_subgroup_group` on
        `order_lines`.`product_id` = `product_subgroup_group`.`product_subgroup_group_joinkey`
```

Of course, you can left join with `$cube->leftJoinTo('some.join.path')`

### Naming conventions
In order to avoid naming conflicts, by default none of the joined columns are selected. Columns can be added like so:
`$cube->joinTo('some.join.path', ['id', 'label'])` and will be exposed as `some_join_path_id` and `some_join_path_label`

The columns can be aliased: `$cube->joinTo('some.join.path', ['col1 as some_column_alias'])` &rarr; `some_column_alias`

Or the join may be aliased: `$cube->joinTo('some.join.path as some_join_alias', ['col1'])` &rarr; `some_join_alias_col1`

## groupBy, where and orderBy
The cube watches for "dot" notation in the column selectors of the various query clauses and automatically performs the required joins (inner by default).

So `->joinTo('product.subgroup', ['group_id'])->groupBy('product_subgroup_group_id')` can be abbreviated as
`->groupBy('product.subgroup.group_id')`

Dot detection is enabled for `where` (`whereIn`, `whereBetween`, `whereNull`), `groupBy` and `orderBy`

As with the [laravel-aggregate-builder](https://github.com/hertroys/laravel-aggregate-builder), the grouping columns are automatically added to the select and aggregate functions may be chained.

```
$salesCube
    ->sum('quantity')
    ->sum('subtotal as sales')
    ->where('order.status', 'done')
    ->groupBy('product.label')
    ->orderBy('product.label')
    ->get();
```
