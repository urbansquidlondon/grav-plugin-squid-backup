# Squid Backup Plugin

The **Squid Backup** Plugin is for [Grav CMS](http://github.com/getgrav/grav).

## Installation

Installing the Squid Backup plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install squid-backup

This will install the Squid Backup plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/squid-backup`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `squid-backup`. You can find these files on [GitHub](https://github.com/urbansquidlondon/grav-plugin-squid-backup) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/squid-backup
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Admin](https://github.com/getgrav/grav-plugin-admin) to operate.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/squid-backup/squid-backup.yaml` to `user/config/plugins/squid-backup.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
max_backups: 3
notification_timer: 14
allow_auto_backups: false
auto_backup: 30
notifications: true
```

## Usage

The plugin configuration should be set via admin or yaml file, then it will self automate without any user input based on your values.

## To Do

- [ ] Downloadable list of backups in admin dashboard

