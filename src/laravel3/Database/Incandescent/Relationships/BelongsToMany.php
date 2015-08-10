<?php

namespace WallaceMaxters\Laravel3\Database\Incandescent\Relationships;

use Laravel\Database\Eloquent\Relationships\Has_Many;
use WallaceMaxters\Laravel3\Database\Incandescent\Pivot;
use Laravel\Database\Eloquent\Relationships\Has_Many_And_Belongs_To;

class BelongsToMany extends Has_Many_And_Belongs_To
{
	public function pivot()
	{
		$pivot = new Pivot($this->joining, $this->model->connection());

		return new Has_Many($this->base, $pivot, $this->foreign_key());
	}
}