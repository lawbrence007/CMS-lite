<?php

namespace App\Presenters;

use App\Components\ContactForm\IContactFormFactory;
use App\Components\MainMenu\IMainMenuFactory;
use Nette;
use WebLoader;

abstract class BasePresenter extends Nette\Application\UI\Presenter
{

	/** @var IMainMenuFactory @inject */
	public $mainMenuFactory;

	/** @var IContactFormFactory @inject */
	public $contactFormFactory;

	protected function createComponentMainMenu()
	{
		return $this->mainMenuFactory->create();
	}

	protected function createComponentContactForm()
	{
		return $this->contactFormFactory->create();
	}

	protected function createComponentCss()
	{
		$files = new WebLoader\FileCollection(WWW_DIR . '/css');
		$files->addRemoteFile('//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css');
		$files->addFile('front.css');
		$compiler = WebLoader\Compiler::createCssCompiler($files, WWW_DIR . '/temp');
		//TODO: minify
		$control = new WebLoader\Nette\CssLoader($compiler, 'temp');
		$control->setMedia('screen');
		return $control;
	}

	protected function createComponentJs()
	{
		$files = new WebLoader\FileCollection(WWW_DIR . '/js');
		$files->addRemoteFiles([
			'//code.jquery.com/jquery-1.11.2.min.js',
			'//nette.github.io/resources/js/netteForms.min.js',
		]);
		$files->addFile('main.js');
		$compiler = WebLoader\Compiler::createJsCompiler($files, WWW_DIR . '/temp');
		//TODO: minify
		$control = new WebLoader\Nette\JavaScriptLoader($compiler, 'temp');
		return $control;
	}

}