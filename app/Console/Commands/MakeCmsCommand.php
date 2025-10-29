<?php

namespace App\Console\Commands;

use App\Support\Scaffold\StubRenderer;
use App\Support\Scaffold\TableIntrospector;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeCmsCommand extends Command
{
    private const CMS_GROUP_START  = "// [cms-generator] START";
    private const CMS_GROUP_END    = "// [cms-generator] END";
    private const CMS_INSERT_MARK  = "// [cms-generator] INSERT HERE";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:cms {table}
    {--force : Overwrite files if exist}
    {--with-relations : Generate belongsTo & form selects}
    {--with-softdeletes : Enable soft deletes if deleted_at exists}
    {--with-search : Add simple search on index}
    {--with-policy : Generate policy & register}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CMS (Model, Controller, Requests, Views, Route web) from existing table';

    /**
     * Execute the console command.
     */
    public function handle(TableIntrospector $inspector, StubRenderer $stub)
    {
        $table = $this->argument('table');
        $meta = $inspector->describe($table);

        $rules = $inspector->rules($meta['fields'], $meta['foreignKeys'], $meta['table']);
        $fillable = $inspector->fillable($meta['fields'], $meta['foreignKeys']);

        $Model = $meta['model'];
        $var = Str::camel($Model);
        $kebabs = $meta['kebabPlural'];

        // ===== Prefix default CMS (/cms, cms.*)
        $routeUriPrefix = 'cms';
        $routeNamePrefix = 'cms.';

        // ===== relations (belongsTo) untuk Model
        $relations = '';
        if ($this->option('with-relations')) {
            foreach ($meta['foreignKeys'] as $fk) {
                $refModel = Str::studly(Str::singular($fk['references']));
                $relName = $this->relationNameFromFk($fk['column']);

                $relations .= "\n    public function {$relName}()\n    {\n        return \$this->belongsTo(\\App\\Models\\{$refModel}::class, '{$fk['column']}', '{$fk['referencedKey']}');\n    }\n";
            }
        }

        // ===== soft deletes placeholders
        $useSoftDeletes = ($meta['hasSoftDeletes'] && $this->option('with-softdeletes')) ? 'use Illuminate\\Database\\Eloquent\\SoftDeletes;' : '';
        $softDeletesTrait = ($meta['hasSoftDeletes'] && $this->option('with-softdeletes')) ? 'use SoftDeletes;' : '';

        // ===== model content (FINAL)
        $modelContent = $stub->render(base_path('stubs/cms/model.stub'), [
            'model' => $Model,
            'fillableArray' => "'".implode("','", $fillable)."'",
            'relations' => trim($relations),
            'useSoftDeletes' => $useSoftDeletes,
            'softDeletesTrait' => $softDeletesTrait,
        ]);

        // ===== FK map untuk form select
        $fkMap = [];
        if ($this->option('with-relations')) {
            foreach ($meta['foreignKeys'] as $fk) {
                $refModel = Str::studly(Str::singular($fk['references']));
                $listVar = '$'.Str::camel(Str::pluralStudly($refModel));

                $fkMap[$fk['column']] = [
                    'table' => $fk['references'],
                    'model' => $refModel,
                    'listVar' => $listVar,
                    'label' => 'name',
                ];
            }
        }

        // ===== form fields
        $formCreate = '';
        $formEdit = '';
        foreach ($meta['fields'] as $f) {
            $formCreate .= $this->inputForField($f, $var, $fkMap)."\n";
            $formEdit .= $this->inputForField($f, $var, $fkMap)."\n";
        }

        // ===== FK loads & compact untuk controller
        $fkLoadsCreate = $fkLoadsEdit = $fkCompactCreate = $fkCompactEdit = '';
        if ($this->option('with-relations') && count($fkMap)) {
            foreach ($fkMap as $ref) {
                $listVarNoDollar = ltrim($ref['listVar'], '$');
                $fkLoadsCreate .= "{$ref['listVar']} = \\App\\Models\\{$ref['model']}::select('id','name')->orderBy('name')->get();\n        ";
                $fkLoadsEdit .= "{$ref['listVar']} = \\App\\Models\\{$ref['model']}::select('id','name')->orderBy('name')->get();\n        ";
                $fkCompactCreate .= ", '{$listVarNoDollar}'";
                $fkCompactEdit .= ", '{$listVarNoDollar}'";
            }
        }

        // ===== soft deletes query block (controller)
        $softDeletesQuery = '';
        if ($meta['hasSoftDeletes'] && $this->option('with-softdeletes')) {
            $softDeletesQuery = <<<'PHP'
        if (request('with_trashed')) { $query->withTrashed(); }
        if (request('only_trashed')) { $query->onlyTrashed(); }
        PHP;
        }

        // ===== search query block (controller)
        $searchQuery = '';
        if ($this->option('with-search')) {
            // short array: ['title','body',...]
            $colsList = '[' . implode(', ', array_map(fn($c) => "'".$c."'", array_values($fillable))) . ']';
            $searchQuery = <<<PHP
                if (\$q) {
                    \$cols = {$colsList};
                    \$query->where(function (\$w) use (\$q, \$cols) {
                        foreach (\$cols as \$col) {
                            \$w->orWhere(\$col, 'like', '%'.\$q.'%');
                        }
                    });
                }
            PHP;
        }

        // ===== tulis Model
        $modelPath = base_path("app/Models/{$Model}.php");
        if (! file_exists($modelPath) || $this->option('force')) {
            $stub->put($modelPath, $modelContent);
            $this->info("Model Created: app/Models/{$Model}.php");
        } else {
            $this->line('Model exists: skip (use --force to overwrite)');
        }

        // ===== Requests
        $rulesArray = $this->phpArray($rules);
        $stub->put(
            base_path("app/Http/Requests/{$Model}/StoreRequest.php"),
            $stub->render(base_path('stubs/cms/request.store.stub'), [
                'model' => $Model,
                'rulesArray' => $rulesArray,
            ])
        );
        $stub->put(
            base_path("app/Http/Requests/{$Model}/UpdateRequest.php"),
            $stub->render(base_path('stubs/cms/request.update.stub'), [
                'model' => $Model,
                'rulesArray' => $rulesArray,
            ])
        );
        $this->info('Requests Created: Store/Update');

        $policyCalls = '';
        if ($this->option('with-policy')) {
            $policyCalls = [
                'index'   => "\$this->authorize('viewAny', \\App\\Models\\{$Model}::class);",
                'create'  => "\$this->authorize('create', \\App\\Models\\{$Model}::class);",
                'store'   => "\$this->authorize('create', \\App\\Models\\{$Model}::class);",
                'show'    => "\$this->authorize('view', \${$var});",
                'edit'    => "\$this->authorize('update', \${$var});",
                'update'  => "\$this->authorize('update', \${$var});",
                'destroy' => "\$this->authorize('delete', \${$var});",
                'restore' => "\$this->authorize('restore', \${$var});", // optional
            ];
        } else {
            $policyCalls = [
                'index'   => '',
                'create'  => '',
                'store'   => '',
                'show'    => '',
                'edit'    => '',
                'update'  => '',
                'destroy' => '',
                'restore' => '',
            ];
        }

        // ===== Controller
        $controllerContent = $stub->render(base_path('stubs/cms/controller.stub'), [
            'model'               => $Model,
            'var'                 => $var,
            'kebabs'              => $kebabs,
            'softDeletesQuery'    => rtrim($softDeletesQuery),
            'searchQuery'         => rtrim($searchQuery),
            'fkListLoadsCreate'   => rtrim($fkLoadsCreate),
            'fkListLoadsEdit'     => rtrim($fkLoadsEdit),
            'fkCompactIndex'      => '',
            'fkCompactCreate'     => $fkCompactCreate,
            'fkCompactEdit'       => $fkCompactEdit,
            'routeNamePrefix'     => $routeNamePrefix,

            // policy injection
            'authorizeIndex'      => $policyCalls['index'],
            'authorizeCreate'     => $policyCalls['create'],
            'authorizeStore'      => $policyCalls['store'],
            'authorizeShow'       => $policyCalls['show'],
            'authorizeEdit'       => $policyCalls['edit'],
            'authorizeUpdate'     => $policyCalls['update'],
            'authorizeDestroy'    => $policyCalls['destroy'],
            'authorizeRestore'    => $policyCalls['restore'],
        ]);
        $stub->put(base_path("app/Http/Controllers/{$Model}Controller.php"), $controllerContent);
        $this->info("Controller Created: {$Model}Controller");

        // ===== Views
        $viewIndex = $stub->render(base_path('stubs/cms/view.index.stub'), [
            'model' => $Model,
            'var' => $var,
            'kebabs' => $kebabs,
            'hasSoftDeletesFlag' => ($meta['hasSoftDeletes'] && $this->option('with-softdeletes')) ? 'true' : 'false',
            'routeNamePrefix' => $routeNamePrefix,
        ]);
        $stub->put(base_path("resources/views/{$kebabs}/index.blade.php"), $viewIndex);

        $viewCreate = $stub->render(base_path('stubs/cms/view.create.stub'), [
            'model' => $Model,
            'var' => $var,
            'kebabs' => $kebabs,
            'formFieldsCreate' => $formCreate,
            'routeNamePrefix' => $routeNamePrefix,
        ]);
        $stub->put(base_path("resources/views/{$kebabs}/create.blade.php"), $viewCreate);

        $viewEdit = $stub->render(base_path('stubs/cms/view.edit.stub'), [
            'model' => $Model,
            'var' => $var,
            'kebabs' => $kebabs,
            'formFieldsEdit' => $formEdit,
            'routeNamePrefix' => $routeNamePrefix,
        ]);
        $stub->put(base_path("resources/views/{$kebabs}/edit.blade.php"), $viewEdit);

        $viewShow = $stub->render(base_path('stubs/cms/view.show.stub'), [
            'model' => $Model,
            'var' => $var,
            'kebabs' => $kebabs,
            'routeNamePrefix' => $routeNamePrefix,
        ]);
        $stub->put(base_path("resources/views/{$kebabs}/show.blade.php"), $viewShow);

        if ($this->option('with-policy')) {
            $policyPath = base_path("app/Policies/{$Model}Policy.php");

            if (! file_exists($policyPath) || $this->option('force')) {
                // pastikan folder Policies ada
                if (! is_dir(base_path('app/Policies'))) {
                    mkdir(base_path('app/Policies'), 0777, true);
                }

                $policyContent = $stub->render(base_path('stubs/cms/policy.stub'), [
                    'model' => $Model,
                ]);

                $stub->put($policyPath, $policyContent);

                $this->info("Policy Created: app/Policies/{$Model}Policy.php");
            } else {
                $this->line('Policy exists: skip (use --force to overwrite)');
            }
        }
        // // ===== Routes (web) => selalu /cms + cms.*
        // $this->appendRouteWeb(
        //     base_path('routes/web.php'),
        //     "Route::middleware(['auth'])->prefix('{$routeUriPrefix}')->as('{$routeNamePrefix}')->resource('{$kebabs}', \\App\\Http\\Controllers\\{$Model}Controller::class);"
        // );

        // if ($meta['hasSoftDeletes'] && $this->option('with-softdeletes')) {
        //     $this->appendRouteWeb(
        //         base_path('routes/web.php'),
        //         "Route::middleware(['auth'])->prefix('{$routeUriPrefix}')->as('{$routeNamePrefix}')->post('{$kebabs}/{id}/restore', [\\App\\Http\\Controllers\\{$Model}Controller::class, 'restore'])->name('{$kebabs}.restore');"
        //     );
        // }

        // ===== Routes (web) => injeksikan ke CMS group
        $webFile = base_path('routes/web.php');

        // 1) pastikan group ada
        $this->ensureCmsGroupExists($webFile);

        // 2) bersihkan route lama (opsional, dianjurkan)
        $this->removeLegacyCmsRoutes($webFile, $kebabs);

        // 3) siapkan baris-baris yang akan diinject (idempotent)
        $lines = [
            "Route::resource('{$kebabs}', \\App\\Http\\Controllers\\{$Model}Controller::class)->names('{$kebabs}')",
        ];
        if ($meta['hasSoftDeletes'] && $this->option('with-softdeletes')) {
            $lines[] = "Route::post('{$kebabs}/{id}/restore', [\\App\\Http\\Controllers\\{$Model}Controller::class, 'restore'])->name('{$kebabs}.restore')";
        }

        // 4) sisipkan ke dalam group setelah marker INSERT
        $this->addLinesToCmsGroup($webFile, $lines);

        // ===== Format otomatis
        $this->formatFiles([
            base_path("app/Models/{$Model}.php"),
            base_path("app/Http/Controllers/{$Model}Controller.php"),
            base_path("app/Http/Requests/{$Model}/StoreRequest.php"),
            base_path("app/Http/Requests/{$Model}/UpdateRequest.php"),
            base_path("resources/views/{$kebabs}/index.blade.php"),
            base_path("resources/views/{$kebabs}/create.blade.php"),
            base_path("resources/views/{$kebabs}/edit.blade.php"),
            base_path("resources/views/{$kebabs}/show.blade.php"),
        ]);

        $this->info("Successfully generated CMS for table '{$table}'");

        return self::SUCCESS;
    }

    private function inputForField(array $f, string $var, array $fkMap): string
    {
        $name = $f['name'];
        $valueExpr = "{{ old('{$name}', \${$var}->{$name} ?? '') }}";

        // FK => select
        if (isset($fkMap[$name])) {
            $ref = $fkMap[$name];
            $listVar = $ref['listVar'];
            $label = $ref['label'] ?? 'name';

            return <<<HTML
<div>
  <label>{$name}</label>
  <select name="{$name}">
    <option value="">-- choose --</option>
    @foreach({$listVar} as \$opt)
      <option value="{{ \$opt->id }}" {{ (string)\$opt->id === (string)old('{$name}', \${$var}->{$name} ?? '') ? 'selected' : '' }}>
        {{ \$opt->{$label} ?? ('ID ' . \$opt->id) }}
      </option>
    @endforeach
  </select>
  @error('{$name}') <small>{{ \$message }}</small> @enderror
</div>
HTML;
        }

        // Type lainnya
        switch ($f['type']) {
            case 'integer':
            case 'decimal':
                $type = 'number';
                break;

            case 'boolean':
                return <<<HTML
<div>
  <label>{$name}</label>
  <input type="checkbox" name="{$name}" value="1" {{ old('{$name}', \${$var}->{$name} ?? false) ? 'checked' : '' }}>
  @error('{$name}') <small>{{ \$message }}</small> @enderror
</div>
HTML;

            case 'datetime':
                $type = 'datetime-local';
                break;

            case 'json':
                return <<<HTML
<div>
  <label>{$name}</label>
  <textarea name="{$name}" rows="4">{{ old('{$name}', isset(\${$var}) && \${$var}->{$name} ? json_encode(\${$var}->{$name}, JSON_PRETTY_PRINT) : '') }}</textarea>
  <small>JSON</small>
  @error('{$name}') <small>{{ \$message }}</small> @enderror
</div>
HTML;

            default:
                if (preg_match('/email/i', $name)) {
                    $type = 'email';
                } elseif (preg_match('/password/i', $name)) {
                    $type = 'password';
                } elseif (preg_match('/url|link/i', $name)) {
                    $type = 'url';
                } elseif (preg_match('/date/i', $name)) {
                    $type = 'date';
                } elseif (preg_match('/time/i', $name)) {
                    $type = 'time';
                } else {
                    $type = 'text';
                }
        }

        return <<<HTML
<div>
  <label>{$name}</label>
  <input type="{$type}" name="{$name}" value="{$valueExpr}">
  @error('{$name}') <small>{{ \$message }}</small> @enderror
</div>
HTML;
    }

    private function phpArray(array $arr): string
    {
        $export = var_export($arr, true);
        $export = str_replace(['array (', ')'], ['[', ']'], $export);
        $export = preg_replace('/=>\s+array\s*\(/', '=> [', $export);

        return $export;
    }

    private function appendRouteWeb(string $file, string $line): void
    {
        $content = file_exists($file) ? file_get_contents($file) : "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";

        if (! str_contains($content, $line)) {
            $content .= "\n".$line."\n";
            file_put_contents($file, $content);
        }
    }

    private function relationNameFromFk(string $fk): string
    {
        return Str::camel(preg_replace('/_id$/', '', $fk));
    }

    private function formatFiles(array $paths): void
    {
        $files = array_values(array_filter($paths, fn ($p) => file_exists($p)));
        if (empty($files)) {
            return;
        }

        // Tentukan executable Pint (Windows vs *nix)
        $pintBinCandidates = [
            base_path('vendor/bin/pint'),
            base_path('vendor\bin\pint.bat'),
        ];
        $pint = null;
        foreach ($pintBinCandidates as $cand) {
            if (file_exists($cand)) {
                $pint = $cand;
                break;
            }
        }
        if (! $pint) {
            $this->warn('! Pint not found (vendor/bin/pint). Run: composer require --dev laravel/pint');

            return;
        }

        // Build perintah (gunakan PHP untuk portabilitas)
        $php = PHP_BINARY ?: 'php';
        $cmd = escapeshellcmd($php).' '.escapeshellarg($pint).' '.
            implode(' ', array_map('escapeshellarg', $files));

        // 1) Coba dengan exec() jika tidak dinonaktifkan
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        $canExec = function_exists('exec') && ! in_array('exec', $disabled, true);

        if ($canExec) {
            @exec($cmd, $output, $code);
            if ($code === 0) {
                $this->line('• Formatted: '.implode(', ', array_map(fn ($p) => str_replace(base_path().'/', '', $p), $files)));

                return;
            }
            // Kalau gagal, lanjutkan ke fallback Symfony Process
        }

        // 2) Fallback: Symfony Process (lebih andal di beberapa OS)
        try {
            // Hindari require jika tidak ada symfony/process
            if (class_exists(\Symfony\Component\Process\Process::class)) {
                $process = \Symfony\Component\Process\Process::fromShellCommandline($cmd, base_path(), null, null, 120);
                $process->run();
                if ($process->isSuccessful()) {
                    $this->line('• Formatted (process): '.implode(', ', array_map(fn ($p) => str_replace(base_path().'/', '', $p), $files)));

                    return;
                }
                // Tampilkan sedikit log jika gagal
                $this->warn('! Pint failed via Process: '.$process->getErrorOutput());
            } else {
                $this->warn('! symfony/process not installed. You can install: composer require symfony/process --dev');
            }
        } catch (\Throwable $e) {
            $this->warn('! Pint formatting exception: '.$e->getMessage());
        }

        // 3) Pesan akhir: jalankan manual
        $this->warn('! Pint formatting skipped/failure (run manually: vendor/bin/pint)');
    }

    /**
     * Pastikan CMS group ada + marker INSERT ada di dalam group.
     */
    private function ensureCmsGroupExists(string $webFile): void
    {
        $content = file_exists($webFile)
            ? file_get_contents($webFile)
            : "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";

        if (!str_contains($content, self::CMS_GROUP_START)) {
            $block = <<<PHP

    // ---------------------------------------------
    // CMS routes group (auto-managed)
    // Jangan hapus marker START/END agar generator bisa inject resource
    // [cms-generator] START
    Route::middleware(['auth'])
        ->prefix('cms')
        ->as('cms.')
        ->group(function () {
            // [cms-generator] INSERT HERE
        });
    // [cms-generator] END

    PHP;
            $content .= $block;
            file_put_contents($webFile, $content);
            return;
        }

        // pastikan marker INSERT ada
        if (!str_contains($content, self::CMS_INSERT_MARK)) {
            // sisipkan marker sebelum END, di dalam function
            $posEnd = strpos($content, self::CMS_GROUP_END);
            if ($posEnd !== false) {
                // cari posisi baris '});' terdekat sebelum END
                $closePos = strrpos(substr($content, 0, $posEnd), '});');
                if ($closePos !== false) {
                    // sisipkan marker tepat sebelum '});'
                    $content = substr($content, 0, $closePos)
                        . "        " . self::CMS_INSERT_MARK . "\n    "
                        . substr($content, $closePos);
                    file_put_contents($webFile, $content);
                }
            }
        }
    }

    /**
     * Sisipkan baris ke DALAM group: ganti marker INSERT dengan marker + baris-baris.
     * Idempotent: tidak menambah jika sudah ada.
     */
    private function addLinesToCmsGroup(string $webFile, array $lines): void
    {
        $content = file_get_contents($webFile);

        if (!str_contains($content, self::CMS_INSERT_MARK)) {
            $this->ensureCmsGroupExists($webFile);
            $content = file_get_contents($webFile);
        }

        $injectBlock = '';
        foreach ($lines as $line) {
            $line = rtrim($line);
            if (!str_ends_with($line, ';')) {
                $line .= ';';
            }
            if (!str_contains($content, $line)) {
                $injectBlock .= "        {$line}\n";
            }
        }

        if ($injectBlock !== '') {
            $content = str_replace(
                self::CMS_INSERT_MARK,
                self::CMS_INSERT_MARK . "\n" . rtrim($injectBlock, "\n"),
                $content
            );
            file_put_contents($webFile, $content);
        }
    }

    /**
     * (Opsional) Hapus route lama 'posts' di luar group /cms
     */
    private function removeLegacyCmsRoutes(string $webFile, string $kebabs): void
    {
        if (!file_exists($webFile)) return;

        $content = file_get_contents($webFile);
        $patterns = [
            // resource posts tanpa prefix cms
            "/Route::resource\('{$kebabs}'.*?\);\n/s",
            // baris restore lama tanpa group
            "/Route::post\('{$kebabs}\/\{id\}\/restore'.*?\);\n/s",
        ];

        $new = $content;
        foreach ($patterns as $re) {
            // Jangan hapus yang berada di dalam block START..END
            $new = preg_replace_callback($re, function ($m) use ($content) {
                $s = strpos($content, $m[0]);
                $start = strpos($content, self::CMS_GROUP_START);
                $end   = strpos($content, self::CMS_GROUP_END);
                if ($start !== false && $end !== false && $s > $start && $s < $end) {
                    return $m[0]; // biarkan jika di dalam group
                }
                return ''; // hapus jika di luar group
            }, $new);
        }

        if ($new !== $content) {
            file_put_contents($webFile, $new);
        }
    }
}
