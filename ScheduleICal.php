<?php
    include_once(__DIR__ . "/Schedule.php");
    include_once(__DIR__ . "/Event.php");
    include_once(__DIR__ . "/iCalParser.php");

    class ScheduleICal extends Schedule {
        private string $icalUrl;

        /**
         * Constructor
         * @param string $icalUrl URL of the iCal file
         */
        public function __construct(string $icalUrl) {
            // Initialize JSON schedule first
            parent::__construct();

            // Load Google iCal data
            $this->icalUrl = $icalUrl;
            $icp = new ICalParser($this->icalUrl);
            $calendarEvents = $icp->getGoogleCalendarEvents();
            if ($calendarEvents !== null) {
                $this->mergeCalendarEvents($calendarEvents);
                // Set modification time to now since we just pulled new data
                $this->modifiedDate = time();
            }
        }

        /**
         * Merge calendar events that don't conflict with existing events
         * @param Event[] $calendarEvents Array of Event objects indexed by timestamp
         */
        protected function mergeCalendarEvents(array $calendarEvents): void {
            foreach ($calendarEvents as $timestamp => $event) {
                if (!isset($this->events[$timestamp])) {
                    $this->events[$timestamp] = $event;
                }
            }
        }
    }
?>
