# Builtin info types
## Text
| Name | Output type | Description | Example |
| :---: | :---: | :---: | :---: |
| `infoapi.string.uppercase` | Text | Convert the whole text to upper case | HELLO WORLD |
| `infoapi.string.lowercase` | Text | Convert the whole text to lower case | hello world |

## Number
| Name | Output type | Description | Example |
| :---: | :---: | :---: | :---: |
| `infoapi.number.english.ordinal` | Text | The number's ordinal form in English (only works on non-negative integers) | 1st, 3rd, 112th |
| `infoapi.number.percent` | Text | Displays the number as a percentage | 12.3% |

## Proportion
| Name | Output type | Description | Example |
| :---: | :---: | :---: | :---: |
| `infoapi.ratio.current` | Number | The current value of this proportion | 1 (for 1/3) |
| `infoapi.ratio.max` | Number | The maximum value of this proportion | 3 (for 1/3) |
| `infoapi.ratio.remaining` | Number | One minus this proportion | 2/3 (for 1/3) |
| `infoapi.ratio.invert` | Number | One minus this proportion | 2/3 (for 1/3) |
| `infoapi.ratio.lost` | Number | One minus this proportion | 2/3 (for 1/3) |
| (fallback) | Number | The proportion as a fraction |  |

## Position
| Name | Output type | Description | Example |
| :---: | :---: | :---: | :---: |
| `infoapi.position.x` | Number | The X-coordinate of this position | 128 (in (128, 64, 256)) |
| `infoapi.position.y` | Number | The Y-coordinate of this position | 64 (in (128, 64, 256)) |
| `infoapi.position.z` | Number | The Z-coordinate of this position | 256 (in (128, 64, 256)) |
| `infoapi.position.world` | World | The world containing this position |  |

## World
| Name | Output type | Description | Example |
| :---: | :---: | :---: | :---: |
| `infoapi.world.name` | Number | The folder name of this world | world (2) |
| `infoapi.world.folderName` | Number | The folder name of this world | world (2) |
| `infoapi.world.customName` | Number | The display name of this world | world |
| `infoapi.world.displayName` | Number | The display name of this world | world |
| `infoapi.world.time` | Number | The current world time, in ticks | 12000 |
| `infoapi.world.seed` | Number | The seed of this world | world |

