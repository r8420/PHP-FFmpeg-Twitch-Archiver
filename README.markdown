How It Works
------------------

This project is written to work on an Apache Linux server.

**Setup Steps For the Twitch Archiver**

Import twitch_dl.sql in your MySql database.

Edit the config in config.php

Setup a cronjob for refreshqueue.php every hour

**Setup Steps For the Twitch Re-encoder**

This is if you want to manually upload Twitch Broadcasts

Upload files to the source folder titled in this format: 2019 11 20 22_31_02 Title.mp4