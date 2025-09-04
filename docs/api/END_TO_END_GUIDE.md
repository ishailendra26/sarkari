# Sarkari Jobs API: End-to-End Guide

This guide explains the API design, response format, pagination, filtering, versioning, error handling, tooling (Swagger UI and Postman), and how a mobile/web app should be designed to consume these endpoints reliably and efficiently.

- Base URL (Local, no rewrites): `http://localhost/sarkari/api/index.php/v1`
- OpenAPI spec: `docs/api/openapi.yaml`
- Swagger UI: `http://localhost/sarkari/docs/api/swagger/index.html`
- Postman collection: `docs/api/postman_collection.json`

## 0) Servers & Base URLs

- Local (XAMPP, no rewrites):
  - `http://localhost/sarkari/api/index.php/v1`
- Production (examsz.in, no rewrites):
  - `https://examsz.in/api/index.php/v1`

The OpenAPI spec already includes both servers in `servers[]`, so Swagger UI and client generators can switch environments easily.

## 1) API Overview

- Read-only, versioned REST API.
- Resources: Jobs, Results, Admit Cards, Syllabi, Posts.
- Methods: GET only.
- Responses: JSON with a consistent envelope.
- Cross-origin: CORS enabled and OPTIONS preflight supported.

Directory references:
- API front controller: `api/index.php`
- Helper utilities: `src/api_helpers.php`
- Models: `src/models/*.php`

## 2) Versioning

- All endpoints are under `/api/v1`.
- Future breaking changes will use `/api/v2` while keeping v1 stable.

## 3) Endpoints

List endpoints (paginated, optionally searchable):
- `GET /jobs`
- `GET /results`
- `GET /admit-cards`
- `GET /syllabi`
- `GET /posts`

Detail endpoints (fetch by slug):
- `GET /jobs/{slug}`
- `GET /results/{slug}`
- `GET /admit-cards/{slug}`
- `GET /syllabi/{slug}`
- `GET /posts/{slug}`

### 3.a Endpoints Reference (Full URLs)

Replace `{slug}` with the actual slug (use `docs/api/generate_examples.php` locally to discover real slugs).

- Jobs
  - Local list: `http://localhost/sarkari/api/index.php/v1/jobs`
  - Local detail: `http://localhost/sarkari/api/index.php/v1/jobs/{slug}`
  - Prod list: `https://examsz.in/api/index.php/v1/jobs`
  - Prod detail: `https://examsz.in/api/index.php/v1/jobs/{slug}`

- Results
  - Local list: `http://localhost/sarkari/api/index.php/v1/results`
  - Local detail: `http://localhost/sarkari/api/index.php/v1/results/{slug}`
  - Prod list: `https://examsz.in/api/index.php/v1/results`
  - Prod detail: `https://examsz.in/api/index.php/v1/results/{slug}`

- Admit Cards
  - Local list: `http://localhost/sarkari/api/index.php/v1/admit-cards`
  - Local detail: `http://localhost/sarkari/api/index.php/v1/admit-cards/{slug}`
  - Prod list: `https://examsz.in/api/index.php/v1/admit-cards`
  - Prod detail: `https://examsz.in/api/index.php/v1/admit-cards/{slug}`

- Syllabi
  - Local list: `http://localhost/sarkari/api/index.php/v1/syllabi`
  - Local detail: `http://localhost/sarkari/api/index.php/v1/syllabi/{slug}`
  - Prod list: `https://examsz.in/api/index.php/v1/syllabi`
  - Prod detail: `https://examsz.in/api/index.php/v1/syllabi/{slug}`

- Posts
  - Local list: `http://localhost/sarkari/api/index.php/v1/posts`
  - Local detail: `http://localhost/sarkari/api/index.php/v1/posts/{slug}`
  - Prod list: `https://examsz.in/api/index.php/v1/posts`
  - Prod detail: `https://examsz.in/api/index.php/v1/posts/{slug}`

