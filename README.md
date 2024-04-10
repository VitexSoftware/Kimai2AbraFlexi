Kimai to AbraFlexi
==================

![Logo](abraflexi-kimai-importer.svg?raw=true)


Process all time records within given scope into invoice with items grouped by Clients + Also the CSV Timesheet is attached to Invoice.


Commandline tool i used to issue AbraFlexi invoice using Kimai API



Configuration
-------------


Example environment or .env file contents:

```
KIMAI_HOST=your.server.com
KIMAI_USER=user.login
KIMAI_TOKEN=xxxxxx
KIMAI_SCOPE=last_month

ABRAFLEXI_URL="https://demo.abraflexi.eu:5434"
ABRAFLEXI_LOGIN="winstrom"
ABRAFLEXI_PASSWORD="winstrom"
ABRAFLEXI_COMPANY="demo"
ABRAFLEXI_CUSTOMER="DEMO"
ABRAFLEXI_TYP_FAKTURY="FAKTURA"
ABRAFLEXI_CENIK="WORK"
ABRAFLEXI_SEND=True
ABRAFLEXI_CC=some@recipient.com                 - into "poznam" field (abraflexi-mailer can handle it)
INVOICE_DOWNLOAD=true                           - into "reports" directory

REPORTS_DIR="/tmp/"
```

Scope can be: **last_month** or  **previous_month**, **two_months_ago**, **last_two_months**, **current_month***
Or month name:     January    February    March    April    May    June    July    August    September    October    November    December

Running
-------

run `src/kimai2abraflexi.php`

or

`/usr/bin/kimai2abraflexi` - generate invoices
`/usr/bin/kimai2xls`       - generate reports only

when installed from debian package

Installation
------------

```shell
sudo apt install lsb-release wget apt-transport-https bzip2

wget -qO- https://repo.vitexsoftware.com/keyring.gpg | sudo tee /etc/apt/trusted.gpg.d/vitexsoftware.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/vitexsoftware.gpg]  https://repo.vitexsoftware.com  $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list

sudo apt update
sudo apt install abraflexi-kimai-importer
```

See also:

 * https://github.com/VitexSoftware/Toggl-to-AbraFlexi
 * https://github.com/VitexSoftware/Redmine2AbraFlexi


MultiFlexi
----------

Kimai2AbraFlexi is ready for run as [MultiFlexi](https://multiflexi.eu) application.
See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/apps.php)

