<?php
class NotImplementedException extends BadMethodCallException{}
class PHPCronTab{
    private $weekDayMap = [
        1 => "monday",
        2 => "tuesday",
        3 => "wednesday",
        4 => "thursday",
        5 => "friday",
        6 => "saturday",
        7 => "sunday"
    ];
    public $cronTasks;
    function __construct($fileName = NULL){
        $this->cronTasks = [];
        $this->nextRunTimeAmount = 0;
        $this->previousRunTimeAmount = 0;
        if($fileName != NULL){
            $file = file_get_contents($fileName);
            $fileExploded = explode("\n",$file);
            $tabsToLoad = [];
            foreach($fileExploded as $fE){
                $line = trim($fE);
                if(empty($line)){
                    continue;
                }
                if( substr($line, 0, 1) === '#'){
                }
                else{
                    if(preg_match("/^(\d|\*)/",$line)){
                        $min = preg_split("/\s+/",$line,2);
                        $hour = preg_split("/\s+/",$min[1],2);
                        $dayM = preg_split("/\s+/",$hour[1],2);
                        $month = preg_split("/\s+/",$dayM[1],2);
                        $dayOfWeek = preg_split("/\s+/",$month[1],2);
                        $user = preg_split("/\s+/",$dayOfWeek[1],2);
                        $command = preg_split("/\s+/",$user[1],1);
                        $entry = new PHPCronEntry($min[0],$hour[0],$dayM[0],$month[0],$dayOfWeek[0],$user[0],$command[0]);
                        $tabsToLoad[] = $entry;
                    }
                }
            }
            $this->cronTasks = $tabsToLoad;
        }
    }
    public function toCSV($csvName = NULL,$separator = ";") : void{
        if($csvName == NULL){
            $csvName = "phpcron_export".date('Y_m_d').".csv";
        }
        $columns = ["minute","hour","dayMonth","month","dayWeek","user","command"];
        $fp = fopen($csvName, 'w');
        fputcsv($fp,$columns,$separator);
        foreach ($this->cronTasks as $row) {
            $rowArr = $row->toArray();
            fputcsv($fp, $rowArr,$separator);
        }
        fclose($fp);
    }
    public function addCronEntry($entry) : void{
        $this->cronTasks[] = $entry;
    }
    public function setCronEntries($entries) : void{
        $this->cronTasks = $entries;
    }
    private function prettyPrint($data,$keys,$defaultRightPadding = 3): string{
        $sentData = [$keys];
        foreach($data as $task){
            $currentTask = $task->toAssociativeArray();
            $currentToAdd = [];
            foreach($keys as $key){
                $currentToAdd[] = isset($currentTask[$key]) ? $currentTask[$key] : '';
            }
            $sentData[] = $currentToAdd;
        }

        $dataArray = [];
        for ($i=0; $i < count($sentData[0]); $i++) { 
            $sentData[0][$i] = str_pad($sentData[0][$i] ?? '',strlen($sentData[0][$i]) + $defaultRightPadding,' ',STR_PAD_RIGHT);
        }
        $mostData = count($sentData[0]);
        $dataArray[] = $sentData[0];
        $keysToDrop = array_shift($sentData);
        foreach ($sentData as $oneData) {
            $countCurrentData = count($oneData);
            if($mostData < $countCurrentData){
                $mostData = $countCurrentData;
            }
            $dataArray[] = $oneData;
        }
        $longest = [];
        for ($i=0; $i < $mostData; $i++) {
            $currentLongest = 0;
            foreach ($dataArray as $oneData) {
                if(isset($oneData[$i])){
                    $currentLength = strlen($oneData[$i]);
                    if($currentLength > $currentLongest){
                        $currentLongest = $currentLength;
                    }
                }
            }
            $longest[] = $currentLongest;
        }
        $toTableOutputArray = [];
        foreach($dataArray as $currentKey => $currentData){
            $oneLine = "";
            foreach($longest as $currentLengthKey => $currentLengthValue){
                $oneLine .= str_pad($currentData[$currentLengthKey] ?? '',$currentLengthValue,' ',STR_PAD_RIGHT);
            }
            $toTableOutputArray[] = $oneLine;
        }
        $lengthOfLines = [];
        foreach($toTableOutputArray as $oneLineValue){
            $lengthOfLines[] = strlen($oneLineValue);
        }
        $longestText = -1;
        $longestTextIndex = -1;
        foreach($lengthOfLines as $lengthKey => $lengthValue){
            if($lengthValue > $longestText){
                $longestText = $lengthValue;
                $longestTextIndex = $lengthKey;
            }
        }
        $header = str_pad('',$longestText,'-',STR_PAD_RIGHT);
        array_splice($toTableOutputArray, 1, 0, $header);
        array_splice($toTableOutputArray, 0, 0, $header);
        $toTableOutputReturn = implode("\n",$toTableOutputArray)."\n";
        return $toTableOutputReturn;
    }
    /**
    * Returns a formatted string only with the basic properties of the parsed cron entries.
    * @param int $defaultPadding Provides a right-side padding for the table columns.
    * Defaults to 3.
    * @return string A table-like formatted string with the basic properties of the cron entries.
    */
    public function printTable($defaultPadding = 3) : string{
        $filteredData = $this->cronTasks;
        return $this->prettyPrint($filteredData,["minute","hour","daymonth","month","dayweek","user","command"],$defaultPadding);
    }
    public function printExtendedTable($defaultPadding = 3) : string{
        $filteredData = $this->cronTasks;
        return $this->prettyPrint($filteredData,["minute","startingmin","endingmin","hour","startinghour","endinghour","daymonth","month","startingmonth","endingmonth","dayweek","user","command"],$defaultPadding);
    }
    /**
    * Filters and retrieves cron jobs by a specified username.
    * @param string|array|null $username The username or an array of usernames to filter the cron jobs.
    * If NULL or empty, all cron jobs are returned.
    * @return PHPCronTab A new PHPCronTab instance containing only the cron jobs that match the specified username(s).
    */
    public function getCronsBy($username = NULL) : PHPCronTab{
        $newCronObj = new PHPCronTab();
        $newCronObj->setCronEntries($this->cronTasks);
        if($username == NULL){
            return $newCronObj;
        }
        if(empty($username)){
            return $newCronObj;
        }
        $usernames = gettype($username) == "string" ? [$username] : $username;
        $crons =  array_filter($this->cronTasks, function($entry) use ($usernames) { return in_array($entry->user,$usernames); } );
        $newCronObj = new PHPCronTab();
        $newCronObj->setCronEntries($crons);
        return $newCronObj;
    }
    // TODO
    public function getUpcomingCrons($n = 5) : PHPCronTab{
        //$newCronObj = new PHPCronTab();
        //$newCronObj->setCronEntries($this->cronTasks);
        throw new NotImplementedException();
    }

