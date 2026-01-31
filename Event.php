<?php
    class Event {
    private int $timestamp;
    private string $title1;
    private string $title2;
    private string $type;
    private string $msgLine1 = '';
    private string $msgLine2 = '';

        public static array $color = array(
            "free"          =>  "#F6F5EE;",
            "class"         =>  "#8DB4E2;",
            "holiday"       =>  "#D8E4BC;",
            "activities"    =>  "#FABF8F;",
            "lecture"       =>  "#B1A0C7;",
            "discussion"    =>  "#B7DEE8;",
            "exam"          =>  "#DA9694;",
            "work"          =>  "#E5BBF8;",
            "other"         =>  "#C2C2C2;"
        );

    /**
     * @param int $D  Day of week (0-6)
     * @param int $T  Hour of day (0-23)
     * @param string $C  Code
     * @param string $N  Name
     * @param string $P  Type
     * @param array|null $M  Message array, contains EN/CH
     */
        public function __construct(int $D, int $T, string $title1, string $title2, string $P, ?array $M = null) {
            $this->timestamp = ($D * 24) + $T;
            $this->title1 = $title1;
            $this->title2 = $title2;
            $this->type = $P;
            if ($M !== null) {
                $this->msgLine1 = $M["line-1"] ?? '';
                $this->msgLine2 = $M["line-2"] ?? '';
            }
        }

        public function display(int $id): string {
            $title1 = htmlspecialchars($this->title1, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $title2 = htmlspecialchars($this->title2, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $msgLine1 = htmlspecialchars($this->msgLine1, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $msgLine2 = htmlspecialchars($this->msgLine2, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $type = htmlspecialchars($this->type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $color = Event::$color[$this->type] ?? '#F6F5EE;';
            if (!empty($this->msgLine1) || !empty($this->msgLine2)) {
                $mouseHover = "ddrivetip('" . $msgLine1 . "<br>" . $msgLine2 . "','yellow', 300)";
                $innerHTML = "<td id=\"c$id\" class=\"$type\" style=\"background: $color\" onMouseover=\"$mouseHover\" onMouseout=\"hideddrivetip()\">$title1 <br> $title2</td>";
            } else {
                $innerHTML = "<td id=\"c$id\" class=\"$type\" style=\"background: $color\">$title1 <br> $title2</td>";
            }
            return $innerHTML;
        }

        public function getnum(): int {
            return $this->timestamp;
        }
    }
?>
