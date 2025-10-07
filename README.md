# Katalysis Pro AI Package for Concrete CMS

An advanced AI package for Concrete CMS that provides intelligent search functionality and interactive chatbot capabilities with Retrieval-Augmented Generation (RAG) for legal services websites.

## Features

### 🔍 AI-Powered Search System (Primary Feature)
- **Enhanced AI Search Block**: New self-contained block with consolidated search logic
- **Comprehensive Service Explanations**: AI provides detailed 5-point structured responses covering direct answers, related services, capabilities, guidance, and competitive advantages
- **Smart Specialist Matching**: AI evaluates all specialists against queries for optimal recommendations with office information
- **Intelligent Place Recommendations**: AI-enhanced location suggestions with distance context and relevance matching  
- **Semantic Review Search**: Vector-based review matching with relevance scoring
- **Professional Tone**: Accessible yet authoritative communication optimized for legal services
- **Dual Block Support**: Original search block plus new enhanced block with advanced features

### 🤖 Interactive Chatbot System
- **Multiple AI Providers**: Support for OpenAI, Anthropic, and Ollama
- **Advanced Form Types**: Static step forms, AI-controlled dynamic forms, and simple all-at-once forms
- **Welcome Message System**: AI-generated contextual greetings with dashboard integration
- **Real-time Chat**: Interactive interface with comprehensive message history
- **Context-Aware Responses**: AI responses enhanced with your website's actual content

### 🧠 Vector Search & Content Indexing
- **Dual Vector Systems**: Separate indexes for CMS pages and Katalysis Pro entities (specialists, reviews, locations)
- **Page Index Service**: `PageIndexService.php` manages CMS content with OpenAI text-embedding-3-small
- **Katalysis Pro Index Service**: `KatalysisProIndexService.php` handles specialized legal entity matching
- **Enhanced Vector Store**: `KatalysisProVectorStore.php` with multi-file support and advanced search capabilities
- **Factory Pattern**: `TypesenseVectorStoreFactory.php` enables future Typesense integration
- **Batch Processing**: Fault-tolerant indexing with progress tracking for large sites
- **Smart Content Filtering**: Automatically excludes system pages and insufficient content
- **Metadata Preservation**: Stores page URLs, types, and entity relationships for accurate referencing
- **Extensible Architecture**: Ready for Typesense vector database when needed


## Installation

1. Install the `katalysis_neuron_ai` package first (foundation layer)
2. Install this `katalysis_pro_ai` package
3. Configure your AI provider API keys in Dashboard > System > Basics > AI
4. Run the "Build Page Index" task to index your CMS content
5. Run the "Build Katalysis Pro Index" task to index specialists, reviews, and locations

## Configuration

### Required Settings
- **OpenAI API Key**: For AI responses and embeddings
- **OpenAI Model**: Default is `gpt-4o-mini`
- **Anthropic Key**: Optional alternative AI provider
- **Ollama URL**: Optional local AI provider

### Automated Tasks
- **Build Page Index**: Indexes CMS pages for content search (uses `PageIndexService`)
- **Build Katalysis Pro Index**: Indexes specialists, reviews, and places (uses `KatalysisProIndexService`)
- **Content Filtering**: Excludes system pages and short content
- **Vector Store Management**: Handles document embeddings with separate storage files



## Dependencies

- Concrete CMS 9.3+
- `katalysis_neuron_ai` package (NeuronAI framework)
- PHP 8.1+

## Recent Major Improvements

### Architecture Refactoring (Latest)
- **Enhanced AI Search Block**: New self-contained block with all search logic in controller
- **RagAgent Singleton Pattern**: Fixed to prevent 500 Internal Server errors
- **Database Chat History**: New `DatabaseChatHistory` class for Neuron AI compatibility
- **Vector Store Factory**: Extensible factory pattern ready for Typesense integration
- **Dual Chat History**: Added `chatHistory` field to Chat entity alongside existing field
- **Code Consolidation**: Search logic moved from dashboard to block for better separation of concerns

### AI-First Architecture
- **Pure AI-Powered Approach**: Eliminated 500+ lines of manual scoring methods
- **Comprehensive AI Responses**: 5-point structured response template for detailed service explanations
- **Smart AI Evaluation**: AI analyzes specialists, places, and reviews for optimal matching
- **Code Optimization**: 30% smaller codebase (1,600+ → 1,132 lines) with improved maintainability
- **Enhanced Performance**: Reduced debug logging and streamlined execution paths

### Advanced Search Features
- **Expanded Service Queries**: AI provides comprehensive explanations instead of basic answers
- **Office Integration**: Specialist recommendations include office location and contact information  
- **Distance Context**: AI-generated distance and travel information for location queries
- **Match Reasoning**: AI explains why specific specialists or places are recommended

## Architecture

This package builds on the `katalysis_neuron_ai` foundation package, providing:
- Advanced AI search system with comprehensive response generation
- Interactive chatbot with multiple form types and welcome messages
- Vector-based content indexing and RAG implementation  
- Dashboard integration with chat management and analytics
- Environment-agnostic URL handling for multi-site compatibility

## Technical Documentation

For comprehensive technical documentation, see:
- **APPLICATION_ARCHITECTURE.md**: Complete technical architecture and AI system details
- **DEVELOPMENT_ENVIRONMENT.md**: Development setup, constraints, and best practices
