<?php

namespace Pages\Components\PageForm;

use App\Components\AControl;
use Files\Components\IUploaderFactory;
use Files\File;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;
use Pages\Category;
use Pages\Page;
use Pages\PageFacade;
use Url\Components\RedirectForm\IRedirectFormFactory;
use Url\DuplicateRouteException;
use Url\RedirectFacade;
use Users\User;

/**
 * @method onSave(PageForm $control, Page $entity)
 * @method onPublish(PageForm $control, Page $entity)
 * @method onComplete(PageForm $control)
 * @method onException(PageForm $control, \Exception $exc)
 */
class PageForm extends AControl
{

	/** @var \Closure[] */
	public $onSave = [];

	/** @var \Closure[] */
	public $onPublish = [];

	/** @var \Closure[] */
	public $onComplete = [];

	/** @var \Closure[] */
	public $onException = [];

	/** @var PageFacade */
	private $pageFacade;

	/** @var EntityManager */
	private $em;

	/** @var Page */
	private $editablePage;
	private $edit = TRUE;

	/** @var RedirectFacade */
	private $redirectFacade;

	public function __construct(
		$editablePage,
		PageFacade $pageFacade,
		EntityManager $em,
		RedirectFacade $redirectFacade
	) {
		if ($editablePage === NULL) { //NEW
			$editablePage = new Page;
			$this->edit = FALSE;
		}
		$this->editablePage = $editablePage;
		$this->pageFacade = $pageFacade;
		$this->em = $em;
		$this->redirectFacade = $redirectFacade;
	}

	public function render(array $parameters = NULL)
	{
		if ($parameters) {
			$this->template->parameters = ArrayHash::from($parameters);
		}
		$this->template->showPublish = $this->editablePage->isPublished() ? FALSE : TRUE;
		$this->template->page = $this->editablePage;
		$this->template->edit = $this->edit;
		$this->template->render($this->templatePath ?: __DIR__ . '/PageForm.latte');
	}

	protected function createComponentRedirectForm(IRedirectFormFactory $factory)
	{
		return $factory->create($this->editablePage->getId());
	}

	protected function createComponentPicturesUploader(IUploaderFactory $factory)
	{
		$control = $factory->create(TRUE);

		$control->onSuccess[] = function ($_, File $file, array $result) {
			$this->editablePage->addFile($file);
			$this->em->flush($this->editablePage);
			$this->presenter->sendResponse(new JsonResponse($result));
		};

		$control->onFailed[] = function ($_, array $result) {
			$this->presenter->sendResponse(new JsonResponse($result));
		};

		return $control;
	}

	protected function createComponentFilesUploader(IUploaderFactory $factory)
	{
		$control = $factory->create();

		$control->onSuccess[] = function ($_, File $file, array $result) {
			$this->editablePage->addFile($file);
			$this->em->flush($this->editablePage);
			$this->presenter->sendResponse(new JsonResponse($result));
		};

		$control->onFailed[] = function ($_, array $result) {
			$this->presenter->sendResponse(new JsonResponse($result));
		};

		return $control;
	}

