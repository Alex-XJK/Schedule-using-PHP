# Schedule-by-PHP
A dynamic multi-timezone personal schedule display and management system written in native PHP.

## Disclaimer
This is the webpage code that Alex used to explore the functionality of PHP. The source code was completed in the fall of 2021 and has been run on Alex's personal homepage since then.  

However, for ease of understanding and server security reasons, we have made minor changes to the file paths and configuration files associated with it before posting it online.  

We understand that a lot of cut-price timetable management software is available on the market, and some of them are written in PHP too, and we don't deny their excellent performance. However, all the code in this project was written by Alex after he learned it by himself, in order to try to have a deeper understanding of PHP from scratch.   

There may be many hidden bugs or security concerns, so if you are focusing on the security and performance of this webpage, please check the code yourself and make the appropriate changes. For general improvements, feel free to submit a Pull Request!

## Project structure
- `Event.php`, `Schedule.php`, and `ScheduleRemote.php` are the PHP component code. They follow the object-oriented program development approach and are encapsulated in an individual class.
- `regularSchedule.json` and `temporarySchedule.json` are the example codes in JSON format. They are used to provide data support for the timetable, and you can learn the structure and make personalized updates.
- `scheduleInTimezone.php` is the formal entry point to display the timetable. It calls the relevant classes and performs the parameter adjustment.
- `scheduleManagement.php` is the back-end interface for editing the timetable.

## Schedule Display
`scheduleInTimezone.php` can accept some optional parameters in the request URL through the GET method:
- `city=ABC` sets the timezone to some pre-defined values. In the `Schedule.php#69`, they are set as follows:
```php
$timemap = array (
    "BJS"   =>  8,
    "HKT"   =>  8,
    "UTC"   =>  0,
    "LON"   =>  0,
    "NYC"   =>  -5,
    "CHI"   =>  -6,
    "LAX"   =>  -8
);
```
- `zone=N` set the local timezone from `-12` to `12`. Timezone values and city codes are not supposed to appear simultaneously, yet when they do appear simultaneously, the time zone values are more deterministic.
- `week=N` shifts the displayed week by `N` weeks. Use `week=0` for the current week, `week=1` for next week, and `week=-1` for last week. Local JSON events are treated as recurring weekly events, while iCal events are loaded from the selected week.
- `width=X` where 'X' follows the CSS style. It sets the width of the schedule table.

Example:
The following request URL will display your schedule in Hong Kong Time with a table width of 85%.
`https://xxx/scheduleInTimezone.php?city=HKT&width=85%`
However, the following code will display your schedule in UTC time, since the `city` parameter is ignored.
`https://xxx/scheduleInTimezone.php?city=HKT&zone=0`

## Schedule Management
`scheduleManagement.php` can receive commands through the POST method.  
The `function` parameter will perform those target behaviors when it equals the following value.
| function code | additional parameters needed | function |
| ---- | ----- | ---- |
| change | date, time, [type], [code], [name], [msge], [msgc] | To add/change/override event on the current schedule. The `type` parameter must be one of the existing types in the `Schedule.php` code. For the remaining parameters, check the provided JSON file for reference. |
| setnotice | notice | To set/change the notice line. |
| clsnotice | (null) | Clear the current notice line. |
| roll | (null) | Rollback to a previously recorded state. |

## Example
[Alex Schedule Core](https://homepage.cs.cityu.edu.hk/jiakaixu2/schedule/zone=auto)  
Please note that this page is here only to show the intended outcome of the code, does not reflect Alex's actual personal schedule, and should not be used for any other purpose.



