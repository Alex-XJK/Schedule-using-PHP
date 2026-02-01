<?php
    include_once(__DIR__ . "/Event.php");

    class ICalParser {
        private string $icalUrl;
        private string $timezoneString = 'America/New_York';

        /**
         * Constructor
         * @param string $icalUrl URL of the iCal file
         */
        public function __construct(string $icalUrl) {
            $this->icalUrl = $icalUrl;
        }

        /**
         * Fetch and merge Google Calendar events for the current week
         */
        public function getGoogleCalendarEvents(): ?array {
            $icalData = $this->fetchICalData();
            if ($icalData === false) {
                return null; // Failed to fetch, skip silently
            }
            
            $calendarEvents = $this->parseICalForCurrentWeek($icalData);
            
            return $calendarEvents;
        }
        
        /**
         * Fetch iCal data with caching
         */
        protected function fetchICalData(): string|false {
            $cacheFile = sys_get_temp_dir() . '/ical_cache_' . md5($this->icalUrl) . '.txt';
            $cacheTime = 900; // 15 minutes
            
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
                return file_get_contents($cacheFile);
            }
            
            $icalData = @file_get_contents($this->icalUrl);
            if ($icalData !== false) {
                file_put_contents($cacheFile, $icalData);
            }
            
            return $icalData;
        }
        
        /**
         * Parse iCal data and extract events for current week only
         * @return Event[] Array of Event objects indexed by timestamp
         */
        protected function parseICalForCurrentWeek(string $icalData): array {
            $events = [];
            $lines = explode("\n", $icalData);
            
            // Get current week boundaries
            $now = new DateTime();
            $now->setTimezone(new DateTimeZone($this->timezoneString));
            
            $dayOfWeek = (int)$now->format('w'); // 0 (Sunday) to 6 (Saturday)
            $weekStart = (clone $now)->modify("-{$dayOfWeek} days")->setTime(0, 0, 0);
            $weekEnd = (clone $weekStart)->modify('+7 days');
            
            $inEvent = false;
            $currentEvent = [];
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                if ($line === 'BEGIN:VEVENT') {
                    $inEvent = true;
                    $currentEvent = [];
                } elseif ($line === 'END:VEVENT') {
                    $inEvent = false;
                    
                    // Process the event
                    if (isset($currentEvent['DTSTART'])) {
                        $eventStart = $this->parseICalDateTime($currentEvent['DTSTART']);
                        
                        if ($eventStart !== false) {
                            // Check if event is in current week
                            if ($eventStart >= $weekStart && $eventStart < $weekEnd) {
                                $dayOfWeek = (int)$eventStart->format('w'); // 0-6
                                $hour = (int)$eventStart->format('H'); // 0-23
                                
                                // Create Event object
                                // $summary = $currentEvent['SUMMARY'] ?? 'Busy';
                                $evt = new Event($dayOfWeek, $hour, 'Busy', '', 'calendar');
                                $evtNum = $evt->getnum();
                                $events[$evtNum] = $evt;
                            }
                        }
                    }
                } elseif ($inEvent) {
                    // Parse the line
                    if (strpos($line, ':') !== false) {
                        $parts = explode(':', $line, 2);
                        $key = explode(';', $parts[0])[0]; // Remove parameters like ;TZID=
                        $currentEvent[$key] = $parts[1];
                    }
                }
            }
            
            return $events;
        }
        
        /**
         * Parse iCal datetime string to DateTime object
         */
        protected function parseICalDateTime(string $dateTimeStr): DateTime|false {
            // Remove TZID parameter if present
            $dateTimeStr = preg_replace('/^TZID=.*?:/', '', $dateTimeStr);
            
            // Handle UTC format: YYYYMMDDTHHMMSSZ
            if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z$/', $dateTimeStr, $matches)) {
                $dt = DateTime::createFromFormat('Ymd H:i:s', 
                    "{$matches[1]}{$matches[2]}{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}",
                    new DateTimeZone('UTC')
                );
                if ($dt !== false) {
                    $dt->setTimezone(new DateTimeZone($this->timezoneString));
                }
                // echo "[Converted to local time: " . $dt->format('Y-m-d H:i:s') . "]\n";
                return $dt;
            }
            
            // Handle local format: YYYYMMDDTHHMMSS
            if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})$/', $dateTimeStr, $matches)) {
                return DateTime::createFromFormat('Ymd H:i:s', 
                    "{$matches[1]}{$matches[2]}{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}",
                    new DateTimeZone($this->timezoneString)
                );
            }
            
            // Handle date only: YYYYMMDD
            if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $dateTimeStr, $matches)) {
                return DateTime::createFromFormat('Ymd H:i:s', 
                    "{$matches[1]}{$matches[2]}{$matches[3]} 00:00:00",
                    new DateTimeZone($this->timezoneString)
                );
            }
            
            return false;
        }
    }
?>