<?php

namespace MOC\NotFound\Fusion\Eel\Helper;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

class ContextHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\InjectConfiguration(path="contentDimensions", package="Neos.ContentRepository")
     * @var array
     */
    protected $contentDimensionsConfiguration;

    /**
     * Returns a context array with matched dimension values per dimension for given request uri path. If nothing
     * matches, it returns a context array with default dimension values per dimension.
     *
     * @param $requestUriPath
     * @return array
     */
    public function ofRequestUriPath($requestUriPath)
    {
        // No dimensions configured, context is empty
        if (count($this->contentDimensionsConfiguration) === 0) {
            return [];
        }

        $uriSegments = $this->getUriSegments($requestUriPath);
        $dimensionValues = $this->getDimensionValuesForUriSegments($uriSegments);
        if (!$dimensionValues) {
            $dimensionValues = $this->getDefaultDimensionValues();
        }

        $targetDimensionValues = array_map(function ($dimensionValues) {
            return reset($dimensionValues); // Default target dimension value is first dimension value
        }, $dimensionValues);


        return [
            'dimensions' => $dimensionValues,
            'targetDimensions' => $targetDimensionValues
        ];
    }

    /**
     * @param array $uriSegments
     * @return array
     */
    protected function getDimensionValuesForUriSegments($uriSegments)
    {
        if (count($uriSegments) !== count($this->contentDimensionsConfiguration)) {
            return [];
        }

        $index = 0;
        $dimensionValues = [];
        foreach ($this->contentDimensionsConfiguration as $dimensionName => $dimensionConfiguration) {
            $uriSegment = $uriSegments[$index++];
            foreach ($dimensionConfiguration['presets'] as $preset) {
                if ($uriSegment === $preset['uriSegment']) {
                    $dimensionValues[$dimensionName] = $preset['values'];
                    continue 2;
                }
            }
        }

        if (count($uriSegments) !== count($dimensionValues)) {
            return [];
        }

        return $dimensionValues;
    }

    /**
     * Returns default dimension values per dimension.
     *
     * @return array
     */
    protected function getDefaultDimensionValues()
    {
        $dimensionValues = [];
        foreach ($this->contentDimensionsConfiguration as $dimensionName => $dimensionConfiguration) {
            $dimensionValues[$dimensionName] =  [$dimensionConfiguration['default']];
        }
        return $dimensionValues;
    }

    protected function getUriSegments($requestUriPath)
    {
        $pathParts = explode('/', trim($requestUriPath, '/'), 2);
        return explode('_', $pathParts[0]);
    }

    function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
