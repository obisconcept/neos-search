<?php

namespace ObisConcept\NeosSearch\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Service\LinkingService;

/**
 * Class IndexedSearchController
 *
 * @package ObisConcept\NeosSearchPlugin
 * @subpackage Controller
 */
class IndexedSearchController extends \Neos\Flow\Mvc\Controller\ActionController {

    /**
     * Linking service
     *
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * Workspace repository
     *
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Repository\WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * NodeData repository
     *
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * Node factory
     *
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Factory\NodeFactory
     */
    protected $nodeFactory;

    /**
     * Context factory
     *
     * @Flow\Inject
     * @var \Neos\Neos\Domain\Service\ContentContextFactory
     */
    protected $contextFactory;

    /**
     * Indexed search service
     *
     * @Flow\Inject
     * @var \ObisConcept\NeosSearch\Domain\Service\IndexedSearchService
     */
    protected $indexedSearchService;

    /**
     * Settings
     *
     * @var array
     */
    protected $settings;

    /**
     * Inject settings
     *
     * @param array $settings
     */
    public function injectSettings(array $settings) {

        $this->settings = $settings;

    }

    /**
     * Initializes the view before invoking an action method
     *
     * @param \Neos\Flow\Mvc\View\ViewInterface $view The view to be initialized
     * @return void
     */

    protected function initializeView(\Neos\Flow\Mvc\View\ViewInterface $view) {
        if ($view instanceof \Neos\Fluid\View\TemplateView) {
            $view->setTemplateRootPath($this->settings['templateRootPath']);
        }
    }

    /**
     * Search box
     *
     * @return void
     */
    public function indexAction() {

        $this->prepareSearchBox();

    }

    /**
     * Ajax Search box
     *
     * @return void
     */
    public function indexAjaxAction() {

        $this->prepareSearchBox();

    }

    /**
     * Prepare search box view data
     *
     * @return void
     */
    protected function prepareSearchBox() {

        $searchResultNode = $this->request->getInternalArgument('__node')->getProperty('searchResultNode');


        if (!$searchResultNode) {

            $searchResultIdentifier = $this->request->getInternalArgument('__searchResultIdentifier');
            if ($searchResultIdentifier != '') {

                $siteNode = $this->request->getInternalArgument('__documentNode')->getContext()->getCurrentSiteNode();
                $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery(array($siteNode));
                $operation = new \Neos\ContentRepository\Eel\FlowQueryOperations\FindOperation();
                $operation->evaluate($flowQuery, array('#'.$searchResultIdentifier));
                $searchResultNode = (isset($flowQuery->getContext()[0]) && $flowQuery->getContext()[0] != NULL) ? $flowQuery->getContext()[0] : NULL;

            }

        }

        $this->view->assign(
            'searchResultNode',
            $searchResultNode
        );

        $this->view->assign(
            'documentNode',
            $this->request->getInternalArgument('__documentNode')
        );

    }

    /**
     * Search for all properties in node for given term
     *
     * @return void
     */
    public function searchResultAction() {

        $searchArguments = $this->request->getHttpRequest()->getArgument('--obisconcept_neossearch-indexedsearch');
        if ($searchArguments === NULL) {

            $searchArguments = $this->request->getHttpRequest()->getArgument('--neos_nodetypes-page');

        }

        if ($searchArguments === NULL) {

            $searchArguments = $this->request->getHttpRequest()->getArguments();

        }

        if (isset($searchArguments['searchParameter'])) {

            $searchParameter = $searchArguments['searchParameter'];

        } else {

            $searchParameter = '';

        }

        $currentNode = $this->request->getInternalArgument('__documentNode');

        if ($searchParameter !== NULL && $searchParameter !== '') {

            $searchResults = $this->indexedSearchService->search($searchParameter, $currentNode);
            $this->view->assignMultiple(array('searchResults'=> $searchResults, 'searchParameter' => $searchParameter));

        }

    }

    /**
     * Search for all properties in node for given term for AJAX requests
     *
     * @param string $language
     * @return void
     */
    public function searchResultAjaxAction($language) {

        $results = array();
        $searchArguments = $this->request->getArguments();

        if (isset($searchArguments['searchParameter']) && isset($searchArguments['currentNodePath'])) {

            $searchParameter = $searchArguments['searchParameter'];
            $currentNodePath = $searchArguments['currentNodePath'];

            $liveWorkspace = $this->workspaceRepository->findOneByName('live');
            $nodeData = $this->nodeDataRepository->findOneByPath($currentNodePath, $liveWorkspace);
            $context = $this->contextFactory->create(array('dimensions' => array('language' => array($language))));
            $currentNode = $this->nodeFactory->createFromNodeData($nodeData, $context);

            if ($searchParameter !== NULL && $searchParameter !== '') {

                $counter = 1;
                $searchResults = $this->indexedSearchService->search($searchParameter, $currentNode);

                if ($searchResults) {
                    foreach ($searchResults as $searchResult) {

                        if ($counter <= 10) {

                            $result = array();
                            $result['title'] = $searchResult['documentNode']->getProperty('title');
                            $result['findString'] = $searchResult['findString'];
                            $result['uri'] = $this->linkingService->createNodeUri($this->getControllerContext(), $searchResult['documentNode'], $currentNode, 'html', true);
                            array_push($results, $result);

                        }

                        $counter++;

                    }
                }

            }

        }

        $this->view->assign('value', array('results' => $results));

    }

}
