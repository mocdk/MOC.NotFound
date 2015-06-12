<?php
namespace MOC\NotFound\ViewHelpers;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\RequestHandler;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Routing\Router;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Loads the content of a given URL
 */
class RequestViewHelper extends AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @Flow\Inject(lazy = false)
	 * @var Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @Flow\Inject(lazy = false)
	 * @var Router
	 */
	protected $router;

	/**
	 * @Flow\Inject
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * Initialize this engine
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->router->setRoutesConfiguration($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_ROUTES));
	}

	/**
	 * @param string $path
	 * @return string
	 * @throws \Exception
	 */
	public function render($path = NULL) {
		/** @var RequestHandler $activeRequestHandler */
		$activeRequestHandler = $this->bootstrap->getActiveRequestHandler();
		$parentHttpRequest = $activeRequestHandler->getHttpRequest();
		$httpRequest = Request::create(new Uri($parentHttpRequest->getBaseUri() . '/' . $path));
		$matchingRoute = $this->router->route($httpRequest);
		if (!$matchingRoute) {
			throw new \Exception(sprintf('Uri with path "%s" could not be found.', $parentHttpRequest->getBaseUri() . '/' . $path), 1426446160);
		}
		$request = new ActionRequest($parentHttpRequest);
		foreach ($matchingRoute as $argumentName => $argumentValue) {
			$request->setArgument($argumentName, $argumentValue);
		}
		$response = new Response($activeRequestHandler->getHttpResponse());

		$this->dispatcher->dispatch($request, $response);
		return $response->getContent();
	}

}
