<?php
use ICal\ICal;

/**
 * @package redaxo\sked_ics
 */
class rex_cronjob_sked_ics_import extends rex_cronjob
{

    
    public function execute()
    {
        $debug_dump = []; // Debug-Array, das bei enstprechender Option ausgegeben wird

        // Das ICS-Objekt initialisieren und Datei abrufen
        try {
            $ical = new ICal('ICal.ics', array(
                'defaultSpan'                 => 2,     // Default value
                'defaultTimeZone'             => 'UTC',
                'defaultWeekStart'            => 'MO',  // Default value
                'disableCharacterReplacement' => false, // Default value
                'skipRecurrence'              => false, // Default value
                'useTimeZoneWithRRules'       => false, // Default value
            ));
            $ical->initUrl($this->getParam('url'));
            $debug_dump['$ical'] = $ical;
            $vEvents = $ical->cal['VEVENT'];
        } catch (\Exception $e) {
            $this->setMessage('ICS-Datei Konnte nicht importiert werden.');
            return false;
        }

        // Wenn die Option "default" nicht gesetzt ist, werden zusätzliche Sked-Kategorien angelegt:
        if ($this->getParam('category_sync') !== 'default') {
            // ...andernfalls werden Kategorien aus der ICS-Datei in Sked angelegt
            $sql = rex_sql::factory()->setDebug(0);

            // Herausfinden, welche Kategorien in Sked vorkommen
            $existing_categories = $sql->getArray('SELECT id, name_'.$this->getParam('clang_id').' AS name FROM `rex_sked_categories`');
            $debug_dump['$existing_categories'] = $existing_categories;    
            $existing_categories_names = [];
            foreach($existing_categories as $existing_category) {
                $existing_categories_names[] = $existing_category['name'];
            }
    
            // Herausfinden, welche Kategorien in der ICS-Datei vorkommen
            $category_names_per_event = [];
            if (count($vEvents)) {
                foreach($vEvents as $vEvent) {
                    $category_names_per_event = array_merge($category_names_per_event, explode(",", $vEvent['CATEGORIES']));
                }
            }
            $category_names_per_event = array_unique($category_names_per_event);

            // Herausfinden, welche Kategorie-Namen noch nicht vorhanden sind
            $debug_dump['$add_categories'] = $add_categories = array_diff($category_names_per_event, $existing_categories_names);
    
            // Neue Kategorien hinzufügen
            foreach($add_categories as $category_name) {
            $category_query = '
            INSERT INTO rex_sked_categories
                (name_'.$this->getParam('clang_id').', createdate, updatedate, createuser, updateuser)
            VALUES
                (:name, :createdate, :updatedate, :createuser, :updateuser)';

                $values = [];
                $values[':name'] = $category_name;
                $values[':createdate'] = date("Y-m-d H:i:s", strtotime($vEvent['DTSTAMP'])); // TODO: Ist es wirklich immer DTSTAMP? Ist die Uhrzeit korrekt?
                $values[':updatedate'] = date("Y-m-d H:i:s", strtotime($vEvent['DTSTAMP']));
                $values[':createuser'] = "Cronjob";
                $values[':updateuser'] = "Cronjob";

            $debug_dump['insert_category'][] = rex_sql::factory()->setDebug(0)->setQuery($category_query, $values);
            }
        } 

        // Wenn Option "Remove" gesetzt ist, werden überschüssige Kategorien gelöscht
        if ($this->getParam('category_sync') === 'remove') {
            $debug_dump['$remove_categories'] = $remove_categories = array_diff($existing_categories_names, $category_names_per_event);

            foreach($remove_categories as $remove_category) {
                $category_query = 'DELETE FROM rex_sked_categories WHERE name_'.$this->getParam('clang_id').' = :name';    
                $debug_dump['remove_category'][] = rex_sql::factory()->setDebug(0)->setQuery($category_query, [":name" => $remove_category]);
            }
        }

        // Neue hinzugefügte Kategorien berücksichtigen
        $existing_categories = rex_sql::factory()->getArray('SELECT id, name_'.$this->getParam('clang_id').' AS name FROM `rex_sked_categories`');
        $debug_dump['$existing_categories new'] = $existing_categories;

        // aktuelle Locations herausfinden
        $existing_locations = rex_sql::factory()->getArray('SELECT id, name_'.$this->getParam('clang_id').' AS name FROM `rex_sked_venues`');
        $debug_dump['$existing_locations'] = $existing_locations;
        // TODO: Gleiche Überprüfung der Locations wie mit Kategorien + Parsen der Adresse

        // Termine einfügen und aktualisieren
        if (count($vEvents)) {
            foreach($vEvents as $vEvent) {

                $category_ids = [];
                if ($this->getParam('category_sync') === 'default') {
                    $category_ids[] = $this->getParam('category_id');
                } else {   
                    foreach($existing_categories as $existing_category) {
                        if($id = array_search($existing_category['name'], explode(",", $vEvent['CATEGORIES'])))
                        $category_ids[] = $id;
                    }
                }

                $location_id = 0;
                if ($this->getParam('location_id')) {
                    $location_id = $this->getParam('location_id');
                } else {
                    // TODO: Location ausfindig machen und ggf. geocodieren, neue Locations eintragen.
                }


                $category_ids = array_unique($category_ids);
                $query = '
                    INSERT INTO rex_sked_entries
                        (start_date, end_date, is_fulltime, start_time, end_time, category, venue, status, name_'.$this->getParam('clang_id').', text_'.$this->getParam('clang_id').', createdate, updatedate, createuser, updateuser, uid, raw, source_url)
                    VALUES
                        (:start_date, :end_date, :is_fulltime, :start_time, :end_time, :category, :venue, :status, :name, :text, :createdate, :updatedate, :createuser, :updateuser, :uid, :raw, :source_url)
                    ON DUPLICATE KEY UPDATE
                        start_date  = :start_date,
                        end_date    = :end_date,
                        is_fulltime = :is_fulltime, 
                        start_time  = :start_time,
                        end_time    = :end_time, 
                        category    = :category,
                        venue       = :venue,
                        status      = :status,
                        name_'.$this->getParam('clang_id').' = :name,
                        text_'.$this->getParam('clang_id').' = :text,
                        createdate  = :createdate,
                        updatedate  = :updatedate,
                        createuser  = :createuser,
                        updateuser  = :updateuser,
                        uid         = :uid,
                        raw         = :raw,
                        source_url  = :source_url
                ';

                $values = [];
                $values[':start_date']  = date("Y-m-d", strtotime($vEvent['DTSTART']));
                $values[':end_date']    = date("Y-m-d", strtotime($vEvent['DTEND']));
                $values[':is_fulltime'] = (int)!(bool)(strtotime($vEvent['DTEND']) - strtotime($vEvent['DTSTART'] ."+ 1 DAY")); // Dirty Hack - ganztägige Ereignisse sind von 00:00 bis 00:00 des Folgetages
                $values[':start_time']  = date("H:i:s", strtotime($vEvent['DTSTART']));
                $values[':end_time']    = date("H:i:s", strtotime($vEvent['DTEND']));
                $values[':category']    = implode(",",$category_ids);
                $values[':venue']       = $location_id;
                $values[':status']      = 1; // TODO: Status zuordnen. CONFIRMED? Abgesagt?
                $values[':name']        = $vEvent['SUMMARY'];
                $values[':text']        = " ".$vEvent['DESCRIPTION']; // Dirty Hack - darf nicht NULL sein
                $values[':createdate']  = date("Y-m-d H:i:s", strtotime($vEvent['DTSTAMP'])); // TODO: Ist es wirklich immer DTSTAMP? Ist die Uhrzeit korrekt?
                $values[':updatedate']  = date("Y-m-d H:i:s", strtotime($vEvent['DTSTAMP']));
                $values[':createuser']  = "Cronjob";
                $values[':updateuser']  = "Cronjob";
                $values[':uid']         = $vEvent['UID'];
                $values[':raw']         = json_encode($vEvent);
                $values[':source_url']   = $this->getParam('url');
                // TODO: wiederkehrende Termine auslesen und verwenden. Siehe Adminer für Felder
                // type	repeat, repeat_year, repeat_week, repeat_month, end_repeat_date 

                try {
                    $debug_dump['inserts'][$vEvent['UID']] = rex_sql::factory()->setDebug(0)->setQuery($query, $values);
                    $success_counter++;

                } catch (rex_sql_exception $e) {
                    $debug_dump['inserts_and_updates'][$vEvent['UID']] = $e->getMessage();
                    $error_counter++;
                };


            }
        }
        // Debug-Ausgabe
        if($this->getParamFields('debug') === 1) { dump($debug_dump); }

        $this->setMessage((int)$success_counter.' Datensätze importiert / aktualisiert, '.(int)$error_counter.' Fehler.'); // TODO: Meldung übersetzen und Parameter als Platzhalter einfügen

        // Richtigen Status zurückgeben und Meldung im Backend einfärben 
        if($error_counter) {
            return false;
        } else {
            return true;
        }

    }

