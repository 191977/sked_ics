<?php

if (rex_addon::get('cronjob')->isAvailable() && !rex::isSafeMode()) {
    rex_cronjob_manager::registerType('rex_cronjob_sked_ics_export');
    rex_cronjob_manager::registerType('rex_cronjob_sked_ics_import');
}

