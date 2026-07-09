<?php

declare(strict_types=1);

use App\Jobs\AnnualReviewReminderJob;
use App\Jobs\DigestEmailJob;
use App\Jobs\ExpireMutedThreadsJob;
use App\Jobs\IncidentAutoCloseCheckJob;
use App\Jobs\StaleIncidentAlertJob;
use App\Jobs\StewardResponsivenessCheckJob;
use App\Jobs\StripeWebhookProcessorJob;
use App\Jobs\SubscriptionRenewalCheckJob;
use App\Jobs\VaultSealCheckJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes — Scheduled Jobs
|--------------------------------------------------------------------------
| Drives all background cadences for Aegis. Run `php artisan schedule:work`
| in development or wire `schedule:run` to system cron in production
| (* * * * * php artisan schedule:run).
*/

// Daily 09:00 UTC — fire AnnualReviewDue at 30d / 7d / 0d windows.
Schedule::job(new AnnualReviewReminderJob)->dailyAt('09:00')->name('aegis.annual_review_reminder');

// Daily — flag plans whose vault attestation is stale (>1 year or missing).
Schedule::job(new VaultSealCheckJob)->daily()->name('aegis.vault_seal_check');

// Every 6 hours — surface stale (>72h) active incidents to CS + admins.
Schedule::job(new StaleIncidentAlertJob)->everySixHours()->name('aegis.stale_incident_alert');

// Sundays 08:00 UTC — weekly digest for users opted into notify_summary.
Schedule::job(new DigestEmailJob('weekly'))->weeklyOn(0, '08:00')->name('aegis.digest_weekly');

// 1st of month 08:00 UTC — monthly digest.
Schedule::job(new DigestEmailJob('monthly'))->monthlyOn(1, '08:00')->name('aegis.digest_monthly');

// Every 5 minutes — sweep any unprocessed Stripe webhook rows.
Schedule::job(new StripeWebhookProcessorJob)->everyFiveMinutes()->name('aegis.stripe_webhook_sweep');

// Every minute — auto-unmute message threads whose muted_until has passed.
Schedule::job(new ExpireMutedThreadsJob)->everyMinute()->name('aegis.expire_muted_threads')->withoutOverlapping();

// Daily 10:00 UTC — flag Continuity Stewards who have been inactive for 14+ days.
Schedule::job(new StewardResponsivenessCheckJob)->dailyAt('10:00')->name('aegis.steward_responsiveness_check');

// Daily 08:00 UTC — warn users whose subscription renews in 7 days.
Schedule::job(new SubscriptionRenewalCheckJob)->dailyAt('08:00')->name('aegis.subscription_renewal_check');

// Hourly — auto-close incidents that hit "ready for closure" state past CS_INCIDENT_AUTOCLOSE_DAYS
// window (default 7d) without explicit Provider or SS verification.
Schedule::job(new IncidentAutoCloseCheckJob)->hourly()->name('aegis.incident_auto_close');
