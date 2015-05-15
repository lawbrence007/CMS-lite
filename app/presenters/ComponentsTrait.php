<?php

use App\Components;

trait ComponentsTrait
{

	use Kdyby\Autowired\AutowireComponentFactories;

	protected function createComponentMainMenu(Components\MainMenu\IMainMenuFactory $factory)
	{
		return $factory->create();
	}

	protected function createComponentContactForm(Components\ContactForm\IContactFormFactory $factory)
	{
		return $factory->create();
	}

	protected function createComponentCss(Components\Css\ICssFactory $factory)
	{
		return $factory->create();
	}

	protected function createComponentJs(Components\Js\IJsFactory $factory)
	{
		return $factory->create();
	}

}
