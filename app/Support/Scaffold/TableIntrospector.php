<?php

namespace App\Support\Scaffold;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TableIntrospector
{
    public function describe(string $table): array
    {
        $database = DB::getDatabaseName();

        $cols = DB::select(
            'SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, CHARACTER_MAXIMUM_LENGTH
               FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
              ORDER BY ORDINAL_POSITION',
            [$database, $table]
        );

        $fks = DB::select(
            'SELECT kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME
               FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
              WHERE kcu.TABLE_SCHEMA = ?
                AND kcu.TABLE_NAME = ?
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL',
            [$database, $table]
        );

        $fields = [];
        $hasSoftDeletes = false;

        foreach ($cols as $col) {
            $name = $col->COLUMN_NAME;
            $dataType = strtolower($col->DATA_TYPE);
            $nullable = strtoupper($col->IS_NULLABLE) === 'YES';
            $hasDefault = ! is_null($col->COLUMN_DEFAULT);
            $maxLen = $col->CHARACTER_MAXIMUM_LENGTH;

            if ($name === 'deleted_at') {
                $hasSoftDeletes = true;
            }
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            $fields[] = [
                'name' => $name,
                'type' => $this->mapType($dataType),
                'rawType' => $dataType,
                'required' => ! $nullable && ! $hasDefault,
                'max' => $maxLen,
            ];
        }

        $foreignKeys = [];
        foreach ($fks as $fk) {
            $foreignKeys[] = [
                'column' => $fk->COLUMN_NAME,
                'references' => $fk->REFERENCED_TABLE_NAME,
                'referencedKey' => $fk->REFERENCED_COLUMN_NAME ?: 'id',
            ];
        }

        $Model = Str::studly(Str::singular($table));
        $kebabPlural = Str::kebab(Str::pluralStudly($Model));

        return [
            'table' => $table,
            'model' => $Model,
            'kebabPlural' => $kebabPlural,
            'fields' => $fields,
            'foreignKeys' => $foreignKeys,
            'hasSoftDeletes' => $hasSoftDeletes,
        ];
    }

    /**
     * Peta tipe MySQL -> tipe logis untuk rules.
     */
    private function mapType(string $mysql): string
    {
        return match ($mysql) {
            'varchar', 'char', 'tinytext', 'text', 'mediumtext', 'longtext', 'enum', 'set' => 'string',
            'int', 'integer', 'bigint', 'smallint', 'mediumint', 'tinyint' => 'integer',
            'bool', 'boolean' => 'boolean',
            'date', 'datetime', 'timestamp', 'time', 'year' => 'datetime',
            'decimal', 'float', 'double' => 'decimal',
            'json' => 'json',
            default => 'string',
        };
    }

    public function rules(array $fields, array $foreignKeys, string $table): array
    {
        $rules = [];

        foreach ($fields as $f) {
            $base = $f['required'] ? ['required'] : ['nullable'];

            switch ($f['type']) {
                case 'string':
                    $base[] = 'string';
                    break;
                case 'integer':
                    $base[] = 'integer';
                    break;
                case 'boolean':
                    $base[] = 'boolean';
                    break;
                case 'datetime':
                    $base[] = 'date';
                    break;
                case 'decimal':
                    $base[] = 'numeric';
                    break;
                case 'json':
                    $base[] = 'array';
                    break;
                default:
                    $base[] = 'string';
            }

            if ($f['type'] === 'string' && $f['max']) {
                $base[] = 'max:'.$f['max'];
            }
            if (preg_match('/email/i', $f['name'])) {
                $base[] = 'email';
            }
            if (preg_match('/url|link/i', $f['name'])) {
                $base[] = 'url';
            }

            $rules[$f['name']] = implode('|', $base);
        }

        // FK => exists
        $fieldsByName = array_column($fields, null, 'name');
        foreach ($foreignKeys as $fk) {
            $col = $fk['column'];
            $refTable = $fk['references'];
            $refKey = $fk['referencedKey'] ?? 'id';

            if (isset($fieldsByName[$col])) {
                $rules[$col] = ($rules[$col] ?? 'nullable').'|exists:'.$refTable.','.$refKey;
            }
        }

        return $rules;
    }

    public function fillable(array $fields, array $foreignKeys): array
    {
        $fillable = array_map(fn ($f) => $f['name'], $fields);

        foreach ($foreignKeys as $fk) {
            if (! in_array($fk['column'], $fillable, true)) {
                $fillable[] = $fk['column'];
            }
        }

        return $fillable;
    }
}
