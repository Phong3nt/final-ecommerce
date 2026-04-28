<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IMP-017: Firebase Realtime Database push service.
 *
 * Writes a "latest_notification" node to the admin RTDB path so the
 * admin bell can use Firebase on('value') instead of 30-second polling.
 * All writes are server-side only; the DB Secret is never sent to the browser.
 */
class FirebaseService
{
    /**
     * Push a new-order notification to Firebase RTDB.
     *
     * Silently no-ops when FIREBASE_DB_URL is not configured so the app
     * works without Firebase credentials (dev / test / environments).
     */
    public function pushAdminNotification(int $orderId, int $unreadCount): void
    {
        $dbUrl  = rtrim(config('services.firebase.db_url', ''), '/');
        $secret = config('services.firebase.secret', '');

        if (empty($dbUrl)) {
            return;
        }

        try {
            Http::timeout(5)->put(
                "{$dbUrl}/admin/latest_notification.json?auth={$secret}",
                [
                    'order_id'     => $orderId,
                    'unread_count' => $unreadCount,
                    'timestamp'    => now()->timestamp,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning("FirebaseService: push failed — {$e->getMessage()}");
        }
    }
}
