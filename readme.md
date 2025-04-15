# phpcrontab

**phpcrontab** is a PHP library designed to parse, analyze, and manage cron jobs, primarly from *nix like system's `/etc/crontab` and user-specific crontab files. It provides an easy-to-use API for filtering, scheduling, and handling cron expressions programmatically.

## Features

- [x] Parse and analyze system-wide and user-specific cron jobs
- [x] Search cron jobs by command, user, or schedule
- [x] Validate and convert cron expressions into human-readable formats
- [ ] Retrieve upcoming and past cron executions
- [ ] Detect overlapping and redundant cron jobs

What this library does **NOT** do:

- Modify existing crontab files, directly on the disk

![Written in PHP](https://img.shields.io/badge/written%20in-PHP-blue)
![Version: 1.0](https://img.shields.io/badge/Version:-1.0-green)

## Installation

No composer required: just include the autoload.php:


```php
require_once "./phpcrontab/autoload.php";
$tabs = new PHPCronTab("./crontab_examples/easy");
// print basic crontab data
echo ($tabs->printTable());
```

```console
---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
minute               hour               daymonth               month               dayweek               user               command
---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
17-19                *                  *                      1-2                 *                     root               cd / && run-parts --report /etc/cron.hourly
25                   6-8                *                      2-3                 *                     root               test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.daily )
47                   6                  *                      *                   7                     www-data           test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.weekly )
52                   6                  1                      *                   *                     data               test -x /usr/sbin/anacron || ( cd / && run-parts --report /etc/cron.monthly )
```


The `crontab_examples` folder contains several different crontab files, for experimenting. The crontab files were generated, using `ChatGPT`.

## Example usage

**Get crons that run between the hours 2 and 6**
```php
$cronsBetween = $tabs->getCronsBetweenHours(2,6)
echo $cronsBetween->printTable();
```

**Get crons which is run by `root`, or `user1` user**
```php
$cronsByUser = $tabs->getCronsBy(["root","user1"])
echo $cronsByUser->printTable();
```

**Get crons where the command contains certain text**
```php
$cronsByCommand = $tabs->getCommandContains([".sh","etc"])
echo $cronsByCommand->printTable();
```

**You can chain these commands, too: get the crons that run between the hours 6 and 12, and run by `root` user**
```php
// You can omit the array structure if you only have one username.
$cronsAtHoursByRoot = $tabs->getCronsBetweenHours(6,12)->getCronsBy("root")
echo $cronsAtHoursByRoot->printTable();
```

## Contributing

We welcome contributions! Feel free to submit issues, feature requests, or pull requests.

## License

This project is licensed under the MIT License.
