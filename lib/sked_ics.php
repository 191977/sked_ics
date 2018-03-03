<?php
/** 
*
* sked_ics
* Verarbeitet Termineinträge aus Sked, um sie im ICS- oder JSON-LD-Format auszugeben.
*
* @author: @alexplus_de Alexander Walther
* @version: 0.1
*/

class sked_ics
{
	private static $sked_events; // Array an Sked-Termin-Objekten

    protected function __construct($sked_events = false)
    {
        $this->sked_events = $sked_events;
    }

    public function factory($sked_events)
    {
        return new self($sked_events);
    }
	
    public function getSkedEventsAsIcs()
    {
        $vCalendar = new \Eluceo\iCal\Component\Calendar(rex::getServer()); 

        foreach($this->sked_events as $sked_event) {
            $vEvent = self::getSkedEventAsIcs($sked_event);
            $vCalendar->addComponent($vEvent);
        }

        return $vCalendar->render();

    }

    public static function getSkedEventAsIcs($sked_event)
    {
            $vEvent = new \Eluceo\iCal\Component\Event();

            $is_fulltime = (int)!(bool)($sked_event['date']->entry_end_date->getTimestamp() - strtotime($sked_event['date']->entry_start_date->getTimestamp() ."+ 1 DAY")); // Dirty Hack - ganztÃ¤gige Ereignisse sind von 00:00 bis 00:00 des Folgetages

            $vEvent
            ->setDtStart($sked_event['date']->entry_start_date)
            ->setDtEnd($sked_event['date']->entry_end_date)
            ->setNoTime($is_fulltime) // Wenn Ganztag
            ->setUseTimezone(true)
            ->setCategories(explode(",",$sked['entry']->category_name))
            ->setSummary($sked_event['entry']->entry_text);

            // TODO: Hier gibt es noch viele Eigenschaften, die synchronisiert werden können

            return($vEvent);
    }

    public function getSkedEventsAsJsonld($with_markup = true)
    {
        $jsonlds = [];
        foreach($this->sked_events as $sked_event) {
            $jsonlds[] = json_decode(self::getSkedEventAsJsonld($sked_event, false));
        }
        if($with_markup) {
            return("<script type='application/ld+json'>".json_encode($jsonlds)."</script>");
        } else {
            return(json_encode($jsonlds));
        }
    }

    public function getSkedEventAsJsonld($sked_event, $with_markup = true)
    {

        // Siehe https://jsonld.com/event/
        // Werte testen: https://search.google.com/structured-data/testing-tool
    
        $jsonld = [];
        $jsonld['@context'] = "http://www.schema.org";
        $jsonld['@type'] = "Event";
        $jsonld["name"] = $sked_event['entry']->entry_name; // TODO: Clang berücksichtigen
        $jsonld['description'] = $sked_event['entry']->entry_text; // TODO: Clang berücksichtigen
        $jsonld["startDate"] = $sked_event['date']->entry_start_date->format(DateTime::ATOM);
        $jsonld["endDate"] = $sked_event['date']->entry_end_date->format(DateTime::ATOM); // TODO: Format "10/05/2015 12:00PM"
        $jsonld['description'] = $sked_event['entry']->entry_text; // TODO: Clang berücksichtigen
        $jsonld['image'] = $sked_event['entry']->entries_image; // TODO: Clang berücksichtigen
        $jsonld['description'] = $sked_event['entry']->entry_text; // TODO: Clang berücksichtigen
    
        // Venue / Location
        $jsonld['location']['@type'] = "Place";
        $jsonld['location']['name'] = $sked_event['entry']->venue_name;
        // TODO: $jsonld['location']['sameAs'] = "http://www.example.com";
    
        // Adresse
        $jsonld['location']['address']['@type'] = "PostalAddress";
        $jsonld['location']['address']['streetAddress'] = $sked_event['entry']->venues_street. " " . $sked_event['entry']->venues_housenumber; // TODO: street und housenumber in Sked zusammenführen? 
        $jsonld['location']['address']['addressLocality'] = $sked_event['entry']->venues_city;
        // TODO: $jsonld['location']['address']['addressRegion'] = "";
        $jsonld['location']['address']['postalCode'] = $sked_event['entry']->venues_zip;
        $jsonld['location']['address']['addressCountry'] = $sked_event['entry']->venues_country;
    
        // Angebote
        /* 
        $jsonld['offers']['@type'] = "Offer";
        $jsonld['offers']['description'] = "an offer description";
        $jsonld['offers']['url'] = "http://www.example.com";
        $jsonld['offers']['price'] = "$9.99";
        */

        // TODO: Hier gibt es noch viele Eigenschaften, die synchronisiert werden können
        // TODO: EP hinzufügen, um einen Termin weitere Eigenschaften zuzuordnen, bspw. den @type zu ändern. Vlt. auch als selbst definiertes REDAXO-Fragment?
        // TODO: weitere Eigenschaften prüfen: http://schema.org/Event
        if($with_markup) {
            return("<script type='application/ld+json'>".json_encode($jsonld)."</script>");
        } else {
            return(json_encode($jsonld));
        }
    }

}