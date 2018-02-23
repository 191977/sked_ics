<?php

class rex_api_sked_ics_file extends rex_api_function
{
    protected $published = true;

    function execute()
    {

        $event_uid = rex_request('ics_uid','int',0);
        if ( !$event_uid )
        {
            $result = [ 'errorcode' => 1, rex_i18n::msg('rex_api_sked_ics_file_no_id') ];
            self::httpError( $result );
        }

        // Termin senden

	$vCalendar = new \Eluceo\iCal\Component\Calendar('www.example.com');
	$vEvent = new \Eluceo\iCal\Component\Event();
	$vEvent
	    ->setDtStart(new \DateTime('2012-12-24'))
	    ->setDtEnd(new \DateTime('2012-12-24'))
	    ->setNoTime(true)
	    ->setSummary('Christmas');
	$vCalendar->addComponent($vEvent);

	header('Content-Type: text/calendar; charset=utf-8');
	header('Content-Disposition: attachment; filename=invite.ics');
	$vCalendar->render();

        exit();
    }

    public static function httpError( $result )
    {
        header( 'HTTP/1.1 500 Internal Server Error' );
        header('Content-Type: application/json; charset=UTF-8');
        exit( json_encode( $result ) );
    }
}
?>