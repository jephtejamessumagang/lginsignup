<?php

/**
 * Cross-role notifications for JJJ GentleCare.
 *
 * Roles that can receive: staff | patient | doctor | admin
 * Target can be a role ("staff") or an email ("patient@gentlecare.com").
 */
function log_notification(string $target, string $message, string $type = 'info', array $meta = []): void
{
    $file = __DIR__ . '/notifications.json';
    $items = [];
    if (file_exists($file)) {
        $decoded = json_decode(file_get_contents($file), true);
        if (is_array($decoded)) {
            $items = $decoded;
        }
    }

    $maxId = 0;
    foreach ($items as $n) {
        $id = (int)($n['id'] ?? 0);
        if ($id > $maxId) {
            $maxId = $id;
        }
    }

    $now = time();
    array_unshift($items, [
        'id'        => $maxId + 1,
        'target'    => strtolower(trim($target)),
        'message'   => $message,
        'type'      => $type, // info | success | danger | primary
        'read'      => false,
        'meta'      => $meta,
        'timestamp' => $now,
        'date'      => date('Y-m-d H:i:s', $now),
        'time'      => date('g:i A', $now),
    ]);

    if (count($items) > 300) {
        $items = array_slice($items, 0, 300);
    }

    file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT));
}

/**
 * Notifications for a logged-in user (by role and email).
 */
function get_user_notifications(array $user, bool $unreadOnly = false): array
{
    $file = __DIR__ . '/notifications.json';
    if (!file_exists($file)) {
        return [];
    }
    $items = json_decode(file_get_contents($file), true) ?? [];
    if (!is_array($items)) {
        return [];
    }

    $role  = strtolower($user['role'] ?? '');
    $email = strtolower($user['email'] ?? '');
    $name  = strtolower($user['name'] ?? '');

    $out = [];
    foreach ($items as $n) {
        $t = strtolower($n['target'] ?? '');
        $match = (
            $t === $role ||
            ($email !== '' && $t === $email) ||
            ($name !== '' && $t === $name) ||
            ($role === 'doctor' && ($t === 'doctor' || $t === 'dr. vistal' || str_contains($t, 'vistal')))
        );
        if (!$match) {
            continue;
        }
        if ($unreadOnly && !empty($n['read'])) {
            continue;
        }
        $out[] = $n;
    }
    return $out;
}

function mark_notifications_read(array $user): void
{
    $file = __DIR__ . '/notifications.json';
    if (!file_exists($file)) {
        return;
    }
    $items = json_decode(file_get_contents($file), true) ?? [];
    if (!is_array($items)) {
        return;
    }

    $role  = strtolower($user['role'] ?? '');
    $email = strtolower($user['email'] ?? '');
    $name  = strtolower($user['name'] ?? '');
    $changed = false;

    foreach ($items as &$n) {
        $t = strtolower($n['target'] ?? '');
        $match = (
            $t === $role ||
            ($email !== '' && $t === $email) ||
            ($name !== '' && $t === $name) ||
            ($role === 'doctor' && ($t === 'doctor' || $t === 'dr. vistal' || str_contains($t, 'vistal')))
        );
        if ($match && empty($n['read'])) {
            $n['read'] = true;
            $changed = true;
        }
    }
    unset($n);

    if ($changed) {
        file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT));
    }
}

function format_notif_time(array $n): string
{
    $ts = $n['timestamp'] ?? null;
    if (!$ts && !empty($n['date'])) {
        $ts = strtotime($n['date']);
    }
    if (!$ts) {
        return $n['time'] ?? '';
    }
    $diff = time() - (int)$ts;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return (int)floor($diff / 60) . ' min ago';
    if ($diff < 86400 && date('Y-m-d', $ts) === date('Y-m-d')) {
        return date('g:i A', $ts);
    }
    if (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) {
        return 'Yesterday';
    }
    return date('M j, g:i A', $ts);
}
