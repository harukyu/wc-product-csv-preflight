# Product CSV Preflight – deutsche Kurzanleitung

**Vorabversion 0.1.0:** Syntax und Paketbau sind der veröffentlichte Prüfumfang. Die tatsächliche WordPress-/WooCommerce-Integration und das Analyseverhalten wurden noch nicht funktional geprüft.

## Zweck

Produktdateien vor dem WooCommerce-Import auf typische Fehler untersuchen: doppelte Artikelnummern, ungültige Preise, fehlende Varianten-Eltern und widersprüchliche Angaben. Es werden keine Produkte importiert oder geändert.

## Installation und Bedienung

1. Unter [Releases](https://github.com/harukyu/wc-product-csv-preflight/releases) `wc-product-csv-preflight-0.1.0.zip` laden.
2. Auf einer Entwicklungsinstallation als Plugin hochladen und aktivieren.
3. **Werkzeuge → Product CSV Preflight** öffnen, CSV und Trennzeichen wählen und prüfen.

Benötigt die Berechtigung `manage_woocommerce`, die WooCommerce üblicherweise Administratoren und Shop-Managern erteilt. Vorgesehene Mindestversionen: WordPress 6.0 und PHP 7.4. Die Oberfläche ist englisch.

## Format

UTF-8 mit englischen Spaltennamen des eingebauten WooCommerce-Importers, etwa `SKU`, `Name`, `Type`, `Parent`, `Regular price` und `Sale price`. Mindestens eine der Spalten SKU, ID oder Name muss vorkommen. Eigene Spaltenzuordnungen und deutsche Spaltennamen werden nicht automatisch erkannt. Komma, Semikolon oder Tab sind auswählbar. Preise brauchen einen Dezimalpunkt ohne Währungssymbol.

Varianten verweisen mit einer Artikelnummer oder `id:123` auf ihr Elternprodukt. Ein Verweis außerhalb der Datei wird nur als Warnung gemeldet: Das Produkt könnte bereits im Shop bestehen. Der Shop wird nicht abgefragt.

## Ergebnis

- Fehler müssen anhand der Quelldatei geklärt werden.
- Warnungen können bei Teilaktualisierungen beabsichtigt sein.
- Datensatznummern zählen den Kopf als 1. Bei mehrzeiligen Feldern sind sie keine Editor-Zeilennummern.
- Artikelnummern, Namen und andere Produktwerte erscheinen nicht im Bericht.
- Nur die ersten 200 Befunde werden angezeigt; die Gesamtzahlen berücksichtigen alle Befunde.
- Maximal 5 MiB, 10.000 Datensätze, 256 Spalten und 100.000 Zellen.

Ein leerer Fehlerbericht garantiert keinen erfolgreichen Import. Attribute, Bilder, Shopbestand, Datumsangaben und Erweiterungsspalten werden nicht geprüft. Ein vollständig eingelesener Bericht beschreibt nur den unterstützten Prüfumfang.

## Ohne WordPress

```sh
php bin/preflight.php products.csv
php bin/preflight.php products.csv semicolon
wp nakaryu csv preflight products.csv
```

Die eigenständige Variante benötigt nur PHP. Rückgabecodes: 0 ohne Befund, 1 Warnungen, 2 Fehlerbefunde, 3 Eingabefehler.

Im Backend wird die Datei an euren eigenen WordPress-Server übertragen und nur aus der temporären PHP-Datei gelesen. Das Plugin speichert keine Kopie und sendet nichts an Nakaryu. Andere Serverkomponenten können eigene Protokolle führen.

Entwickelt von [Nakaryu GmbH](https://nakaryu.de), unabhängig von den Verkaufsplugins. GPL-2.0-or-later.