Query parameters (where applicable):
- `q` (string, min 2 chars): full-text search. If not provided, returns latest items.
- `category` (string): supported on Jobs and Posts list endpoints.
- `page` (int, >=1): default 1.
- `per_page` (int, 1–50): default 10.

## 4) Response Envelope

All responses are wrapped with a `success` field and payload in `data`:

- List response:
```json
{
  "success": true,
  "data": {
    "items": [ { /* resource */ }, ... ],
    "meta": { "page": 1, "per_page": 10, "total": 250, "total_pages": 25 }
  }
}
```

- Detail response:
```json
{
  "success": true,
  "data": { /* the resource object */ }
}
```

- Error response:
```json
{
  "success": false,
  "error": { "code": "validation_error", "message": "Query parameter q must be at least 2 characters" }
}
```

## 5) Validation and Limits

- `q` must be at least 2 characters when present.
- `per_page` is clamped to the range 1–50.
- Invalid parameters return `422` with the error envelope.
- Not found returns `404` with the error envelope.

## 6) Pagination Strategy in Apps

Recommended approach for feeds:
- Start with `page=1&per_page=10` (or your chosen page size up to 50).
- Use `meta.total_pages` or `meta.total` to determine when to stop.
- For infinite scroll, increment `page` until you reach `total_pages`.

## 7) Searching and Filtering

- Use `q` for full-text search.
- Use `category` for filtering (Jobs, Posts). When `q` is present, category filtering may be disabled by some models; consult `src/models/*.php` for exact semantics.

## 8) CORS / Access-Control

- CORS headers and preflight handling are enabled in `src/api_helpers.php`.
- Mobile and web apps can call the API from a different origin.

## 9) Tooling

- Swagger UI: interactive docs and testing at `docs/api/swagger/index.html`. It loads `docs/api/openapi.yaml`.
- Postman: import `docs/api/postman_collection.json`. Set collection variable `baseUrl` to your server.
- Helper to fetch real slugs from DB (read-only): `docs/api/generate_examples.php`.

## 10) Example Requests

- List jobs (latest, production):
```
curl -s \
  -H "Accept: application/json" \
  "https://examsz.in/api/index.php/v1/jobs?page=1&per_page=10"
```

- Search results (production):
```
curl -s \
  -H "Accept: application/json" \
  "https://examsz.in/api/index.php/v1/results?q=ssc&page=1"
```

- Job detail (production):
```
curl -s \
  -H "Accept: application/json" \
  "https://examsz.in/api/index.php/v1/jobs/ssc-cgl-2025"
```

Full example payloads per endpoint are embedded in `openapi.yaml` under `paths.*.responses.*.content.application/json.examples`.

## 11) Client Integration Patterns

Below are baseline patterns for popular stacks. Replace the `baseUrl` with your deployment base.

- Base URL: `http://localhost/sarkari/api/index.php/v1`

### 11.1 Android (Kotlin + Retrofit)

```kotlin
interface ApiService {
  @GET("jobs")
  suspend fun listJobs(
    @Query("page") page: Int = 1,
    @Query("per_page") perPage: Int = 10,
    @Query("q") q: String? = null,
    @Query("category") category: String? = null
  ): Envelope<ListResponse<Job>>

  @GET("jobs/{slug}")
  suspend fun getJob(@Path("slug") slug: String): Envelope<Job>
}
```

Caching tip: Use OkHttp cache with `Cache-Control` heuristics; on failures, serve from cache.

### 11.2 iOS (Swift + URLSession)

```swift
let baseUrl = URL(string: "http://localhost/sarkari/api/index.php/v1")!
let url = baseUrl.appendingPathComponent("results")
var comps = URLComponents(url: url, resolvingAgainstBaseURL: false)!
comps.queryItems = [
  URLQueryItem(name: "q", value: "ssc"),
  URLQueryItem(name: "page", value: "1")
]
var req = URLRequest(url: comps.url!)
req.addValue("application/json", forHTTPHeaderField: "Accept")
```

### 11.3 Flutter (Dart + http)

