<?php
    include_once(__DIR__ . "/Event.php");

    class ICalParser {
        private string $icalUrl;
        private string $timezoneString = 'America/New_York';
        private bool $isCached = false;
        private DateTime $cacheTime;

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
         * Get the cache time if data was fetched from cache, otherwise return false
         */
        public function getCacheStatus(): DateTime|false {
            return $this->isCached ? $this->cacheTime : false;
        }

        /**
         * Fetch iCal data with caching
         */
        protected function fetchICalData(): string|false {
            $cacheFile = sys_get_temp_dir() . '/ical_cache_' . md5($this->icalUrl) . '.txt';
            $cacheTime = 900; // 15 minutes

            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
                $this->isCached = true;
                $this->cacheTime = new DateTime('@' . filemtime($cacheFile));
                return file_get_contents($cacheFile);
            }

            $icalData = @file_get_contents($this->icalUrl);
            if ($icalData !== false) {
                $this->isCached = false;
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
                        // Check attendance status - only include ACCEPTED or TENTATIVE
                        $partstat = $currentEvent['PARTSTAT'] ?? 'ACCEPTED';
                        if ($partstat === 'DECLINED') {
                            continue; // Skip declined events
                        }

                        $eventStart = $this->parseICalDateTime($currentEvent['DTSTART']);
                        $eventEnd = null;

                        // Parse end time if available
                        if (isset($currentEvent['DTEND'])) {
                            $eventEnd = $this->parseICalDateTime($currentEvent['DTEND']);
                        }

                        if ($eventStart !== false) {
                            // Check if event is in current week
                            if ($eventStart >= $weekStart && $eventStart < $weekEnd) {
                                // If no end time, assume 1 hour duration
                                if ($eventEnd === false || $eventEnd === null) {
                                    $eventEnd = (clone $eventStart)->modify('+1 hour');
                                }

                                // Determine event type from DESCRIPTION prefix if present
                                $eventType = $this->extractTypeFromDescription($currentEvent['DESCRIPTION'] ?? '');

                                // Create Event objects for each hour slot the event occupies
                                // We need to mark an hour as busy if the event touches it at all
                                $startHour = (clone $eventStart)->setTime((int)$eventStart->format('H'), 0, 0);
                                $endHour = (clone $eventEnd)->setTime((int)$eventEnd->format('H'), 0, 0);

                                // If event ends past the hour mark (e.g., 18:15), include that hour
                                if ((int)$eventEnd->format('i') > 0 || (int)$eventEnd->format('s') > 0) {
                                    $endHour->modify('+1 hour');
                                }

                                $currentSlot = clone $startHour;

                                while ($currentSlot < $endHour) {
                                    $slotDay = (int)$currentSlot->format('w'); // 0-6
                                    $slotHour = (int)$currentSlot->format('H'); // 0-23

                                    // Create Event object for this hour
                                    $evt = new Event($slotDay, $slotHour, '', '', $eventType);
                                    $evtNum = $evt->getnum();
                                    $events[$evtNum] = $evt;

                                    // Move to next hour slot
                                    $currentSlot->modify('+1 hour');

                                    // Stop if we've gone past the current week
                                    if ($currentSlot >= $weekEnd) {
                                        break;
                                    }
                                }
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

        /**
         * Extract an event type from the DESCRIPTION field using the pattern:
         * "iCalCtype#<type>...".
         * Only returns types that exist in Event::$color; otherwise falls back to 'calendar'.
         */
        protected function extractTypeFromDescription(?string $description): string {
            if ($description === null) {
                return 'calendar';
            }
            $desc = str_replace(['\\n', '\\N'], "\n", $description);
            if (preg_match('/iCalCtype#([A-Za-z0-9_-]+)/i', $desc, $m)) {
                $candidate = strtolower($m[1]);
                if (isset(Event::$color[$candidate])) {
                    return $candidate;
                }
            }
            return 'calendar';
        }
    }
?>
