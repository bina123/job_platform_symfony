# Job Platform REST API

A complete REST API built with **Symfony 8** demonstrating enterprise-level architecture patterns: JWT Authentication, Service Layer, DTOs, Event-Driven Design, Async Messaging, Voters, Custom Exceptions, and Middleware.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Setup](#setup)
- [System Design](#system-design)
  - [Architecture Overview](#architecture-overview)
  - [Entity Relationship Diagram](#entity-relationship-diagram)
  - [Request Lifecycle](#request-lifecycle)
  - [Authentication Flow](#authentication-flow)
  - [Event-Driven Architecture](#event-driven-architecture)
  - [Authorization Model](#authorization-model)
  - [Exception Handling](#exception-handling)
- [API Documentation](#api-documentation)
  - [Authentication](#authentication)
  - [Jobs](#jobs)
  - [Applications](#applications)
- [Error Responses](#error-responses)
- [Testing](#testing)
- [Project Structure](#project-structure)

---

## Tech Stack

| Component        | Technology                       |
|------------------|----------------------------------|
| Framework        | Symfony 8.0                      |
| Language         | PHP 8.4                         |
| Database         | MySQL 8.0                       |
| ORM              | Doctrine 3.6                    |
| Auth             | LexikJWTAuthenticationBundle    |
| Queue            | Symfony Messenger (Doctrine transport) |
| Validation       | Symfony Validator               |
| Testing          | PHPUnit 13                      |

---

## Setup

### Prerequisites

- PHP >= 8.4
- Composer
- MySQL 8.0

### Installation

```bash
# Clone and install dependencies
git clone <repo-url> && cd job-platform
composer install

# Configure database in .env
# DATABASE_URL="mysql://root:root@127.0.0.1:3306/job_platform?serverVersion=8.0"

# Create database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Generate JWT keys
php bin/console lexik:jwt:generate-keypair

# Start the server
symfony server:start
# or
php -S localhost:8000 -t public
```

### Test Setup

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
php bin/phpunit
```

---

## System Design

### Architecture Overview

The application follows a **layered architecture** with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                     HTTP Request                            │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│              Event Subscribers (Middleware)                  │
│  ┌──────────────────┐ ┌──────────┐ ┌────────────────────┐  │
│  │ JsonRequest      │ │ Request  │ │ ApiResponse        │  │
│  │ Transformer      │ │ Logging  │ │ Subscriber         │  │
│  └──────────────────┘ └──────────┘ └────────────────────┘  │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                  Security Layer                              │
│  ┌──────────────────┐ ┌──────────────────────────────────┐  │
│  │ JWT Authenticator│ │ Voters (JobVoter, AppVoter)      │  │
│  └──────────────────┘ └──────────────────────────────────┘  │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│              Controllers (thin, delegate only)              │
│  ┌──────────────┐ ┌──────────────┐ ┌─────────────────────┐ │
│  │ AuthController│ │ JobController│ │ApplicationController│ │
│  └──────────────┘ └──────────────┘ └─────────────────────┘ │
└──────────┬─────────────┬────────────────────┬───────────────┘
           │             │                    │
┌──────────▼─────────────▼────────────────────▼───────────────┐
│  RequestValidatorService (deserialize JSON → DTO → validate)│
└──────────┬─────────────┬────────────────────┬───────────────┘
           │             │                    │
┌──────────▼─────────────▼────────────────────▼───────────────┐
│                    Service Layer                             │
│  ┌─────────────┐ ┌──────────────┐ ┌──────────────────────┐  │
│  │ UserService │ │ JobService   │ │ ApplicationService   │  │
│  └─────────────┘ └──────┬───────┘ └──────────┬───────────┘  │
│                         │ dispatch            │ dispatch     │
│                    ┌────▼────────────────┐    │              │
│                    │  Domain Events      │◄───┘              │
│                    │  (JobCreated,       │                    │
│                    │   AppSubmitted,     │                    │
│                    │   StatusChanged)    │                    │
│                    └────┬───────────────┘                    │
└─────────────────────────┼────────────────────────────────────┘
                          │
┌─────────────────────────▼────────────────────────────────────┐
│                   Event Listeners                             │
│  ┌────────────────────┐ ┌──────────────────────────────────┐ │
│  │ Log side effects   │ │ Dispatch async messages          │ │
│  └────────────────────┘ └──────────────┬───────────────────┘ │
└────────────────────────────────────────┼─────────────────────┘
                                         │
┌────────────────────────────────────────▼─────────────────────┐
│                  Messenger (Async Queue)                      │
│  ┌──────────────────────────┐ ┌────────────────────────────┐ │
│  │ SendJobPostedNotification│ │SendApplicationNotification │ │
│  │ Handler                  │ │Handler                     │ │
│  └──────────────────────────┘ └────────────────────────────┘ │
│                                                               │
│  Transport: Doctrine DBAL │ Retry: 3x with 2x backoff       │
└──────────────────────────────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                   Persistence Layer                          │
│  ┌─────────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ UserRepository  │ │JobRepository │ │AppRepository     │  │
│  │ • findByEmail   │ │• findOpenJobs│ │• findByDeveloper │  │
│  │ • upgradePass   │ │• findByFilter│ │• findByJob       │  │
│  │                 │ │• findPaginatd│ │• hasUserApplied  │  │
│  └─────────────────┘ └──────────────┘ └──────────────────┘  │
│                                                               │
│  EntityManager (Doctrine ORM) → MySQL 8.0                    │
└──────────────────────────────────────────────────────────────┘
```

### Entity Relationship Diagram

```
┌──────────────────────┐
│        User          │
├──────────────────────┤
│ id          PK       │
│ name        VARCHAR  │
│ email       UNIQUE   │
│ password    VARCHAR  │
│ roles       JSON     │
│ createdAt   DATETIME │
└──────────┬───────────┘
           │
     ┌─────┴──────┐
     │ 1        1 │
     │            │
     │ *        * │
┌────▼─────┐ ┌───▼──────────┐
│   Job    │ │ Application  │
├──────────┤ ├──────────────┤
│ id    PK │ │ id        PK │
│ title    │ │ status       │
│ desc     │ │ coverLetter  │
│ salary   │ │ createdAt    │
│ status   │ │ job_id    FK │──┐
│ created  │ │ dev_id    FK │  │
│ emp_id FK│ └──────────────┘  │
└────┬─────┘                   │
     │ 1                       │
     └─────────────────────────┘
                *
```

**Relationships:**
- **User 1 ──* Job**: An employer creates many jobs
- **User 1 ──* Application**: A developer submits many applications
- **Job 1 ──* Application**: A job receives many applications

### Request Lifecycle

Every API request passes through this pipeline:

1. **JsonRequestTransformerSubscriber** (priority 100) — Decodes JSON body into request parameters
2. **RequestLoggingSubscriber** (priority 200) — Logs `METHOD /path` and starts timer
3. **JWT Authenticator** — Validates Bearer token, loads User
4. **Access Control** — Checks firewall rules (`IS_AUTHENTICATED_FULLY`)
5. **Controller** — Calls `denyAccessUnlessGranted()` which invokes **Voters**
6. **RequestValidatorService** — Deserializes JSON → DTO, validates constraints
7. **Service Layer** — Executes business logic, dispatches events
8. **Event Listeners** — Log side effects, dispatch async messages
9. **Response DTOs** — Transform entities to JSON-safe objects
10. **ApiResponseSubscriber** (priority -100) — Sets `Content-Type: application/json`
11. **RequestLoggingSubscriber** (priority -200) — Logs `STATUS (duration_ms)`
12. **ExceptionListener** (if error) — Catches any exception, returns structured JSON

### Authentication Flow

```
┌──────────────┐     POST /api/register      ┌──────────────┐
│              │ ───────────────────────────► │              │
│   Client     │     { name, email,          │  AuthController│
│              │       password, role }       │  + UserService │
│              │ ◄─────────────────────────── │              │
│              │     201 { user data }        │              │
│              │                              └──────────────┘
│              │
│              │     POST /api/login          ┌──────────────┐
│              │ ───────────────────────────► │ json_login    │
│              │     { email, password }      │ authenticator │
│              │ ◄─────────────────────────── │              │
│              │     200 { token: "eyJ..." }  └──────────────┘
│              │
│              │     GET /api/jobs            ┌──────────────┐
│              │ ───────────────────────────► │ JWT           │
│              │     Authorization:           │ authenticator │
│              │     Bearer eyJ...            │     ↓         │
│              │ ◄─────────────────────────── │ Controller    │
└──────────────┘     200 [ jobs... ]          └──────────────┘
```

### Event-Driven Architecture

Business events flow through a three-stage pipeline:

```
Service Layer                Event Listeners              Messenger Queue
─────────────               ────────────────             ────────────────

JobService.create()    ──►  JobCreatedListener     ──►  SendJobPostedNotification
  dispatches                  • Logs creation              • Handler looks up Job
  JobCreatedEvent             • Dispatches message         • Sends email (or logs)

AppService.apply()     ──►  AppSubmittedListener   ──►  SendApplicationNotification
  dispatches                  • Logs submission            • Handler looks up App
  AppSubmittedEvent           • Dispatches message         • Notifies employer

AppService.update      ──►  StatusChangedListener  ──►  SendApplicationNotification
  Status() dispatches         • Logs old→new status       • Handler looks up App
  StatusChangedEvent          • Dispatches message         • Notifies developer
```

**Why this pattern?**
- Services focus on business logic only
- Listeners handle side effects (logging, notifications)
- Messenger processes expensive work (emails) asynchronously with retry

### Authorization Model

Two **Voters** handle fine-grained access control:

| Action | Voter | Rule |
|--------|-------|------|
| View any job | JobVoter::VIEW | Always allowed (public) |
| Create job | JobVoter::CREATE | Must have `ROLE_EMPLOYER` |
| Edit job | JobVoter::EDIT | Must be the job's employer |
| Delete job | JobVoter::DELETE | Must be the job's employer |
| Apply to job | ApplicationVoter::CREATE | Must have `ROLE_DEVELOPER` |
| View application | ApplicationVoter::VIEW | Must be the applicant OR the job's employer |
| Accept/reject | ApplicationVoter::MANAGE | Must be the job's employer |

### Exception Handling

All exceptions are caught by `ExceptionListener` and returned as structured JSON:

```
ApiException (abstract)
  ├── ValidationException      → 422  (field-level errors)
  ├── ResourceNotFoundException → 404  (entity not found)
  └── AccessDeniedException     → 403  (permission denied)

Symfony Exceptions (also handled):
  ├── NotFoundHttpException    → 404
  ├── AccessDeniedHttpException → 403
  └── Any other exception      → 500
```

---

## API Documentation

### Base URL

```
http://localhost:8000/api
```

### Headers

All requests should include:
```
Content-Type: application/json
```

Authenticated requests also need:
```
Authorization: Bearer <jwt_token>
```

---

### Authentication

#### Register

```
POST /api/register
```

**Auth:** None

**Request:**
```json
{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "secret123",
    "role": "employer"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| name | string | Yes | Max 255 chars |
| email | string | Yes | Valid email format |
| password | string | Yes | 6–255 chars |
| role | string | Yes | `"employer"` or `"developer"` |

**Response:** `201 Created`
```json
{
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "roles": ["ROLE_EMPLOYER", "ROLE_USER"],
    "createdAt": "2026-03-16T10:30:00+00:00"
}
```

---

#### Login

```
POST /api/login
```

**Auth:** None

**Request:**
```json
{
    "email": "jane@example.com",
    "password": "secret123"
}
```

**Response:** `200 OK`
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Error:** `401 Unauthorized`
```json
{
    "code": 401,
    "message": "Invalid credentials."
}
```

---

#### Get Current User

```
GET /api/me
```

**Auth:** Bearer token required

**Response:** `200 OK`
```json
{
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "roles": ["ROLE_EMPLOYER", "ROLE_USER"],
    "createdAt": "2026-03-16T10:30:00+00:00"
}
```

---

### Jobs

#### List Jobs (Public)

```
GET /api/jobs
```

**Auth:** None

**Query Parameters (all optional):**

| Param | Type | Description |
|-------|------|-------------|
| status | string | Filter by `"open"` or `"closed"` |
| minSalary | int | Minimum salary filter |
| maxSalary | int | Maximum salary filter |
| search | string | Search in title and description |
| limit | int | Number of results |
| offset | int | Skip first N results |

**Examples:**
```
GET /api/jobs?status=open&minSalary=80000
GET /api/jobs?search=PHP&limit=10&offset=0
```

**Response:** `200 OK`
```json
[
    {
        "id": 1,
        "title": "Senior PHP Developer",
        "description": "We need a Symfony expert...",
        "salary": 120000,
        "status": "open",
        "createdAt": "2026-03-16T10:30:00+00:00",
        "employer": {
            "id": 1,
            "name": "Jane Doe"
        },
        "applicationCount": 3
    }
]
```

---

#### Get Job

```
GET /api/jobs/{id}
```

**Auth:** None

**Response:** `200 OK` — Single JobResponse (same shape as list item)

**Error:** `404 Not Found`
```json
{
    "error": "Job not found (id: 99)",
    "code": 404
}
```

---

#### Create Job

```
POST /api/jobs
```

**Auth:** Bearer token required (must be `ROLE_EMPLOYER`)

**Request:**
```json
{
    "title": "Senior PHP Developer",
    "description": "Must know Symfony, Doctrine, REST APIs",
    "salary": 120000
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| title | string | Yes | Max 255 chars |
| description | string | Yes | — |
| salary | int | No | Must be positive |

**Response:** `201 Created` — JobResponse

**Error:** `403 Forbidden` (if not employer)

---

#### Update Job

```
PUT /api/jobs/{id}
```

**Auth:** Bearer token required (must be the job's owner)

**Request** (all fields optional):
```json
{
    "title": "Updated Title",
    "salary": 150000,
    "status": "closed"
}
```

| Field | Type | Rules |
|-------|------|-------|
| title | string | Max 255 chars |
| description | string | — |
| salary | int | Must be positive |
| status | string | `"open"` or `"closed"` |

**Response:** `200 OK` — JobResponse

**Error:** `403 Forbidden` (if not owner)

---

#### Delete Job

```
DELETE /api/jobs/{id}
```

**Auth:** Bearer token required (must be the job's owner)

**Response:** `204 No Content`

**Error:** `403 Forbidden` (if not owner)

---

#### Get My Jobs (Employer)

```
GET /api/jobs/employer/me
```

**Auth:** Bearer token required

**Response:** `200 OK` — Array of JobResponse

---

### Applications

#### Apply to Job

```
POST /api/jobs/{id}/apply
```

**Auth:** Bearer token required (must be `ROLE_DEVELOPER`)

**Request:**
```json
{
    "coverLetter": "I am a great fit for this role because..."
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| coverLetter | string | No | Max 5000 chars |

**Response:** `201 Created`
```json
{
    "id": 1,
    "status": "pending",
    "coverLetter": "I am a great fit for this role because...",
    "createdAt": "2026-03-16T11:00:00+00:00",
    "job": {
        "id": 1,
        "title": "Senior PHP Developer"
    },
    "developer": {
        "id": 2,
        "name": "John Smith"
    }
}
```

**Errors:**
- `403 Forbidden` — User is not a developer
- `422 Unprocessable Entity` — Job is closed or user already applied

---

#### List My Applications (Developer)

```
GET /api/applications
```

**Auth:** Bearer token required

**Response:** `200 OK` — Array of ApplicationResponse

---

#### Get Application

```
GET /api/applications/{id}
```

**Auth:** Bearer token required (must be the applicant or the job's employer)

**Response:** `200 OK` — ApplicationResponse

---

#### Update Application Status (Employer)

```
PUT /api/applications/{id}/status
```

**Auth:** Bearer token required (must be the job's employer)

**Request:**
```json
{
    "status": "accepted"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| status | string | Yes | `"accepted"` or `"rejected"` |

**Response:** `200 OK` — ApplicationResponse with updated status

---

#### Get Job's Applications (Employer)

```
GET /api/jobs/{id}/applications
```

**Auth:** Bearer token required (must be the job's owner)

**Response:** `200 OK` — Array of ApplicationResponse

---

## Error Responses

All errors follow a consistent JSON format:

#### Validation Error — `422`
```json
{
    "error": "Validation failed",
    "code": 422,
    "errors": {
        "name": "The name field is required.",
        "role": "Role must be either \"employer\" or \"developer\"."
    }
}
```

#### Not Found — `404`
```json
{
    "error": "Job not found (id: 42)",
    "code": 404
}
```

#### Access Denied — `403`
```json
{
    "error": "Access denied",
    "code": 403
}
```

#### Unauthorized — `401`
```json
{
    "code": 401,
    "message": "Invalid credentials."
}
```

#### Server Error — `500`
```json
{
    "error": "Internal server error",
    "code": 500
}
```

---

## Testing

```bash
# Run all tests (24 tests, 36 assertions)
php bin/phpunit

# Run by category
php bin/phpunit --filter=AuthController       # Auth flow tests
php bin/phpunit --filter=JobController         # Job CRUD + auth tests
php bin/phpunit --filter=ApplicationController # Application tests
php bin/phpunit --filter=RegisterRequest       # DTO validation unit tests
php bin/phpunit --filter=JobVoter              # Voter logic unit tests
```

### Test Coverage

| Suite | Tests | What It Covers |
|-------|-------|----------------|
| AuthControllerTest | 5 | Register, login, /me, invalid creds, no auth |
| JobControllerTest | 6 | List, create, show, update, delete, validation |
| ApplicationControllerTest | 3 | Apply, employer denied, list apps |
| RegisterRequestValidationTest | 5 | DTO validation rules |
| JobVoterTest | 3 | Voter grant/deny logic |

---

## Project Structure

```
src/
├── Controller/
│   ├── AuthController.php           # Register, Login, /me
│   ├── JobController.php            # Job CRUD
│   └── ApplicationController.php    # Apply, manage applications
│
├── DTO/
│   ├── Request/
│   │   ├── RegisterRequest.php      # Registration validation
│   │   ├── CreateJobRequest.php     # Job creation validation
│   │   ├── UpdateJobRequest.php     # Job update validation
│   │   └── CreateApplicationRequest.php
│   └── Response/
│       ├── UserResponse.php         # User output format
│       ├── JobResponse.php          # Job output format
│       └── ApplicationResponse.php  # Application output format
│
├── Entity/
│   ├── User.php                     # UserInterface + roles
│   ├── Job.php                      # Job with status constants
│   └── Application.php             # Application with status constants
│
├── Event/
│   ├── JobCreatedEvent.php
│   ├── ApplicationSubmittedEvent.php
│   └── ApplicationStatusChangedEvent.php
│
├── EventListener/
│   ├── ExceptionListener.php        # Global JSON error handling
│   ├── JobCreatedListener.php       # Log + dispatch async message
│   ├── ApplicationSubmittedListener.php
│   └── ApplicationStatusChangedListener.php
│
├── EventSubscriber/
│   ├── JsonRequestTransformerSubscriber.php  # Decode JSON bodies
│   ├── RequestLoggingSubscriber.php          # Log requests + timing
│   └── ApiResponseSubscriber.php             # Force JSON Content-Type
│
├── Exception/
│   ├── ApiException.php             # Abstract base (statusCode, errors)
│   ├── ValidationException.php      # 422 with field errors
│   ├── ResourceNotFoundException.php # 404
│   └── AccessDeniedException.php    # 403
│
├── Message/
│   ├── SendJobPostedNotification.php
│   └── SendApplicationNotification.php
│
├── MessageHandler/
│   ├── SendJobPostedNotificationHandler.php
│   └── SendApplicationNotificationHandler.php
│
├── Repository/
│   ├── UserRepository.php           # findByEmail, upgradePassword
│   ├── JobRepository.php            # findOpenJobs, findByFilters, findPaginated
│   └── ApplicationRepository.php    # findByDeveloper, findByJob, hasUserApplied
│
├── Security/
│   └── Voter/
│       ├── JobVoter.php             # CREATE, VIEW, EDIT, DELETE
│       └── ApplicationVoter.php     # CREATE, VIEW, MANAGE
│
└── Service/
    ├── UserService.php              # register, findById
    ├── JobService.php               # CRUD + event dispatch
    ├── ApplicationService.php       # apply, updateStatus + events
    └── RequestValidatorService.php  # JSON → DTO → validate

tests/
├── Controller/
│   ├── AuthControllerTest.php
│   ├── JobControllerTest.php
│   └── ApplicationControllerTest.php
├── Unit/
│   ├── DTO/RegisterRequestValidationTest.php
│   └── Voter/JobVoterTest.php
├── Trait/
│   └── AuthenticatedClientTrait.php  # JWT test helper
└── bootstrap.php

config/
├── packages/
│   ├── security.yaml                # Firewalls, providers, access control
│   ├── messenger.yaml               # Async transport, message routing
│   ├── lexik_jwt_authentication.yaml # JWT keys config
│   ├── doctrine.yaml                # ORM + DBAL config
│   └── ...
└── routes/
    └── security.yaml
```
