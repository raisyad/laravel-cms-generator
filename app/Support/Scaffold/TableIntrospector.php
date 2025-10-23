<?php

namespace App\Support\Scaffold;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TableIntrospector
{
    public function describe(string $table): array {
        $database = DB::getDatabaseName();

        $rows = DB::select(
            /** @lang MySQL */
            'SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
               FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME   = ?
              ORDER BY ORDINAL_POSITION',
            [$database, $table]
        );

        $fields = [];
        foreach ($rows as $col) {
            $name      = $col->COLUMN_NAME;
            $dataType  = strtolower($col->DATA_TYPE);
            $nullable  = strtoupper($col->IS_NULLABLE) === 'YES';
            $hasDefault= !is_null($col->COLUMN_DEFAULT);

            // skip kolom bawaan umum
            if (in_array($name, ['id','created_at','updated_at','deleted_at'])) {
                continue;
            }

            $fields[] = [
                'name'     => $name,
                'type'     => $this->mapType($dataType),
                'required' => !$nullable && !$hasDefault,
            ];
        }

        $Model       = Str::studly(Str::singular($table));
        $kebabPlural = Str::kebab(Str::pluralStudly($Model));

        return compact('table','fields') + [
            'model'       => $Model,
            'kebabPlural' => $kebabPlural,
        ];
    }

    /**
     * Peta tipe MySQL -> tipe logis untuk rules.
     */
    private function mapType(string $mysqlType): string
    {
        return match ($mysqlType) {
            'varchar','char','tinytext','text','mediumtext','longtext','enum','set' => 'string',
            'int','integer','bigint','smallint','mediumint','tinyint'                => 'integer',
            'bool','boolean'                                                        => 'boolean',
            'date','datetime','timestamp','time','year'                             => 'datetime',
            'decimal','float','double'                                             => 'decimal',
            'json'                                                                  => 'json',
            default                                                                 => 'string',
        };
    }

    public function rules(array $fields): array
    {
        $rules = [];
        foreach ($fields as $f) {
            $base = $f['required'] ? ['required'] : ['nullable'];
            switch ($f['type']) {
                case 'string':   $base[] = 'string';  break;
                case 'integer':  $base[] = 'integer'; break;
                case 'boolean':  $base[] = 'boolean'; break;
                case 'datetime': $base[] = 'date';    break;
                case 'decimal':  $base[] = 'numeric'; break;
                case 'json':     $base[] = 'array';   break;
                default:         $base[] = 'string';
            }
            $rules[$f['name']] = implode('|', $base);
        }
        return $rules;
    }

    public function fillable(array $fields): array
    {
        return array_map(fn($f) => $f['name'], $fields);
    }
}
