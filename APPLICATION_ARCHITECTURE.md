# Katalysis Pro AI - Application Architecture

This document provides comprehensive technical documentation for the Katalysis Pro AI application architecture, focusing on the current Enhanced AI Search Block implementation.

## 🏗️ Application Overview

Katalysis Pro AI is a sophisticated Concrete CMS package that provides AI-powered search and chatbot functionality for legal services websites. The application centers around five main components:

1. **Enhanced AI Search Block** - Frontend search interface with comprehensive AI functionality
2. **Dashboard Search Settings** - Backend configuration and structure management 
3. **Chatbot System** - Interactive AI-powered chat interface
4. **Dashboard Chat Bot Settings** - Backend configuration for chatbot
5. **General Settings** - Backend configuration for Typesense and AI integrations


## 🔍 Enhanced AI Search Block (Frontend Component)

### Architecture Overview
**Location**: `blocks/katalysis_ai_enhanced_search/`
**Controller**: `blocks/katalysis_ai_enhanced_search/controller.php`
**View**: `blocks/katalysis_ai_enhanced_search/view.php`

The Enhanced AI Search Block is a self-contained Concrete CMS block that provides comprehensive AI-powered search functionality. All search logic, AI response generation, specialist matching, and content retrieval is handled within the block controller.

### Core Block Methods

#### AI Response Generation
- `generate_ai_response()` - Main AJAX endpoint for AI response generation
- `generateAIResponse()` - Core AI response processing with RagAgent integration
- `buildAIResponsePrompt()` - Creates comprehensive prompts with search context
- `parseAIResponse()` - Handles JSON parsing and structured response formatting

#### Search & Content Retrieval  
- `perform_search()` - Main AJAX endpoint for search operations
- `performSearch()` - Orchestrates multi-phase search process
- `getSupportingContent()` - Retrieves specialists, reviews, and places
- `getTargetedSpecialists()` - Priority-based specialist matching with fallbacks

#### AI Intent Analysis
- `performQueryCategorization()` - Multi-dimensional intent analysis using GPT-4o-mini
- `isConveyancingQuery()` / `isInjuryOrAccidentQuery()` - Query type detection
- `getDetectedCategoryForMatrix()` - Intent-based content categorization

### Block Architecture Benefits
- **Self-Contained**: All functionality encapsulated within the block
- **Reusable**: Can be added to any page via Concrete CMS block system
- **Configurable**: Uses dashboard settings for response structure and AI configuration
- **Maintainable**: Single point of logic for easier debugging and updates

## 🔧 Dashboard Search Settings (Configuration Component)

### Architecture Overview
**Location**: `controllers/single_page/dashboard/katalysis_pro_ai/search_settings.php`
**Purpose**: Backend administrative interface for configuring AI search behavior and response structure

### Core Dashboard Features

#### Response Structure Management
- **Section Configuration**: Define AI response sections (Direct Answer, Our Capabilities, etc.)
- **Sentence Count Control**: Specify exact sentence counts per section 
- **Section Ordering**: Manage display order and section priorities
- **Guidelines Management**: Configure AI response formatting rules

#### AI Configuration
- **Provider Settings**: OpenAI API keys, model selection (gpt-4o-mini)
- **Search Parameters**: Result limits, response length settings
- **Debug Controls**: Enable/disable debug panel and logging

#### Administrative Interface
- **Section Editor**: Add, edit, disable response sections
- **Settings Forms**: Configure search behavior and AI parameters
- **Status Monitoring**: View system status and configuration validation

### Integration with Enhanced AI Search Block
The dashboard settings provide configuration that the Enhanced AI Search Block uses for:
- AI response structure via `getResponseFormatInstructions()`
- Search behavior parameters
- Debug panel visibility controls
- Response formatting guidelines

## 🤖 Chatbot System (Frontend Component)

### Architecture Overview
**Location**: `blocks/katalysis_chatbot/`
**Purpose**: Interactive AI-powered chat interface for website visitors

### Core Chatbot Features

#### Multi-Provider AI Support
- **OpenAI Integration**: GPT-4o-mini for conversational AI
- **Anthropic Support**: Alternative AI provider option
- **Ollama Integration**: Local AI model support
- **RagAgent Integration**: Enhanced responses with document retrieval

