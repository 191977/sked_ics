class rex_cronjob_sked_ics_export extends rex_cronjob
{
    public function execute()
    {
/*
        $streams = rex_yfeed_stream::getAllActivated();
        $errors = [];
        $countAdded = 0;
        $countUpdated = 0;
        $countNotUpdatedChangedByUser = 0;
        foreach ($streams as $stream) {
            try {
                $stream->fetch();
            } catch (\Exception $e) {
                $errors[] = $stream->getTitle();
            } catch (\Throwable $e) {
                $errors[] = $stream->getTitle();
            }
            $countAdded += $stream->getAddedCount();
            $countUpdated += $stream->getUpdateCount();
            $countNotUpdatedChangedByUser += $stream->getChangedByUserCount();
        }
*/
        $this->setMessage(sprintf(
            '%d errors%s, %d items added, %d items updated, %d items not updated because changed by user',
            count($errors),
            $errors ? ' ('.implode(', ', $errors).')' : '',
            $countAdded,
            $countUpdated,
            $countNotUpdatedChangedByUser
        )); 
        return empty($errors);
    }

    public function getTypeName()
    {
        return rex_addon::get('sked_ics')->i18n('sked_ics_cronjob_export');
    }
}
