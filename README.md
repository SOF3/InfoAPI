# InfoAPI
The standard for user config variables.

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
There are multiple ways to check what infos you can use.

#### Check the plugin description
TODO: requires standardized description format

https://sof3.github.io/InfoAPI/defaults.html

#### Use the `/info` command
TODO: requires plugins to register help keys, then finish the UI

### How to write `{}` if I don't want it replaced?
Simply write `{{` or `}}` when you want `{` or `}`.

> What if I really want `{{` or `}}`?

Write `{{{{` or `}}}}`. Just duplicate every brace. Simple.

## Developer guide
### Providing info for InfoAPI
If your plugin stores data (esp. about players, etc.), you can expose your data to InfoAPI so that users can use these data in other plugins.

To provide info for InfoAPI, use the API methods on `InfoRegistry` in `onEnable`:
```php
public function onEnable(){
	// other normal stuff
	if(class_exists(InfoAPI::class)){
		InfoAPI::provideInfo(SomeInfo::class, MyInfo::class, "pluginname.infoname", function(SomeInfo $info){
			return new MyInfo(...);
		});
	}
}
```

`SomeInfo` is the type of the parent of the info you provide, and `MyInfo` is the type of the info provided.

The third parameter is a fully-qualified identifier for the info you provide. Users only need to type the part behind the last dot, but they can use the previous parts in case two infos have identical names (especially from different plugins). (For the first example above, `{infoname}`, `{modulename.infoname}` and `{pluginname.modulename.infoname}` will all be matched)

For example, if you provide a kills/deaths ratio for a player in a plugin called KDCounter, your code might look like this:

```php
public function onEnable(){
	// other normal stuff
	if(class_exists(InfoRegistry::class)){
		InfoAPI::provideInfo(PlayerInfo::class, NumberInfo::class, "kdcounter.kills", function(PlayerInfo $info){
			return new NumberInfo($this->getKills($info->getPlayer()));
		});
		InfoAPI::provideInfo(PlayerInfo::class, NumberInfo::class, "kdcounter.deaths", function(PlayerInfo $info){
			return new NumberInfo($this->getDeaths($info->getPlayer()));
		});
		InfoAPI::provideInfo(PlayerInfo::class, NumberInfo::class, "kdcounter.kd", function(PlayerInfo $info){
			return new RatioInfo($this->getKills($info->getPlayer()), $this->getDeaths($info->getPlayer()));
		});
	}
}
```

The second parameter is a closure that gets called if the event is matched. It should return an `Info` object that is the resolved result.

InfoAPI introduces some builtin subclasses for `Info`, but plugins may also implement new subclasses of such type. Please consult the API documentation for usage.

### Using InfoAPI for config
If your plugin uses placeholders in your config, you can use InfoAPI so that users can use data from other plugins.

To use InfoAPI, add `InfoAPI` to the `depend` attribute in plugin.yml

```yaml
depend: [InfoAPI]
```

Let's say the template string is in the `format` key in your config.yml:

```yaml
format: "{gold}<{speaker}> {white}{message}"
```

`{gold}` and `{white}` are default infos defined in InfoAPI itself.
`{speaker}` and `{message}` are extra placeholders
which refer to the player `$player` and the string `$message`.
We can create the formatted string with this code:

```php
use SOFe\InfoAPI\{InfoAPI, PlayerInfo, StringInfo};

$format = $this->getConfig()->get("format");
$text = InfoAPI::resolve($format, new class([
	"message" => new StringInfo($msg),
	"speaker" => new PlayerInfo($player),
]) extends AnonInfo {});
```

Each placeholder must be an instance of `Info`.
InfoAPI provides several info types by default;
you can check them from the wiki.

Do NOT use `AnonInfo` for return values in `provideInfo`/`provideFallback`!
`ContextInfo` is only for the convenient creation when resolving.
Please refer to the previous guide if you are actively *providing* some info.
