<?php

namespace App\Support\Scaffold;

use Illuminate\Filesystem\Filesystem;

class StubRenderer
{
    public function __construct(private Filesystem $files = new Filesystem) {}

    public function render(string $stubPath, array $vars): string
    {
        $contents = $this->files->get($stubPath);

        // Replace placeholders: {{ key }} (flex spacing/case)
        $contents = preg_replace_callback(
            '/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/',
            function ($m) use ($vars) {
                $key = $m[1];
                $v = $vars[$key] ?? $vars[lcfirst($key)] ?? null;
                if ($v === null) {
                    return $m[0]; // biarkan bila tidak diset (akan kita rapikan di bawah)
                }

                return is_array($v) ? implode(', ', $v) : (string) $v;
            },
            $contents
        );

        // Normalisasi whitespace:
        // 1) trim trailing spaces per line
        $contents = preg_replace('/[ \t]+(\r?\n)/', '$1', $contents);
        // 2) collapse 3+ newlines => 2
        $contents = preg_replace("/\n{3,}/", "\n\n", $contents);
        // 3) hapus baris yang berisi placeholder kosong
        $contents = preg_replace('/^\s*\{\{\s*[A-Za-z_][A-Za-z0-9_]*\s*\}\}\s*$/m', '', $contents);
        // 4) pastikan newline EOF
        if (! str_ends_with($contents, "\n")) {
            $contents .= "\n";
        }

        return $contents;
    }

    public function put(string $path, string $content): void
    {
        $dir = dirname($path);

        if (! $this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        $this->files->put($path, $content);
    }
}
