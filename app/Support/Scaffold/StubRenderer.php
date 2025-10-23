<?php

namespace App\Support\Scaffold;

use Illuminate\Filesystem\Filesystem;

class StubRenderer {
    public function __construct(private Filesystem $files = new Filesystem) {}

    public function render(string $stubPath, array $vars = []): string {
        $contents = $this->files->get($stubPath);

        $contents = preg_replace_callback('/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/',
            function ($m) use ($vars) {
                $key = $m[1];
                // dukung kunci dalam dua bentuk: 'model' dan 'Model'
                if (array_key_exists($key, $vars)) {
                    return is_array($vars[$key]) ? implode(', ', $vars[$key]) : (string)$vars[$key];
                }
                $lower = lcfirst($key);
                if (array_key_exists($lower, $vars)) {
                    return is_array($vars[$lower]) ? implode(', ', $vars[$lower]) : (string)$vars[$lower];
                }
                return $m[0]; // biarkan apa adanya jika tidak ada pengganti
            },
            $contents
        );

        return $contents;
    }

    public function put (string $path, string $content): void {
        $dir = dirname($path);
        if (!$this->files->isDirectory($dir)) $this->files->makeDirectory($dir, 0755, true);
        $this->files->put($path, $content);
    }
}
