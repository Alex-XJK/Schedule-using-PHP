<?php
    include_once(__DIR__ . "/Schedule.php");
    include_once(__DIR__ . "/Event.php");
    include_once(__DIR__ . "/iCalParser.php");

    class ScheduleICal extends Schedule {
        private string $icalUrl;
        private ICalParser $icp;

        /**
         * Constructor
         * @param string $icalUrl URL of the iCal file
         */
        public function __construct(string $icalUrl) {
            // Initialize JSON schedule first
            parent::__construct();

            // Load Google iCal data
            $this->icalUrl = $icalUrl;
            $this->icp = new ICalParser($this->icalUrl);
            $calendarEvents = $this->icp->getGoogleCalendarEvents();
            if ($calendarEvents !== null) {
                $this->mergeCalendarEvents($calendarEvents);
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

        /**
        * displayModification - Override to append cache status to modification time
        */
        protected function displayModification() {
            $modiString = "DB: ";
            if($this->modifiedDate == -1) {
                $modiString .= "<span style='color: gray;'>Unrecognized</span>";
            }
            else {
                $now = new DateTime();
                $modifiedDateTime = (new DateTime())->setTimestamp($this->modifiedDate);

                // Calculate the difference in weeks
                $interval = $now->diff($modifiedDateTime);
                $weeksAgo = (int)floor($interval->days / 7);

                // Determine color and message based on weeks
                $modiString .= match(true) {
                    $weeksAgo === 0 => "<span style='color: green;'>This week</span>",
                    $weeksAgo === 1 => "<span style='color: gold;'>1 week</span>",
                    $weeksAgo === 2 => "<span style='color: orange;'>2 weeks</span>",
                    $weeksAgo >= 3 => "<span style='color: red;'>{$weeksAgo} weeks</span>",
                    default => "<span style='color: gray;'>Unknown</span>"
                };
            }
            $modiString .= ", iCal: ";
            $cacheStatus = $this->icp->getCacheStatus();
            if ($cacheStatus !== false) {
                $minutesAgo = intdiv((new DateTime())->getTimestamp() - $cacheStatus->getTimestamp(), 60);
                if ($minutesAgo < 5) {
                    $modiString .= "<span style='color: gold;'>{$minutesAgo} min</span>";
                }
                else {
                    $modiString .= "<span style='color: red;'>{$minutesAgo} min</span>";
                }   
            }
            else {
                $modiString .= "<span style='color: green;'>Fetched</span>";
            }
            return $modiString;
        }
    }
?>
