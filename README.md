# InfoAPI

Extensible templating for PocketMine plugins.

In a nutshell, InfoAPI provides a simple API to register placeholders between plugins.
But it is more powerful than just that:

- Object-oriented placeholder expressions
- Continuously upfate a template when variables change
- Parametric infos &mdash; mathematical operations on info expressions

## Developer guide: Templating

If you let users customize messages in a config,
you can consider formatting the message with InfoAPI.

2. Pass the config message into InfoAPI:

```php
use SOFe\InfoAPI;

// $this is the plugin main
$player->sendMessage(InfoAPI::render($this, $this->getConfig()->get("message"), [
  "arg" => $arg,
], $player));
```

  - "message" is the config key for the message template
  - The args array are the base variables for the template.
    The variables must use one of the types with infos.
  - We also pass $player to InfoAPI so that InfoAPI may decide how to localize the message better,
    e.g. by formatting for the player's language.

## Developer guide: install InfoAPI

InfoAPI v2 is a virion library using virion 3.1.
Virion 3.1 uses composer to install libraries:

1. Include the InfoAPI virion by adding sof3/infoapi in your composer.json:

```json
{
  "require": {
    "sof3/infoapi": "^2"
  }
}
```

You can place this file next to your plugin.yml.
Installing composer is recommended but not required.

2. Build your plugin with the InfoAPI virion using [pharynx](https://github.com/SOF3/pharynx).
  You can test it using the custom start.sh provided by pharynx.

3. Use the [pharynx GitHub action](https://github.com/SOf3/timer-pmmp/blob/master/.github/workflows/ci.yml)
  to integrate with Poggit.
  Remember not to commit your vendor directory onto GitHub.

## Developer guide: Registry

You can provide your own infos so that other plugins can use them as variables when templating.

For example, to provide the rank of an online player:

```php
InfoAPI::addMapping(
  $this, "myplugin.money",
  fn(Player $player) : ?int => $this->getMoney($player),
);
```

## User guide (for config setup)
### Formatting infos
InfoAPI formats your text using placeholders called "info".
You can put the info in `{}` and it will be replaced into the actual value.

For example, if you are using a chat plugin
that provides an info called `player`
which represents the player chatting,
and an info called `message` which represents the message to be sent.
Then you can customize the chat message like this:

```
<{player}> {message}
```

If the player is called `Steve` and the message is `Hello world`, the formatted chat message would become

```
<Steve> Hello world
```

### More detailed templates
Some types of infos provide extra details.
For example, an info representing a player has a detail called `health`.
So you can write the detail name after the original info name
(separated by a space):

```
[{player health}] <{player}> {message}
```

This will become

```
[9.5/10] <Steve> Hello world
```

You can get details of details!
The player health is a proportion.
Proportion infos have a detail called `percent`,
which converts the fraction into a percentage:

```
[{player health percent}] <{player}> {message}
```

This will become

```
[95%] <Steve> Hello world
```

### Checking available info types

TODO: setup a common registry to track global kinds and mappings.

### How to write `{}` if I don't want it replaced?
Simply write `{{` or `}}` when you want `{` or `}`.

> What if I really want `{{` or `}}`?

Write `{{{{` or `}}}}`. Just duplicate every brace. Simple.
