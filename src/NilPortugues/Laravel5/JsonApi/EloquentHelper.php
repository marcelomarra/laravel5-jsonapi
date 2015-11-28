<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 11/27/15
 * Time: 7:47 PM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Laravel5\JsonApiJsonApiSerializer;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use NilPortugues\Api\JsonApi\Http\Factory\RequestFactory;
use NilPortugues\Laravel5\JsonApi\JsonApiSerializer;

/**
 * Class EloquentHelper
 * @package App\Http\Controllers
 */
trait EloquentHelper
{

    /**
     * @param JsonApiSerializer $serializer
     * @param Builder           $builder
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function paginate(JsonApiSerializer $serializer, Builder $builder)
    {
        self::sort($serializer, $builder, $builder->getModel());

        $request = RequestFactory::create();

        $builder->paginate(
            $request->getPageSize(),
            self::columns($serializer, $request->getFields()),
            'page',
            $request->getPageNumber()
        );
        return $builder->get();
    }

    /**
     * @param JsonApiSerializer $serializer
     * @param Builder           $builder
     * @param Model             $model
     *
     * @return Builder
     */
    protected static function sort(JsonApiSerializer $serializer, Builder $builder, Model $model)
    {
        $mapping = $serializer->getTransformer()->getMappingByClassName(get_class($model));
        $aliased = (array) $mapping->getAliasedProperties();

        if (!empty($aliased)) {
            $sorts = RequestFactory::create()->getSortDirection();
            if (!empty($sorts)) {
                $sortsFields = str_replace(array_values($aliased), array_keys($aliased), array_keys($sorts));
                $sorts = array_combine($sortsFields, array_values($sorts));

                foreach ($sorts as $field => $direction) {
                    $builder->orderBy($field, ($direction === 'ascending') ? 'ASC' : 'DESC');
                }
            }

        }

        return $builder;
    }


    /**
     * @param JsonApiSerializer $serializer
     * @param array             $fields
     *
     * @return array
     */
    public static function columns(JsonApiSerializer $serializer, array $fields)
    {
        $filterColumns = [];

        foreach ($serializer->getTransformer()->getMappings() as $mapping) {
            $classAlias = $mapping->getClassAlias();

            if (!empty($fields[$classAlias])) {

                $className = $mapping->getClassName();
                $aliased = $mapping->getAliasedProperties();

                /** @var \Illuminate\Database\Eloquent\Model $model * */
                $model = new $className();
                $columns = $fields[$classAlias];

                if (count($aliased)>0) {
                    $columns = str_replace(array_values($aliased), array_keys($aliased), $columns);
                }

                foreach ($columns as &$column) {
                    $filterColumns[] = sprintf("%s.%s", $model->getTable(), $column);
                }
                $filterColumns[] = sprintf("%s.%s", $model->getTable(), $model->getKeyName());
            }
        }


        return (count($filterColumns)>0) ? $filterColumns : ['*'];
    }

} 