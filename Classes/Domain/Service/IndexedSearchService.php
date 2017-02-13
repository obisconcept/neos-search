<?php

namespace ObisConcept\NeosSearch\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Class IndexedSearchService
 *
 * @package ObisConcept\NeosSearchPlugin
 * @subpackage Domain\Service
 */
class IndexedSearchService {

    /**
     * Node search service
     *
     * @Flow\Inject
     * @var Neos\Neos\Domain\Service\NodeSearchService
     */
    protected $nodeSearchService;

    /**
     * Node type manager
     *
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * Search for all properties in node for given term
     *
     * @param string $searchParameter
     * @param \Neos\ContentRepository\Domain\Model\Node $currentNode Current node
     * @return array
     */
    public function search($searchParameter, \Neos\ContentRepository\Domain\Model\Node $currentNode) {

        $nodes = $this->nodeSearchService->findByProperties($searchParameter, $this->getSearchabelNodeTypes(), $currentNode->getContext());
        $results = array();

        foreach ($nodes as $node) {

            $properties = $node->getProperties();
            $findString = '';
            foreach ($properties as $property => $propetyName) {
                if ($property != 'searchTags' && !is_object($propetyName) && is_string($propetyName) && (strpos($propetyName, $searchParameter) !== FALSE || strpos($propetyName, strtolower($searchParameter)) !== FALSE)) {
                    $findString = strip_tags($propetyName);
                    $findString = str_replace($searchParameter, '<b>'.$searchParameter.'</b>', $findString);
                    $findString = str_replace(strtolower($searchParameter), '<b>'.strtolower($searchParameter).'</b>', $findString);
                    break;
                }
            }

            if ($node !== NULL && (string) $node->getNodeType() !== 'Neos.Neos:Document') {
                $flowQuery = new FlowQuery(array($node));
                $documentNode = $flowQuery->closest('[instanceof Neos.Neos:Document]')->get(0);

                if ($documentNode) {
                    $findString = $this->prepareFindString($findString, $searchParameter);

                    if (isset($results[$documentNode->getIdentifier()])) {

                        if ($results[$documentNode->getIdentifier()]['findString'] < $findString) {

                            $results[$documentNode->getIdentifier()] = array('findString' => $findString, 'documentNode' => $documentNode);

                        }

                    } else {

                        $results[$documentNode->getIdentifier()] = array('findString' => $findString, 'documentNode' => $documentNode);

                    }
                }
            }

        }

        return (count($results) > 0) ? $results : NULL;

    }

    /**
     * Get the searchable NodeTypes
     *
     * @return array
     */
    public function getSearchabelNodeTypes() {

        $nodeTypes = array();

        $fullConfiguration = $this->nodeTypeManager->getNodeTypes(FALSE);
        foreach ($fullConfiguration as $key => $value) {

            $properties = $value->getProperties();
            if (!empty($properties)) {
                foreach ($properties as $property) {
                    if (isset($property['searchable'])) {
                        if ($property['searchable'] === TRUE) {
                            $nodeTypes[] = $key;
                        }
                    }
                }
            }

        }

        return $nodeTypes;

    }

    /**
     * Prepare find string
     *
     * @param $string
     * @param $searchParameter
     * @return string
     */
    protected function prepareFindString($string, $searchParameter) {

        $parts = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
        $partsCount = count($parts);

        $length = 0;
        $lastPart = 0;
        for (; $lastPart < $partsCount; ++$lastPart) {
            $length += strlen($parts[$lastPart]);
        }

        $findRegex = '/^.*'.$searchParameter.'.*$/';
        $findItems = preg_grep($findRegex, $parts);

        if (count($findItems) == 0) {

            $findRegex = '/^.*'.strtolower($searchParameter).'.*$/';
            $findItems = preg_grep($findRegex, $parts);

        }

        $keys = array();
        foreach ($findItems as $key => $value) {
            $keys[] = $key;
        }

        if (isset($keys[0])) {

            $start = $keys[0];

            if (($keys[0] - 20) > 0) {

                $start = $keys[0] - 20;

            }

            if (($start + 20) < (count($parts) - 1)) {
                $end = $start + 20;
            } else {
                $end = count($parts) - 1;
            }

        } else {

            $start = 0;
            $end = count($parts) - 1;

        }

        if (implode(array_slice($parts, $start, $end)) == '') {

            $findString = NULL;

        } else {

            if ($start == 0) {

                $findString = implode(array_slice($parts, $start, $end));

            } else {

                $findString = '... '.implode(array_slice($parts, $start, $end));

            }

            if ($end != (count($parts) - 1)) {
                $findString = $findString.' ...';
            }

        }

        return $findString;

    }

}
