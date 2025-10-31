# 🧱 Wall Social Platform

AI-Powered Social Network with AI-Generated Web Applications

## Overview

Wall Social Platform is a modern social networking platform that enables users to create AI-generated web applications using natural language prompts. Built with PHP 8.2, MySQL, Redis, and Ollama AI, it combines social features with collaborative AI development.

## Key Features

### 🤖 AI Generation
- **Natural Language Prompts**: Describe what you want, AI creates HTML/CSS/JavaScript applications
- **Powered by Ollama**: Uses DeepSeek-Coder or compatible models for code generation
- **Queue System**: Fair FIFO queue with real-time status updates via Server-Sent Events
- **Live Progress Tracking**: Monitor generation progress, token usage, and queue position

### 🔄 Collaborative Creation
- **Remix System**: Modify prompts to iterate on existing applications
- **Fork System**: Edit generated code directly for fine-tuning
- **Attribution Chains**: Track original creators and derivative works
- **Prompt Templates**: Share and reuse successful prompts with community ratings

### 🧱 Bricks Currency
- **Token-Based Economy**: 100 tokens = 1 brick for resource management
- **Daily Claims**: Free bricks every day for active users
- **Registration Bonus**: Welcome bricks for new users
- **Purchase Integration**: Buy bricks to support development
- **Cost Estimation**: Preview bricks cost before generation

### 💬 Social Features
- **User Walls**: Personal profile pages for content publishing
- **Threaded Comments**: Nested discussions with reactions
- **Reactions**: Like, love, laugh, wow, sad, angry on posts and comments
- **Subscriptions**: Follow walls to see new content in your feed
- **Friendships**: Mutual connections with friend requests
- **Direct Messaging**: Private conversations and group chats
- **Post Sharing**: Share wall posts in messages
- **Notifications**: Real-time alerts for social interactions

### 🎨 User Experience
- **6 Themes**: Light, Dark, Green, Cream, Blue, High Contrast
- **Responsive Design**: Mobile-first approach with 6 breakpoints
- **Profile Statistics**: Track posts, comments, AI usage, engagement
- **Activity Feed**: Recent actions and achievements
- **Social Links**: Display external profiles and websites
- **Search**: Full-text search for posts, walls, and users

## Tech Stack

### Backend
- **PHP 8.2+**: Modern PHP with type hints and performance improvements
- **MySQL 8.0+**: Relational database with 28 tables
- **Redis**: Caching and queue management
- **Nginx**: High-performance web server
- **Docker**: Containerized deployment

### AI Integration
- **Ollama**: Local AI inference server
- **DeepSeek-Coder**: Code generation model (or alternatives)
- **Server-Sent Events**: Real-time progress updates

### Architecture
- **RESTful API**: JSON-based API with versioning
- **Queue Workers**: Background job processing
- **Session Management**: Redis-backed sessions
- **File Storage**: Local file system (configurable to S3/CDN)

## Quick Start

