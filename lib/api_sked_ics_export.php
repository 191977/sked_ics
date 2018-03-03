<?php

class rex_api_sked_ics_file extends rex_api_function
{

    function execute()
    {
        $event_uid = rex_request('uid','int',0); // Todo: Sked-Event mittels UID abrufen, da ggf. eindeutig
        $event_uid = rex_request('id','int',0);
        if ( !$event_uid )
        {
            $result = [ 'errorcode' => 1, rex_i18n::msg('rex_api_sked_ics_file_no_id') ];
            self::httpError( $result );
        } else {
            $sked_events = \Sked\Handler\SkedHandler::getEntry($event_id));

            header('Content-Type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename=invite.ics'); // Todo: Dateinamen generieren

            sked_ics::factory($sked_events)::getSkedEventsAsIcs();
            exit();
        }

    }

    public static function httpError( $result )
    {
        header( 'HTTP/1.1 500 Internal Server Error' );
        header('Content-Type: application/json; charset=UTF-8');
        exit( json_encode( $result ) );
    }
}
?>