    public function sortByCommandLength($direction = "ascending") : PHPCronTab{
        $newCronObj = new PHPCronTab();
        $currentCronTasks = $this->cronTasks;
        usort($currentCronTasks,function($first,$second) use ($direction){
            return ($direction == "ascending" ? strlen($first->command) > strlen($second->command) : strlen($second->command) > strlen($first->command));
        });
        $newCronObj->setCronEntries($currentCronTasks);
        return $newCronObj;
    }


    private function getMostAmountByProperies($value,$min,$max,$values,$propertyName) : int{
        if($value == ASTERISK_CHAR){
            switch($propertyName){
                case "minute":
                    return 60;
                case "hour":
                    return 24;
                case "dayofweek":
                    return 7;
                case "month":
                    return 12;
                case "dayofmonth":
                    return 31;
            }
        }
    }

    // TODO
    public function sortByRunAmountPerProperty($property = NULL, $direction = NULL) : PHPCronTab{
        throw new NotImplementedException();
        $newCronObj = new PHPCronTab();
        $currentCronTasks = $this->cronTasks;
        return $newCronObj;
        
    }
    // TODO
    public function getRecentCrons($n = 5) : PHPCronTab{
        throw new NotImplementedException();
    }

    // TODO
    public function getMostFrequentCrons($n = 5) : PHPCronTab{
        throw new NotImplementedException();
    }

    // TODO
    public function getLeastFrequentCrons($n = 5) : PHPCronTab{
        throw new NotImplementedException();
    }

    /**
    * Filters the cron tasks and returns a new PHPCronTab instance 
    * containing only those tasks whose command contains the specified text(s).
    *
    * @param string|array $possibleTexts A single string or an array of strings to search for in the command field of the cron tasks.
    * @param bool $caseInsensitive Whether the search should be case-insensitive. Defaults to true.
    *
    * @return PHPCronTab A new PHPCronTab object containing only the matching cron tasks.
    */
    public function getCommandContains($possibleTexts, $caseInsensitive = true): PHPCronTab {
        $newCronObj = new PHPCronTab();
        $tasks = [];
        $texts = is_string($possibleTexts) ? [$possibleTexts] : $possibleTexts;
        foreach ($this->cronTasks as $task) {
            foreach ($texts as $text) {
                if (($caseInsensitive ? stripos($task->command, $text) : strpos($task->command, $text)) !== false) {
                    $tasks[] = $task;
                }
            }
        }
        
        $newCronObj->setCronEntries($tasks);
        return $newCronObj;
    }
    public function getCronsBetweenMinutes($from = NULL, $to = NULL) : PHPCronTab{
        return $this->getCronsBetween($from,$to,"minute","startingMin","endingMin");
    }


