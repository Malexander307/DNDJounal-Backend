# DND Journal — Campaign API Documentation

> Base URL: `/api/v1`  
> All endpoints require **Bearer token** authentication via Laravel Sanctum.  
> All requests and responses use `Content-Type: application/json`.

---

## Authentication

Every request must include the Authorization header:

```
Authorization: Bearer <your-sanctum-token>
```

---

## Response Envelope

### Success
```json
{
  "data": { ... },
  "message": "Human readable message",
  "meta": {
    "request_id": "uuid-or-null"
  }
}
```

### Paginated Success
```json
{
  "data": [ ... ],
  "message": "Human readable message",
  "meta": {
    "pagination": {
      "total": 50,
      "per_page": 15,
      "current_page": 1,
      "last_page": 4
    },
    "request_id": "uuid-or-null"
  }
}
```

### Error
```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error",
    "details": { ... }
  },
  "meta": {
    "request_id": "uuid-or-null"
  }
}
```

### Validation Error (422)
```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

---

## Campaign Object

```ts
interface Campaign {
  id: number
  name: string
  description: string | null
  status: 'active' | 'archived'
  created_by: number
  creator?: {          // included on show
    id: number
    name: string
  }
  users?: Array<{      // included on show
    id: number
    name: string
    role: 'dm' | 'player'
  }>
  created_at: string   // ISO 8601
  updated_at: string   // ISO 8601
}
```

---

## Endpoints

---

### `GET /api/v1/campaigns`

List all campaigns the authenticated user is a member of.

**Query Parameters**

| Parameter  | Type    | Default | Description             |
|------------|---------|---------|-------------------------|
| `per_page` | integer | `15`    | Items per page          |
| `page`     | integer | `1`     | Page number             |

**Response `200`**
```json
{
  "data": [
    {
      "id": 1,
      "name": "The Lost Mines",
      "description": "A classic starter adventure.",
      "status": "active",
      "created_by": 3,
      "created_at": "2026-03-31T12:00:00.000Z",
      "updated_at": "2026-03-31T12:00:00.000Z"
    }
  ],
  "message": "Campaigns retrieved successfully.",
  "meta": {
    "pagination": {
      "total": 1,
      "per_page": 15,
      "current_page": 1,
      "last_page": 1
    },
    "request_id": null
  }
}
```

**Vue example**
```ts
const { data } = await axios.get('/api/v1/campaigns', {
  params: { per_page: 15, page: 1 }
})
const campaigns = data.data          // Campaign[]
const pagination = data.meta.pagination
```

---

### `POST /api/v1/campaigns`

Create a new campaign. The authenticated user becomes the creator and is added as **DM**.

**Request Body**

| Field         | Type   | Required | Rules                    |
|---------------|--------|----------|--------------------------|
| `name`        | string | ✅        | max 255 chars            |
| `description` | string | ❌        | nullable, max 5000 chars |

```json
{
  "name": "Curse of Strahd",
  "description": "Horror in Barovia."
}
```

**Response `201`**
```json
{
  "data": {
    "id": 2,
    "name": "Curse of Strahd",
    "description": "Horror in Barovia.",
    "status": "active",
    "created_by": 1,
    "created_at": "2026-04-03T10:00:00.000Z",
    "updated_at": "2026-04-03T10:00:00.000Z"
  },
  "message": "Campaign created successfully.",
  "meta": { "request_id": null }
}
```

**Vue example**
```ts
const { data } = await axios.post('/api/v1/campaigns', {
  name: 'Curse of Strahd',
  description: 'Horror in Barovia.'
})
const campaign = data.data   // Campaign
```

---

### `GET /api/v1/campaigns/:id`

Get a single campaign with its creator and member list.

> ⚠️ **403** if the authenticated user is not a member of the campaign.

**Response `200`**
```json
{
  "data": {
    "id": 2,
    "name": "Curse of Strahd",
    "description": "Horror in Barovia.",
    "status": "active",
    "created_by": 1,
    "creator": {
      "id": 1,
      "name": "Dungeon Master Dave"
    },
    "users": [
      { "id": 1, "name": "Dungeon Master Dave", "role": "dm" },
      { "id": 4, "name": "Player Pete", "role": "player" }
    ],
    "created_at": "2026-04-03T10:00:00.000Z",
    "updated_at": "2026-04-03T10:00:00.000Z"
  },
  "message": "Campaign retrieved successfully.",
  "meta": { "request_id": null }
}
```

**Vue example**
```ts
const { data } = await axios.get(`/api/v1/campaigns/${id}`)
const campaign = data.data   // Campaign (with creator & users)
```

---

### `PUT /PATCH /api/v1/campaigns/:id`

Update an existing campaign.

> ⚠️ **403** if the user is not the **DM**, or if the campaign is **archived**.  
> All fields are optional (`sometimes`), send only what you want to change.

**Request Body**

| Field         | Type   | Required | Rules                              |
|---------------|--------|----------|------------------------------------|
| `name`        | string | ❌        | max 255 chars                      |
| `description` | string | ❌        | nullable, max 5000 chars           |
| `status`      | string | ❌        | `active` or `archived`             |

```json
{
  "name": "Curse of Strahd (Revised)",
  "status": "archived"
}
```

**Response `200`**
```json
{
  "data": {
    "id": 2,
    "name": "Curse of Strahd (Revised)",
    "description": "Horror in Barovia.",
    "status": "archived",
    "created_by": 1,
    "created_at": "2026-04-03T10:00:00.000Z",
    "updated_at": "2026-04-03T11:30:00.000Z"
  },
  "message": "Campaign updated successfully.",
  "meta": { "request_id": null }
}
```

**Vue example**
```ts
const { data } = await axios.patch(`/api/v1/campaigns/${id}`, {
  status: 'archived'
})
const updated = data.data   // Campaign
```

---

### `DELETE /api/v1/campaigns/:id`

Delete a campaign permanently.

> ⚠️ **403** if the user is not the **DM** of the campaign.

**Response `200`**
```json
{
  "data": null,
  "message": "Campaign deleted successfully.",
  "meta": { "request_id": null }
}
```

**Vue example**
```ts
await axios.delete(`/api/v1/campaigns/${id}`)
```

---

## HTTP Status Codes

| Code | Meaning                                         |
|------|-------------------------------------------------|
| 200  | OK                                              |
| 201  | Created                                         |
| 401  | Unauthenticated — missing or invalid token      |
| 403  | Forbidden — policy check failed                 |
| 404  | Campaign not found                              |
| 422  | Validation error — check `errors` object        |
| 500  | Server error                                    |

---

## Authorization Rules Summary

| Action  | Who can do it                                          |
|---------|--------------------------------------------------------|
| list    | Any authenticated user                                 |
| create  | Any authenticated user                                 |
| view    | Campaign members only                                  |
| update  | DM only + campaign must **not** be `archived`          |
| delete  | DM only                                                |

---

## Suggested Vue Composable (`useCampaigns.ts`)

```ts
import axios from 'axios'

const BASE = '/api/v1/campaigns'

export function useCampaigns() {
  const list = (page = 1, perPage = 15) =>
    axios.get(BASE, { params: { page, per_page: perPage } })

  const show = (id: number) =>
    axios.get(`${BASE}/${id}`)

  const store = (payload: { name: string; description?: string }) =>
    axios.post(BASE, payload)

  const update = (id: number, payload: Partial<{ name: string; description: string; status: 'active' | 'archived' }>) =>
    axios.patch(`${BASE}/${id}`, payload)

  const destroy = (id: number) =>
    axios.delete(`${BASE}/${id}`)

  return { list, show, store, update, destroy }
}
```

---

## Campaign Status Flow

```
active  ──(DM archives)──▶  archived
```

Once archived a campaign **cannot** be updated via the API (403 returned).