#### Interactive Form System
**Four Form Types Supported**:
1. **Basic Actions**: Simple button interactions following AI instructions
2. **Simple Forms**: All fields displayed simultaneously (traditional web form experience)
3. **Static Forms**: Step-by-step multi-step forms with navigation
4. **Dynamic Forms**: AI-controlled conditional progression with smart logic

#### Frontend Features
- **Real-time Interface**: Interactive chat with immediate responses
- **Conversation History**: Client-side chat persistence
- **Welcome Messages**: AI-generated contextual greetings
- **Progressive Enhancement**: Works across all device types and browsers

### Chatbot Architecture Benefits
- **Multi-Modal Interaction**: Supports both conversational AI and structured forms
- **Contextual Responses**: AI responses enhanced with website content via RAG
- **Progressive Enhancement**: Works across all device types and browsers

## 🔧 Dashboard Chat Bot Settings (Configuration Component)

### Architecture Overview
**Location**: `controllers/single_page/dashboard/katalysis_pro_ai/chat_bot_settings.php`
**Purpose**: Backend administrative interface for configuring chatbot behavior and managing conversations

### Core Dashboard Features

#### Chatbot Configuration
- **AI Provider Settings**: Configure OpenAI, Anthropic, or Ollama providers
- **Conversation Management**: View and manage chat histories
- **Welcome Message Configuration**: Set up AI-generated greetings
- **Form Action Management**: Configure interactive form behaviors

#### Administrative Interface
- **Chat History Viewer**: Review all conversation interactions
- **Analytics Dashboard**: Monitor chatbot usage and performance
- **Action Configuration**: Set up and manage chatbot actions and forms
- **Provider Switching**: Enable/disable different AI providers

### Integration with Chatbot System
The dashboard settings provide configuration that the Chatbot System uses for:
- AI provider selection and API keys
- Conversation storage and retrieval settings
- Welcome message templates and timing
- Form action configurations and workflows

## ⚙️ General Settings (System Configuration Component)

### Architecture Overview
**Location**: Various dashboard configuration interfaces
**Purpose**: System-wide configuration for AI integrations and core functionality

### Core Configuration Areas

#### AI Provider Configuration
- **OpenAI Settings**: API keys, model selection (gpt-4o-mini, text-embedding-3-small)
- **Anthropic Configuration**: Claude model settings and API keys
- **Ollama Integration**: Local AI model configuration and endpoints

#### Vector Search Configuration
- **Typesense Settings**: Search server configuration and indexing parameters
- **Vector Store Management**: Embedding storage and retrieval settings
- **Index Building**: Batch processing configuration for large sites

#### System Integration Settings
- **RAG Configuration**: Document retrieval and context settings
- **Performance Settings**: Caching, batch sizes, and timeout configurations
- **Debug Settings**: Global debug mode and logging configuration

### Integration with All Components
General settings provide system-wide configuration that all components use for:
- AI provider access and authentication
- Vector search capabilities and performance
- Debug infrastructure and logging levels
- System performance and optimization settings

#### Expanded Query Template Implementation
```php
private function buildExpandedServiceQuery($query)
{
    return "Based on the search query '{$query}', provide a comprehensive explanation that includes:

1. DIRECT ANSWER: Answer the specific question or query asked
2. RELATED SERVICES: Detail all relevant legal services, expertise areas, and specializations we offer that relate to this query
3. OUR CAPABILITIES: Explain our firm's specific experience, qualifications, and track record in these areas
4. PRACTICAL GUIDANCE: Provide helpful information, next steps, or considerations related to this topic
5. WHY CHOOSE US: Highlight what makes our approach or expertise distinctive in this area

Please structure your response to be informative and comprehensive, helping the user understand both the answer to their query and the full scope of how we can assist them in this area of law. Use a professional but accessible tone.

Query: {$query}";
}
```



### Multi-Dimensional AI Intent Analysis
The Enhanced AI Search Block provides sophisticated query understanding through single AI API calls with comprehensive context awareness.

