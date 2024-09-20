<?php

namespace App\Console\Commands;

use App\Domains\Documents\ChunkContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ChunkFolder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:chunk-folder {absolute-path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chunk all files in the folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->withProgressBar(File::allFiles($this->argument('absolute-path')), function ($file) {
            try {
                $fileName = $file->getFilenameWithoutExtension();
                $content = File::get($file);
                $this->info('Chunking '.$file);
                app(ChunkContent::class)->handle($content, $fileName);
            } catch (\Throwable $e) {
                $this->error('Error chunking '.$file);
                Log::error($e);
            }
        });
    }
}
