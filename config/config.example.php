<?php
/**
 * Konfigurationsdatei für adventmarkt.usbdata.at
 *
 * INSTALLATION:
 * 1. Diese Datei kopieren nach config.php
 * 2. Admin-Zugangsdaten anpassen
 * 3. Seiten-Einstellungen anpassen
 */

return [
    # Seiten-Einstellungen
    'site' => [
        'title' => 'Adventmarkt Fotos',
        'footer' => 'Adventmarkt Musterstadt',
    ],

    # Admin-Zugangsdaten (erster Admin-User)
    'admin' => [
        'email' => 'admin@example.com',
        'password' => 'HIER_SICHERES_PASSWORT_SETZEN',
    ],

    # Pfade
    'paths' => [
        'data' => __DIR__ . '/../data',
        'uploads' => __DIR__ . '/../uploads',
        'originals' => __DIR__ . '/../uploads/originals',
        'thumbs' => __DIR__ . '/../uploads/thumbs',
    ],

    # Bild-Einstellungen
    'images' => [
        'thumb_max_width' => 500,
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_upload_size' => 20 * 1024 * 1024, # 20 MB
    ],

    # Session
    'session' => [
        'name' => 'adventmarkt_session',
        'lifetime' => 86400, # 24 Stunden
    ],
];
