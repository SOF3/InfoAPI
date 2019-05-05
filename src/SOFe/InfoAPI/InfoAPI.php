<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019 SOFe
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace SOFe\InfoAPI;

use InvalidArgumentException;
use function explode;
use function implode;
use function strlen;
use function strpos;
use function substr;

final class InfoAPI{
	public static function resolveTemplate(string $template, Info $info) : string{
		$offset = 0;
		$output = "";
		while($offset < strlen($template) - 2){
			$char = $template{$offset++};
			if($char === "\\"){
				$char = $template{$offset++};
				switch($char){
					case "n":
						$out = "\n";
						break;
					case "$":
					case "{":
					case "}";
						$out = $char;
						break;
					default:
						throw new InvalidArgumentException("Unknown escape sequence \"\\$char\"");
				}
				$output .= $out;
			}elseif($char === "$" && $template[$offset] === "{"){
				$offset++;
				$next = strpos($template, "}", $offset);
				if($next === false){
					throw new InvalidArgumentException("Unclosed \${");
				}
				$iden = substr($template, $offset, $next - $offset);
				$offset = $next + 1;
				$output .= self::resolve($iden, $info);
			}else{
				$output .= $char;
			}
		}
		return $output;
	}

	public static function resolve(string $iden, Info $info) : string{
		$parts = explode(" ", $iden);
		while(!empty($parts)){
			$event = new InfoResolveEvent($parts, $info);
			$event->call();
			if(!$event->isCancelled()){
				throw new InvalidArgumentException("Unresolved info \"" . implode(" ", $parts) . "\"");
			}
			$parts = $event->getResidue();
			$info = $event->getResult();
		}
		return $info->toString();
	}
}
