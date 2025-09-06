<?php
    class Event {
        private int $timestamp;
        private string $code;
        private string $name;
        private string $type;
        private string $msgEN = '';
        private string $msgCH = '';

        public static array $color = array(
            "free"          =>  "#F6F5EE;",
            "class"         =>  "#8DB4E2;",
            "holiday"       =>  "#D8E4BC;",
            "activities"    =>  "#FABF8F;",
            "lecture"       =>  "#B1A0C7;",
            "discussion"    =>  "#B7DEE8;",
            "exam"          =>  "#DA9694;",
            "other"         =>  "#C4BD97;",
            "subother"      =>  "#c2c2c2;",
            "placement"     =>  "#e5bbf8;"
        );

    /**
     * @param int $D  Day of week (0-6)
     * @param int $T  Hour of day (0-23)
     * @param string $C  Code
     * @param string $N  Name
     * @param string $P  Type
     * @param array|null $M  Message array, contains EN/CH
     */
        public function __construct(int $D, int $T, string $C, string $N, string $P, ?array $M = null) {
            $this->timestamp = ($D * 24) + $T;
            $this->code = $C;
            $this->name = $N;
            $this->type = $P;
            if ($M !== null) {
                $this->msgEN = $M["EN"] ?? '';
                $this->msgCH = $M["CH"] ?? '';
            }
        }

        public function display(int $id): string {
            $code = htmlspecialchars($this->code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $name = htmlspecialchars($this->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $msgEN = htmlspecialchars($this->msgEN, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $msgCH = htmlspecialchars($this->msgCH, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $type = htmlspecialchars($this->type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $color = Event::$color[$this->type] ?? '#F6F5EE;';
            if (!empty($this->msgEN) || !empty($this->msgCH)) {
                $mouseHover = "ddrivetip('" . $msgEN . "<br>" . $msgCH . "','yellow', 300)";
                $innerHTML = "<td id=\"c$id\" class=\"$type\" style=\"background: $color\" onMouseover=\"$mouseHover\" onMouseout=\"hideddrivetip()\">$code <br> $name</td>";
            } else {
                $innerHTML = "<td id=\"c$id\" class=\"$type\" style=\"background: $color\">$code <br> $name</td>";
            }
            return $innerHTML;
        }

        public function getnum(): int {
            return $this->timestamp;
        }
    }
?>
