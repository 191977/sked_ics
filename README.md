# Sked .ics

Mit diesem Addon wird Sked um einen ICS Import/Export erweitert und der Möglichkeit, Sked-Einträge als JSON-LD Event auszugeben.

> Hinweis: Nicht für den Produktiveinsatz empfohlen, jedoch zum Testen, Feedback geben und erweitertn! Beteilige dich aktiv an der Entwicklung auf Github: [`alexplusde/sked_ics`](https://github.com/alexplusde/sked_ics).


## Features

* Import von .ics-Kalenderdaten zu Sked via URL-Aufruf
* geplant: Datei aus dem Medienpool manuell importieren
* Automatisches Erstellen / Zuordnen neuer Kategorien in Sked
* geplant: Automatische Erstellung neuer Locations in Sked
* Ausgabe von .ics-Kalenderdaten via API oder via Klassenaufruf 
* Ausgabe von Kalenderdaten im JSON-LD-Format via Klassenaufruf
* geplant: Vollständiger Umgang mit mehreren REDAXO-Sprachen

Beteilige dich aktiv an der Entwicklung auf Github: [`alexplusde/sked_ics`](https://github.com/alexplusde/sked_ics).

## Installation

* Addon über den Installer herunterladen oder
* alternativ GitHub-Version entpacken, den Ordner in `sked_ics` umbenennen und in den REDAXO AddOn-Ordner legen `/redaxo/src/addons/sked_ics`
* alternativ über das AddOn zip_install hochladen und anschließend in der AddOns-Page installieren

Mit der Installation werden in Sked neue Tabellenfelder angelegt: 
* `is_fulltime` für einen Marker, ob es sich um einen ganztägigen Termin handelt.
* `uid`, `raw`, `source_url`, um Informationen über per Cronjob importierte Events zu sammeln und ggf. beizubehalten.
* `categories`, um mehrere Kategorien einer importierten ICS-Datei auch in mehreren Kategorien in Sked ablegen zu können.

> Hinweis: Diese Tabellenfelder sind nicht in der Bearbeitungsoberfläche sichtbar. Um diese sichtbar zu schalten, bitte wie in Sked angegeben eigene Felder definieren.
 
## Verwendung

### Import-Cronjob

Einfach das Cronjob-Addon aufrufen, einen neuen Cronjob anlegen und den Instruktionen folgen.

### In Modulen und Templates

```
$sked_events = \Sked\Handler\SkedHandler::getEntries("2018-01-01", "", true, 'SORT_ASC', "");

// Array an Sked-Events in einem ICS-Kalender
dump(sked_ics::factory($sked_events)->getSkedEventsAsIcs());

// Array an Sked-Events im JSON-LD-Format
dump(sked_ics::factory($sked_events)->getSkedEventsAsJsonld());

// Einzelnes Event in einem ICS-Kalender
dump(sked_ics::getSkedEventAsIcs($sked_events[0]));

// Einzelnes Event im JSON-LD-Format
dump(sked_ics::getSkedEventAsJsonld($sked_events[0]));
```

# Dank an

* [Markus Poerschke, @markuspoerschke](https://github.com/markuspoerschke/iCal) 
* [Jonathan Goode, @u01jmg3](https://github.com/u01jmg3) 
