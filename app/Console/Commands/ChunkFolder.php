<?php

namespace App\Console\Commands;

use App\Domains\Documents\ChunkContent;
use App\Services\LlmServices\LlmDriverFacade;
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

                if (str(File::mimeType($file))->contains('image')) {
                    $content = $this->getImageContent($file);
                }

                $this->info('Chunking '.$file);
                app(ChunkContent::class)->handle($content, $fileName);
            } catch (\Throwable $e) {
                $this->error('Error chunking '.$file);
                Log::error($e);
            }
        });
    }

    protected function getImageContent($file): string
    {
        $prompt = <<<'PROMPT'

This is an image with chart data about Pickleball. Please describe the data shown image and render as a markdown table.
Do not add any flavor text.

PROMPT;

        $results = LlmDriverFacade::driver('openai')->image(
            prompt: $prompt,
            base64Image: base64_encode(File::get($file))
        );

        return $results->content;
    }
}
