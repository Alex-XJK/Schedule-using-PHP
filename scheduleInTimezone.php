<?php
include_once(__DIR__ . "/ScheduleICal.php");

// Week offset setting: 0=this week, 1=next week, -1=last week
$weekOffset = 0;
if (isset($_GET["week"])) {
    $weekOffset = (int)$_GET["week"];
    if ($weekOffset < -520 || $weekOffset > 520) {
        echo "Wrong week offset [$weekOffset]!";
        exit(1);
    }
}

$schedule = new ScheduleICal('https://xxx/basic.ics', $weekOffset);

// Dynamic Timezone settings
if (isset($_GET["zone"])) {
    $zone = (int)$_GET["zone"];
    if ($zone < -12 || $zone > 12) {
        echo "Wrong timezone [$zone]!";
        exit(1);
    }
    $schedule->setTimezone($zone);
} elseif (isset($_GET["city"])) {
    $city = $_GET["city"];
    if (!$schedule->setCityCode($city)) {
        echo "Wrong city code [$city]!";
        exit(1);
    }
}

// Style setting
if (isset($_GET["width"])) {
    Schedule::defaultStyle($_GET["width"]);
} else {
    Schedule::defaultStyle();
}

$schedule->highlight();
$schedule->drawTable();
?>