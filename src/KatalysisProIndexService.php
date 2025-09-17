<?php

namespace KatalysisProAi;


use KatalysisProAi\KatalysisProVectorStore;
use NeuronAI\RAG\Document;
use Concrete\Core\Support\Facade\Database;
use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;
use Concrete\Package\KatalysisPro\Src\KatalysisPro\People\Person;
use Concrete\Core\Support\Facade\Config;

class KatalysisProIndexService
{
    private KatalysisProVectorStore $peopleStore;
    private KatalysisProVectorStore $reviewsStore;
    private KatalysisProVectorStore $placesStore;
    private OpenAIEmbeddingsProvider $embeddingsProvider;

    public function __construct()
    {
        // Create vector store directory
        $storeDirectory = DIR_FILES_UPLOADED_STANDARD . '/neuron';
        if (!is_dir($storeDirectory)) {
            mkdir($storeDirectory, 0755, true);
        }

        // Create separate vector stores for each entity type
        $this->peopleStore = new KatalysisProVectorStore($storeDirectory, 5, 'people', '.store');
        $this->reviewsStore = new KatalysisProVectorStore($storeDirectory, 5, 'reviews', '.store');
        $this->placesStore = new KatalysisProVectorStore($storeDirectory, 3, 'places', '.store');
        
        $apiKey = Config::get('katalysis.ai.open_ai_key');
        error_log("DEBUG: OpenAI API Key configured: " . (!empty($apiKey) ? 'Yes' : 'No'));
        
        $this->embeddingsProvider = new OpenAIEmbeddingsProvider(
            $apiKey,
            'text-embedding-3-small'
        );
    }
    
    /**
     * Build vector database for all people/specialists
     */
    public function buildPeopleIndex(): void
    {
        echo "Building People Vector Index...\n";
        
        // Clear existing index to prevent duplicates
        $this->peopleStore->clearStore();
        
        $db = Database::get();
        $people = $db->GetAll("SELECT sID, name, jobTitle, email, phone, page, featured, active, biography, shortBiography FROM KatalysisPeople WHERE active = 1");
        
        $documents = [];
        foreach ($people as $person) {
            // Create rich text content for embedding
            $content = $this->buildPersonContent($person);
            
            if (!empty($content)) {
                $document = new Document($content);
                $document->sourceType = 'person';
                $document->sourceName = $person['name'];
                $document->id = 'person_' . $person['sID'];
                
                // Add metadata for retrieval
                $document->addMetadata('person_id', $person['sID']);
                $document->addMetadata('name', $person['name']);
                $document->addMetadata('job_title', $person['jobTitle']);
                $document->addMetadata('email', $person['email']);
                $document->addMetadata('phone', $person['phone']);
                $document->addMetadata('page', $person['page']);
                $document->addMetadata('featured', (bool)$person['featured']);
                $document->addMetadata('biography', $person['biography']);
                $document->addMetadata('short_biography', $person['shortBiography']);
                
                // Try to add additional metadata from Person object
                try {
                    $personObj = Person::getByID($person['sID']);
                    if ($personObj) {
                        try {
                            $specialisms = $personObj->getSpecialisms($person['sID']);
                            $document->addMetadata('specialisms', json_encode($specialisms));
                        } catch (\Exception $e) {
                            error_log("DEBUG: Error getting specialisms for metadata: " . $e->getMessage());
                            $document->addMetadata('specialisms', json_encode([]));
                        }
                        
                        try {
                            $topics = $personObj->getTopics($person['sID']);
                            $document->addMetadata('topics', json_encode($topics));
                        } catch (\Exception $e) {
                            error_log("DEBUG: Error getting topics for metadata: " . $e->getMessage());
                            $document->addMetadata('topics', json_encode([]));
                        }
                        
                        try {
                            $places = $personObj->getPlaces($person['sID']);
                            $document->addMetadata('places', json_encode($places));
                        } catch (\Exception $e) {
                            error_log("DEBUG: Error getting places for metadata: " . $e->getMessage());
                            $document->addMetadata('places', json_encode([]));
                        }
                    }
                } catch (\Exception $e) {
                    error_log("DEBUG: Error creating Person object for metadata: " . $e->getMessage());
                }
                
                $documents[] = $document;
            }
        }
        
        if (!empty($documents)) {
            $this->peopleStore->addDocuments($documents);
            echo "Indexed " . count($documents) . " people.\n";
        }
    }
    
