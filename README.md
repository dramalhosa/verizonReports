# verizonReports
The purpose of this is to pull the usage history from Verizon's API, put them into a csv file, then upload it to a FTP server.

The script starts with a cron job running report.php.  From there it pulls the devices provisioned in Verizon, stores it into MySQL, then requests the usage for them.

When Verizon gets the request, it sends the information to a callback URL that was providered.  In this case it calls DeviceUsage.php

DeviceUsage.php pulls the device list and matches it to what it got from Verizon.  It creates a csv with a line item for each device.  When it's done, it creates a FTP connection and pushes the file through