#### Intent Detection System
**Multi-Dimensional Analysis** in single GPT-4o-mini API call:
- **Service Area**: Maps queries to legal specialisms (Conveyancing, Road Accident, etc.)
- **Location**: Identifies UK cities/towns for office distance calculations
- **Person**: Detects specific staff member mentions with fuzzy matching
- **Intent Type**: Classifies as service/location/person/general
- **Urgency**: Assesses query urgency level for prioritization

#### Smart Content Delivery Matrix
**Intent-Based Content Selection**:
- **Service Intent**: Shows relevant specialists + reviews + legal content
- **Location Intent**: Shows nearest offices + distance calculations + local specialists
- **Person Intent**: Shows specific person info + general fallback content
- **General Intent**: Shows Our Services + About Us curated pages

#### Production-Ready Debug Infrastructure
**Conditional Debug System**:
- Backend: `Config::get('katalysis.debug.enabled', false)` 
- Frontend: `DEBUG_MODE = false` with `debugLog()` helper
- Three-column debug visualization with AI analysis breakdown
- Zero performance overhead when debug mode disabled

## 🗄️ Data Architecture & Storage

### Database Tables

#### Core Search Data
- **KatalysisPeople**: Specialist profiles with job titles, expertise, contact info
- **KatalysisPlaces**: Office locations with addresses, contact details
- **KatalysisReviews**: Client testimonials with ratings and content

#### AI & Chat Data  
- **Chat Entities**: Conversation histories with welcome messages
- **Action Entities**: Form configurations and interactive elements
- **Vector Store**: File-based embeddings storage (`/application/files/neuron/`)

### Vector Embeddings System
**Technology**: OpenAI text-embedding-3-small
**Storage**: Custom file-based vector store
**Indexing**: Batch processing with progress tracking
**Search**: Similarity-based retrieval with relevance scoring

## 🔄 RAG (Retrieval Augmented Generation) System

### Document Processing Pipeline
1. **Content Extraction**: Page content with metadata preservation
2. **Vector Generation**: OpenAI embeddings for semantic search
3. **Storage**: Custom vector store with efficient retrieval
4. **Query Processing**: Similarity search with relevance filtering
5. **Context Integration**: Retrieved documents enhance AI responses

### Page Index Building
**Method**: Batch processing to handle large sites
**Commands**: 
- `BuildPageIndexCommand`: Orchestrates the page indexing process
- `IndexPageCommand`: Processes individual pages
**Service**: `PageIndexService.php` - Manages CMS page content extraction and vector storage
**Features**: Progress tracking, fault tolerance, metadata preservation

### Katalysis Pro Index Building  
**Method**: Batch processing for entity types (people, reviews, places)
**Commands**:
- `BuildKatalysisProIndexCommand`: Orchestrates entity indexing process
- `IndexKatalysisProEntityCommand`: Processes individual entity types
**Service**: `KatalysisProIndexService.php` - Manages specialist, review, and location indexing
**Vector Store**: `KatalysisProVectorStore.php` - Enhanced storage with multiple .store files
**Features**: Multi-entity support, expertise detection, location awareness

### Content Prioritization
```php
// Legal service content gets priority scoring
$priorityMapping = [
    'legal_service_index' => 10,  // Highest priority
    'legal_service' => 8,         // High priority  
    'general_pages' => 5          // Standard priority
];
```

## 🌐 Frontend Integration

### Enhanced AI Search Block Frontend
**Primary Block**: `katalysis_ai_enhanced_search`
**Location**: `blocks/katalysis_ai_enhanced_search/view.php`
**Features**:
- **Real-time AI Search Interface**: Responsive search form with progressive enhancement
- **Comprehensive Results Display**: AI responses, specialists, places, and reviews
- **Specialist Integration**: Complete specialist profiles with office information and contact details
- **Location Intelligence**: AI-generated distance context and travel information
- **Review Showcase**: Contextually relevant client testimonials with ratings
- **Professional Styling**: Bootstrap 5 framework with gradient designs and accessibility features
- **Mobile-Responsive Design**: Progressive enhancement for all device types
- **Interactive Elements**: Loading states, error handling, and user feedback
- **Debug Panel**: Conditional three-column AI analysis visualization

