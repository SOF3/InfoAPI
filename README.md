# InfoAPI

Extensible templating for PocketMine plugins.

In a nutshell, InfoAPI provides a simple API to register placeholders between plugins.
But it is more powerful than just that:

- Object-oriented placeholder expressions
- Continuously update a template when variables change
- Parametric infos &mdash; mathematical operations on info expressions

## Developer guide: Templating

If you let users customize messages in a config,
you can consider formatting the message with InfoAPI.

Pass the config message into InfoAPI:

```php
use SOFe\InfoAPI;

// $this is the plugin main
$player->sendMessage(InfoAPI::render($this, $this->getConfig()->get("format"), [
    "arg" => $arg,
], $player));
```

- "message" is the config key for the message template
- The args array are the base variables for the template.
  The types of the variables must be one of the default types
  or provided by another plugin through `InfoAPI::addKind`.
- `$player` is optional.
  It tells InfoAPI how to localize the message better,
  e.g. by formatting for the player's language.

### Advanced: Continuous templating

You can create a template and watch for changes using the `renderContinuous` API:

```php
use SOFe\AwaitGenerator\Await;
use SOFe\InfoAPI;

Await::f2c(function() use($player) {
    $traverser = InfoAPI::renderContinuous($this, $this->getConfig()->get("format"), [
        "arg" => $arg,
    ], $player);

    while(yield from $traverser->next($message)) {
        $player->sendPopup($message);
    }
});
```

## Developer guide: Register mapping

A mapping converts one info to another,
e.g. `money` converts a player to the amount of money in `{player money}`.
You can register your own mappings
so that your plugin as well as other plugins using InfoAPI
can use this info in the template.

For example, to provide the money of an online player:

```php
InfoAPI::addMapping(
    $this, "myplugin.money",
    fn(Player $player) : ?int => $this->getMoney($player),
);
```

The source and return types must be a default or `InfoAPI::addKind` types.

### Advanced: Register continuous mapping

You can additionally provide a `watchChanges` closure,
which returns a [traverser](https://sof3.github.io/await-generator/master/async-iterators.html)
that yields a value when a change is detected.
The [pmevent](https://github.com/SOF3/pmevent) library may help
with building traversers from events:

```php
InfoAPI::addMapping(
    $this, "myplugin.money",
    fn(Player $player) : ?int => $this->getMoney($player),
    watchChanges: fn(Player $player) => Events::watch(
        $this, MoneyChangeEvent::class, $player->getName(),
        fn(MoneyChangeEvent $event) => $event->getPlayer()->getName(),
    )->asGenerator(),
);
```

## Developer guide: Install InfoAPI

> If you are not developing a plugin,
> you do **not** need to install InfoAPI yourself.
> Plugins should have included InfoAPI in their phar release.

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
  You can test it on a server using the custom start.cmd/start.sh provided by pharynx.

3. Use the [pharynx GitHub action](https://github.com/SOf3/timer-pmmp/blob/master/.github/workflows/ci.yml)
  to integrate with Poggit.
  Remember to gitignore your vendor directory so that you don't push it to GitHub.

## User guide: Writing a template

InfoAPI replaces expressions inside `{}` with variables.
For example, if a chat plugin provides two variables:

- `sender`: the player who sent chat (SOFe)
- `message`: the chat message
the following will become something like `<SOFe> Hello world`
if the plugin provides `sender` and `message` ("Hello world")for the template:

```
<{sender}> {message}
```

Color codes are default variables.
Instead of writing &sect;1 &sect;b etc, you could also write:

```
{aqua}<{sender}> {white}{message}
```

You can get more detailed info for a variable.
For example, to get the coordinates of a player `player`:

```
{player} is at ({player x}, {player y}, {player z}).
```

Writing `{` directly will cause error without a matching `}`.
If you want to write a `{`/`}` that is not part of an expression, write twice instead:

```
hello {{world}}.
```

This will become

```
hello {world}.
```
