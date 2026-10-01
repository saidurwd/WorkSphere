<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;

/**
 * Issues Sanctum API tokens — Phase 11 item 3.
 *
 * Sanctum was installed and NO code path ever issued a token, so the API could
 * not be used at all. This is that path, and it is deliberately narrow:
 *
 * - **super-admin only.** Token issuance grants programmatic access to
 *   everything that account can see, so it is gated on the role rather than on a
 *   permission that might later be granted more widely.
 * - **Every issuance is audited**, including the token's abilities, so a
 *   compromised token is traceable to the moment it was created.
 * - **Expiry is enforced** from config, so a leaked token dies on its own.
 */
class IssueApiTokenCommand extends Command
{
    protected $signature = 'sanctum:issue-token
                            {--name= : A label so the token can be identified later}
                            {--expires= : Lifetime in days, overriding the configured default}';

    protected $description = 'Issue a Sanctum API token for a super-admin account.';

    public function __construct(private readonly ActivityLogger $activity)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = $this->ask('Email for the token owner');

        if (! is_string($email) || trim($email) === '') {
            $this->error('An email is required.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error('No such user.');

            return self::FAILURE;
        }

        if ($user->status !== null && strtolower((string) $user->status) !== 'active') {
            $this->error('That account is not active.');

            return self::FAILURE;
        }

        // Checked through the role rather than a permission string, so a
        // permission grant cannot silently open token issuance.
        $isSuperAdmin = $user->roles()->pluck('slug')->intersect(['super-admin'])->isNotEmpty();

        if (! $isSuperAdmin) {
            $this->error('Only a super-admin account may issue an API token.');

            return self::FAILURE;
        }

        $configured = (int) ($this->laravel['config']->get('sanctum.expiration') ?? 0);
        $expiresInDays = $this->option('expires') !== null ? (int) $this->option('expires') : $configured;

        $abilities = ['*'];

        $token = $user->createToken(
            (string) ($this->option('name') ?: 'cli-issued'),
            $abilities,
            $expiresInDays > 0 ? now()->addDays($expiresInDays) : null,
        );

        // The audit row records WHICH abilities the token carries. A token with `*`
        // is a very different act from a read-only one, and an audit trail that
        // cannot tell them apart is not an audit trail. The token VALUE is never
        // recorded — only the id and abilities.
        // Recorded through the shared ActivityLogger, which writes BOTH
        // obligation_activity_logs-style module rows and the shared activity_logs
        // timeline, so a token issue appears wherever activity is read.
        $this->activity->record(
            User::class,
            $user,
            'api_token_issued',
            null,
            [
                'token_id' => $token->accessToken->id,
                'abilities' => $abilities,
                'expires_at' => $expiresInDays > 0 ? now()->addDays($expiresInDays)->toIso8601String() : null,
            ],
            $user->id,
        );

        $this->newLine();
        $this->line('Token issued. Copy it now — it is not shown again:');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->warn('Treat it as a password: anyone holding it has full API access as this account.');

        return self::SUCCESS;
    }
}
