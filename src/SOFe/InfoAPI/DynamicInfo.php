<?php

/*
 * InfoAPI
 *
 * Copyright (C) 2019-2021 SOFe
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

/**
 * A marker interface for Info implementations that support dynamic details.
 */
interface DynamicInfo{
	/**
	 * Resolves dynamic details.
	 *
	 * This method is only called if and only if
	 * allowDynamic() returns true AND none of the registered details nor fallbacks resolve into anything.
	 *
	 * Only use this function to resolve details with truly dynamic names,
	 * such as numbers (used in `MultiplyInfo`),
	 */
	public function resolveDynamic(string $key) : ?Info;
}
