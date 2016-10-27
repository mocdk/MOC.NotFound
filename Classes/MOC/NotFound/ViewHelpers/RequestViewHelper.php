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
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

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
	 * @Flow\Inject(lazy=false)
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @Flow\InjectConfiguration("routing.supportEmptySegmentForDimensions", package="TYPO3.Neos")
	 * @var boolean
	 */
	protected $supportEmptySegmentForDimensions;

	/**
	 * @Flow\Inject
	 * @var ContentDimensionPresetSourceInterface
	 */
	protected $contentDimensionPresetSource;

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
		$this->appendFirstUriPartIfValidDimension($path);
		/** @var RequestHandler $activeRequestHandler */
		$activeRequestHandler = $this->bootstrap->getActiveRequestHandler();
		$parentHttpRequest = $activeRequestHandler->getHttpRequest();
		$uri = rtrim($parentHttpRequest->getBaseUri(), '/') . '/' . $path;
		$httpRequest = Request::create(new Uri($uri));
		$matchingRoute = $this->router->route($httpRequest);
		if (!$matchingRoute) {
			$exception = new \Exception(sprintf('Uri with path "%s" could not be found.', $uri), 1426446160);
			$exceptionHandler = set_exception_handler(null)[0];
			$exceptionHandler->handleException($exception);
			exit();
		}
		$request = new ActionRequest($parentHttpRequest);
		foreach ($matchingRoute as $argumentName => $argumentValue) {
			$request->setArgument($argumentName, $argumentValue);
		}
		$response = new Response($activeRequestHandler->getHttpResponse());

		$this->securityContext->withoutAuthorizationChecks(function() use ($request, $response) {
			$this->dispatcher->dispatch($request, $response);
		});

		return $response->getContent();
	}

	/**
	 * @param string $path
	 * @return void
	 */
	protected function appendFirstUriPartIfValidDimension(&$path) {
		$requestPath = ltrim($this->controllerContext->getRequest()->getHttpRequest()->getUri()->getPath(), '/');
		$matches = [];
		preg_match(\TYPO3\Neos\Routing\FrontendNodeRoutePartHandler::DIMENSION_REQUEST_PATH_MATCHER, $requestPath, $matches);
		if (!isset($matches['firstUriPart']) && !isset($matches['dimensionPresetUriSegments'])) {
			return;
		}

		$dimensionPresets = $this->contentDimensionPresetSource->getAllPresets();
		if (count($dimensionPresets) === 0) {
			return;
		}

		$firstUriPartExploded = explode('_', $matches['firstUriPart'] ?: $matches['dimensionPresetUriSegments']);
		if ($this->supportEmptySegmentForDimensions) {
			foreach ($firstUriPartExploded as $uriSegment) {
				$uriSegmentIsValid = false;
				foreach ($dimensionPresets as $dimensionName => $dimensionPreset) {
					$preset = $this->contentDimensionPresetSource->findPresetByUriSegment($dimensionName, $uriSegment);
					if ($preset !== null) {
						$uriSegmentIsValid = true;
						break;
					}
				}
				if (!$uriSegmentIsValid) {
					return;
				}
			}
		} else {
			if (count($firstUriPartExploded) !== count($dimensionPresets)) {
				$dimensionValues = [];

				foreach ($dimensionPresets as $dimensionName => $dimensionPreset) {
					$dimensionValues[] = $dimensionPreset['default'];
				}
				
				$pathDefaultDimensionsPrefix = implode('_', $dimensionValues);
				
				// Add default dimensions in front of the path
				$path = $pathDefaultDimensionsPrefix . '/' . $path;
				
				return;
			}
			foreach ($dimensionPresets as $dimensionName => $dimensionPreset) {
				$uriSegment = array_shift($firstUriPartExploded);
				$preset = $this->contentDimensionPresetSource->findPresetByUriSegment($dimensionName, $uriSegment);
				if ($preset === null) {
					return;
				}
			}
		}

		$path = $matches['firstUriPart'] . '/' . $path;
	}

}