### Prerequisites
- Docker Desktop installed
- At least 8GB RAM available
- 20GB disk space for models and data

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/yourusername/wall.cyka.lol.git
cd wall.cyka.lol
```

2. **Configure environment**
```bash
cp .env.example .env
# Edit .env with your configuration
```

3. **Build and start containers**
```bash
docker-compose up -d
```

4. **Initialize Ollama model**
```bash
docker exec -it wall_ollama ollama pull deepseek-coder:6.7b
```

5. **Install PHP dependencies**
```bash
docker exec -it wall_php composer install
```

6. **Access the application**
- Web Interface: http://localhost:8080
- API Documentation: http://localhost:8080/api/v1
- Health Check: http://localhost:8080/health

### Default Credentials
- **Username**: admin
- **Password**: admin123 (change immediately!)

## Project Structure

```
wall.cyka.lol/
├── config/                 # Configuration files
│   └── database.php       # Database connection
├── database/              # Database schema
│   └── schema.sql        # MySQL schema with 28 tables
├── docker/               # Docker configuration
│   └── php/
│       ├── Dockerfile    # PHP-FPM image
│       └── php.ini       # PHP configuration
├── nginx/                # Nginx configuration
│   └── conf.d/
│       └── default.conf  # Server configuration
├── public/               # Web root
│   ├── index.php        # Application entry point
│   ├── uploads/         # User media uploads
│   └── ai-apps/         # Generated applications
├── src/                  # PHP application code
│   ├── Controllers/     # Request handlers
│   ├── Models/          # Data models
│   ├── Services/        # Business logic
│   └── Utils/           # Helper functions
├── workers/             # Background workers
│   └── ai_generation_worker.php
├── .env.example         # Environment template
├── composer.json        # PHP dependencies
├── docker-compose.yml   # Docker services
└── README.md           # This file
```

## Development Roadmap

### Phase 1: Environment Setup ✅ (Current)
- [x] Docker Compose with 5 services
- [x] Database schema (28 tables)
- [x] Project structure
- [x] Basic routing

### Phase 2: Authentication System
- [ ] Local registration and login
- [ ] OAuth integration (Google, Yandex, Telegram)
- [ ] Session management
- [ ] User profiles and walls

### Phase 3: Post System & Queue
- [ ] Post creation, editing, deletion
- [ ] Media upload handling
- [ ] Redis queue implementation
- [ ] Queue worker daemon

### Phase 4: AI Integration
- [ ] Ollama API integration
- [ ] AI generation with SSE updates
- [ ] Bricks currency system
- [ ] Cost estimation and tracking

### Phase 5: Social Features
- [ ] Reactions system
- [ ] Threaded comments
- [ ] Wall subscriptions
- [ ] Friendship system

### Phase 6: Messaging System
- [ ] Direct messages
- [ ] Group chats
- [ ] Post sharing in messages
- [ ] Read receipts

### Phase 7: Advanced Features
- [ ] Full-text search
- [ ] Notifications
- [ ] Queue monitor page
- [ ] Prompt templates

### Phase 8: Theming & Design
- [ ] 6 theme implementation
- [ ] Responsive CSS
- [ ] Mobile optimization
- [ ] Accessibility (WCAG AA)

### Phase 9: Testing & Optimization
- [ ] Unit tests
- [ ] Integration tests
- [ ] Performance optimization
- [ ] Security hardening

## API Documentation

### Base URL
```
http://localhost:8080/api/v1
```

### Authentication Endpoints
```
POST /api/v1/auth/register      # Register new user
POST /api/v1/auth/login         # Login
POST /api/v1/auth/logout        # Logout
GET  /api/v1/auth/oauth/{provider}/initiate  # OAuth flow
```

### AI Generation Endpoints
```
POST /api/v1/ai/generate        # Create generation job
GET  /api/v1/ai/jobs/{jobId}    # Get job status
GET  /api/v1/ai/jobs/{jobId}/status  # SSE real-time updates
```

### Wall & Post Endpoints
```
GET  /api/v1/walls              # List walls
GET  /api/v1/walls/{wallId}     # Get wall details
GET  /api/v1/walls/{wallId}/posts  # Get wall posts
POST /api/v1/walls/{wallId}/posts  # Create post
```

### Bricks Management
```
GET  /api/v1/bricks/balance     # Current balance
POST /api/v1/bricks/claim-daily # Claim daily bricks
GET  /api/v1/bricks/transactions  # Transaction history
```

Full API documentation will be available at `/api/v1/docs` (coming soon).

## Database Schema

28 tables organized into categories:

### Core Entities (6 tables)
- users, oauth_connections, walls, posts, media_attachments, locations

### AI System (6 tables)
- ai_applications, prompt_templates, template_ratings, app_collections, collection_items, ai_generation_jobs

### Social Features (6 tables)
- reactions, comments, subscriptions, friendships, notifications

### Messaging (5 tables)
- conversations, conversation_participants, messages, message_media, message_read_status

### Supporting (5 tables)
- sessions, bricks_transactions, user_social_links, user_activity_log

## Configuration

### Environment Variables

Key configuration options in `.env`:

```env
# Database
DB_HOST=mysql
DB_NAME=wall_social_platform
DB_USER=wall_user
DB_PASSWORD=your_secure_password

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Ollama AI
OLLAMA_HOST=ollama
OLLAMA_MODEL=deepseek-coder:6.7b

# Bricks System
BRICKS_PER_TOKEN=100
DAILY_BRICKS_AMOUNT=100
REGISTRATION_BONUS_BRICKS=500
```

## Deployment

### Production Checklist
- [ ] Change default admin password
- [ ] Update database credentials
- [ ] Set strong JWT secret
- [ ] Configure OAuth providers
- [ ] Enable HTTPS/SSL
- [ ] Set up backups
- [ ] Configure email service
- [ ] Enable production error logging
- [ ] Set up monitoring
- [ ] Configure CDN for media

### Docker Production
```bash
docker-compose -f docker-compose.prod.yml up -d
```

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Follow PSR-12 coding standards
4. Write tests for new features
5. Submit a pull request

### Code Standards
```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Run static analysis
composer phpstan

# Run tests
composer test
```

## Security

### Reporting Vulnerabilities
Please report security issues to security@wall.cyka.lol

### Security Features
- Password hashing with bcrypt/Argon2
- SQL injection prevention via prepared statements
- XSS protection with HTMLPurifier
- CSRF token validation
- Rate limiting
- Secure file upload validation
- Content Security Policy for AI apps

## License

MIT License - see LICENSE file for details

## Support

- **Documentation**: Coming soon at docs.wall.cyka.lol
- **Issues**: GitHub Issues
- **Discussions**: GitHub Discussions
- **Email**: support@wall.cyka.lol

## Acknowledgments

- Ollama for local AI inference
- DeepSeek for code generation models
- PHP community for excellent libraries
- All contributors and users

---

Built with ❤️ for the AI-powered social web

**Status**: Phase 1 Complete - Environment Setup ✅
