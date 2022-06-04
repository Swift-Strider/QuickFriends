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

|Names|Description|
|:----|----------:|
|/f <player>, /f add <player>|Adds a friend|
|/f remove <player>|Removes a friend|
|/f list|Lists friends|
|/f join|Teleport to a player, if the world is joinable|
|/block, /block add|Blocks a player|
|/block remove|Unblocks a player|
|/block list|Lists blocked players|

/friend is an alias of /f

# Permissions

* quickfriends.command.friend - /f
  * quickfriends.command.friend.add
  * quickfriends.command.friend.remove
  * quickfriends.command.friend.list
  * quickfriends.command.friend.join
* quickfriends.command.block - /block
  * quickfriends.command.block.add
  * quickfriends.command.block.remove
  * quickfriends.command.block.list

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
