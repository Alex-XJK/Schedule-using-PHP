<?php
    include_once(__DIR__ . "/Event.php");

    class Schedule {
        protected string $sysBase;
        protected int $weekValue;

        protected int $defaultTimezone;
        protected int $newTimezone;
        protected int $weekOffset = 0;

        /** @var Event[] */
        protected array $events = array();
        protected ?string $notice = null;
        protected int $modifiedDate = -1;

        public function __construct() {
            // Step 0: Set variables
            $this->weekValue = 24 * 7;
            $this->sysBase = ".";

            // Step 1: Read json files
            $regSchedule = "$this->sysBase/regularSchedule.json";
            $json_string = file_get_contents($regSchedule);
            $decode_string = json_decode($json_string, true);

            $data = $decode_string["schedule"];
            $blockOut = $decode_string["blockOut"];

            if (file_exists("$this->sysBase/temporarySchedule.json")) {
                $tmpSchedule = "$this->sysBase/temporarySchedule.json";
                $json_stringT = file_get_contents($tmpSchedule);
                $decode_stringT = json_decode($json_stringT, true);
                $dataT = $decode_stringT["schedule"];
                if (count($dataT) > 0) {
                    $data = array_merge($data, $dataT);
                }
                // Read Notice
                if (isset($decode_stringT["notice"]) && $decode_stringT["notice"] != null) {
                    $this->notice = $decode_stringT["notice"];
                }
                // Get Modification Date
                $this->modifiedDate = filemtime($tmpSchedule);
            }

            // Step 2: Read timezone and set "new timezone" = "default timezone"
            $this->defaultTimezone = $decode_string["timezone"];
            $this->newTimezone = $this->defaultTimezone;

            // Step 3: Load Block Out Time into an array-of-Events
            if ($blockOut != null) {
                $this->loadBlockOutTime($blockOut);
            }

            // Step 4: Load into an array-of-Events
            foreach ($data as $d) {
                $evt = new Event($d["date"], $d["time"], $d["title-1"], $d["title-2"], $d["type"], $d["message"]);
                $n = $evt->getnum();
                $this->events[$n] = $evt;
            }
        }

        public function setTimezone(int $newZone): void {
            if ($newZone < -12 || $newZone > 12) {
                echo "Wrong Timezone, may cause error!";
            }
            $this->newTimezone = $newZone;
        }

        public function setWeekOffset(int $weekOffset): void {
            $this->weekOffset = $weekOffset;
        }

        public function setCityCode(string $city): bool {
            $timemap = array(
                "BJS"   => 8,
                "HKT"   => 8,
                "UTC"   => 0,
                "LON"   => 0,
                "NYC"   => -5,
                "CHI"   => -6,
                "LAX"   => -8
            );
            if (array_key_exists($city, $timemap)) {
                $this->setTimezone($timemap[$city]);
                return true;
            } else {
                return false;
            }
        }

        protected function loadBlockOutTime(array $blockOut): void {
            $datMap = array(
                "Sunday"    => 0,
                "Monday"    => 1,
                "Tuesday"   => 2,
                "Wednesday" => 3,
                "Thursday"  => 4,
                "Friday"    => 5,
                "Saturday"  => 6
            );
            foreach ($blockOut as $day => $times) {
                $dayNumber = $datMap[$day];
                foreach ($times as $time) {
                    $evt = new Event($dayNumber, $time, "", "", "other");
                    $n = $evt->getnum();
                    $this->events[$n] = $evt;
                }
            }
        }

        protected function displayModification() {
            if($this->modifiedDate == -1) {
                return "<span style='color: gray;'>Unrecognized timestamp...</span>";
            }
            else {
                $Fri = strtotime('friday');
                $laFri = $Fri - 86400 * 7;
                $lalaFri = $laFri - 86400 * 7;
                if($this->modifiedDate < $lalaFri) {
                    return "<span style='color: red;'>Updated two weeks ago</span>";
                }
                elseif ($this->modifiedDate < $laFri) {
                    return "<span style='color: gold;'>Updated last week</span>";
                }
                else {
                    return "<span style='color: green;'>Updated this week</span>";
                }
            }
        }

        public function drawTable() {

            //Mouse Hover
            $this->dhtmltooltips();

            //Compute starting element
            $delta = $this->defaultTimezone - $this->newTimezone;

            //Get elements row-by-row
            echo "<table id='schedule' border=1>";
            echo "<thead><tr id='weekday' style='background: #00f7ff;'>";
            echo "<th></th> <th>Sunday</th> <th>Monday</th> <th>Tuesday</th> <th>Wednesday</th> <th>Thursday</th> <th>Friday</th> <th>Saturday</th>";
            echo "</tr></thead>";
            for ($hour = 0; $hour < 24; $hour++) {
                echo "<tr id='hour".$hour."'>";
                //Print left header
                echo "<td class='time' style='background: #00f7ff;'>".sprintf('%02d', $hour)."</td>";
                for ($day = 0; $day < 7; $day++) {
                    //The sequencial id of table cells 0~23 24~47 ...
                    $idx = ($day * 24) + $hour;

                    //The array index of events
                    $inx = $idx + $delta;

                    //Handle over-the-week issue
                    if($inx < 0) {
                        $inx = $inx + $this->weekValue;
                    }
                    elseif ($inx >= $this->weekValue) {
                        $inx = $inx - $this->weekValue;
                    }

                    //Look up in the array and print
                    if (array_key_exists($inx, $this->events)) {
                        echo $this->events[$inx]->display($idx);
                    }
                    else {
                        echo "<td id='c$idx' class='free' style='background: ".Event::$color["free"]."'></td>";
                    }
                }
                echo "</tr>";
            }
            date_default_timezone_set("UTC");
            echo "<tfoot>";
            if($this->notice != null) {
                echo "<tr><td colspan='8'>$this->notice</td></tr>";
            }
            $timezonesec = time()+($this->newTimezone*60*60)+($this->weekOffset*7*24*60*60);
            $timezoneSymbol = ($this->newTimezone > 0)? "+".$this->newTimezone : $this->newTimezone;
            if ($this->weekOffset === 0) {
                $timestr = date("Y M(m) d l, H:i:s", $timezonesec);
                $footerLabel = "Local time in UTC $timezoneSymbol : $timestr";
            }
            else {
                $weekStart = $timezonesec - ((int)date("w", $timezonesec) * 24 * 60 * 60);
                $weekEnd = $weekStart + (6 * 24 * 60 * 60);
                $footerLabel = "Week of " . date("M. j", $weekStart) . " - " . date("M. j", $weekEnd);
            }
            $modification = $this->displayModification();
            echo "<tr>";
            echo "<td colspan='6'>$footerLabel</td>";
            echo "<td colspan='2'>$modification</td>";
            echo "</tr>";
            echo "</tfoot>";
            echo "</table>";
        }

        public function highlight() {
            date_default_timezone_set("UTC");
            $timezonesec = time()+($this->newTimezone*60*60);
            $targetW = date("w", $timezonesec);
            $targetH = date("H", $timezonesec);
            $targetID = ($targetW*24)+$targetH;
            if ($this->weekOffset !== 0) {
                return;
            }
            echo "<script>";
            echo "function changeColor() {var elem=document.getElementById('c$targetID'); elem.style.border ='5px dashed red';}";
            echo "window.onload = changeColor";
            echo "</script>";
        }

        public static function defaultStyle($width="100%") {
            echo "<meta charset='utf-8'>";
            echo "<link rel='shortcut icon' href='../images/sunsetclock.ico'>";
            echo "<title>ALEX Schedule Core</title>";
            echo "<style type='text/css'>";
            echo "#schedule {width: $width; word-break:break-word; word-wrap:break-word;}";
            echo "#schedule th {font-weight: bold;}";
            echo "#schedule td {width: 12.5%; text-align: center; border: 1px double;}";
            echo "#schedule td.time {width: 5%; font-weight: bold;}";
            echo "#dhtmltooltip {position: absolute; width: 150px; border: 2px solid black; padding: 2px; background-color: lightyellow; visibility: hidden; z-index: 100; filter: progid:DXImageTransform.Microsoft.Shadow(color=gray,direction=135);}";
            echo "</style>";
        }

        protected function dhtmltooltips() {
echo <<<DHYMLTOOLTIP
<div id="dhtmltooltip"></div>

<script type="text/javascript">

/***********************************************
* Cool DHTML tooltip script- (c) Dynamic Drive DHTML code library (www.dynamicdrive.com)
* Please keep this notice intact
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/

var offsetxpoint=-60 //Customize x offset of tooltip
var offsetypoint=20 //Customize y offset of tooltip
var ie=document.all
var ns6=document.getElementById && !document.all
var enabletip=false
if (ie||ns6)
var tipobj=document.all? document.all["dhtmltooltip"] : document.getElementById? document.getElementById("dhtmltooltip") : ""
document.body.appendChild(tipobj)

function ietruebody(){
return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
}

function ddrivetip(thetext, thecolor, thewidth){
if (ns6||ie){
if (typeof thewidth!="undefined") tipobj.style.width=thewidth+"px"
if (typeof thecolor!="undefined" && thecolor!="") tipobj.style.backgroundColor=thecolor
tipobj.innerHTML=thetext
enabletip=true
return false
}
}

function positiontip(e){
if (enabletip){
var curX=(ns6)?e.pageX : event.clientX+ietruebody().scrollLeft;
var curY=(ns6)?e.pageY : event.clientY+ietruebody().scrollTop;
//Find out how close the mouse is to the corner of the window
var rightedge=ie&&!window.opera? ietruebody().clientWidth-event.clientX-offsetxpoint : window.innerWidth-e.clientX-offsetxpoint-20
var bottomedge=ie&&!window.opera? ietruebody().clientHeight-event.clientY-offsetypoint : window.innerHeight-e.clientY-offsetypoint-20

var leftedge=(offsetxpoint<0)? offsetxpoint*(-1) : -1000

//if the horizontal distance isn't enough to accomodate the width of the context menu
if (rightedge<tipobj.offsetWidth)
//move the horizontal position of the menu to the left by it's width
tipobj.style.left=ie? ietruebody().scrollLeft+event.clientX-tipobj.offsetWidth+"px" : window.pageXOffset+e.clientX-tipobj.offsetWidth+"px"
else if (curX<leftedge)
tipobj.style.left="5px"
else
//position the horizontal position of the menu where the mouse is positioned
tipobj.style.left=curX+offsetxpoint+"px"

//same concept with the vertical position
if (bottomedge<tipobj.offsetHeight)
tipobj.style.top=ie? ietruebody().scrollTop+event.clientY-tipobj.offsetHeight-offsetypoint+"px" : window.pageYOffset+e.clientY-tipobj.offsetHeight-offsetypoint+"px"
else
tipobj.style.top=curY+offsetypoint+"px"
tipobj.style.visibility="visible"
}
}

function hideddrivetip(){
if (ns6||ie){
enabletip=false
tipobj.style.visibility="hidden"
tipobj.style.left="-1000px"
tipobj.style.backgroundColor=''
tipobj.style.width=''
}
}

document.onmousemove=positiontip

</script>
DHYMLTOOLTIP;
        }
    }
?>