	protected function createComponentPageForm()
	{
		$form = new UI\Form;
		$form->addProtection();
		$form->addText('title', NULL)->setRequired('Je zapotřebí vyplnit název stránky.');
		$form->addText('fakePath', 'URL stránky:')
			->addRule(\App\Validator::FAKE_PATH, 'URL cesta může obsahovat pouze písmena, čísla, lomítko a pomlčku.');
		$form->addTinyMCE('editor', NULL)
			->setRequired('Je zapotřebí napsat nějaký text.');

		$authors = $this->em->getRepository(User::class)->findPairs('email');
		$user_id = $this->presenter->user->id;
		$form->addMultiSelect('authors', 'Autor:',
			[NULL => 'Bez autora'] + $authors
		)->setDefaultValue(array_key_exists($user_id, $authors) ? $user_id : NULL);

		$form->addMultiSelect('categories', 'Kategorie:',
			[NULL => 'Bez kategorie'] +
			$this->em->getRepository(Category::class)->findPairs('name')
		);

		// ADVANCED:
		$form->addText('tags', 'Štítky:');
		$form->addText('individual_css', 'Individuální CSS třída nebo ID:');

		$form
			->addCheckbox('protected', 'Zaheslovat stránku:')
			->addCondition($form::EQUAL, TRUE)
			->toggle('protected');
		$form->addPassword('password', 'Heslo:');

		// OPTIMIZATION:
		$form->addText('individualTitle', 'Individuální titulek:');
		$form->addTextArea('description', 'Popis stránky (Description):');
		$form->addSelect('index', 'Indexace stránky:', [
			NULL => 'Výchozí',
			'index' => 'Indexovat (index)',
			'noindex' => 'Neindexovat (noindex)',
		]);
		$form->addSelect('follow', 'Sledování odkazů', [
			NULL => 'Výchozí',
			'follow' => 'Sledovat (follow)',
			'nofollow' => 'Nesledovat (nofollow)',
		]);

		// FCBk:
		$form->addText('fcbk_title', 'Individuální titulek příspěvku na Facebooku:');
		$form->addTextArea('fcbk_description', 'Popis stránky v příspěvku na Facebooku:');

		$this->setDefaults($form);
		$form->addSubmit('saveAndRedirect', NULL)->onClick[] = $this->savePageAndRedirect;
		$form->addSubmit('saveAndStay', NULL)->onClick[] = $this->savePageAndStay;
		$form->addSubmit('publish', NULL)->onClick[] = $this->publishPage;
		$form->addSubmit('preview', NULL)->onClick[] = function (SubmitButton $sender) {
			$this->savePage($sender, TRUE);
		};
		return $form;
	}

	public function savePageAndRedirect(SubmitButton $sender)
	{
		$this->savePage($sender);
		$this->presenter->redirect('default');
	}

	public function savePageAndStay(SubmitButton $sender)
	{
		$page = $this->savePage($sender);
		if ($page) {
			$this->presenter->redirect('edit', $page->getId());
		}
	}

	private function savePage(SubmitButton $sender, $preview = FALSE)
	{
		try {
			$entity = $this->editablePage;
			$values = $sender->getForm()->getValues();
			$this->pageFacade->onSave[] = function () use ($entity) {
				$this->onSave($this, $entity);
			};
			$this->pageFacade->save($entity, $values);
		} catch (DuplicateRouteException $exc) {
			$this->presenter->flashMessage($exc->getMessage());
			return NULL;
		} catch (\Exception $exc) {
			$this->onException($this, $exc);
			return NULL;
		}
		if ($preview) {
			$this->presenter->redirect(':Pages:Front:Page:preview', $entity->id);
		}
		return $entity;
	}

	public function publishPage(SubmitButton $sender)
	{
		try {
			$entity = $this->editablePage;
			$values = $sender->getForm()->getValues();
			$this->pageFacade->onPublish[] = function () use ($entity) {
				$this->onPublish($this, $entity);
			};
			$this->pageFacade->publish($entity, $values);
		} catch (DuplicateRouteException $exc) {
			$this->presenter->flashMessage($exc->getMessage());
			return;
		} catch (\Exception $exc) {
			$this->onException($this, $exc);
			return;
		}
		$this->presenter->redirect('default');
	}

	private function setDefaults(UI\Form $form)
	{
		if ($this->editablePage !== NULL) { //EDITING
			$e = $this->editablePage;
			$form->setDefaults([
				'title' => $e->getTitle(),
				'fakePath' => $e->getUrl() ? $e->getUrl()->getFakePath() : '',
				'editor' => $e->getBody(),
				'authors' => $e->getAuthorIds(),
				'categories' => $e->getCategoryIds(),
				'individualTitle' => $e->getIndividualTitle(),
				'description' => $e->getDescription(),
				'index' => $e->getIndex(),
				'follow' => $e->getFollow(),
				'tags' => $e->getTagsString(),
				'individual_css' => $e->getIndividualCss(),
				'protected' => $e->getProtected(),
				'fcbk_title' => $e->getOpenGraphContent('og:title'),
				'fcbk_description' => $e->getOpenGraphContent('og:description'),
			]);
		}
	}

}

interface IPageFormFactory
{
	/**
	 * @param NULL|Page $editablePage
	 *
	 * @return PageForm
	 */
	public function create($editablePage);
}