    /**
     * Filters the cron tasks and returns a new PHPCronTab instance 
     * containing only those tasks which starts between the specified hour interval.
     *
     * @param int|null $from A number, which acts as the starting hour filter
     * @param int|null $to A number, which acts as the ending hour filter
     *
     * @return PHPCronTab A new PHPCronTab object containing only the matching cron tasks.
     */
    public function getCronsBetweenHours($from = NULL,$to = NULL) : PHPCronTab{
        return $this->getCronsBetween($from,$to,"hour","startingHour","endingHour");
    }

    /**
     * Filters the cron tasks and returns a new PHPCronTab instance 
     * containing only those tasks which starts between the specified month interval.
     *
     * @param int|null $from A number, which acts as the starting month filter
     * @param int|null $to A number, which acts as the ending month filter
     *
     * @return PHPCronTab A new PHPCronTab object containing only the matching cron tasks.
     */
    public function getCronsBetweenMonths($from = NULL,$to = NULL) : PHPCronTab{
        return $this->getCronsBetween($from,$to,"month","startingMonth","endingMonth");
    }

    /**
     * Filters the cron tasks and returns a new PHPCronTab instance 
     * containing only those tasks which starts between the specified day of month interval.
     *
     * @param int|null $from A number, which acts as the starting day of month filter
     * @param int|null $to A number, which acts as the ending day of month filter
     *
     * @return PHPCronTab A new PHPCronTab object containing only the matching cron tasks.
    */
    public function getCronsBetweenDayOfMonth($from = NULL,$to = NULL) : PHPCronTab{
        return $this->getCronsBetween($from,$to,"dayMonth","startingDayMonth","endingDayMonth");
    }

    /**
     * Filters the cron tasks and returns a new PHPCronTab instance 
     * containing only those tasks which starts between the specified day of week interval.
     *
     * @param int|null $from A number, which acts as the starting day of week filter
     * @param int|null $to A number, which acts as the ending day of week filter
     *
     * @return PHPCronTab A new PHPCronTab object containing only the matching cron tasks.
    */
    public function getCronsBetweenDayOfWeek($from = NULL,$to = NULL) : PHPCronTab{
        return $this->getCronsBetween($from,$to,"dayWeek","startingDayWeek","endingDayWeek");
    }
    public function getCronCount() : int{
        return count($this->cronTasks);
    }
    public function getUniqueCronCommandsCount() : int{
        return count(array_unique(array_map(function($obj) {
            return $obj->getSerializedProperties();
        }, $this->cronTasks)));
    }
    private function getCronsBetween($from = NULL,$to = NULL,$property,$startingProperty,$endingProperty) : PHPCronTab{
        $fromNumeric = NULL;
        $toNumeric = NULL;
        $newCronObj = new PHPCronTab();
        $newCronObj->setCronEntries($this->cronTasks);
        if(!isset($from) && !isset($to)){
            return $newCronObj;
        }
        if(isset($from) && !is_numeric($from)){
            return $newCronObj;
        }
        else{
            $fromNumeric = $from;
        }
        if(isset($to) && !is_numeric($to)){
            return $newCronObj;
        }
        else{
            $toNumeric = $to;
        }
        $filteredtasks = [];
        foreach($newCronObj->cronTasks as $task){
            $toAdd = false;
            if(!is_numeric($task->{$property})){
                if($task->{$property} == ASTERISK_CHAR){
                    $toAdd = true;
                }
                if(!$toAdd){
                    $allRuleCount = 0;
                    if(isset($fromNumeric)){
                        $allRuleCount += 1;
                    }
                    if(isset($toNumeric)){
                        $allRuleCount += 1;
                    }
                    $currentCheckRule = 0;
                    if(isset($fromNumeric) && isset($task->{$startingProperty})){
                        if($task->{$startingProperty} >= $fromNumeric){
                            $currentCheckRule += 1;
                        }
                    }
                    if(isset($toNumeric) && isset($task->{$endingProperty})){
                        if($task->{$endingProperty} <= $toNumeric){
                            $currentCheckRule += 1;
                        }
                    }
                    $toAdd = ($allRuleCount == $currentCheckRule);
                }

            }
            else{
                $allRuleCount = 0;
                if(isset($fromNumeric)){
                    $allRuleCount += 1;
                }
                if(isset($toNumeric)){
                    $allRuleCount += 1;
                }
                $currentCheckRule = 0;
                if(isset($fromNumeric)){
                    if($task->{$property} >= $fromNumeric){
                        $currentCheckRule += 1;
                    }
                }
                if(isset($toNumeric)){
                    if($task->{$property} <= $toNumeric){
                        $currentCheckRule += 1;
                    }
                }
                $toAdd = ($allRuleCount == $currentCheckRule);
            }
            if($toAdd){
                $filteredtasks[] = $task;
            }
        }
        $newCronObj->setCronEntries($filteredtasks);
        return $newCronObj;
    }
}
?>