# Jammu First Webhooks

## Outbound (app → your URL)

Set in server environment or `config/config.php`:

- `WEBHOOK_URL` — destination URL (empty = disabled)
- `WEBHOOK_SECRET` — HMAC secret

On events the app POSTs JSON:

```json
{
  "event": "task.assigned",
  "app": "Jammu First",
  "timestamp": "2026-08-07T11:00:00+05:30",
  "data": { }
}
```

Headers:

- `Content-Type: application/json`
- `X-Jammu-Event: task.assigned`
- `X-Jammu-Signature: sha256=<hmac_sha256(body, secret)>`

Events: `task.assigned`, `task.completed`, `task.updated`, `task.deleted`, `message.created`, `upload.created`, `notification.created`

## Inbound (your system → app)

`POST /webhooks/incoming.php`

Same signature header required.

```json
{
  "event": "notification.push",
  "user_id": 2,
  "title": "External alert",
  "body": "Details…",
  "link": "/member/dashboard.php"
}
```