    /**
     * Build vector database for all reviews
     */
    public function buildReviewsIndex(): void
    {
        echo "Building Reviews Vector Index...\n";
        
        // Clear existing index to prevent duplicates
        $this->reviewsStore->clearStore();
        
        $db = Database::get();
        $reviews = $db->GetAll("SELECT sID, author, organization, rating, extract, review, source, featured, active FROM KatalysisReviews WHERE active = 1");
        
        $documents = [];
        foreach ($reviews as $review) {
            // Use extract if available, otherwise full review
            $content = $review['extract'] ?: $review['review'];
            
            if (!empty($content)) {
                $document = new Document($content);
                $document->sourceType = 'review';
                $document->sourceName = $review['author'] ?: 'Client Review';
                $document->id = 'review_' . $review['sID'];
                
                // Add metadata
                $document->addMetadata('review_id', $review['sID']);
                $document->addMetadata('author', $review['author']);
                $document->addMetadata('organization', $review['organization']);
                $document->addMetadata('rating', (int)$review['rating']);
                $document->addMetadata('source', $review['source']);
                $document->addMetadata('featured', (bool)$review['featured']);
                $document->addMetadata('full_review', $review['review']);
                
                $documents[] = $document;
            }
        }
        
        if (!empty($documents)) {
            $this->reviewsStore->addDocuments($documents);
            echo "Indexed " . count($documents) . " reviews.\n";
        }
    }
    
    /**
     * Build vector database for places/locations
     */
    public function buildPlacesIndex(): void
    {
        echo "Building Places Vector Index...\n";
        
        // Clear existing index to prevent duplicates
        $this->placesStore->clearStore();
        
        $db = Database::get();
        $places = $db->GetAll("SELECT sID, name, address1, address2, town, county, postcode, description, phone, email FROM KatalysisPlaces WHERE active = 1");
        
        $documents = [];
        foreach ($places as $place) {
            // Build rich content for place embedding
            $content = $place['name'];
            if (!empty($place['description'])) {
                $content .= ". " . $place['description'];
            }
            
            // Add location information for better matching
            $locationParts = array_filter([
                $place['town'],
                $place['county'],
                $place['address1'],
                $place['address2']
            ]);
            if (!empty($locationParts)) {
                $content .= ". Located in " . implode(', ', $locationParts);
            }
            
            if (!empty($content)) {
                $document = new Document($content);
                $document->sourceType = 'place';
                $document->sourceName = $place['name'];
                $document->id = 'place_' . $place['sID'];
                
                $document->addMetadata('place_id', $place['sID']);
                $document->addMetadata('name', $place['name']);
                $document->addMetadata('description', $place['description']);
                $document->addMetadata('address', trim($place['address1'] . ' ' . $place['address2']));
                $document->addMetadata('town', $place['town']);
                $document->addMetadata('county', $place['county']);
                $document->addMetadata('postcode', $place['postcode']);
                $document->addMetadata('phone', $place['phone']);
                $document->addMetadata('email', $place['email']);
                
                $documents[] = $document;
            }
        }
        
        if (!empty($documents)) {
            error_log("DEBUG: About to add " . count($documents) . " place documents to vector store");
            try {
                $this->placesStore->addDocuments($documents);
                echo "Indexed " . count($documents) . " places.\n";
                error_log("DEBUG: Successfully added place documents to vector store");
            } catch (\Exception $e) {
                error_log("ERROR: Failed to add place documents: " . $e->getMessage());
                throw $e;
            }
        }
    }
    
