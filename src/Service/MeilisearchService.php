<?php

namespace App\Service;

use Meilisearch\Client;

class MeilisearchService
{
    private Client $client;

    public function __construct(string $meiliUrl, string $meiliMasterKey)
    {
        $this->client = new Client($meiliUrl, $meiliMasterKey);
    }

    public function getIndex(string $name = 'users'): \Meilisearch\Endpoints\Indexes
    {
        return $this->client->index($name);
    }

    public function ensureIndex(string $name = 'users', string $primaryKey = 'id'): void
    {
        try {
            $this->client->createIndex($name, ['primaryKey' => $primaryKey]);
        } catch (\Meilisearch\Exceptions\ApiException) {
            // Index already exists
        }
    }

    public function search(string $query, string $index = 'users'): array
    {
        return $this->getIndex($index)->search($query)->toArray();
    }

    public function addDocuments(array $documents, string $index = 'users'): void
    {
        $this->getIndex($index)->addDocuments($documents);
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
