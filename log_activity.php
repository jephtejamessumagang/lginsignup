<?php


function log_activity(string $actor, string $action, string $type = 'info'): void
{
    $file = __DIR__ . '/activity.json';
    $activities = [];

    if (file_exists($file)) {
        $decoded = json_decode(file_get_contents($file), true);
        if (is_array($decoded)) {
            $activities = $decoded;
        }
    }

    $maxId = 0;
    foreach ($activities as $a) {
        $id = (int)($a['id'] ?? 0);
        if ($id > $maxId) {
            $maxId = $id;
        }
    }

    $now = time();
    $entry = [
        'id'        => $maxId + 1,
        'actor'     => $actor,
        'action'    => $action,
        'type'      => $type,
        'time'      => date('g:i A', $now),
        'timestamp' => $now,
        'date'      => date('Y-m-d H:i:s', $now),
    ];

    array_unshift($activities, $entry);

    if (count($activities) > 200) {
        $activities = array_slice($activities, 0, 200);
    }

    file_put_contents($file, json_encode(array_values($activities), JSON_PRETTY_PRINT));
}

function format_activity_time(array $item): string
{
    $ts = $item['timestamp'] ?? null;
    if (!$ts && !empty($item['date'])) {
        $ts = strtotime($item['date']);
    }
    if (!$ts) {
        return $item['time'] ?? '';
    }

    $diff = time() - (int)$ts;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $m = (int)floor($diff / 60);
        return $m . ' min ago';
    }
    if ($diff < 86400 && date('Y-m-d', $ts) === date('Y-m-d')) {
        return date('g:i A', $ts);
    }
    if ($diff < 172800 && date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) {
        return 'Yesterday';
    }
    if ($diff < 604800) {
        return date('D g:i A', $ts);
    }
    return date('M j, g:i A', $ts);
}
