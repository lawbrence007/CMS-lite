<?php

namespace App\Components\MainMenu;

use App\Components\AControl;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Pages\Page;
use Pages\Query\PagesQuery;

class MainMenu extends AControl
{

	/** @var EntityManager */
	private $em;

	/** @var \SplPriorityQueue */
	private $itemsQueue;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->itemsQueue = new \SplPriorityQueue;
	}

	public function render(array $parameters = NULL)
	{
		if ($parameters) {
			$this->template->parameters = Nette\Utils\ArrayHash::from($parameters);
		}
		//TODO: cache?
		$this->setDefaultLinks();
		$this->template->items = $this->itemsQueue;
		$this->template->render($this->templatePath ?: __DIR__ . '/templates/MainMenu.latte');
	}

	public function addMainMenuItem(MainMenuItem $item)
	{
		$this->itemsQueue->insert($item, $item->getPriority());
	}

	private function setDefaultLinks()
	{
		$homepage = new MainMenuItem(PHP_INT_MAX);
		$homepage->setTitle('Home')->setLink(':Front:Homepage:');
		$this->addMainMenuItem($homepage);

		$contact = new MainMenuItem(-PHP_INT_MAX);
		$contact->setTitle('Kontakt')->setLink(':Front:Contact:');
		$this->addMainMenuItem($contact);

		$pages = $this->em->getRepository(Page::class)->fetch((new PagesQuery));
		//TODO: priority of page menu items
		/** @var Page $page */
		foreach ($pages as $page) {
			$item = (new MainMenuItem)->setTitle($page->title)->setLink($this->presenter->lazyLink(':Front:Page:', $page->slug));
			$this->addMainMenuItem($item);
		}

		//Extensions cannot create links properly during compile time, co we have to fix it...
		foreach (clone $this->itemsQueue as $item) {
			$link = $item->getLink();
			if (!$link instanceof Nette\Application\UI\Link) {
				$item->setLink($this->getPresenter()->lazyLink($link));
			}
		}
	}

}

interface IMainMenuFactory
{
	/** @return MainMenu */
	public function create();
}