```dart
final base = Uri.parse('http://localhost/sarkari/api/index.php/v1');
final uri = base.replace(path: '${base.path}/admit-cards', queryParameters: {
  'page': '1', 'per_page': '10', 'q': 'nta'
});
final res = await http.get(uri, headers: {'Accept': 'application/json'});
```

### 11.4 React (fetch)

```js
const baseUrl = 'http://localhost/sarkari/api/index.php/v1';
const resp = await fetch(`${baseUrl}/posts?page=1&per_page=10`, {
  headers: { Accept: 'application/json' },
});
const json = await resp.json();
```

### 11.5 Node.js (axios)

```js
const axios = require('axios');
const api = axios.create({
  baseURL: 'http://localhost/sarkari/api/index.php/v1',
  headers: { Accept: 'application/json' },
});

const list = await api.get('/syllabi', { params: { q: 'upsc', page: 1 } });
```

### 11.6 React Native (React Query + Infinite Scroll)

Below is a minimal React Native example using React Query v5 and `FlatList` to implement infinite scroll against the paginated list endpoints. It demonstrates consuming the response envelope and pagination metadata returned by this API.

Install deps:

```
npm i @tanstack/react-query axios
```

App bootstrap (wrap your app):

```tsx
// App.tsx
import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { JobsScreen } from './JobsScreen';

const client = new QueryClient();

export default function App() {
  return (
    <QueryClientProvider client={client}>
      <JobsScreen />
    </QueryClientProvider>
  );
}
```

Types and API client:

```ts
// api.ts
import axios from 'axios';

export const BASE_URL = 'http://localhost/sarkari/api/index.php/v1';

export const api = axios.create({
  baseURL: BASE_URL,
  headers: { Accept: 'application/json' },
});

export type ListMeta = {
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
};

export type ListResponse<T> = {
  items: T[];
  meta: ListMeta;
};

export type Envelope<T> = {
  success: boolean;
  data: T;
  error?: { code: string; message: string };
};

export type Job = {
  id: number;
  title: string;
  slug: string;
  organization?: string;
  location?: string;
  category_name?: string;
  category_slug?: string;
  published_at?: string;
};
```

Infinite list screen with React Query v5:

```tsx
// JobsScreen.tsx
import React, { useMemo } from 'react';
import { View, Text, ActivityIndicator, FlatList, TouchableOpacity } from 'react-native';
import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { api, Job, Envelope, ListResponse } from './api';

type QueryVars = { q?: string; category?: string; perPage?: number };

async function fetchJobs({ pageParam = 1, signal, queryKey }: any): Promise<ListResponse<Job>> {
  const [_key, vars]: [string, QueryVars] = queryKey as any;
  const res = await api.get<Envelope<ListResponse<Job>>>('/jobs', {
    params: {
      page: pageParam,
      per_page: vars.perPage ?? 10,
      q: vars.q,
      category: vars.category,
    },
    signal,
  });
  if (!res.data.success) throw new Error(res.data.error?.message || 'API error');
  return res.data.data;
}

export function JobsScreen() {
  const vars: QueryVars = { perPage: 10 };

  const query = useInfiniteQuery({
    queryKey: ['jobs', vars],
    queryFn: fetchJobs,
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      const { page, total_pages } = lastPage.meta;
      return page < total_pages ? page + 1 : undefined;
    },
  });

  const items = useMemo(() => (query.data ? query.data.pages.flatMap(p => p.items) : []), [query.data]);

  if (query.isLoading) return <ActivityIndicator style={{ marginTop: 32 }} />;
  if (query.isError) return <Text>Error: {(query.error as Error).message}</Text>;

  return (
    <FlatList
      data={items}
      keyExtractor={(item) => String(item.id)}
      renderItem={({ item }) => (
        <TouchableOpacity style={{ padding: 12, borderBottomWidth: 1, borderColor: '#eee' }}>
          <Text style={{ fontWeight: '600' }}>{item.title}</Text>
          {item.organization ? <Text>{item.organization}</Text> : null}
        </TouchableOpacity>
      )}
      onEndReachedThreshold={0.6}
      onEndReached={() => {
        if (query.hasNextPage && !query.isFetchingNextPage) query.fetchNextPage();
      }}
      refreshing={query.isRefetching}
      onRefresh={() => query.refetch()}
      ListFooterComponent={() => (
        query.isFetchingNextPage ? <ActivityIndicator style={{ marginVertical: 12 }} /> : null
      )}
    />
  );
}
```

