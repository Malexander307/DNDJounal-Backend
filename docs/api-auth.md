# DND Journal — Auth API Documentation

> Base URL: `/api/v1`  
> Auth endpoints are **public** (no token required) except logout.  
> All requests and responses use `Content-Type: application/json`.

---

## Overview

| Method | Endpoint              | Auth required | Description          |
|--------|-----------------------|:-------------:|----------------------|
| POST   | `/api/v1/auth/register` | ❌          | Create a new account |
| POST   | `/api/v1/auth/login`    | ❌          | Log in               |
| POST   | `/api/v1/auth/logout`   | ✅          | Revoke current token |

---

## Auth Payload Object

Returned by both `register` and `login` in `data`:

```ts
interface AuthPayload {
  token: string   // Sanctum plain-text token — store this and use as Bearer
  user: {
    id:    number
    name:  string
    email: string
  }
}
```

---

## Endpoints

---

### `POST /api/v1/auth/register`

Create a new user account. Returns a ready-to-use Bearer token.

**Request Body**

| Field                  | Type   | Required | Rules                                   |
|------------------------|--------|----------|-----------------------------------------|
| `name`                 | string | ✅        | max 255 chars                           |
| `email`                | string | ✅        | valid email, max 255 chars, must be unique |
| `password`             | string | ✅        | min 8 chars, must match `password_confirmation` |
| `password_confirmation`| string | ✅        | must match `password`                   |

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Response `201`**
```json
{
  "data": {
    "token": "1|abc123plaintexttoken",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  },
  "message": "Registration successful.",
  "meta": { "request_id": null }
}
```

**Validation Error `422`**
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The email has already been taken.",
    "details": {
      "email": ["The email has already been taken."]
    }
  },
  "meta": { "request_id": null }
}
```

**Vue example**
```ts
const { data } = await axios.post('/api/v1/auth/register', {
  name: 'John Doe',
  email: 'john@example.com',
  password: 'secret123',
  password_confirmation: 'secret123'
})

const token = data.data.token
const user  = data.data.user

// Persist for subsequent requests
localStorage.setItem('token', token)
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
```

---

### `POST /api/v1/auth/login`

Authenticate with email and password. Returns a new Bearer token.

**Request Body**

| Field      | Type   | Required | Rules          |
|------------|--------|----------|----------------|
| `email`    | string | ✅        | valid email    |
| `password` | string | ✅        | non-empty      |

```json
{
  "email": "john@example.com",
  "password": "secret123"
}
```

**Response `200`**
```json
{
  "data": {
    "token": "2|xyz789plaintexttoken",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  },
  "message": "Login successful.",
  "meta": { "request_id": null }
}
```

**Wrong credentials `401`**
```json
{
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Invalid credentials.",
    "details": null
  },
  "meta": { "request_id": null }
}
```

**Vue example**
```ts
try {
  const { data } = await axios.post('/api/v1/auth/login', {
    email: 'john@example.com',
    password: 'secret123'
  })

  localStorage.setItem('token', data.data.token)
  axios.defaults.headers.common['Authorization'] = `Bearer ${data.data.token}`

} catch (err) {
  if (err.response?.status === 401) {
    // Show "Invalid email or password" message
  }
  if (err.response?.status === 422) {
    // Show field validation errors from err.response.data.error.details
  }
}
```

---

### `POST /api/v1/auth/logout`

Revoke the current Bearer token. Requires authentication.

**Headers**
```
Authorization: Bearer <token>
```

**Response `200`**
```json
{
  "data": null,
  "message": "Logged out successfully.",
  "meta": { "request_id": null }
}
```

**Unauthenticated `401`** (missing or invalid token)
```json
{
  "error": {
    "code": "UNAUTHENTICATED",
    "message": "Unauthenticated.",
    "details": ""
  },
  "meta": { "request_id": null }
}
```

**Vue example**
```ts
await axios.post('/api/v1/auth/logout')

localStorage.removeItem('token')
delete axios.defaults.headers.common['Authorization']
```

---

## HTTP Status Codes

| Code | Meaning                                             |
|------|-----------------------------------------------------|
| 200  | OK                                                  |
| 201  | Created (register)                                  |
| 401  | Unauthenticated / invalid credentials               |
| 422  | Validation error — check `error.details`            |

---

## Suggested Vue Composable (`useAuth.ts`)

```ts
import axios from 'axios'

interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
}

interface LoginPayload {
  email: string
  password: string
}

export function useAuth() {
  const register = (payload: RegisterPayload) =>
    axios.post('/api/v1/auth/register', payload)

  const login = (payload: LoginPayload) =>
    axios.post('/api/v1/auth/login', payload)

  const logout = () =>
    axios.post('/api/v1/auth/logout')

  const setToken = (token: string) => {
    localStorage.setItem('token', token)
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  }

  const clearToken = () => {
    localStorage.removeItem('token')
    delete axios.defaults.headers.common['Authorization']
  }

  const loadToken = () => {
    const token = localStorage.getItem('token')
    if (token) axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    return token
  }

  return { register, login, logout, setToken, clearToken, loadToken }
}
```

---

## Axios Interceptor (recommended)

Set this up once in your `main.ts` / `axios.ts` to handle global 401 redirects:

```ts
axios.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
      // router.push('/login')   // uncomment if using Vue Router
    }
    return Promise.reject(err)
  }
)
```

