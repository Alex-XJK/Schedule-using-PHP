<?php
    include_once("Schedule.php");
    include_once("Event.php");

    class ScheduleRemote extends Schedule {
        public function __construct($regSchedule, $tmpSchedule) {
            //Step 0: Set variables
            $this->weekvalue = 24*7;

            //Step 1: Read json files
            $json_string = file_get_contents($regSchedule);
            $decode_string = json_decode($json_string, true);

            $data = $decode_string["schedule"];
            $blockOut = $decode_string["blockOut"];

            $json_stringT = file_get_contents($tmpSchedule);
            $decode_stringT = json_decode($json_stringT, true);
            $dataT = $decode_stringT["schedule"];
            if(count($dataT)>0){
                $data = array_merge($data, $dataT);
            }

            //Step 2: Readin Notice
            if(isset($decode_stringT["notice"]) && $decode_stringT["notice"] != null) {
                $this->notice = $decode_stringT["notice"];
            }

            //Step 3: Readin timezone and set "new timezone"="default timezone"
            $this->defautTimezone = $decode_string["timezone"];
            $this->newTimezone = $this->defautTimezone;

            //Step 4: Load Block Out Time into an array-of-Events
            if ($blockOut != null) {
                $this->loadBlockOutTime($blockOut);
            }

            //Step 5: Load into an array-of-Events
            foreach($data as $d){
                $evt = new Event($d["date"], $d["time"], $d["code"], $d["name"], $d["type"], $d["message"]);
                $n = $evt->getnum();
                $this->events[$n] = $evt;
            }
        }
    }
?>