Detail screen pattern:

```tsx
// JobDetailScreen.tsx
import React from 'react';
import { View, Text, ActivityIndicator } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { api, Envelope, Job } from './api';

async function fetchJob(slug: string): Promise<Job> {
  const res = await api.get<Envelope<Job>>(`/jobs/${slug}`);
  if (!res.data.success) throw new Error(res.data.error?.message || 'API error');
  return res.data.data;
}

export function JobDetailScreen({ slug }: { slug: string }) {
  const query = useQuery({ queryKey: ['job', slug], queryFn: () => fetchJob(slug) });
  if (query.isLoading) return <ActivityIndicator style={{ marginTop: 32 }} />;
  if (query.isError) return <Text>Error: {(query.error as Error).message}</Text>;

  const job = query.data!;
  return (
    <View style={{ padding: 16 }}>
      <Text style={{ fontSize: 18, fontWeight: '700' }}>{job.title}</Text>
      {job.organization ? <Text>{job.organization}</Text> : null}
    </View>
  );
}
```

Notes:
- Drive the infinite scroll by `meta.page` and `meta.total_pages` from list responses.
- To support search or category filters, include them in `QueryVars` and `queryKey`, and invalidate the query when filters change.
- For offline-first, pair React Query with a persistent cache (e.g., MMKV or AsyncStorage hydration).

### 11.7 React Query Persistence (AsyncStorage Hydration)

Persist the React Query cache to sustain data across app restarts and enable offline-first UX.

Install:

```
npm i @react-native-async-storage/async-storage @tanstack/query-async-storage-persister @tanstack/react-query-persist-client
```

Setup with hydration and versioned buster:

```tsx
// App.tsx
import React from 'react';
import { PersistQueryClientProvider } from '@tanstack/react-query-persist-client';
import { QueryClient } from '@tanstack/react-query';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { createAsyncStoragePersister } from '@tanstack/query-async-storage-persister';
import { JobsScreen } from './JobsScreen';

const client = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60_000, // 1 min
      gcTime: 24 * 60 * 60 * 1000, // 1 day
      retry: 2,
    },
  },
});

const persister = createAsyncStoragePersister({ storage: AsyncStorage });

// Bump this when making breaking cache changes (e.g., API v2)
const CACHE_BUSTER = 'sarkari-api-v1';

export default function App() {
  return (
    <PersistQueryClientProvider
      client={client}
      persistOptions={{
        persister,
        maxAge: 6 * 60 * 60 * 1000, // 6 hours
        buster: CACHE_BUSTER,
      }}
    >
      <JobsScreen />
    </PersistQueryClientProvider>
  );
}
```

Notes:
- If you deploy `/api/v2`, update `CACHE_BUSTER` (e.g., `sarkari-api-v2`) to safely invalidate old cache.
- Consider encrypting storage for sensitive data; public read-only data here typically does not require it.

### 11.8 Filterable Jobs Screen (Search + Category)

Add `q` and `category` as reactive variables in the query key so the list auto-refetches when filters change. Debounce search to limit requests.