### Block-Based AJAX Communication  
**Block Endpoints**: All handled within the block controller
```php
// Block-based URL generation
$view->action('perform_search', $token, $blockID)
$view->action('generate_ai_response', $token, $blockID)
```

**Key Block Methods**:
- `perform_search`: Main search processing with multi-phase results
- `generate_ai_response`: AI response generation with structured output
- `get_supporting_content`: Async loading of specialists, reviews, places

### Responsive Design
- Bootstrap 5 framework integration
- Mobile-first approach
- Progressive enhancement
- Accessibility considerations

## 🛡️ Security & Performance

### Security Measures
- Concrete CMS authentication integration
- Input validation and sanitization
- CSRF protection on all forms
- Proper permission checking

### Performance Optimizations
- Vector search result caching
- Database query optimization
- Batch processing for large operations
- Efficient memory management

### Error Handling
- Graceful AI failure handling
- Comprehensive logging system
- User-friendly error messages
- Fallback mechanisms

## 🔧 Configuration & Settings

### AI Provider Configuration
```php
// Settings stored in Concrete CMS config system
$aiSettings = [
    'katalysis.ai.open_ai_key' => 'API key',
    'katalysis.ai.model' => 'gpt-4o-mini',
    'katalysis.search.max_results' => 8,
    'katalysis.search.result_length' => 'medium'
];
```

### Search Configuration
- **Maximum Results**: Configurable result limits
- **Response Length**: Adjustable detail levels
- **Page Links**: Optional result URL inclusion
- **Snippets**: Configurable content previews

## 📊 Recent Major Improvements

### Enhanced AI Search with Intent Analysis (Latest)
- **Multi-Dimensional Intent Analysis**: Single GPT-4o-mini API call for comprehensive query understanding
  - Service area detection with specialism mapping
  - Location recognition with distance calculations
  - Person detection with fuzzy name matching
  - Intent classification: service/location/person/general
- **Smart Content Delivery**: Intent-based content matrix with dedicated sections
  - Our Services section for service-related fallback pages
  - About Us section for person/general query fallbacks
  - Priority-based specialist selection with sophisticated fallback logic
  - Related specialism mapping for better context matching
- **Production-Ready Debug Infrastructure**: Conditional logging system
  - Backend: `Config::get('katalysis.debug.enabled', false)` for clean production logs
  - Frontend: `DEBUG_MODE` constant with `debugLog()` helper function
  - Three-column debug visualization with expandable sections
  - Zero performance overhead when debug mode disabled

### Code Optimization & Architecture Cleanup (Completed)
- **Eliminated Redundant Code**: ~500+ lines of manual scoring methods removed
  - Maintained essential `recognizeLocationWithAI()` for distance calculations
  - Removed duplicate validation logic and excessive temporary variables
- **Debug Logging Optimization**: Conditional logging implementation (25+ statements made conditional)
- **File Size Reduction**: 30% smaller codebase with improved maintainability
- **Pure AI-Powered Architecture**: Eliminated manual scoring in favor of intelligent AI analysis
- **Production Deployment Ready**: Clean logging and optimized performance

### Smart Specialist & Content Matching (Current Implementation)
- **Priority-Based Specialist Selection**: Four-tier intelligent matching system
  1. **Person Search**: Direct person name queries (intent: person)
  2. **Specialism Filter**: Service area matching (intent: service)
  3. **Location-Based**: Geographic specialist selection (intent: location)
  4. **Smart Fallback**: Senior specialists with related specialism mapping
- **Related Specialism Mapping**: Context-aware fallback logic
  - "Road Accident" → Personal Injury specialists when no direct matches
  - Service area relationships for better specialist recommendations
  - Prevents irrelevant specialist matches through intelligent mapping
- **Comprehensive Debug Transparency**: Real-time visibility into selection logic
  - Priority level used, selection method, fallback status
  - Complete specialist availability and matching process
  - Performance metrics and detailed reasoning

### AI-Powered Distance & Location Intelligence (Advanced Features)
- **Location Recognition**: `recognizeLocationWithAI()` for comprehensive location analysis
  - UK location detection with coordinate lookup
  - Distance calculations to all office locations
  - Direct office matching vs. nearest office recommendations
  - AI-generated distance context and travel information
