# Einfaches Fotoalbum ohne Datenbank

Einfache Foto-Upload und -Verwaltung, Mobile-First Webanwendung ohne Datenbank.

## Features

- Foto-Upload mit automatischer Thumbnail-Erstellung
- Kategorien-Verwaltung
- User-Verwaltung (Admin/User-Rollen)
- EXIF-Daten: Sortierung nach Aufnahmedatum
- Lightbox-Ansicht mit:
  - Lade-Animation (Spinner)
  - Vor/Zurück-Navigation (Loop)
  - Tastatur-Navigation (← →, Escape)
  - Inline-Bearbeitung von Beschreibungen
- Responsive Design (Mobile-First)

## Technologie

- PHP 8.4
- JSON-Dateien als Datenspeicher (keine Datenbank nötig)
- Pico CSS + Alpine.js
- GD-Library für Bildverarbeitung

## Installation

1. Repository klonen
2. Konfiguration erstellen:
   ```bash
   cp config/config.example.php config/config.php
   ```
3. In `config/config.php` anpassen:
   - Admin-Zugangsdaten
   - Seiten-Titel und Footer
4. Verzeichnisse erstellen:
   ```bash
   mkdir -p data uploads/originals uploads/thumbs
   chmod 755 data uploads uploads/originals uploads/thumbs
   ```
5. Webserver auf das Verzeichnis zeigen lassen

## Anforderungen

- PHP 8.4+
- GD-Extension
- mod_rewrite (Apache) oder entsprechende Nginx-Config

## Verzeichnisstruktur

```
├── index.php              # Router + Controller
├── config/
│   ├── config.example.php # Konfigurations-Template
│   └── config.php         # Eigene Konfiguration (nicht im Repo)
├── src/                   # PHP-Klassen
├── templates/             # PHP-Templates
├── css/                   # Stylesheets
├── js/                    # JavaScript
├── data/                  # JSON-Daten (nicht im Repo)
└── uploads/               # Bilder (nicht im Repo)
```

## Lizenz

MIT