    public function getTypeName()
    {
        return rex_i18n::msg('sked_ics_import_cronjob_name');
    }

    public function getParamFields()
    {
        // ICS-Datei als Demo vorschlagen 
        $default_url = 'https://www.schulferien.org/deutschland/ical/download/?lid=81&j='.date("Y").'&t=2';
        
        // Auswahl für REDAXO-Sprachen zusammenzustellen
        $clangs = rex_clang::getAll();
        $clang_ids = [];
        foreach($clangs as $clang) {
            $clang_ids[$clang->getValue('id')] = $clang->getValue('name');
        }

        // Benutzerdefinierte Standard-Kategorie auswählen
        $sql_categories = rex_sql::factory()->setDebug(0)->getArray('SELECT id, name_'.rex_clang::getCurrentId().' AS name FROM `rex_sked_categories`');

        $sked_category_ids = [];
        $sked_category_ids[0] = rex_i18n::msg('sked_ics_import_cronjob_choose');

        foreach($sql_categories as $sql_category) {
            $sked_category_ids[$sql_category['id']] = $sql_category['name'];
        }
        
        // Benutzerdefinierte Standard-Location auswählen
        $sql_locations = rex_sql::factory()->setDebug(0)->getArray('SELECT id, name_'.rex_clang::getCurrentId().' AS name FROM `rex_sked_venues`');
        $sked_location_ids = [];
        $sked_location_ids[0] = rex_i18n::msg('sked_ics_import_cronjob_choose_none');

        foreach($sql_locations as $sql_location) {
            $sked_location_ids[$sql_location['id']] = $sql_location['name'];
        }

        // Eingabefelder des Cronjobs definieren
        $fields = [
            [
                'label' => rex_i18n::msg('sked_ics_import_cronjob_url_label'),
                'name' => 'url',
                'type' => 'text',
                'default' => $default_url,
                'notice' => rex_i18n::msg('sked_ics_import_cronjob_url_notice'),
            ],
            [
                'label' => rex_i18n::msg('sked_ics_import_cronjob_media_label'),
                'name' => 'media',
                'type' => 'media',
                'types' => "ics", // TODO: Einschränkung funktioniert nicht
                'notice' => rex_i18n::msg('sked_ics_import_cronjob_media_notice'),
            ],
            [
                'name' => 'category_sync',
                'label' => 'Kategorie-Optionen',
                'type' => 'select',
                'default' => 'keep',
                'options' => ['remove' => rex_i18n::msg('sked_ics_import_cronjob_category_remove'),
                              'default' => rex_i18n::msg('sked_ics_import_cronjob_category_default_id'),
                              'keep' => rex_i18n::msg('sked_ics_import_cronjob_category_keep')],
                'notice' => rex_i18n::msg('sked_ics_import_cronjob_category_sync')
            ],
            [
                'name' => 'category_id',
                'type' => 'select',
                'default' => $sql_categories[0]['id'],
                'options' => $sked_category_ids,
                'notice' => rex_i18n::msg('sked_ics_import_cronjob_default_category_sync_id_notice')
            ],
            [
                'name' => 'location_id',
                'type' => 'select',
                'label' => 'Standard-Location',
                'default' => $sql_locations[0]['id'],
                'options' => $sked_location_ids,
                'notice' => rex_i18n::msg('sked_ics_import_cronjob_default_location_sync_id_notice')
            ],
            [
                'name' => 'clang_id',
                'type' => 'select',
                'label' => 'Sprache',
                'default' => rex_clang::getCurrentId(),
                'options' => $clang_ids,
                'notice' => rex_i18n::msg('sked_ics_import_cronjob_clang_id_notice')
            ],
            [
                'name' => 'geocoding',
                'type' => 'checkbox',
                'default' => 0,
                'options' => [1 => rex_i18n::msg('sked_ics_import_cronjob_geocoding')], // TODO: Geocodierung umsetzen
                'notice' => rex_i18n::msg('sked_ics_import_cronjob_geocoding_notice')
            ],
            [
                'name' => 'debug',
                'type' => 'checkbox',
                'default' => 0,
                'options' => [1 => rex_i18n::msg('sked_ics_import_cronjob_debug')],
                'notice' => rex_i18n::msg('sked_ics_import_cronjob_debug_notice')
            ]
        ];

        return $fields;
    }
}