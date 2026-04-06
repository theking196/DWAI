# DWAI Studio - Project Documentation

## Overview

**DWAI Studio** (Digital Works AI Studio) is a Laravel-based creative writing and content generation platform that helps users create cinematic visual stories through AI-assisted workflows.

### Mission
Enable creators to generate professional-quality visual content (images, videos, music) through intelligent AI workflows with proper project management, continuity tracking, and canon management.

---

## Features

### 1. Project Management
- Create/edit/archive projects
- Project types: brainstorm, script, storyboard, edit
- Visual style with main + supporting images
- Reference image management with tags
- Full-text search + semantic search

### 2. Session Types

| Type | Description |
|------|-------------|
| **Normal** | Standard AI generation workflow |
| **Assistant Agent** | Chat-based AI assistant with phase progression |
| **Progressive Build** | Step-by-step with context awareness |

### 3. Short-Term Memory
- Temporary notes
- AI reasoning traces
- Draft text
- Session references
- Auto-promotion to canon

### 4. Long-Term Canon
- Canonical story entries
- Version history with restore/diff
- Canon candidates (approval workflow)
- Semantic search ready

### 5. AI Integration
- **Mock Provider** (development)
- **OpenRouter** (production)
- **Groq** (production)
- Swappable via `.env`

### 6. Context-Aware Generation
- Project visual style
- Canon entries
- Reference images
- Previous step outputs (progressive)
- Cinematic language (camera angles, lighting, mood)

### 7. Timeline Tracking
- Timeline events per session
- Order/index tracking
- Sequence detection
- Conflict validation

### 8. Conflict Detection
- Canon contradictions
- Timeline clashes
- Missing references
- Resolution suggestions

### 9. Backup & Import/Export
- Full project packages (.dwai)
- Scheduled backups
- Restore with checkpoint

### 10. Settings System
- App defaults
- AI provider configuration
- Storage settings
- Visual/generation defaults

---

## Architecture

### Tech Stack
- **Backend**: Laravel 11 (PHP 8.2)
- **Frontend**: Blade templates + Vanilla JS
- **Database**: PostgreSQL (recommended) / SQLite
- **Vector DB**: Ready for embeddings

### Key Models
```
Project → Session (normal/assistant/progressive)
       → CanonEntry (long-term)
       → ReferenceImage
       → TimelineEvent

Session → AIOutput
        → ShortTermMemory
        → BuildSteps (progressive)
        → AssistantState
```

### API Structure
- RESTful endpoints
- Form Request validation
- Service layer pattern
- Unified data/search services

---

## Session Workflows

### Normal Session
1. Create session in project
2. Enter prompt
3. Generate text/image
4. Review outputs
5. Optionally save to canon

### Assistant Agent Mode
```
idea_input → refining → structuring → images → video → music → complete
```

**Phases:**
1. **idea_input**: User drops initial idea
2. **refining**: AI asks clarifying questions
3. **structure_planning**: AI generates JSON structure
4. **image_prompts**: Generate cinematic image prompts
5. **video_prompts**: Convert to motion prompts
6. **music_prompt**: Generate BGM prompt
7. **complete**: All outputs ready

### Progressive Build Mode
- Steps generated based on input complexity:
  - **Short** (<30 words): 3 steps
  - **Medium** (30-100 words): 6 steps
  - **Long** (>100 words): 7 steps
- Each step builds on previous outputs
- Context: project style, canon, references
- Actions: Next, Refine, Skip

---

## Database Schema

### Core Tables
- `projects` - Project metadata + visual style
- `sessions` - Sessions with type, build_steps, assistant fields
- `canon_entries` - Long-term story canon
- `canon_versions` - Version history
- `canon_candidates` - Promotion queue
- `reference_images` - Image metadata + tags
- `ai_outputs` - Generated content
- `timeline_events` - Story timeline
- `assets` - Asset management
- `settings` - App configuration
- `activity_logs` - Audit trail

### Key Migrations
- 2026_04_06_000001 - Assistant mode fields
- 2026_04_06_000002 - Session type fields

---

## API Endpoints

### Projects
- `GET/POST /api/projects`
- `GET/PUT/DELETE /api/projects/{id}`
- `GET /api/projects/{id}/stats`

### Sessions
- `GET/POST /api/projects/{project}/sessions`
- `GET/PUT/DELETE /api/sessions/{id}`
- `POST /api/sessions/{id}/close`
- `POST /api/sessions/{id}/archive`

### Assistant
- `POST /api/dwai/assistant/{session}/handle`
- `GET /api/dwai/assistant/{session}/state`
- `POST /api/dwai/assistant/{session}/reset`

### Progressive
- `POST /api/dwai/progressive/{session}/handle`
- `GET /api/dwai/progressive/{session}/state`
- `POST /api/dwai/progressive/{session}/next`
- `POST /api/dwai/progressive/{session}/refine`

### Canon
- `GET/POST /api/dwai/canon`
- `GET/PUT/DELETE /api/dwai/canon/{id}`
- `POST /api/dwai/canon/{id}/promote`
- `GET /api/dwai/canon/{id}/versions`

### AI
- `POST /api/ai/generate/text`
- `POST /api/ai/generate/image`
- `GET /api/ai/outputs/{session}`
- `GET /api/ai/outputs/{session}/status/{output}`

---

## UI Pages

### Dashboard (`/dashboard`)
- Real project/session/outputs stats
- Recent activity

### Projects (`/projects`)
- Project list with cards
- Create new project

### Project Workspace (`/projects/{id}`)
- Tabs: Overview, Sessions, Canon, References, Timeline
- Visual style editor

### Session Workspace (`/sessions/{id}`)
- AI Generator panel
- Memory bar
- Assistant Agent panel (if assistant mode)
- Progressive Build panel (if progressive mode)
- Conflicts panel
- Visual Style panel

### Create Session (`/sessions/create`)
- Session type selector: Normal / Assistant / Progressive
- Project selection

---

## Deployment

### Free Hosting Options
1. **Render.com** - Free PostgreSQL + Web Service
2. **Fly.io** - Free tier
3. **Railway** - $5 credit

### Files Included
- `Dockerfile` - PHP-FPM container
- `docker-compose.yml` - Local development
- `render.yaml` - Render deployment config

### Environment Variables
```
APP_ENV=production
APP_KEY=base64:...
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=dwai_studio
DB_USERNAME=...
DB_PASSWORD=...

# AI Providers
AI_TEXT_PROVIDER=mock
AI_IMAGE_PROVIDER=mock
AI_STORYBOARD_PROVIDER=mock
OPENROUTER_API_KEY=...
GROQ_API_KEY=...
```

---

## Git History (Recent)

| Commit | Description |
|--------|-------------|
| 4dc54ba | Add deployment files (Dockerfile, render.yaml) |
| 1982e0c | Context-aware progressive generation |
| e6e87b8 | Progressive Build UI |
| 457ac59 | Dynamic step generation |
| cc41389 | ProgressiveSessionController |
| aa1c856 | Session type fields |
| 085afc2 | Assistant Agent UI panel |
| 1ec60c1 | AssistantContextService |
| 1e20104 | AssistantController |
| a0fea0b | Assistant mode fields |
| 5d6036a | OpenRouter + Groq providers |

---

## Next Steps

1. **Deploy** to Render.com
2. **Configure** AI providers (OpenRouter/Groq API keys)
3. **Add** image generation service
4. **Add** video generation integration
5. **Add** music generation integration
6. **Build** React/Vue frontend (optional)
7. **Add** user authentication
8. **Add** multi-tenancy

---

## License
Private - For personal use only