```tsx
// JobsFilterScreen.tsx
import React, { useMemo, useState, useEffect } from 'react';
import { View, Text, TextInput, ActivityIndicator, FlatList } from 'react-native';
import { useInfiniteQuery } from '@tanstack/react-query';
import { api, Job, Envelope, ListResponse } from './api';

type Vars = { q?: string; category?: string; perPage?: number };

async function fetchJobs({ pageParam = 1, signal, queryKey }: any): Promise<ListResponse<Job>> {
  const [_key, vars]: [string, Vars] = queryKey as any;
  const res = await api.get<Envelope<ListResponse<Job>>>('/jobs', {
    params: { page: pageParam, per_page: vars.perPage ?? 10, q: vars.q, category: vars.category },
    signal,
  });
  if (!res.data.success) throw new Error(res.data.error?.message || 'API error');
  return res.data.data;
}

export function JobsFilterScreen() {
  const [search, setSearch] = useState('');
  const [debounced, setDebounced] = useState('');
  const [category, setCategory] = useState<string | undefined>(undefined);

  useEffect(() => {
    const id = setTimeout(() => setDebounced(search.trim().length >= 2 ? search.trim() : ''), 350);
    return () => clearTimeout(id);
  }, [search]);

  const vars = useMemo<Vars>(() => ({ q: debounced || undefined, category, perPage: 10 }), [debounced, category]);

  const query = useInfiniteQuery({
    queryKey: ['jobs', vars],
    queryFn: fetchJobs,
    initialPageParam: 1,
    getNextPageParam: (last) => (last.meta.page < last.meta.total_pages ? last.meta.page + 1 : undefined),
  });

  const items = useMemo(() => (query.data ? query.data.pages.flatMap(p => p.items) : []), [query.data]);

  return (
    <View style={{ flex: 1 }}>
      <View style={{ padding: 12, gap: 8 }}>
        <TextInput
          placeholder="Search jobs (min 2 chars)"
          value={search}
          onChangeText={setSearch}
          style={{ borderWidth: 1, borderColor: '#ccc', borderRadius: 6, padding: 8 }}
        />
        {/* Replace with a proper picker/select in your app */}
        <Text onPress={() => setCategory(category ? undefined : 'banking')} style={{ color: '#0366d6' }}>
          Toggle Category: {category || 'none'}
        </Text>
      </View>

      {query.isLoading ? (
        <ActivityIndicator style={{ marginTop: 24 }} />
      ) : (
        <FlatList
          data={items}
          keyExtractor={(it) => String(it.id)}
          renderItem={({ item }) => (
            <View style={{ padding: 12, borderBottomWidth: 1, borderColor: '#eee' }}>
              <Text style={{ fontWeight: '600' }}>{item.title}</Text>
              {item.organization ? <Text>{item.organization}</Text> : null}
            </View>
          )}
          onEndReachedThreshold={0.6}
          onEndReached={() => {
            if (query.hasNextPage && !query.isFetchingNextPage) query.fetchNextPage();
          }}
          refreshing={query.isRefetching}
          onRefresh={() => query.refetch()}
          ListFooterComponent={() => (query.isFetchingNextPage ? <ActivityIndicator style={{ marginVertical: 12 }} /> : null)}
        />
      )}
    </View>
  );
}
```

Notes:
- The server enforces `q` minimum length; the debounce ensures we don’t send short queries.
- Include `q`/`category` inside `queryKey` so React Query caches distinct result sets per filter.

### 11.9 Reuse for Results/Admit Cards/Syllabi/Posts

Use a generic hook that encapsulates infinite pagination for any resource path; then render it with a resource-specific item component.

```ts
// useInfiniteList.ts
import { useInfiniteQuery } from '@tanstack/react-query';
import { api, Envelope, ListResponse } from './api';

export type Vars = { q?: string; category?: string; perPage?: number };

export function useInfiniteList<T>(path: string, vars: Vars) {
  return useInfiniteQuery({
    queryKey: [path, vars],
    initialPageParam: 1,
    queryFn: async ({ pageParam = 1, signal }) => {
      const res = await api.get<Envelope<ListResponse<T>>>(path, {
        params: { page: pageParam, per_page: vars.perPage ?? 10, q: vars.q, category: vars.category },
        signal,
      });
      if (!res.data.success) throw new Error(res.data.error?.message || 'API error');
      return res.data.data;
    },
    getNextPageParam: (last) => (last.meta.page < last.meta.total_pages ? last.meta.page + 1 : undefined),
  });
}
```

Usage examples:

