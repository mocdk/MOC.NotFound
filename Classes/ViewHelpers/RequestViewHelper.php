<?php
namespace MOC\NotFound\ViewHelpers;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\RequestHandler;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Neos\Routing\FrontendNodeRoutePartHandler;

/**
 * Loads the content of a given URL
 */
class RequestViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @Flow\Inject(lazy=false)
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject(lazy=false)
     * @var Router
     */
    protected $router;

    /**
     * @Flow\Inject(lazy=false)
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\InjectConfiguration(path="routing.supportEmptySegmentForDimensions", package="Neos.Neos")
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
    public function initializeObject()
    {
        $this->router->setRoutesConfiguration($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_ROUTES));
    }

    /**
     * @param string $path
     *
     * @return string
     * @throws \Exception
     */
    public function render($path = null)
    {
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

        $this->securityContext->withoutAuthorizationChecks(function () use ($request, $response) {
            $this->dispatcher->dispatch($request, $response);
        });

        return $response->getContent();
    }

    /**
     * @param string $path
     *
     * @return void
     */
    protected function appendFirstUriPartIfValidDimension(&$path)
    {
        $requestPath = ltrim($this->controllerContext->getRequest()->getHttpRequest()->getUri()->getPath(), '/');
        $matches = [];
        preg_match(FrontendNodeRoutePartHandler::DIMENSION_REQUEST_PATH_MATCHER, $requestPath, $matches);
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
