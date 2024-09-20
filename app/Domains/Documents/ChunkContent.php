<?php

namespace App\Domains\Documents;

use App\Domains\Documents\Chunking\TextChunker;
use App\Models\Chunk;
use App\Models\Document;
use Illuminate\Support\Facades\Http;

class ChunkContent
{
    public function handle(string $content, ?string $title = null): Document
    {
        $document = new Document;
        $document->title = $title;
        $document->content = $content;
        $document->save();

        $chunks = (new TextChunker)->handle($content);

        foreach ($chunks as $chunkSection => $chunk) {
            $embedding = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ollama',
            ])->post('http://localhost:11434/api/embed', [
                'model' => 'nomic-embed-text',
                'input' => $chunk,
            ])->json();

            Chunk::create(
                [
                    'content' => $chunk,
                    'document_id' => $document->id,
                    'sort_order' => $chunkSection,
                    'embedding_768' => $embedding['embeddings'][0],
                ]
            );
        }

        return $document;
    }
}
