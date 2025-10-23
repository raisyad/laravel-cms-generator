<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Support\Scaffold\TableIntrospector;
use App\Support\Scaffold\StubRenderer;

class MakeApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API (ApiController, Resource, apiRoute) from existing table';

    /**
     * Execute the console command.
     */
    public function handle(TableIntrospector $inspector, StubRenderer $stub) {
        $table = $this->argument('table');
        $meta  = $inspector->describe($table);

        $Model  = $meta['model'];
        $var    = Str::camel($Model);
        $kebabs = $meta['kebabPlural'];

        // Api Controller
        $stub->put(base_path("app/Http/Controllers/Api/{$Model}ApiController.php"),
            $stub->render(base_path('stubs/api/controller.stub'), [
                'model' => $Model,
                'var'   => $var,
            ])
        );
        $this->info("ApiController Created: {$Model}ApiController");

        // Resource
        $stub->put(base_path("app/Http/Resources/{$Model}Resource.php"),
            $stub->render(base_path('stubs/api/resource.stub'), [
                'model' => $Model,
            ])
        );
        $this->info("Resource Created: {$Model}Resource");

        // Route API (pakai Sanctum)
        $this->appendRouteApi(
            base_path('routes/api.php'),
            "Route::middleware('auth:sanctum')->apiResource('{$kebabs}', \\App\\Http\\Controllers\\Api\\{$Model}ApiController::class);"
        );
        $this->info("Route api added");

        $this->info("Successfully generated API for table '{$table}'");
        return self::SUCCESS;
    }

    private function appendRouteApi(string $file, string $line): void {
        $content = file_exists($file) ? file_get_contents($file) : "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";
        if (!str_contains($content, $line)) {
            $content .= "\n".$line."\n";
            file_put_contents($file, $content);
        }
    }
}
