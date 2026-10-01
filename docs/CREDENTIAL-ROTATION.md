# Credential Rotation — Phase 11

There is no automated way to rotate any of these. Each is a manual procedure, and
each is written so it can be followed without reading code.

**Before any rotation:** confirm you have a current database backup
(`php artisan tinker` → the backup screen, or `mysqldump`). Rotating `APP_KEY`
without one is unrecoverable for anything encrypted with it.

---

## 1. `APP_KEY`

Rotating invalidates **everything** encrypted with the old key: every session, every
cookie, and every encrypted column — including the database backups added in
Phase 11.

```bash
php artisan key:generate --force
php artisan optimize:clear
```

**Blast radius:** all users are signed out. Restore works only if you also rotate
`SESSION_ENCRYPT` handling or accept the re-login.

**Verify:** sign in again; open a stored backup and confirm it still decrypts. If it
does not, that backup is unrecoverable and must be re-taken.

---

## 2. Mail (SMTP) password

**Triggered by:** `.env.backup` on disk holds a populated `MAIL_PASSWORD` (see the
report). Assume it is compromised and rotate whether or not you believe otherwise.

1. Issue a new app password in the mail provider's console. Do not reuse the old one.
2. Update `MAIL_PASSWORD` in `.env` **and** in any deployment secret store.
3. `php artisan optimize:clear` so the new value is picked up.
4. Trigger a real send and confirm it arrives:
   ```bash
   php artisan tdos:overdue      # queues an overdue notification
   php artisan queue:work --stop-when-empty
   ```
5. **Revoke the old password** in the provider console. Updating your own config
   does not invalidate the credential.

**Also:** delete `.env.backup`. It is git-ignored, so it was never committed, but
it is a plaintext credential file sitting in the project directory — and it holds a
*different* `APP_KEY`, which suggests it came from another environment entirely.

---

## 3. Database password

```sql
ALTER USER 'worksphere'@'localhost' IDENTIFIED BY '<new>';
```

Then update `DB_PASSWORD`, `php artisan optimize:clear`, and confirm:

```bash
php artisan migrate:status
php artisan tinker --execute="echo DB::connection()->getDatabaseName();"
```

**Blast radius:** every pooled connection using the old credential fails until the
old grants are dropped. Do the `FLUSH PRIVILEGES` and restart PHP-FPM in the same
maintenance window.

---

## 4. `users.password` hashes

There is no global password salt in Laravel: each hash embeds its own salt, so
rotating is a per-user operation, not a global one.

For a compromised account only:

```php
php artisan tinker --execute="
\$u = App\Models\User::where('email', 'someone@example.com')->firstOrFail();
\$u->update(['password' => 'the-new-raw-password']);
\$u->tokens()->delete();   // kill any API token they held
"
```

Then invalidate their sessions (`DELETE FROM sessions WHERE user_id = ?`) and record
the change in `activity_logs`.

---

## 5. Sanctum API tokens

Phase 11 gives every token an expiry (`SANCTUM_EXPIRATION`, default 1440 minutes),
so a leaked token ages out on its own. For an immediate revocation, ask the holder
to revoke it, or delete the row directly:

```sql
DELETE FROM personal_access_tokens WHERE last_used_at < NOW() - INTERVAL 30 DAY;
```

Every issuance and revocation is in `activity_logs` under
`action = 'api_token_issued'` / `'revoked'`, with the token id and its abilities —
never the token value.

---

## 6. Cloud keys (`AWS_SECRET_ACCESS_KEY`, `REDIS_PASSWORD`)

1. Issue a new key in the provider console.
2. Update `.env`, `php artisan optimize:clear`.
3. Revoke the old key last — revoking first breaks every deployment that has not
   been updated yet.

---

## 7. `bcrypt` cost

`BCRYPT_ROUNDS=12` in the production template, raised from the development value.
Raising it only affects NEW hashes: existing ones keep their cost until the user
next changes their password. To migrate everyone, force a reset rather than
re-hashing in place — an in-place rehash would have to authenticate the user first,
which is the same thing.