```tsx
// ResultsScreen.tsx
import React, { useMemo } from 'react';
import { FlatList, Text, ActivityIndicator } from 'react-native';
import { useInfiniteList } from './useInfiniteList';

type Result = { id: number; title: string; slug: string; result_date?: string };

export function ResultsScreen() {
  const query = useInfiniteList<Result>('/results', { perPage: 10, q: undefined });
  const items = useMemo(() => (query.data ? query.data.pages.flatMap(p => p.items) : []), [query.data]);
  if (query.isLoading) return <ActivityIndicator style={{ marginTop: 24 }} />;
  return (
    <FlatList
      data={items}
      keyExtractor={(it) => String(it.id)}
      renderItem={({ item }) => <Text style={{ padding: 12 }}>{item.title}</Text>}
      onEndReachedThreshold={0.6}
      onEndReached={() => query.hasNextPage && !query.isFetchingNextPage && query.fetchNextPage()}
      ListFooterComponent={() => (query.isFetchingNextPage ? <ActivityIndicator style={{ marginVertical: 12 }} /> : null)}
    />
  );
}
```

```tsx
// AdmitCardsScreen.tsx
type AdmitCard = { id: number; title: string; slug: string; exam_date?: string };
export function AdmitCardsScreen() {
  const query = useInfiniteList<AdmitCard>('/admit-cards', { perPage: 10 });
  // ... identical rendering as ResultsScreen
}
```

```tsx
// SyllabiScreen.tsx
type Syllabus = { id: number; title: string; slug: string; exam_date?: string };
export function SyllabiScreen() {
  const query = useInfiniteList<Syllabus>('/syllabi', { perPage: 10, q: 'upsc' });
  // ... identical rendering as ResultsScreen
}
```

```tsx
// PostsScreen.tsx
type Post = { id: number; title: string; slug: string; category_slug?: string };
export function PostsScreen() {
  const query = useInfiniteList<Post>('/posts', { perPage: 10, category: 'news' });
  // ... identical rendering as ResultsScreen
}
```

## 12) App Architecture Recommendations

- **Networking Layer**
  - Centralize base URL, headers, and error handling.
  - Map the envelope to internal types (`success`, `data`, `error`).
  - Normalize pagination (`meta`) to a common type used by list screens.

- **Caching**
  - Short-term caching on the client to improve UX and reduce calls (e.g., 1–5 minutes for lists).
  - Offline-first: store last successful responses in local DB (Room/Realm/SQLite/CoreData).

- **Error Handling**
  - Inspect `success` and `error.code`.
  - Show actionable messages for `404` (resource not found) and `422` (validation).
  - Retry transient failures with exponential backoff.

- **Search UX**
  - Debounce user input before calling endpoints with `q` (min 2 chars enforced by API).
  - Preload first page and append on scroll.

- **Detail Screens**
  - Fetch by slug. If navigated from list, optimistically render the list item, then hydrate with detail.

- **Analytics**
  - Optionally log endpoint timings and errors for monitoring.

## 13) Performance & Future Enhancements

- **Indexes**: Models/DB schemas include full-text and time indexes for fast list/search.
- **ETag/Last-Modified (optional)**: Could be added to reduce bandwidth; clients should send `If-None-Match`/`If-Modified-Since` and handle `304 Not Modified`.
- **Rate limiting (optional)**: Consider IP-based or token-based throttling for public deployments.
- **Auth (optional)**: The current API is public and read-only. For admin/metrics endpoints, add API keys or JWTs.

## 14) Testing

- Use Swagger UI for quick try-outs.
- Use Postman collection with environment variable `baseUrl`.
- For real slugs from DB: open `docs/api/generate_examples.php`.

## 15) Deployment Notes

- Without Apache rewrites, always include `index.php` in the API path as shown.
- Ensure PHP extensions required by PDO/MySQL are enabled (XAMPP defaults are fine).
- Keep `src/config.php` updated with DB credentials for staging/production.

---

If you want, I can tailor this guide with your production domain, add more platform-specific code (e.g., Android Paging 3, Swift Combine/async-await, Flutter Riverpod), and include ETag/Last-Modified instructions once implemented.
