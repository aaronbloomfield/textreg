# Text Number Registration System

This is a simple PHP script meant to allow the registration of mobile numbers for sending out mass text messages.  The names, numbers and providers are stored in a SQLite database file.

All the major mobile service providers have a email-to-text service. If you send an e-mail to a specific email address, it is delivered to the phone as a text message. For example, if one has AT&T, and if the cell phone number is (123) 456-7890, then an email to 1234567890@txt.att.net will be delivered to that mobile phone as a text message.  Currently, this script can send messages to: AT&T, Sprint, T-Mobile, and Verizon.

For admins, this script provides a list of the email addresses that will enable sending a (ideally short) emails/texts to a group of recipients.

This code is released under the [MIT license](LICENSE)


## Installation

This requires a LAMP server that runs PHP.

1. Clone this git repo: `git clone https://github.com/aaronbloomfield/textreg`
2. Ensure that the PHP sqlite3 extension is installed.  On an 14.04 Ubuntu machine, this is the `php5-sqlite` package; on 16.04, that's the `php7.0-sqlite3` package (followed by `phpenmod sqlite3` and restarting apache2).  If that extension is not installed, then the script will indicate so when loaded.
3. Create a logo file as `logo.png`, which (if present) will be displayed at the top of each page.
3. Edit the `config.php` file.  You must modify the first section of values.  The second section of values might need modification.  The last section likely will not need modification.  That file explains what each configuration option means.
4. Ensure that the database file (numbers.db is the default name) and the log file (textreg.log) are writable by the web server user.  If you are familiar with file permissions, you may want to ensure that others cannot read those files.
5. Load up the textreg.php script, as it should work at this point.
6. If the password that was set in the `config.php` file is 'foobar', and the script is at http://www.example.com/textreg.php, then the admin page is at http://www.example.com/textreg.php?admin&password=foobar

## SQLite DB

In case you are interested, the sqlite3 database creation command is as follows.  The numbers.db that comes in this repo already has this table created.
```
create table numbers(id integer primary key, name tinytext, number tinytext, provider tinytext, valid boolean default 1, thedate datetime);
```

In an effort to keep the SQLite DB relatively small, the log file is a separate text file; no logging occurs to the DB file.
