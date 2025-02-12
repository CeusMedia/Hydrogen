<?php

use CeusMedia\Common\Alg\ID;
use CeusMedia\HydrogenFramework\Logic;

class Logic_Example extends Logic
{
	public string $uuid;

	protected function __onInit(): void
	{
		$this->uuid = ID::uuid();
	}
}