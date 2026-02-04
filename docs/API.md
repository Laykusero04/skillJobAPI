# SkillJob API Documentation

**Version:** 1.0
**Last Updated:** 05/02/2026
**Base URL:** `/api`
**Authentication:** Laravel Sanctum (Bearer Token)

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Verification](#verification)
4. [Users](#users)
5. [Skills](#skills)
6. [User Skills](#user-skills)
7. [Freelancer Profile](#freelancer-profile)
8. [Gigs](#gigs)
9. [Gig Applications (Employer)](#gig-applications-employer)
10. [Freelancer Applications](#freelancer-applications)
11. [Gig Bookmarks](#gig-bookmarks)
12. [Notifications](#notifications)
13. [Conversations](#conversations)
14. [Messages](#messages)
15. [Reports](#reports)
16. [Penalties](#penalties)
17. [Enums & Constants](#enums--constants)
18. [Error Responses](#error-responses)

---

## Overview

SkillJob is a freelance gig marketplace API built with Laravel. It connects **Employers** (role 2) who post short-term gigs with **Freelancers** (role 3) who apply for them.

### Roles

| Role | Value | Description |
|------|-------|-------------|
| Admin | `1` | Platform administrator |
| Employer | `2` | Posts gigs and manages applicants |
| Freelancer | `3` | Browses and applies to gigs |

### Authentication

All protected routes require a Bearer token in the `Authorization` header:

```
Authorization: Bearer {token}
```

Tokens are issued via Laravel Sanctum and expire after **7 days**.

### Pagination

Paginated endpoints return the standard Laravel pagination envelope:

```json
{
  "current_page": 1,
  "data": [],
  "first_page_url": "...",
  "from": 1,
  "last_page": 1,
  "last_page_url": "...",
  "next_page_url": null,
  "path": "...",
  "per_page": 15,
  "prev_page_url": null,
  "to": 10,
  "total": 10
}
```

---

## Authentication

### Register

Create a new user account and receive an auth token.

```
POST /api/auth/register
```

**Access:** Public

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `first_name` | string | Yes | max:255 |
| `last_name` | string | No | max:255 |
| `email` | string | Yes | valid email, unique |
| `password` | string | Yes | min:6 |
| `password_confirmation` | string | Yes | must match password |
| `role` | integer | No | `1`, `2`, or `3` (default: `3`) |
| `phone_number` | string | No | max:255 |
| `profile_image_url` | string | No | max:255 |

**Response:** `201 Created`

```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "role": 3,
    "profile_image_url": null,
    "phone_number": null,
    "email_verified": false,
    "phone_verified": false,
    "created_at": "2026-02-05T10:00:00+00:00",
    "updated_at": "2026-02-05T10:00:00+00:00"
  },
  "token": "1|abc123...",
  "refresh_token": null,
  "expires_at": "2026-02-12T10:00:00+00:00"
}
```

---

### Login

Authenticate an existing user.

```
POST /api/auth/login
```

**Access:** Public

**Request Body:**

| Field | Type | Required |
|-------|------|----------|
| `email` | string | Yes |
| `password` | string | Yes |

**Response:** `200 OK`

```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "role": 3,
    "profile_image_url": null,
    "phone_number": null,
    "email_verified": true,
    "phone_verified": false,
    "created_at": "2026-02-05T10:00:00+00:00",
    "updated_at": "2026-02-05T10:00:00+00:00"
  },
  "token": "2|def456...",
  "refresh_token": null,
  "expires_at": "2026-02-12T10:00:00+00:00"
}
```

**Error:** `401 Unauthorized` — Invalid email or password.

---

### Get Current User

Retrieve the authenticated user's profile.

```
GET /api/auth/me
```

**Access:** Authenticated

**Response:** `200 OK`

```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "role": 3,
    "profile_image_url": null,
    "phone_number": "+1234567890",
    "email_verified": true,
    "phone_verified": true,
    "created_at": "2026-02-05T10:00:00+00:00",
    "updated_at": "2026-02-05T10:00:00+00:00",
    "unread_notifications_count": 5
  },
  "skills": [
    { "id": 1, "name": "Cleaning", "created_at": "...", "updated_at": "..." }
  ],
  "freelancer_profile": {
    "bio": "Experienced freelancer...",
    "resume_url": "/storage/resumes/file.pdf",
    "resume_uploaded_at": "2026-02-05T10:00:00+00:00",
    "availability": "Weekdays",
    "available_today": true,
    "avg_rating": 4.50,
    "completed_gigs": 12,
    "no_shows": 0
  }
}
```

> **Note:** `skills` and `freelancer_profile` are only included for freelancer users (role 3).

---

### Logout

Revoke the current access token.

```
POST /api/auth/logout
```

**Access:** Authenticated

**Response:** `204 No Content`

---

## Users

### Get All Users

Retrieve a list of all registered users.

```
GET /api/users
```

**Access:** Authenticated

**Response:** `200 OK`

```json
{
  "users": [
    {
      "id": 1,
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "role": 3,
      "profile_image_url": null,
      "phone_number": null,
      "created_at": "2026-02-05T10:00:00+00:00",
      "updated_at": "2026-02-05T10:00:00+00:00"
    }
  ]
}
```

---

## Verification

> **Note:** These are temporary testing endpoints. Replace with real email/SMS verification in production.

### Get Verification Status

```
GET /api/verification/status
```

**Access:** Authenticated

**Response:** `200 OK`

```json
{
  "email_verified": true,
  "email_verified_at": "2026-02-05T10:00:00+00:00",
  "phone_verified": false,
  "phone_verified_at": null
}
```

---

### Verify Email

```
POST /api/verification/email/verify
```

**Access:** Authenticated

**Response:** `200 OK`

```json
{
  "message": "Email verified successfully.",
  "email_verified": true,
  "email_verified_at": "2026-02-05T10:00:00+00:00"
}
```

---

### Verify Phone

```
POST /api/verification/phone/verify
```

**Access:** Authenticated

**Request Body (optional):**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `phone_number` | string | No | max:20 (updates phone number if provided) |

**Response:** `200 OK`

```json
{
  "message": "Phone number verified successfully.",
  "phone_verified": true,
  "phone_verified_at": "2026-02-05T10:00:00+00:00",
  "phone_number": "+1234567890"
}
```

**Error:** `422` — Phone number is required (if none set).

---

## Skills

Full CRUD for the skills catalog.

### List All Skills

```
GET /api/skills
```

**Access:** Authenticated

**Response:** `200 OK`

```json
[
  { "id": 1, "name": "Cleaning", "created_at": "...", "updated_at": "..." },
  { "id": 2, "name": "Cooking", "created_at": "...", "updated_at": "..." }
]
```

---

### Create Skill

```
POST /api/skills
```

**Access:** Authenticated

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | Yes | max:255, unique |

**Response:** `201 Created`

```json
{ "id": 3, "name": "Driving", "created_at": "...", "updated_at": "..." }
```

---

### Get Skill

```
GET /api/skills/{skill}
```

**Access:** Authenticated

**Response:** `200 OK`

```json
{ "id": 1, "name": "Cleaning", "created_at": "...", "updated_at": "..." }
```

---

### Update Skill

```
PUT /api/skills/{skill}
```

**Access:** Authenticated

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | Yes | max:255, unique (except current) |

**Response:** `200 OK`

---

### Delete Skill

```
DELETE /api/skills/{skill}
```

**Access:** Authenticated

**Response:** `200 OK`

```json
{ "message": "Skill deleted successfully." }
```

---

## User Skills

Manage the authenticated freelancer's skill set.

### List My Skills

```
GET /api/my-skills
```

**Access:** Authenticated, Freelancer only

**Response:** `200 OK`

```json
[
  { "id": 1, "name": "Cleaning", "created_at": "...", "updated_at": "..." }
]
```

---

### Update My Skills

Replaces the freelancer's entire skill set (sync).

```
PUT /api/my-skills
```

**Access:** Authenticated, Freelancer only

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `skill_ids` | array | Yes | min:1 |
| `skill_ids.*` | integer | Yes | must exist in skills table |

**Response:** `200 OK` — Returns the updated skills array.

---

## Freelancer Profile

### Get My Profile

```
GET /api/freelancer-profile
```

**Access:** Authenticated, Freelancer only

**Response:** `200 OK`

```json
{
  "freelancer_profile": {
    "bio": "Experienced in multiple fields...",
    "resume_url": "/storage/resumes/file.pdf",
    "resume_uploaded_at": "2026-02-05T10:00:00+00:00",
    "availability": "Weekdays",
    "available_today": true,
    "avg_rating": 4.50,
    "completed_gigs": 12,
    "no_shows": 0
  }
}
```

---

### Update My Profile

```
PATCH /api/freelancer-profile
```

**Access:** Authenticated, Freelancer only

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `bio` | string | No | nullable, max:5000 |
| `availability` | string | No | nullable, max:255 |
| `available_today` | boolean | No | |

**Response:** `200 OK` — Returns the full `freelancer_profile` object.

---

### Upload Resume

```
POST /api/freelancer-profile/resume
```

**Access:** Authenticated, Freelancer only

**Request Body:** `multipart/form-data`

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `resume` | file | Yes | pdf, doc, docx; max 5MB |

**Response:** `200 OK`

```json
{
  "resume_url": "/storage/resumes/abc123.pdf",
  "resume_uploaded_at": "2026-02-05T10:00:00+00:00"
}
```

---

## Gigs

### List Gigs

Behavior depends on the user's role:

- **Employer (role 2):** Lists their own gigs, optionally filtered by status.
- **Freelancer (role 3):** Lists open gigs with filters.

```
GET /api/gigs
```

**Access:** Authenticated

**Query Parameters (Employer):**

| Param | Type | Description |
|-------|------|-------------|
| `status` | string | Filter by gig status (`open`, `filled`, `completed`, `closed`). Use `all` or omit for all. |

**Query Parameters (Freelancer):**

| Param | Type | Description |
|-------|------|-------------|
| `location` | string | Partial match on location |
| `skill_id` | integer | Filter by primary or supporting skill |
| `min_pay` | numeric | Minimum pay |
| `max_pay` | numeric | Maximum pay |
| `time_slot` | string | `morning` (06:00-12:00), `afternoon` (12:00-18:00), `evening` (18:00+) |
| `latitude` | numeric | User latitude (requires `longitude`) |
| `longitude` | numeric | User longitude (requires `latitude`) |
| `radius_km` | numeric | Search radius in km (default: 50) |
| `new_only` | boolean | Only gigs created in the last 24 hours |

**Response:** `200 OK` — Paginated (15 per page).

**Gig Object:**

```json
{
  "id": 1,
  "employer_id": 2,
  "title": "Restaurant Server",
  "primary_skill_id": 1,
  "location": "Dublin, Ireland",
  "start_at": "2026-02-10T09:00:00.000000Z",
  "end_at": "2026-02-10T17:00:00.000000Z",
  "pay": "120.00",
  "workers_needed": 3,
  "description": "Need experienced servers for a busy restaurant.",
  "auto_close_enabled": true,
  "auto_close_at": "2026-02-09T18:00:00.000000Z",
  "status": "open",
  "latitude": "53.34980000",
  "longitude": "-6.26031000",
  "app_saving_percent": 10,
  "requirements": ["Must have food safety cert", "Own transport"],
  "spots_left": 2,
  "duration": 8.0,
  "rate_per_hour": 15.0,
  "app_saving_amount": 12.0,
  "freelancer_pay": 108.0,
  "applicants_count": 5,
  "accepted_applications_count": 1,
  "is_bookmarked": false,
  "has_applied": true,
  "primary_skill": { "id": 1, "name": "Serving" },
  "supporting_skills": [{ "id": 2, "name": "Cleaning" }],
  "employer": { "id": 2, "first_name": "Jane", "last_name": "Smith", "..." : "..." }
}
```

**Computed Fields:**

| Field | Description |
|-------|-------------|
| `spots_left` | `workers_needed` minus accepted applications |
| `duration` | Hours between `start_at` and `end_at` |
| `rate_per_hour` | `pay / duration` |
| `app_saving_amount` | `pay * (app_saving_percent / 100)` |
| `freelancer_pay` | `pay - app_saving_amount` |

> `is_bookmarked` and `has_applied` are only included for freelancer users.

---

### Create Gig

```
POST /api/gigs
```

**Access:** Authenticated, Employer only (role 2)

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `title` | string | Yes | max:255 |
| `primary_skill_id` | integer | Yes | must exist in skills |
| `supporting_skill_ids` | array | No | each must exist in skills, distinct, cannot include primary skill |
| `location` | string | Yes | max:255 |
| `date` | date | Yes | |
| `start_time` | string | Yes | format `HH:mm`, must be in the future |
| `end_time` | string | Yes | format `HH:mm`, must be after start_time |
| `pay` | numeric | Yes | greater than 0 |
| `workers_needed` | integer | Yes | min:1 |
| `description` | string | Yes | max:300 |
| `auto_close_enabled` | boolean | No | |
| `auto_close_date` | date | No | required if auto_close_enabled is true, must be before gig start |
| `auto_close_time` | string | No | format `HH:mm`, required if auto_close_enabled is true |
| `latitude` | numeric | No | -90 to 90, required with longitude |
| `longitude` | numeric | No | -180 to 180, required with latitude |
| `app_saving_percent` | integer | No | 0-100 (default: 0) |
| `requirements` | array | No | |
| `requirements.*` | string | — | max:500 each |

**Response:** `201 Created` — Returns the created gig object.

---

### Get Gig

```
GET /api/gigs/{gig}
```

**Access:** Authenticated

- Employers can only view their own gigs.
- Freelancers can only view open gigs.

**Response:** `200 OK` — Returns the gig object.

---

### Update Gig

```
PUT/PATCH /api/gigs/{gig}
```

**Access:** Authenticated, Employer only (owner)

**Request Body:** Same fields as Create, but all fields are optional (`sometimes` rule).

**Response:** `200 OK` — Returns the updated gig object.

---

### Delete Gig

Soft-deletes the gig.

```
DELETE /api/gigs/{gig}
```

**Access:** Authenticated, Employer only (owner)

**Response:** `200 OK`

```json
{ "message": "Gig deleted successfully." }
```

---

### Close Gig

Manually close a gig (stops accepting applications).

```
PATCH /api/gigs/{gig}/close
```

**Access:** Authenticated, Employer only (owner)

**Constraints:** Gig must be `open` or `filled`.

**Response:** `200 OK` — Returns the updated gig object with `status: "closed"`.

---

### Update Workers Needed

Adjust the number of workers required for a gig.

```
PATCH /api/gigs/{gig}/workers
```

**Access:** Authenticated, Employer only (owner)

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `workers_needed` | integer | Yes | min:1, cannot be less than accepted applicants count |

**Response:** `200 OK` — Returns the updated gig object.

> **Side effect:** If workers_needed equals accepted count, gig status becomes `filled`. If increased above accepted count and gig was `filled`, status reverts to `open`.

---

## Gig Applications (Employer)

Manage applications for a specific gig. These endpoints are restricted to the gig's employer.

### List Applications

```
GET /api/gigs/{gig}/applications
```

**Access:** Authenticated, Employer only (gig owner)

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `status` | string | Filter by application status |

**Response:** `200 OK` — Paginated (15 per page). Each application includes the `user` relation.

---

### Apply to Gig

Submit an application as a freelancer.

```
POST /api/gigs/{gig}/applications
```

**Access:** Authenticated, Freelancer only

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `requirement_confirmations` | array | Conditional | Required if gig has `requirements`. Length must match requirements array. All values must be `true`. |
| `requirement_confirmations.*` | boolean | — | |

**Constraints:**
- Gig must be `open`.
- Freelancer cannot apply twice to the same gig.
- Spots must be available (race-condition safe with DB lock).

**Response:** `201 Created`

```json
{
  "id": 1,
  "gig_id": 1,
  "user_id": 3,
  "status": "pending",
  "requirement_confirmations": [true, true],
  "user": { "..." },
  "gig": { "..." }
}
```

**Errors:**
- `422` — Gig not accepting applications / already applied / no spots left / requirement confirmations mismatch.

---

### Update Application Status

Accept, reject, or cancel an application.

```
PATCH /api/gigs/{gig}/applications/{application}/status
```

**Access:** Authenticated, Employer only (gig owner)

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `status` | string | Yes | `accepted`, `rejected`, or `cancelled` |
| `rejection_reason` | string | No | max:500 (only applicable when rejecting) |

**Business Rules:**
- Only `pending` applications can be rejected.
- Accepting checks for available spots (race-condition safe).
- If accepting fills all spots, gig status becomes `filled`.
- Cancelling an accepted application reopens a filled gig.

**Response:** `200 OK` — Returns the updated application with `user` relation.

---

### Review Application

Submit a review for a completed/accepted application. Also marks accepted applications as completed.

```
POST /api/gigs/{gig}/applications/{application}/review
```

**Access:** Authenticated, Employer only (gig owner)

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `rating` | integer | Yes | 1-5 |
| `review` | string | No | max:2000 |
| `earnings` | numeric | No | min:0 (defaults to gig's `freelancer_pay`) |

**Constraints:**
- Application must be `accepted` or `completed`.
- Each application can only be reviewed once.

**Response:** `201 Created`

```json
{
  "application": {
    "id": 1,
    "status": "completed",
    "user": { "..." },
    "review": { "..." }
  },
  "review": {
    "id": 1,
    "gig_id": 1,
    "employer_id": 2,
    "freelancer_id": 3,
    "application_id": 1,
    "rating": 5,
    "review": "Excellent work!",
    "earnings": 108.00
  }
}
```

---

## Freelancer Applications

View and manage the authenticated freelancer's own applications.

### List My Applications

```
GET /api/my-applications
```

**Access:** Authenticated, Freelancer only

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `status` | string | Filter by status (`pending`, `accepted`, `rejected`, `completed`, `cancelled`) |
| `per_page` | integer | Items per page (default: 15) |

**Response:** `200 OK` — Paginated.

```json
{
  "data": [
    {
      "id": 1,
      "gig_id": 1,
      "user_id": 3,
      "status": "accepted",
      "rejection_reason": null,
      "created_at": "...",
      "updated_at": "...",
      "gig": {
        "id": 1,
        "title": "Restaurant Server",
        "location": "Dublin",
        "latitude": 53.3498,
        "longitude": -6.26031,
        "start_at": "...",
        "end_at": "...",
        "pay": "120.00",
        "rate_per_hour": 15.0,
        "freelancer_pay": 108.0,
        "workers_needed": 3,
        "status": "open",
        "employer": {
          "id": 2,
          "first_name": "Jane",
          "last_name": "Smith",
          "name": "Jane Smith",
          "profile_image_url": null
        },
        "primary_skill": { "id": 1, "name": "Serving" }
      },
      "review": null
    }
  ]
}
```

---

### Get Application Detail

```
GET /api/my-applications/{application}
```

**Access:** Authenticated, Freelancer only (owner)

**Response:** `200 OK` — Same format as list item but includes employer email and phone.

---

### Withdraw Application

Withdraw a pending application.

```
POST /api/my-applications/{application}/withdraw
```

**Access:** Authenticated, Freelancer only (owner)

**Constraints:** Application must be `pending`.

**Response:** `200 OK` — Returns the updated application with `status: "cancelled"`.

---

### Application Counts

Get summary counts grouped by status.

```
GET /api/my-applications/counts
```

**Access:** Authenticated, Freelancer only

**Response:** `200 OK`

```json
{
  "pending": 2,
  "accepted": 1,
  "rejected": 0,
  "completed": 5,
  "cancelled": 1
}
```

---

### Completed Summary

Get aggregate stats for completed gigs.

```
GET /api/my-applications/completed-summary
```

**Access:** Authenticated, Freelancer only

**Response:** `200 OK`

```json
{
  "completed_count": 5,
  "total_earnings": 540.00,
  "avg_rating": 4.60
}
```

---

## Gig Bookmarks

### List Bookmarked Gigs

```
GET /api/bookmarks/gigs
```

**Access:** Authenticated, Freelancer only

**Response:** `200 OK` — Paginated (15 per page). Each bookmark includes the `gig` with `primarySkill`, `supportingSkills`, and `employer`.

---

### Toggle Bookmark

Add or remove a bookmark on a gig.

```
POST /api/gigs/{gig}/bookmark
```

**Access:** Authenticated, Freelancer only

**Response:** `200 OK`

```json
{ "bookmarked": true }
```

or

```json
{ "bookmarked": false }
```

---

## Notifications

### List Notifications

```
GET /api/notifications
```

**Access:** Authenticated

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `unread_only` | boolean | Only return unread notifications |

**Response:** `200 OK` — Paginated (20 per page). Standard Laravel notification format.

---

### Unread Count

```
GET /api/notifications/unread-count
```

**Access:** Authenticated

**Response:** `200 OK`

```json
{ "unread_count": 5 }
```

---

### Mark as Read

```
PATCH /api/notifications/{notification}/read
```

**Access:** Authenticated (own notifications only)

**Response:** `200 OK` — Returns the updated notification.

---

### Mark All as Read

```
POST /api/notifications/read-all
```

**Access:** Authenticated

**Response:** `200 OK`

```json
{ "message": "All notifications marked as read." }
```

---

## Conversations

Conversations are between an employer and a freelancer, optionally linked to a gig.

### List Conversations

```
GET /api/conversations
```

**Access:** Authenticated

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `per_page` | integer | Items per page (default: 15) |
| `unread_only` | boolean | Only conversations with unread messages |

**Response:** `200 OK`

```json
{
  "data": {
    "data": [
      {
        "id": 1,
        "gig_id": 1,
        "gig_title": "Restaurant Server",
        "gig_schedule": "Feb 10, 9:00 AM - 5:00 PM",
        "gig_pay": "\u20ac120",
        "other_user": {
          "id": 3,
          "name": "John Doe",
          "initials": "JD",
          "avatar_url": null
        },
        "last_message": {
          "body": "Hi, are you available?",
          "sent_at": "2026-02-05T10:30:00+00:00",
          "is_from_me": true
        },
        "unread_count": 2,
        "updated_at": "2026-02-05T10:30:00+00:00"
      }
    ]
  }
}
```

---

### Create or Get Conversation

Creates a new conversation or returns an existing one between the two users (optionally scoped to a gig).

```
POST /api/conversations
```

**Access:** Authenticated

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `other_user_id` | integer | Yes | must exist in users |
| `gig_id` | integer | No | must exist in gigs |

**Constraints:**
- Cannot create a conversation with yourself.
- Must be between an employer (role 2) and a freelancer (role 3).

**Response:** `201 Created` (new) or `200 OK` (existing) — Returns the formatted conversation object.

---

### Mark Conversation as Read

```
PATCH /api/conversations/{conversation}/read
```

**Access:** Authenticated (participant only)

**Response:** `200 OK`

```json
{ "message": "Conversation marked as read." }
```

---

## Messages

### List Messages

Retrieve messages for a conversation with cursor-based pagination.

```
GET /api/conversations/{conversation}/messages
```

**Access:** Authenticated (participant only)

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `per_page` | integer | Messages per page (default: 30) |
| `before_id` | integer | Load messages before this message ID (for infinite scroll) |

**Response:** `200 OK`

```json
{
  "data": {
    "data": [
      {
        "id": 10,
        "body": "Hello!",
        "sender_id": 3,
        "is_me": true,
        "sent_at": "2026-02-05T10:30:00+00:00",
        "is_edited": false,
        "edited_at": null,
        "deleted_at": null,
        "can_edit": true,
        "can_delete": true
      }
    ]
  }
}
```

> Deleted messages return `body: null` with `deleted_at` set.

---

### Send Message

```
POST /api/conversations/{conversation}/messages
```

**Access:** Authenticated (participant only)

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `body` | string | Yes | max:5000 |

**Response:** `201 Created` — Returns the formatted message object.

---

### Edit Message

```
PATCH /api/conversations/{conversation}/messages/{message}
```

**Access:** Authenticated (message sender only)

**Constraints:**
- Can only edit own messages.
- 15-minute edit window from creation time.
- Cannot edit deleted messages.

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `body` | string | Yes | max:5000 |

**Response:** `200 OK` — Returns the updated message with `is_edited: true`.

---

### Delete Message

Soft-deletes a message.

```
DELETE /api/conversations/{conversation}/messages/{message}
```

**Access:** Authenticated (message sender only)

**Response:** `200 OK`

```json
{ "message": "Message deleted." }
```

---

## Reports

### Report a Conversation or Message

```
POST /api/reports
```

**Access:** Authenticated

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `reportable_type` | string | Yes | `conversation` or `message` |
| `reportable_id` | integer | Yes | ID of the conversation or message |
| `reason` | string | Yes | `spam`, `harassment`, `inappropriate`, `scam`, or `other` |
| `details` | string | No | max:2000 |

**Constraints:**
- Must be a participant of the conversation.
- Cannot report your own messages.

**Response:** `201 Created`

```json
{
  "data": {
    "id": 1,
    "reporter_id": 3,
    "reportable_type": "App\\Models\\Message",
    "reportable_id": 5,
    "reason": "harassment",
    "details": "User sent threatening messages.",
    "status": "pending"
  }
}
```

---

## Penalties

Freelancer penalty and appeal system. Max warnings before escalation: **3**.

### List My Penalties

```
GET /api/my-penalties
```

**Access:** Authenticated, Freelancer only

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `per_page` | integer | Items per page (default: 15) |

**Response:** `200 OK` — Paginated with a `warning_summary` appended.

```json
{
  "data": [
    {
      "id": 1,
      "gig_id": 1,
      "gig_title": "Restaurant Server",
      "company": "Jane Smith",
      "gig_date": "2026-02-10",
      "reason": "no_show",
      "description": "Did not show up for the gig.",
      "issued_at": "2026-02-11T08:00:00.000000Z",
      "current_warnings": 1,
      "max_warnings": 3,
      "next_penalty": null,
      "is_appealed": false,
      "appeal_status": null,
      "created_at": "...",
      "updated_at": "..."
    }
  ],
  "warning_summary": {
    "current_warnings": 1,
    "max_warnings": 3
  }
}
```

**Escalation Rules:**

| Warnings | Next Penalty |
|----------|-------------|
| < 2 | `null` |
| 2 | Temporary restriction for 7 days |
| 3+ | Account suspension |

---

### Get Penalty Detail

```
GET /api/my-penalties/{penalty}
```

**Access:** Authenticated, Freelancer only (owner)

**Response:** `200 OK` — Same format as list item.

---

### Appeal a Penalty

```
POST /api/my-penalties/{penalty}/appeal
```

**Access:** Authenticated, Freelancer only (owner)

**Constraints:** Only one appeal per penalty.

**Request Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `message` | string | No | max:2000 |

**Response:** `201 Created` — Returns the penalty with appeal info included.

---

## Enums & Constants

### Gig Status

| Value | Description |
|-------|-------------|
| `open` | Accepting applications |
| `filled` | All worker spots have been filled |
| `completed` | Gig has been completed |
| `closed` | Manually closed by employer |

### Application Status

| Value | Description |
|-------|-------------|
| `pending` | Awaiting employer decision |
| `accepted` | Employer accepted the application |
| `rejected` | Employer rejected the application |
| `completed` | Gig work completed and reviewed |
| `cancelled` | Withdrawn by freelancer or cancelled by employer |

### Report Reasons

| Value |
|-------|
| `spam` |
| `harassment` |
| `inappropriate` |
| `scam` |
| `other` |

### Report Status

| Value |
|-------|
| `pending` |

### Penalty Appeal Status

| Value |
|-------|
| `pending` |

---

## Error Responses

All errors follow a consistent JSON format:

### 401 Unauthorized

```json
{ "message": "Unauthenticated." }
```

### 403 Forbidden

```json
{ "message": "Forbidden." }
```

### 404 Not Found

```json
{ "message": "Resource not found." }
```

### 422 Unprocessable Entity (Validation)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message here."]
  }
}
```

### 422 Business Logic Error

```json
{ "message": "Descriptive error message." }
```

---

## Middleware

| Middleware | Description |
|-----------|-------------|
| `auth:sanctum` | Validates Bearer token |
| `check.token.expiry` | Checks if the token has expired |
| `ensure.employer` | Restricts route to employer role (role 2) |
| `ensure.freelancer` | Restricts route to freelancer role (role 3) |
