<?php

namespace KatalysisProAi;

use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use NeuronAI\RAG\VectorStore\VectorSimilarity;
use NeuronAI\RAG\Document;
use NeuronAI\Exceptions\VectorStoreException;

class CustomFileVectorStore implements VectorStoreInterface
{
    private FileVectorStore $fileVectorStore;

    public function __construct(string $directory, int $topK = 4, string $name = 'neuron', string $ext = '.store')
    {
        $this->fileVectorStore = new FileVectorStore($directory, $topK, $name, $ext);
    }

    public function addDocument(Document $document): void
    {
        $this->fileVectorStore->addDocument($document);
    }

    public function addDocuments(array $documents): void
    {
        $this->fileVectorStore->addDocuments($documents);
    }

    public function deleteBySource(string $sourceType, string $sourceName): void
    {
        $this->fileVectorStore->deleteBySource($sourceType, $sourceName);
    }

    public function similaritySearch(array $embedding): array
    {
        // Use reflection to access the private methods of FileVectorStore
        $reflection = new \ReflectionClass($this->fileVectorStore);
        
        $getFilePathMethod = $reflection->getMethod('getFilePath');
        $getFilePathMethod->setAccessible(true);
        $filePath = $getFilePathMethod->invoke($this->fileVectorStore);
        
        $getLineMethod = $reflection->getMethod('getLine');
        $getLineMethod->setAccessible(true);
        
        $topKProperty = $reflection->getProperty('topK');
        $topKProperty->setAccessible(true);
        $topK = $topKProperty->getValue($this->fileVectorStore);
        
        $distances = [];

        // Process all documents first
        foreach ($getLineMethod->invoke($this->fileVectorStore, $filePath) as $document) {
            $document = \json_decode((string) $document, true);

            if (empty($document['embedding'])) {
                throw new VectorStoreException("Document with the following content has no embedding: {$document['content']}");
            }
            
            $dist = VectorSimilarity::cosineDistance($embedding, $document['embedding']);
            $distances[] = ['dist' => $dist, 'document' => $document];
        }

        // Sort by distance (ascending - lower distance = more similar)
        \usort($distances, fn (array $a, array $b): int => $a['dist'] <=> $b['dist']);

        // Take only the top K items
        $topItems = \array_slice($distances, 0, $topK);

        return \array_map(function (array $item): Document {
            $itemDoc = $item['document'];
            $document = new Document($itemDoc['content']);
            $document->embedding = $itemDoc['embedding'];
            $document->sourceType = $itemDoc['sourceType'];
            $document->sourceName = $itemDoc['sourceName'];
            $document->id = $itemDoc['id'];
            $document->score = VectorSimilarity::similarityFromDistance($item['dist']);
            $document->metadata = $itemDoc['metadata'] ?? [];

            return $document;
        }, $topItems);
    }
} 