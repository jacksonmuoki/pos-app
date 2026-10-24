<?php
function setting(string $key, string $default = ''): string
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
                $cache[$row['setting_key']] = (string) $row['setting_value'];
            }
        } catch (PDOException $e) {
            // Table missing or DB down: fall back to defaults so the login page still loads
        }
    }

    return $cache[$key] ?? $default;
}