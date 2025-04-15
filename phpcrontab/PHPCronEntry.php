<?php
class PHPCronEntry{
    public $startingMin;
    public $endingMin;
    public $minute;
    public $minutes;
    public $startingHour;
    public $endingHour;
    public $hour;
    public $hours;
    public $startingDayMonth;
    public $endingDayMonth;
    public $dayMonth;
    public $dayMonths;
    public $startingMonth;
    public $endingMonth;
    public $month;
    public $months;
    public $dayWeek;
    public $dayWeeks;
    public $startingDayWeek;
    public $endingDayWeek;
    public $user;
    public $command;
    public $comment;
    public $nextRunTimeList;
    public $previousRunTimeList;
    function __construct($min = NULL,$hour = NULL,$dayM = NULL,$mnth = NULL,$dW = NULL,$usr = NULL,$cmd = NULL,$cmt = NULL){
        $this->minute = $this->parseTimeData($min, $this->startingMin, $this->endingMin,$this->minutes,"minute");
        $this->hour = $this->parseTimeData($hour, $this->startingHour, $this->endingHour,$this->hours,"hour");
        $this->dayMonth = $this->parseTimeData($dayM, $this->startingDayMonth, $this->endingDayMonth,$this->dayMonths,"daymonth");
        $this->month = $this->parseTimeData($mnth, $this->startingMonth, $this->endingMonth,$this->months,"month");
        $this->dayWeek = $this->parseTimeData($dW, $this->startingDayWeek, $this->endingDayWeek,$this->dayWeeks,"dayweek");
        $this->user = $usr;
        $this->command = $cmd;
        $this->comment = $cmt;
        $this->nextRunTimeList = [];
        $this->previousRunTimeList = [];
    }
    public function getSerializedProperties() {
        return serialize([$this->startingMin, $this->endingMin, $this->minute, $this->minutes, $this->minutes,$this->startingHour,$this->endingHour,$this->hour,$this->hours,$this->startingDayMonth,$this->endingDayMonth,$this->dayMonth,$this->dayMonths,$this->startingMonth,$this->endingMonth,$this->month,$this->months,$this->dayWeek,$this->startingDayWeek,$this->endingDayWeek,$this->user,$this->command,$this->comment,$this->nextRunTimeList,$this->previousRunTimeList]);
    }
    private function parseTimeData($value, &$start, &$end,&$multipleValues,$which) {
        if ($value === NULL) return NULL;
        $splitted = explode("-", $value);
        $possibleMultipleValues = explode(",",$value);
        if(count($possibleMultipleValues) > 1){
            foreach ($possibleMultipleValues as $multipleVal) {
                $multipleValues[] = intval($multipleVal);
            }
        }
        $divideSignSplitted = explode("/",$value);
        if(count($divideSignSplitted) == 2){
            $start = intval($divideSignSplitted[0]);
            $counter = intval($divideSignSplitted[1]);
            $startAt = intval($divideSignSplitted[0]);
            $limitToCheck = -1;
            switch($which){
                case "minute":
                    $limitToCheck = 59;
                    break;
                case "hour":
                    $limitToCheck = 23;
                    break;
                case "daymonth":
                    $limitToCheck = 30;
                    break;
                case "month":
                    $limitToCheck = 12;
                    break;
                case "dayweek":
                    $limitToCheck = 7;
                    break;
            }
            if($limitToCheck == -1){
                throw new Exception("invalid_format");
            }
            while($startAt < $limitToCheck){
                $multipleValues[] = $startAt;
                $startAt += $counter;
            }
            $end = ($startAt - $counter);
        }
        if (count($splitted) == 2 && is_numeric($splitted[0]) && is_numeric($splitted[1])) {
            $start = intval($splitted[0]);
            $end = intval($splitted[1]);
            return $value;
        }

        return ($value == ASTERISK_CHAR) ? ASTERISK_CHAR : intval($value);
    }
    function toDelimeteredLine($delimeter = ";") : string{
        return implode($delimeter,[$this->minute,$this->hour,$this->dayMonth,$this->month,$this->dayWeek,$this->user,$this->command]);
    }
    function toArray() : array{
        return [$this->minute,$this->hour,$this->dayMonth,$this->month,$this->dayWeek,$this->user,$this->command];
    }
    private function getNextInterval($which,$min,$max,$dateTimeToCheck,$sign) : int
    {
        $value = $dateTimeToCheck->format($sign);
        $valueToAdd = ($which == ASTERISK_CHAR) ? 1 : $which;
        if(isset($min) && isset($max)){
            if($min < $value){
                switch($sign){
                    // 60
                    case "M":
                        break;
                    // 24
                    case "H":
                        break;
                }
            }
            $possibleValues = range($min,$max,1);
            foreach($possibleValues as $currentValueToCheck){
                if($currentValueToCheck > $value){
                    $valueToAdd = $currentValueToCheck;
                    break;
                }
            }
        }
        return $valueToAdd;
    }
    function calculateRunTime($direction = "next",$amount = 1,$fromDateTime = new DateTime()) : DateTime{
        $dateTime = $fromDateTime;
        $nextSecond = 0;
        $nextMinute = $this->getNextInterval($this->minute,$this->startingMin,$this->endingMin,$dateTimeToCheck,"M");
        $nextHour = $this->getNextInterval($this->hour,$this->startingHour,$this->endingHour,$dateTimeToCheck,"H");
        $dateTime->setTime($nextHour,$nextMinute,$nextSecond);

    }
    function toAssociativeArray(){
        return [
            "minute" => $this->minute,
            "startingmin" => $this->startingMin,
            "endingmin" => $this->endingMin,
            "hour" => $this->hour,
            "startinghour" => $this->startingHour,
            "endinghour" => $this->endingHour,
            "daymonth" => $this->dayMonth,
            "month" => $this->month,
            "startingmonth" => $this->startingMonth,
            "endingmonth" => $this->endingMonth,
            "dayweek" => $this->dayWeek,
            "user" => $this->user,
            "command" => $this->command,
            "comment" => $this->comment
        ];
    }
}
?>