# Workpackage API

This document captures the REST endpoints exposed for automating workpackage deployment updates. All endpoints require Sanctum token authentication and live under the `/api` prefix.

## Authentication

1. Create or choose a service account `User` record.
2. Generate a token in Tinker (plain-text value printed once):

```bash
php artisan tinker
>>> $user = App\Models\User::where('email', 'ci@example.test')->first();
>>> $token = $user->createToken('ci-deploy')->plainTextToken;
>>> $token
=> "plain-text-token"
```
3. Store the token as a masked secret, e.g. `WORKPACKAGE_API_TOKEN`.

Laravel Sanctum stores a hashed copy in the `personal_access_tokens` table; delete the record there to revoke access.

## Endpoints

### GET `/api/workpackages`

Returns a paginated list of projects with deployment metadata for mapping IDs in CI scripts.

Query params:
- `per_page` (int) – optional page size (default 15).
- `only_active` (bool) – when truthy, limits to non-completed projects.

Example request:

```bash
curl --fail \
  -H "Authorization: Bearer $WORKPACKAGE_API_TOKEN" \
  "$APP_URL/api/workpackages?per_page=50&only_active=1"
```

Example response:

```json
{
  "data": [
    {
      "id": 42,
      "title": "Learning Portal Refresh",
      "status": "development",
      "deadline": "2025-06-30",
      "updated_at": "2025-02-01T14:05:18+00:00",
      "deployed": {
        "id": 42,
        "deployed_by": 3,
        "environment": "staging",
        "status": "pending",
        "deployment_date": "2025-01-31",
        "version": "1.3.0",
        "production_url": "https://staging.example.org",
        "deployment_notes": null,
        "rollback_plan": null,
        "monitoring_notes": null,
        "deployment_sign_off": "pending",
        "operations_sign_off": "pending",
        "user_acceptance_sign_off": "pending",
        "service_delivery_sign_off": "pending",
        "change_advisory_sign_off": "pending",
        "updated_at": "2025-02-01T14:04:00+00:00"
      }
    }
  ],
  "links": {
    "first": "https://app.test/api/workpackages?page=1",
    "last": "https://app.test/api/workpackages?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "https://app.test/api/workpackages",
    "per_page": 50,
    "to": 1,
    "total": 1
  }
}
```

### POST `/api/workpackages/{project}/deployment`

Updates the `deployeds` record attached to a project. Only `deployment_date` is required; any other field is optional and, when omitted, the existing value is preserved. Provide `null` explicitly if you need to clear a field.

Minimum payload:

```json
{
  "deployment_date": "2025-02-01"
}
```

Quick update with the minimum payload:

```bash
curl --fail -X POST \
  -H "Authorization: Bearer $WORKPACKAGE_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"deployment_date":"2025-02-01"}' \
  "$APP_URL/api/workpackages/42/deployment"
```

Full payload example:

```json
{
  "deployed_by": 3,
  "environment": "production",
  "status": "deployed",
  "deployment_date": "2025-02-01",
  "version": "2025.02.0",
  "production_url": "https://app.example.org",
  "deployment_notes": "Deployed via GitLab",
  "rollback_plan": "Redeploy previous tag",
  "monitoring_notes": "Observe Grafana dashboards",
  "deployment_sign_off": "approved",
  "operations_sign_off": "approved",
  "user_acceptance_sign_off": "pending",
  "service_delivery_sign_off": "pending",
  "change_advisory_sign_off": "approved"
}
```

Example `curl` call (full payload stored in `payload.json`):

```bash
curl --fail -X POST \
  -H "Authorization: Bearer $WORKPACKAGE_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d @payload.json \
  "$APP_URL/api/workpackages/42/deployment"
```

Successful response:

```json
{
  "message": "Deployment details updated.",
  "deployed": {
    "id": 42,
    "deployed_by": 3,
    "environment": "production",
    "status": "deployed",
    "deployment_date": "2025-02-01",
    "version": "2025.02.0",
    "production_url": "https://app.example.org",
    "deployment_notes": "Deployed via GitLab",
    "rollback_plan": "Redeploy previous tag",
    "monitoring_notes": "Observe Grafana dashboards",
    "deployment_sign_off": "approved",
    "operations_sign_off": "approved",
    "user_acceptance_sign_off": "pending",
    "service_delivery_sign_off": "pending",
    "change_advisory_sign_off": "approved",
    "updated_at": "2025-02-01T15:12:10+00:00"
  }
}
```

Validation failures return HTTP 422 with field messages. For example, sending other fields without a date returns:

```json
{
  "message": "The deployment date field is required.",
  "errors": {
    "deployment_date": [
      "The deployment date field is required."
    ]
  }
}
```

## Using the API in CI

### GitLab CI example

```yaml
deploy_production:
  stage: deploy
  script:
    - 'WP_ID=$(curl --fail -H "Authorization: Bearer $WORKPACKAGE_API_TOKEN" "$APP_URL/api/workpackages?only_active=1" | jq -r ''.data[] | select(.title=="${CI_PROJECT_TITLE}") | .id'')'
    - 'curl --fail -X POST \
        -H "Authorization: Bearer $WORKPACKAGE_API_TOKEN" \
        -H "Content-Type: application/json" \
        -d "{\n          \"deployed_by\": ${DEPLOYED_BY_USER_ID},\n          \"environment\": \"production\",\n          \"status\": \"deployed\",\n          \"deployment_date\": \"$(date -u +%Y-%m-%d)\",\n          \"version\": \"${CI_COMMIT_TAG:-$CI_COMMIT_SHORT_SHA}\",\n          \"deployment_notes\": \"GitLab job ${CI_JOB_ID}\",\n          \"deployment_sign_off\": \"approved\",\n          \"operations_sign_off\": \"pending\",\n          \"user_acceptance_sign_off\": \"pending\",\n          \"service_delivery_sign_off\": \"pending\",\n          \"change_advisory_sign_off\": \"pending\"\n        }" \
        "$APP_URL/api/workpackages/${WP_ID}/deployment"'
  variables:
    APP_URL: https://projman.example.org
  secrets:
    WORKPACKAGE_API_TOKEN:
      file: false
```

Omit optional JSON keys when they have not changed; the API retains existing values automatically.

### GitHub Actions example

```yaml
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Lookup workpackage ID
        id: lookup
        env:
          APP_URL: https://projman.example.org
          TOKEN: ${{ secrets.WORKPACKAGE_API_TOKEN }}
        run: |
          id=$(curl --fail -H "Authorization: Bearer ${TOKEN}" "${APP_URL}/api/workpackages?only_active=1" \
            | jq -r '.data[] | select(.title=="'"${{ github.event.repository.name }}"'") | .id')
          echo "workpackage_id=${id}" >> "$GITHUB_OUTPUT"

      - name: Notify deployment
        env:
          APP_URL: https://projman.example.org
          TOKEN: ${{ secrets.WORKPACKAGE_API_TOKEN }}
        run: |
          curl --fail -X POST \
            -H "Authorization: Bearer ${TOKEN}" \
            -H "Content-Type: application/json" \
            -d "{\n              \"deployed_by\": ${DEPLOYED_BY_USER_ID},\n              \"environment\": \"${{ github.ref == 'refs/heads/main' && 'production' || 'staging' }}\",\n              \"status\": \"deployed\",\n              \"deployment_date\": \"$(date -u +%Y-%m-%d)\",\n              \"version\": \"${{ github.sha }}\"\n            }" \
            "${APP_URL}/api/workpackages/${{ steps.lookup.outputs.workpackage_id }}/deployment"
```

Ensure `DEPLOYED_BY_USER_ID` is available to your pipeline (either via a variable or by querying `/api/workpackages` to match on deployer email/username).

For repeat deploys where only the date matters, shrink the payload to `{ "deployment_date": "$(date -u +%Y-%m-%d)" }`.
