<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Support\Scaffold\TableIntrospector;
use App\Support\Scaffold\StubRenderer;

class MakeCmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:cms {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CMS (Model, Controller, Requests, Views, Route web) from existing table';

    /**
     * Execute the console command.
     */
    public function handle(TableIntrospector $inspector, StubRenderer $stub) {
        $table = $this->argument('table');
        $meta  = $inspector->describe($table);

        $rules    = $inspector->rules($meta['fields']);
        $fillable = $inspector->fillable($meta['fields']);

        $Model   = $meta['model'];
        $var     = Str::camel($Model);
        $kebabs  = $meta['kebabPlural'];

        // Model (buat jika belum ada)
        $modelPath = base_path("app/Models/{$Model}.php");
        if (!file_exists($modelPath)) {
            $stub->put($modelPath, $stub->render(base_path('stubs/cms/model.stub'), [
                'model'         => $Model,
                'fillableArray' => "'".implode("','", $fillable)."'",
            ]));
            $this->info("Model Created: app/Models/{$Model}.php");
        } else {
            $this->line("Model has exist");
        }

        // Requests
        $rulesArray = $this->phpArray($rules);
        $stub->put(base_path("app/Http/Requests/{$Model}/StoreRequest.php"),
            $stub->render(base_path('stubs/cms/request.store.stub'), [
                'model' => $Model, 'rulesArray'=> $rulesArray,
            ])
        );
        $stub->put(base_path("app/Http/Requests/{$Model}/UpdateRequest.php"),
            $stub->render(base_path('stubs/cms/request.update.stub'), [
                'model' => $Model, 'rulesArray'=> $rulesArray,
            ])
        );
        $this->info("Requests Created: Store/Update");

        // Controller
        $stub->put(base_path("app/Http/Controllers/{$Model}Controller.php"),
            $stub->render(base_path('stubs/cms/controller.stub'), [
                'model'  => $Model,
                'var'    => $var,
                'kebabs' => $kebabs,
            ])
        );
        $this->info("Controller Created: {$Model}Controller");

        // Views
        foreach (['index','create','edit','show'] as $view) {
            $stub->put(base_path("resources/views/{$kebabs}/{$view}.blade.php"),
                $stub->render(base_path("stubs/cms/view.{$view}.stub"), [
                    'model'  => $Model,
                    'var'    => $var,
                    'kebabs' => $kebabs,
                ])
            );
        }
        $this->info("Views Created: resources/views/{$kebabs}/(index|create|edit|show).blade.php");

        // Route Web (auth proteksi)
        $this->appendRouteWeb(
            base_path('routes/web.php'),
            "Route::middleware(['auth'])->resource('{$kebabs}', \\App\\Http\\Controllers\\{$Model}Controller::class);"
        );
        $this->info("Route web added");

        $this->info("Successfully generated CMS for table '{$table}'");
        return self::SUCCESS;
    }

    private function phpArray(array $arr): string {
        $export = var_export($arr, true);
        $export = str_replace(['array (', ')'], ['[', ']'], $export);
        $export = preg_replace('/=>\s+array\s*\(/', '=> [', $export);
        return $export;
    }

    private function appendRouteWeb(string $file, string $line): void {
        $content = file_exists($file) ? file_get_contents($file) : "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";
        if (!str_contains($content, $line)) {
            $content .= "\n".$line."\n";
            file_put_contents($file, $content);
        }
    }
}
