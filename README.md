# QuickFriends

* ✔ Friend requests and `/f block`
* ✔ List friends and check if their online
* ✔ Customize command messages
* ✔ Configure worlds players can `/f join` to

Allow your players to add each other as friends! This
plugin is drag-and-drop and customize-as-you-go.

# Data Storage

You may store friend data in `plugin_data` (using Sqlite)
or configure a MySQL database for this plugin to sync
friends to.

# Commands

All commands are subcommands of `/f` and `/friend`.

**`/f <subcommand>`**
* **`add`**, **`remove`**, **`list`** - manage your friends
* **`block`**, **`unblock`**, **`listblocked`** - manage people you block
  from sending you friend requests
* **`join`** - teleport to a friend if the friend's world has `/f join` enabled for it

## Easy Command Running

Running `/f` by itself will spawn a form for ease-of-use.

# Config

`config.yml` is the main configuration for the plugin. If an error is detected the server will crash and a helpful error message will be logged.

## Example Crash Message
`config.yml`
```
language:
  default-language: not-a-language
```
`Server Output`
```
[Server thread/CRITICAL]: [QuickFriends] [Config] Failed to load config, details below...
1 error(s) in config.yml
  1 error(s) in language
    The selected default language (not-a-language-name) is not supported. Supported languages are: en_US
```