    /**
     * Search for relevant people using AI similarity
     */
    public function searchPeople(string $query, int $limit = 3): array
    {
        try {
            $embedding = $this->embeddingsProvider->embedText($query);
            $results = $this->peopleStore->similaritySearch($embedding);
            
            $specialists = [];
            foreach (array_slice($results, 0, $limit) as $result) {
                // Skip documents without embeddings
                if (!$result || !isset($result->metadata)) {
                    continue;
                }
                
                $metadata = $result->metadata;
                $specialists[] = [
                    'id' => $metadata['person_id'] ?? 0,
                    'name' => $metadata['name'] ?? 'Unknown',
                    'title' => $metadata['job_title'] ?? 'Specialist',
                    'expertise' => $this->deriveExpertiseFromContent($result->getContent()),
                    'contact' => $metadata['email'] ?: $metadata['phone'] ?: 'Contact Available',
                    'email' => $metadata['email'] ?? '',
                    'phone' => $metadata['phone'] ?? '',
                    'featured' => false, // Remove featured key access since it doesn't exist in vector metadata
                    'relevance_score' => round($result->score * 10, 2), // Scale to 0-10
                    'ai_match' => true
                ];
            }
            
            return $specialists;
        } catch (\Exception $e) {
            error_log("Error in searchPeople: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search for relevant reviews using AI similarity
     */
    public function searchReviews(string $query, int $limit = 3): array
    {
        try {
            $embedding = $this->embeddingsProvider->embedText($query);
            $results = $this->reviewsStore->similaritySearch($embedding);
            
            $reviews = [];
            foreach (array_slice($results, 0, $limit) as $result) {
                // Skip documents without embeddings
                if (!$result || !isset($result->metadata)) {
                    continue;
                }
                
                $metadata = $result->metadata;
                $reviews[] = [
                    'id' => $metadata['review_id'] ?? 0,
                    'client_name' => $metadata['author'] ?? 'Client',
                    'organization' => $metadata['organization'] ?? '',
                    'rating' => $metadata['rating'] ?? 5,
                    'review' => $result->getContent(),
                    'source' => $metadata['source'] ?? 'Client Review',
                    'featured' => false, // Remove featured key access since it doesn't exist in vector metadata
                    'relevance_score' => round($result->score * 10, 2),
                    'ai_match' => true
                ];
            }
            
            return $reviews;
        } catch (\Exception $e) {
            error_log("Error in searchReviews: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Build rich content for person embedding
     */
    private function buildPersonContent(array $person): string
    {
        $content = [];
        
        // Basic info
        $content[] = $person['name'] . " is a " . ($person['jobTitle'] ?: 'legal specialist');
        
        // Add biography information
        if (!empty($person['biography'])) {
            $content[] = $person['biography'];
        } elseif (!empty($person['shortBiography'])) {
            $content[] = $person['shortBiography'];
        }
        
        // Try to get specialisms and add them to content
        try {
            error_log("DEBUG: Getting Person object for sID: " . $person['sID']);
            $personObj = Person::getByID($person['sID']);
            if ($personObj) {
                error_log("DEBUG: Person object created successfully");
                try {
                    $specialisms = $personObj->getSpecialisms($person['sID']);
                    error_log("DEBUG: Got specialisms: " . json_encode($specialisms));
                    if (!empty($specialisms)) {
                        $specialismNames = array_column($specialisms, 'treeNodeName');
                        $content[] = "Specializes in: " . implode(', ', $specialismNames);
                    }
                } catch (\Exception $e) {
                    error_log("DEBUG: Error getting specialisms: " . $e->getMessage());
                }
                
                try {
                    $places = $personObj->getPlaces($person['sID']);
                    error_log("DEBUG: Got places: " . json_encode($places));
                    if (!empty($places)) {
                        $placeNames = array_column($places, 'name');
                        $content[] = "Serves locations: " . implode(', ', $placeNames);
                    }
                } catch (\Exception $e) {
                    error_log("DEBUG: Error getting places: " . $e->getMessage());
                }
            } else {
                error_log("DEBUG: Person object is null for sID: " . $person['sID']);
            }
        } catch (\Exception $e) {
            error_log("DEBUG: Error creating Person object: " . $e->getMessage());
        }
        
        return implode('. ', array_filter($content));
    }
    
    /**
     * Derive expertise area from content for display
     */
    private function deriveExpertiseFromContent(string $content): string
    {
        $content = strtolower($content);
        
        if (strpos($content, 'injury') !== false || strpos($content, 'accident') !== false || strpos($content, 'compensation') !== false) {
            return 'Personal Injury & Medical Negligence';
        } elseif (strpos($content, 'conveyancing') !== false || strpos($content, 'property') !== false) {
            return 'Conveyancing & Property Law';
        } elseif (strpos($content, 'family') !== false || strpos($content, 'divorce') !== false) {
            return 'Family Law';
        } elseif (strpos($content, 'employment') !== false || strpos($content, 'workplace') !== false) {
            return 'Employment Law';
        } elseif (strpos($content, 'will') !== false || strpos($content, 'probate') !== false || strpos($content, 'estate') !== false) {
            return 'Wills & Probate';
        } elseif (strpos($content, 'commercial') !== false || strpos($content, 'business') !== false) {
            return 'Commercial Law';
        } else {
            return 'Legal Services';
        }
    }
    
    /**
     * Search for relevant places using AI similarity
     */
    public function searchPlaces(string $query, int $limit = 3): array
    {
        try {
            $embedding = $this->embeddingsProvider->embedText($query);
            $results = $this->placesStore->similaritySearch($embedding);
            
            error_log("Places vector search - Query: $query, Results count: " . count($results));
            
            $places = [];
            foreach (array_slice($results, 0, $limit) as $result) {
                // Skip documents without embeddings or metadata
                if (!$result || !isset($result->metadata)) {
                    error_log("Skipping place document without metadata");
                    continue;
                }
                
                // Skip documents without embeddings
                if (!$result->getContent()) {
                    error_log("Skipping place document without content: " . json_encode($result->metadata));
                    continue;
                }
                
                $metadata = $result->metadata;
                $places[] = [
                    'id' => $metadata['place_id'] ?? 0,
                    'name' => $metadata['name'] ?? 'Location',
                    'address' => $metadata['address'] ?? '',
                    'town' => $metadata['town'] ?? '',
                    'county' => $metadata['county'] ?? '',
                    'postcode' => $metadata['postcode'] ?? '',
                    'phone' => $metadata['phone'] ?? '',
                    'email' => $metadata['email'] ?? '',
                    'relevance_score' => round($result->score * 10, 2),
                    'ai_match' => true
                ];
            }
            
            return $places;
        } catch (\Exception $e) {
            error_log("Error in searchPlaces: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Build all vector indexes
     */
    public function buildAllIndexes(): void
    {
        echo "Building all Katalysis vector indexes...\n";
        $this->buildPeopleIndex();
        $this->buildReviewsIndex();
        $this->buildPlacesIndex();
        echo "All indexes built successfully!\n";
    }
}
