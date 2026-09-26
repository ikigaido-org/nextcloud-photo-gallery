<?php
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
style('photo_gallery', 'admin');
script('photo_gallery', 'admin');
$s = $_['settings'];
$a = $s['appearance'];
$d = $s['dateOptions'];
?>
<section class="section mpi-settings" aria-labelledby="mpi-settings-title">
    <h2 id="mpi-settings-title">Öffentliche Galerie</h2>
    <p>Öffentlich freigegebene Photos-Alben als Galerie mit eigenen Albumseiten anzeigen.</p>
    <?php if (!$_['ready']): ?><p class="mpi-warning">Bitte zuerst Photos aktivieren.</p><?php endif; ?>
    <form id="mpi-settings-form" action="<?php p($_['saveUrl']); ?>" method="post">
        <input type="hidden" name="requesttoken" value="<?php p(\OCP\Util::callRegister()); ?>">
        <label class="mpi-check"><input type="checkbox" name="enabled" <?php if ($s['enabled']) { print_unescaped('checked'); } ?>> Übersicht öffentlich aktivieren</label>
        <p class="mpi-hint">Damit werden bestehende Albumlinks der unten ausgewählten Konten gemeinsam auffindbar.</p>
        <label for="mpi-title-input">Titel</label>
        <input id="mpi-title-input" name="title" type="text" maxlength="120" required value="<?php p($s['title']); ?>">
        <label for="mpi-description">Beschreibung</label>
        <textarea id="mpi-description" name="description" maxlength="1000" rows="3"><?php p($s['description']); ?></textarea>
        <label class="mpi-check"><input type="checkbox" name="showHeader" value="1" <?php if ($s['showHeader']) { print_unescaped('checked'); } ?>> Nextcloud-Kopfzeile anzeigen (standardmässig aus)</label>
        <label for="mpi-footer">Eigener Footer (Text oder einfaches HTML)</label>
        <textarea id="mpi-footer" name="footerHtml" rows="5" maxlength="16000" placeholder="&lt;p&gt;© Mein Verein · &lt;a href=&quot;https://example.org/&quot;&gt;Zur Website&lt;/a&gt;&lt;/p&gt;"><?php p($s['footerHtml']); ?></textarea>
        <p class="mpi-hint">Leer = kein eigener Footer. Erlaubt: p, br, strong/b, em/i, span, ul, ol, li und a mit HTTPS-/HTTP-/Mailto- oder lokalen Links. Andere Elemente und Attribute werden beim Speichern entfernt; kein CSS, JavaScript oder iframe. Die Textfarbe entspricht „Eigener Footer“.</p>
        <label for="mpi-owners">Konto-IDs (eine pro Zeile)</label>
        <textarea id="mpi-owners" name="owners" rows="4" spellcheck="false" placeholder="galerie"><?php p(implode("\n", $s['owners'])); ?></textarea>
        <p class="mpi-hint">Leer = öffentliche Alben aller aktiven Konten. Die tatsächliche Anmelde-ID verwenden, nicht den Anzeigenamen. Private Alben bleiben ausgeschlossen.</p>
        <fieldset class="mpi-appearance"><legend>Zugriff auf Albumwerkzeuge und Kontowechsel</legend>
        <label class="mpi-check"><input type="checkbox" name="restrictTools" value="1" <?php if($s['restrictTools']): ?>checked<?php endif; ?>> Nur für ausgewählte Konten oder Gruppen</label>
        <label for="pg-tool-users">Konto-IDs (eine pro Zeile)</label>
        <textarea id="pg-tool-users" name="toolUsers" rows="3" spellcheck="false"><?php p(implode("\n", $s['toolUsers'])); ?></textarea>
        <label for="pg-tool-groups">Gruppen-IDs (eine pro Zeile)</label>
        <textarea id="pg-tool-groups" name="toolGroups" rows="3" spellcheck="false"><?php p(implode("\n", $s['toolGroups'])); ?></textarea>
        <p class="mpi-hint">Ein Konto in einer der Listen genügt. Ist unten ein Galeriekonto festgelegt, steuern diese Listen, wer dorthin wechseln darf. „Fotoalbum aus Ordner erstellen“ erscheint dann ausschliesslich im Galeriekonto, nach einem Kontowechsel oder einer direkten Anmeldung. Ohne Galeriekonto steuern die Listen wie bisher den Zugriff auf die Albumwerkzeuge; ohne Einschränkung sind diese für alle aktiven Photos-Konten verfügbar. Die Administration dieser App bleibt erreichbar.</p>
        <p class="mpi-hint">Die veröffentlichte Galerie, Medien und Feeds bleiben öffentlich. Welche Alben veröffentlicht werden, bestimmen die Konto-IDs oben und die Photos-Freigaben. Diese Einstellung verändert keine Datei- oder Photos-Berechtigungen.</p>
        <p class="mpi-hint">Photos 33 unterstützt keine Aktivierung nur für bestimmte Gruppen: Der App-Typ „authentication“ ist davon in Nextcloud ausgeschlossen. Photos bleibt daher unverändert.</p>
        <label for="pg-switch-target">Galeriekonto für den Kontowechsel (Konto-ID)</label>
        <input id="pg-switch-target" name="switchTarget" type="text" maxlength="255" spellcheck="false" autocomplete="off" value="<?php p($s['switchTarget']); ?>" placeholder="galerie">
        <p class="mpi-hint">Leer = Kontowechsel aus. Verwendet die Konten-/Gruppenfreigabe oben; ohne Einschränkung dürfen alle berechtigten Photos-Konten wechseln. Das Ziel muss ein aktives Konto ohne Administrationsrechte sein, bei dem bereits eine direkte Anmeldung erfolgt ist.</p>
        <p class="mpi-hint">Der Wechsel gilt für die ganze Nextcloud-Browsersitzung, einschliesslich anderer Tabs, Dateien und Apps. „Zurück“ stellt das persönliche Konto wieder her; „Abmelden“ beendet die Sitzung vollständig. Keine Passwörter werden gespeichert. Entzug der Freigabe oder Änderung des Ziels beendet aktive Wechselsitzungen beim nächsten Zugriff.</p>
        </fieldset>
        <fieldset class="mpi-appearance"><legend>RSS und Atom</legend>
        <label for="pg-feed-scope">Alben im Feed</label>
        <select id="pg-feed-scope" name="feedScope">
        <?php foreach(['current'=>'Nur das aktuelle Kalenderjahr','from'=>'Ab einem bestimmten Anlassjahr','all'=>'Alle Jahre (auch nachträgliche Archivimporte)'] as $v=>$label): ?><option value="<?php p($v); ?>" <?php if($s['feedScope']===$v): ?>selected<?php endif; ?>><?php p($label); ?></option><?php endforeach; ?>
        </select>
        <label for="pg-feed-year">Frühestes Anlassjahr (bei „Ab einem bestimmten Anlassjahr“)</label>
        <input id="pg-feed-year" name="feedFromYear" type="number" min="1900" max="2099" required value="<?php p($s['feedFromYear']); ?>">
        <p class="mpi-hint">Standard: aktuelles Kalenderjahr (UTC), automatisch mit dem Jahreswechsel. Die Datumszuordnung unten gilt auch für Feeds. Archivimporte aus älteren Jahren bleiben in der Galerie, erscheinen aber nicht im Feed. Ohne Jahreszuordnung erscheint ein Album nur bei „Alle Jahre“. Maximal 24 Einträge. Bereits versandte Benachrichtigungen lassen sich nicht zurückrufen.</p>
        </fieldset>
        <label for="pg-cover-match">Titelbild anhand des Dateinamens wählen</label>
        <input id="pg-cover-match" name="coverMatch" type="text" maxlength="80" value="<?php p($s['coverMatch']); ?>" placeholder="cover_">
        <p class="mpi-hint">Wörtlicher Suchtext an beliebiger Stelle im Dateinamen, ohne Beachtung der Gross-/Kleinschreibung. Nur Bilder, die Mitglied des Albums sind. Bei mehreren Treffern: alphabetischer Dateiname, dann Datei-ID. Leer oder kein Treffer: Photos-Titelbild. Alte manuelle Admin-Auswahlen werden nicht mehr verwendet.</p>
        <fieldset class="mpi-appearance"><legend>Öffentliche Adresse / Reverse Proxy</legend>
        <label for="pg-public-base">Öffentliche Basisadresse</label>
        <input id="pg-public-base" name="publicBaseUrl" type="url" placeholder="https://gallery.example.org" value="<?php p($s['publicBaseUrl']); ?>">
        <label class="mpi-check"><input type="checkbox" name="cleanUrls" value="1" <?php if($s['cleanUrls']): ?>checked<?php endif; ?>> Kurze URLs über eingerichteten Reverse Proxy verwenden</label>
        <p class="mpi-hint">Erzeugt / und /albumname als Seitenlinks. Installiert keine Server-Regeln! Erst einschalten, wenn der externe Proxy gemäss docs/hosting.md eingerichtet ist. Ein CNAME auf Storage Share allein reicht nicht. Standard-Nextcloud-Routen bleiben erreichbar.</p></fieldset>
        <label for="mpi-sort">Sortierung</label>
        <select id="mpi-sort" name="sort">
            <option value="folder_date" <?php if ($s['sort'] === 'folder_date') { print_unescaped('selected'); } ?>>Anlassdatum gemäss Datumszuordnung (neueste zuerst)</option>
            <option value="name" <?php if ($s['sort'] === 'name') { print_unescaped('selected'); } ?>>Albumname (A–Z)</option>
            <option value="newest" <?php if ($s['sort'] === 'newest') { print_unescaped('selected'); } ?>>Zuletzt angelegte Alben zuerst</option>
        </select>
        <label for="mpi-date-source">Datum und Jahr lesen aus</label>
        <select id="mpi-date-source" name="dateOptions[source]">
            <?php foreach (['folders' => 'Quellordner der Albumdateien', 'album' => 'Albumname'] as $v => $label): ?><option value="<?php p($v); ?>" <?php if ($d['source'] === $v) { print_unescaped('selected'); } ?>><?php p($label); ?></option><?php endforeach; ?>
        </select>
        <label for="mpi-date-format">Datumsformat</label>
        <select id="mpi-date-format" name="dateOptions[format]">
            <?php foreach (['auto' => 'Automatisch (alle folgenden Formate)', 'yymmdd' => 'JJMMTT — 260124_Anlass', 'yyyymmdd' => 'JJJJMMTT — 20260124_Anlass', 'ymd' => 'JJJJ-MM-TT — 2026-01-24_Anlass', 'dmy' => 'TT.MM.JJJJ — 24.01.2026_Anlass', 'year' => 'Nur JJJJ — 2026_Anlass'] as $v => $label): ?><option value="<?php p($v); ?>" <?php if ($d['format'] === $v) { print_unescaped('selected'); } ?>><?php p($label); ?></option><?php endforeach; ?>
        </select>
        <label for="mpi-date-position">Position im Namen</label>
        <select id="mpi-date-position" name="dateOptions[position]">
            <option value="start" <?php if ($d['position'] === 'start') { print_unescaped('selected'); } ?>>Am Anfang</option>
            <option value="any" <?php if ($d['position'] === 'any') { print_unescaped('selected'); } ?>>An beliebiger Stelle</option>
        </select>
        <label class="mpi-check"><input type="checkbox" name="dateOptions[ancestors]" value="1" <?php if ($d['ancestors']) { print_unescaped('checked'); } ?>> Übergeordnete Ordner bei fehlendem Datum durchsuchen (nur bei Quelle „Quellordner“)</label>
        <p class="mpi-hint">JJ bedeutet 2000–2099. Bei getrennten Datumsformaten sind Punkt, Bindestrich und Unterstrich möglich. Mehrere Tage im selben Jahr: Jahreszuordnung bleibt erhalten, der früheste Tag bestimmt die Sortierung. Nur ein Jahr: keine erfundene Tagesangabe. Fehlende Angaben oder verschiedene Jahre bleiben ohne Jahreszuordnung.</p>
        <label for="mpi-year-view">Jahresnavigation und Startansicht</label>
        <select id="mpi-year-view" name="yearView">
            <option value="latest" <?php if ($s['yearView'] === 'latest') { print_unescaped('selected'); } ?>>Neuestes verfügbares Jahr zuerst anzeigen</option>
            <option value="all" <?php if ($s['yearView'] === 'all') { print_unescaped('selected'); } ?>>Alle Jahre mit Jahres-Trennlinien anzeigen</option>
            <option value="off" <?php if ($s['yearView'] === 'off') { print_unescaped('selected'); } ?>>Keine Jahresgruppierung</option>
        </select>
        <p class="mpi-hint">Das Jahr stammt aus dem Anlassdatum, nicht aus dem Erstellungsdatum des Albums. Ältere Jahre sind über die Jahresnavigation erreichbar. Alben ohne eindeutiges Datum erscheinen unter „Ohne Jahreszuordnung“. Die gewählte Sortierung gilt innerhalb eines Jahres.</p>
        <fieldset class="mpi-appearance">
            <legend>Schrift für Überschriften</legend>
            <label for="pg-heading-font">Schriftdatei-URL (optional)</label>
            <input id="pg-heading-font" name="appearance[headingFontUrl]" type="url" maxlength="2048" spellcheck="false" placeholder="https://example.org/fonts/heading.woff2" value="<?php p($a['headingFontUrl']); ?>">
            <p class="mpi-hint">Direkter HTTPS-Link auf eine WOFF2-Schrift für die grosse Galerie- und Albumüberschrift. Leer oder nicht verfügbar: Work Sans. Besucher laden die Schrift von der angegebenen Website; diese muss die Einbindung erlauben. Bitte nur Schriften mit passenden Webfont-Rechten verwenden.</p>
        </fieldset>
        <fieldset class="mpi-appearance">
            <legend>Hintergrund und Farben</legend>
            <label class="mpi-check"><input id="mpi-custom-appearance" type="checkbox" name="appearance[enabled]" value="1" <?php if ($a['enabled']) { print_unescaped('checked'); } ?>> Eigenes Erscheinungsbild verwenden</label>
            <p class="mpi-hint">Ohne eigene Einstellungen gilt wie bisher der Hell-/Dunkelmodus des Browsers. Änderungen unten aktivieren das eigene Erscheinungsbild.</p>
            <label for="mpi-background-mode">Hintergrund</label>
            <select id="mpi-background-mode" name="appearance[backgroundMode]">
                <option value="color" <?php if ($a['backgroundMode'] === 'color') { print_unescaped('selected'); } ?>>Eigene Hintergrundfarbe</option>
                <option value="nextcloud" <?php if ($a['backgroundMode'] === 'nextcloud') { print_unescaped('selected'); } ?>>Nextcloud-Hintergrund übernehmen</option>
                <option value="image" <?php if ($a['backgroundMode'] === 'image') { print_unescaped('selected'); } ?>>Bilddatei aus Nextcloud</option>
            </select>
            <p class="mpi-hint" id="mpi-nextcloud-hint">„Nextcloud-Hintergrund“ verwendet das globale Erscheinungsbild der Instanz, auch für Besucher ohne Anmeldung. Persönliche Benutzer-Hintergründe werden nicht veröffentlicht.</p>
            <div id="mpi-background-file">
                <label for="mpi-background-image">Öffentlicher Link der Bilddatei</label>
                <input id="mpi-background-image" name="appearance[backgroundImage]" type="url" maxlength="2048" placeholder="https://cloud.example.org/s/…" value="<?php p($a['backgroundImage']); ?>">
                <p class="mpi-hint">In Nextcloud eine einzelne Bilddatei (z. B. JPG, PNG oder WebP) per öffentlichem Link ohne Passwort freigeben und den Link hier einfügen. Ein interner Dateilink oder eine Ordnerfreigabe funktioniert nicht. Nach Ablauf oder Entfernen der Freigabe bleibt die Hintergrundfarbe sichtbar.</p>
            </div>
            <div id="mpi-background-overlay">
                <label for="mpi-overlay">Überlagerung des Bildes mit der Hintergrundfarbe (%)</label>
                <input id="mpi-overlay" name="appearance[overlay]" type="number" min="0" max="100" step="1" value="<?php p($a['overlay']); ?>">
                <p class="mpi-hint">0 = Bild unverändert, 100 = vollständig mit der Hintergrundfarbe überdeckt. Die Überlagerung verbessert die Lesbarkeit.</p>
            </div>
            <label for="pg-caption-mode">Bildunterschrift auf der quadratischen Albumkarte</label>
            <select id="pg-caption-mode" name="appearance[captionMode]">
            <?php foreach(['overlay'=>'Über dem Bild (transparent)','below'=>'Unter dem Bild (innerhalb des Quadrats)'] as $v=>$label): ?><option value="<?php p($v); ?>" <?php if($a['captionMode']===$v): ?>selected<?php endif; ?>><?php p($label); ?></option><?php endforeach; ?></select>
            <label for="pg-caption-opacity">Deckkraft der Beschriftungsfläche (%)</label>
            <input id="pg-caption-opacity" name="appearance[captionOpacity]" type="number" min="0" max="100" value="<?php p($a['captionOpacity']); ?>">
            <label for="mpi-filter-radius">Rundung der Filter-Auswahlfelder (Pixel)</label>
            <input id="mpi-filter-radius" type="number" name="appearance[filterRadius]" min="0" max="30" step="1" value="<?php p($a['filterRadius']); ?>">
            <div class="mpi-colors">
                <?php foreach (['backgroundColor' => 'Hintergrund / Bildüberlagerung', 'titleColor' => 'Hauptüberschrift', 'subtitleColor' => 'Untertitel', 'yearColor'=>'Jahre und Jahres-Trennlinien', 'navigationColor'=>'Navigation', 'footerColor'=>'Eigener Footer und Trennlinie', 'cardColor' => 'Kartenfläche unter dem Bild', 'albumTitleColor' => 'Albumtitel', 'locationColor' => 'Ort', 'countColor' => 'Dateianzahl', 'filterColor'=>'Filter (Jahr und Ort): Hintergrund', 'filterTextColor'=>'Filter (Jahr und Ort): Text', 'filterBorderColor'=>'Filter (Jahr und Ort): Rahmen', 'filterActiveColor'=>'Filter-Schaltfläche: Hintergrund', 'filterActiveTextColor'=>'Filter-Schaltfläche: Text', 'filterActiveBorderColor'=>'Filter-Schaltfläche: Rahmen'] as $key => $label): ?>
                <div class="mpi-color-row"><label for="mpi-hex-<?php p($key); ?>"><?php p($label); ?></label><div class="mpi-color-controls"><input id="mpi-color-<?php p($key); ?>" aria-label="<?php p($label); ?> – Farbwähler" type="color" data-hex="mpi-hex-<?php p($key); ?>" value="<?php p($a[$key]); ?>"><input id="mpi-hex-<?php p($key); ?>" class="mpi-hex" aria-label="<?php p($label); ?> – HEX-Code" type="text" name="appearance[<?php p($key); ?>]" value="<?php p($a[$key]); ?>" maxlength="7" pattern="#[0-9a-fA-F]{6}" placeholder="#8e2650" spellcheck="false" autocapitalize="off" required></div></div>
                <?php endforeach; ?>
            </div>
            <p class="mpi-hint">Eigene Farben gelten für alle Besucher und werden im Dunkelmodus nicht automatisch verändert.</p>
        </fieldset>
        <fieldset class="mpi-appearance mpi-metadata-settings"><legend>Öffentliche Bildinformationen</legend>
        <p class="mpi-hint">Nur ausgewählte Felder erscheinen im ⓘ-Panel und in der Informationsanzeige. Fehlende Werte werden ausgeblendet. Originalvideos können eigene eingebettete Metadaten enthalten; diese Auswahl entfernt keine Metadaten aus Originaldateien.</p>
        <?php foreach (\OCA\PhotoGallery\Service\Metadata::LABELS as $key => $label): ?>
        <label class="mpi-check"><input type="checkbox" name="metadataFields[<?php p($key); ?>]" value="1" <?php if ($s['metadataFields'][$key]): ?>checked<?php endif; ?>> <?php p($label); ?></label>
        <?php endforeach; ?></fieldset>
        <button type="submit" class="primary">Speichern</button>
        <p id="mpi-save-status" role="status" aria-live="polite"></p>
    </form>
    <p><a href="<?php p($_['publicUrl']); ?>" target="_blank" rel="noopener noreferrer">Öffentliche Übersicht öffnen ↗</a></p>
    <p class="mpi-url"><?php p($_['publicUrl']); ?></p>
    <p class="mpi-hint">PoC 0.8.7 · Nextcloud 33 · Änderungen an Freigaben erscheinen beim nächsten Seitenaufruf.</p>
</section>
