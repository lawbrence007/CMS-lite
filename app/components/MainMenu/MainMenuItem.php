<?php

namespace App\Components\MainMenu;

use Nette;

/**
 * @method string getTitle()
 * @method setTitle(string)
 * @method string getLink()
 * @method setLink()
 * @method integer getPriority()
 * @method setPriority(integer)
 */
class MainMenuItem extends Nette\Object
{

	private $title;
	private $link;

	private $priority;

	//TODO: PHP_INT_MIN since PHP7
	public function __construct($priority = NULL)
	{
		if ($priority === NULL) {
			$priority = (int)(PHP_INT_MAX / 2);
		}
		if (!is_numeric($priority)) {
			throw new Nette\InvalidArgumentException(sprintf('Priority should be numeric, %s given.', gettype($priority)));
		}
		$this->priority = $priority;
	}

}