- **Smart Place Selection**: Intent-driven office visibility
  - Pure service queries hide places section (no location context)
  - Location queries show relevant offices with distances
  - AI reasoning for place recommendations
- **Fallback Pages System**: Curated content for general/person queries
  - Smart categorization: Our Services vs. About Us pages
  - Keyword-based page classification using title/handle analysis
  - Prevents random content mixing through intelligent grouping
  - Professional presentation with ratings

### Frontend Block Enhancements (Latest Features)
- **Real-time Search Interface**: `blocks/katalysis_ai_enhanced_search/view.php` optimized
- **Comprehensive Results Display**: Specialists, places, reviews, and AI responses
- **Mobile-Responsive Design**: Bootstrap 5 with progressive enhancement
- **Professional Styling**: Clean, accessible interface design
- **Distance Context**: AI-generated travel and location information
- **Office Integration**: Complete contact information with specialist recommendations

## �️ Development Workflow & Documentation (Latest Updates)

### GitHub Copilot Integration
**Configuration File**: `.github/copilot-instructions.md`
- **Purpose**: Provides essential context for AI coding assistants
- **Content**: Development constraints, architecture overview, key components
- **Benefits**: Faster onboarding, consistent code patterns, informed development decisions

### Documentation Structure (Optimized)
**Consolidated Architecture**: 4 key documentation files (down from 7+ scattered files)
1. **DEVELOPMENT_ENVIRONMENT.md** (root): SFTP workflow, testing protocols, constraints
2. **APPLICATION_ARCHITECTURE.md** (package): Technical architecture and AI systems  
3. **README.md** (root): Project overview and development setup
4. **.github/copilot-instructions.md**: AI assistant configuration and context

### Code Quality Improvements (Latest Cleanup)
- **Eliminated Redundancy**: Removed `build_vectors.php` (outdated CLI script)
- **Cleaned Empty Files**: Removed `chat_bot_settings.php` (1-byte empty file)
- **Manual Method Removal**: Deleted 5 manual scoring methods (500+ lines)
- **Debug Optimization**: Reduced logging statements by 40%
- **Architecture Purification**: Pure AI-powered approach throughout

## �🔮 Architecture Patterns & Best Practices

### AI Integration Patterns (Pure AI Approach)  
1. **AI-First Architecture**: Let AI handle all complex decision making and evaluation
2. **Eliminate Manual Methods**: Remove manual scoring/matching in favor of AI analysis
3. **Context Preservation**: Maintain conversation and search context across requests
4. **Response Enhancement**: Use AI to provide comprehensive, structured responses
5. **Fallback Strategies**: Graceful degradation when AI services are unavailable

### Code Organization (Optimized Structure)
- **Single Responsibility**: Each class/method has clear, focused purpose
- **Pure AI Methods**: `evaluateSpecialistsWithAI()`, `evaluateAllPlacesWithAI()`, `buildExpandedServiceQuery()`
- **Dependency Injection**: Proper service container usage throughout
- **Error Isolation**: Prevent AI failures from breaking core functionality
- **Configuration Externalization**: All settings managed through CMS interface
- **Clean Architecture**: 30% smaller codebase with improved maintainability

### Database Interaction
- **CMS Patterns**: Follow Concrete CMS conventions
- **Query Optimization**: Efficient database operations
- **Relationship Management**: Proper entity associations
- **Migration Safety**: Backward-compatible updates

## 🚀 Future Architecture Considerations

### Scalability
- **Vector Store Migration**: Potential move to dedicated vector database
- **API Rate Limiting**: Intelligent request management
- **Caching Strategies**: Advanced result caching
- **Load Balancing**: Multi-instance AI processing

### Feature Extensions
- **Multi-language Support**: Internationalization readiness
- **Advanced Analytics**: Search and interaction tracking
- **Machine Learning**: Custom model training capabilities
- **Integration APIs**: External system connectivity

---

This architecture provides a robust, scalable, and maintainable foundation for AI-powered legal services search and interaction, emphasizing user experience, technical excellence, and business value delivery.
