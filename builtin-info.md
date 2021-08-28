Note 1: the names below are fully-qualified, e.g. `pocketmine.block.id`. Users only need to use the last part, e.g. `${id}` instead of `${pocketmine.block.id}`. However you may consider using the fully-qualified form (the latter) if the result seems to go wrong.

Note 2: This is not an exhaustive list. The listed detail infos are only those provided by InfoAPI. Other plugins may add more info types and detail infos.

## BlockInfo
Textual representation: shows the block name (e.g. "Air")

Detail info:

| Name | Type | Description | Examples |
| :---: | :---: | :---: | :---: |
| pocketmine.block.id | NumberInfo | block ID | 0, 1, 2, ... |
| pocketmine.block.damage | NumberInfo | block damage | 0, 1, 2, ... |
| pocketmine.block.name | StringInfo | block name | "Air", "Water" |

## CommonInfo
> CommonInfo is usually directly available

Detail info:

| Name | Type | Description | Examples |
| :---: | :---: | :---: | :---: |
| pocketmine.server.players | NumberInfo | Number of online players | 0, 1, 2, ... |
| pocketmine.server.max players | NumberInfo | Maximum limit of online players | 0, 1, 2, ... |

## WorldInfo
Textual representation: the **folder name** of the world

Detail info:

| Name | Type | Description | Examples |
| :---: | :---: | :---: | :---: |
| pocketmine.world.custom name | StringInfo | The world name in level.dat (not the folder name, may duplicate) | world |
| pocketmine.world.folder name | StringInfo | The world name in level.dat (not the folder name, may duplicate) | world |
| pocketmine.world.name |
| pocketmine.world.time | NumberInfo | The number of ticks for world time | 0, 1, 2, ..., 24000, 24001, ... |
| pocketmine.world.seed | NumberInfo | The world seed | 0, 129348215, ... |

## NumberInfo
Textual representation: the number with at most 6 significant figures, e.g.
- 123456 is represented as `123456`
- 1234567 is represented as `1.23457e+6`
- 0.0001234567 is represented as `0.000123457`
- 0.00001234567 is represented as `1.23457e-5`

Detail info:

| Name | Type | Description | Examples |
| :---: | :---: | :---: | :---: |
| pocketmine.number.ordinal | StringInfo | The number with English ordinal suffix. Only available for integers. | 1st, 2nd, 11th, 21st, 103rd, 0th, -1st, -1011th |
| pocketmine.number.percent | StringInfo | The number in percentage format, with the same precision rules as direct textual representation | 0%, -123.4%, 0.000123457%, -1.23457e-5% |
| pocketmine.number.percentage |

## PlayerInfo
Textual representation: the player login name

Detail info:

| Name | Type | Description | Examples |
| :---: | :---: | :---: | :---: |
| pocketmine.player.name | StringInfo | the player login name | Steve |
| pocketmine.player.nick | StringInfo | the player name in chat | \[ADMIN] Steve |
| pocketmine.player.display name |
| pocketmine.player.nametag | StringInfo | the player name tag above the head | \[ADMIN] Steve |
| pocketmine.player.name tag |
| pocketmine.player.ip | StringInfo | the player IP address | 12.34.56.78 |
| pocketmine.player.ping | NumberInfo | the player ping in milliseconds | 205 |
| pocketmine.player.health | RatioInfo | the player health (current / max) in number of hearts | 19 / 20 |
| pocketmine.player.yaw | NumberInfo | the player yaw, rounded to \[0, 360) degrees. See [this page][yaw_pitch_tutorial] for explanation | 0, 3.6, 275.3, 359.9 |
| pocketmine.player.pitch | NumberInfo | the player pitch rotation in \[-90, 90] degrees | -90, 0, 7.5, 90 |
| pocketmine.player.eye | PositionInfo | the position of player camera |
| pocketmine.player.block below | BlockInfo (with position) | the block the player is standing on (if the player is beyond world height, takes the nearest valid block) | Air |
| pocketmine.player.block facing | BlockInfo (with position) | the nearest solid block the player is looking at (if the player is not facing any solid blocks within 100 meters, takes the block in the player's eyes, which might be non-solid) | Stone, Glass, Air |
| pocketmine.player.facing block |

Detail infos from Position are also available based on the position of the player **feet**.

## PositionInfo
Textual representation: `(x, y, z) @ world`, e.g. `(1, 2, 3) @ world`

Detail info:

| Name | Type | Description | Examples |
| :---: | :---: | :---: | :---: |
| pocketmine.pos.x | NumberInfo | the X coordinate | 128 |
| pocketmine.pos.y | NumberInfo | the Y coordinate | 0, 255.9 |
| pocketmine.pos.z | NumberInfo | the Z coordinate | 128 |
| pocketmine.pos.level | LevelInfo | the world | 128 |
| pocketmine.pos.world |

## RatioInfo
Textual representation: `x / y`, where `x` and `y` are the numerator and denominator in the NumberInfo format (the fraction is not simplified and may contain decimals)

Detail info:

| Name | Type | Description | Examples |
| :---: | :---: | :---: | :---: |
| pocketmine.ratio.num | NumberInfo | the numerator | 1 |
| pocketmine.ratio.value |
| pocketmine.ratio.denom | NumberInfo | the denominator | 2 |
| pocketmine.ratio.max |

Detail infos from NumberInfo are also available based on the actual quotient (unless the denominator is 0)

## StringInfo
Textual representation: the string itself

Detail info:

| Name | Type | Description | Examples |
| :---: | :---: | :---: | :---: |
| pocketmine.string.uppercase | StringInfo | the string converted to upper case | STRING |
| pocketmine.string.lowercase | StringInfo | the string converted to lower case | string |

[yaw_pitch_tutorial]: https://github.com/SOF3/forums-common-sense/wiki/PocketMine-Plugin-Development-FAQ#coordinate-system
