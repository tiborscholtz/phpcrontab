<?php
require_once "./phpcrontab/autoload.php";
try
{
    $tabs = new PHPCronTab("./crontab_examples/complex");
    echo $tabs->printTable(15);
}
catch(Exception $ex){
    echo $ex->getMessage();
}
?>