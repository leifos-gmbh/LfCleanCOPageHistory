# Page History Cleanup Cron Job Plugin

This cron job plugin will remove older entries from the page editor history and remove unreferenced files and media objects accordingly.

Before you use it in a productive environment, please make some test runs on a test installation before.

## Supported ILIAS Versions

ILIAS 5.4.x., 6.x, 7.x

Note: The cron job will be part of ILIAS 8.

## Install

```
mkdir -p Customizing/global/plugins/Services/Cron/CronHook
cd Customizing/global/plugins/Services/Cron/CronHook
git clone https://github.com/leifos-gmbh/LfCleanCOPageHistory.git
```

## Log

The cron job will write in detail to the log what has been deleted, if you set the logger of component "COPage" to "debug".