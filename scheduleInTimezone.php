<?php
include_once(__DIR__ . "/ScheduleICal.php");

$schedule = new ScheduleICal('https://xxx/basic.ics');

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