<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category    Application
 * @package     Module_Admin
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2009-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Administrative work with document metadata.
 *
 * @category    Application
 * @package     Module_Admin
 */
class Admin_DocumentsController extends Application_Controller_Action {

    const PARAM_HITSPERPAGE = 'hitsperpage';
    const PARAM_STATE = 'state';
    const PARAM_SORT_BY = 'sort_order';
    const PARAM_SORT_DIRECTION = 'sort_reverse';

    protected $_sortingOptions = array('id', 'title', 'author', 'publicationDate', 'docType');

    protected $_docOptions = array('unpublished', 'inprogress', 'audited', 'published', 'restricted', 'deleted');

    private $_maxDocsDefault = 10;
    private $_stateOptionDefault = 'unpublished';
    private $_sortingOptionDefault = 'id';

    private $_namespace;

    public function init() {
        parent::init();

        $config = $this->getConfig();

        if (isset($config->admin->documents->linkToAuthorSearch)) {
            $this->view->linkToAuthorSearch = $config->admin->documents->linkToAuthorSearch;
        }
        else {
            $this->view->linkToAuthorSearch = 0;
        }

        if (isset($config->admin->documents->maxDocsDefault)) {
            $this->_maxDocsDefault = $config->admin->documents->maxDocsDefault;
        }
        else {
            $this->_maxDocsDefault = 10;
        }

        if (isset($config->admin->documents->defaultview)) {
            $default = $config->admin->documents->defaultview;
            if (!in_array($default, $this->_docOptions)) {
                $this->getLogger()->err("Option 'admin.documents.defaultview' hat ungegueltigen Wert '$default'.");
            }
            $this->_stateOptionDefault = $default;
        }
    }

    /**
     * Display documents (all or filtered by state)
     *
     * @return void
     */
    public function indexAction() {
        $this->view->title = 'admin_documents_index';

        $this->prepareDocStateLinks();

        $urlCallId = array(
            'module' => 'admin',
            'controller' => 'document',
            'action' => 'index'
        );
        $this->view->url_call_id = $this->view->url($urlCallId, 'default', true);

        $this->prepareSortingLinks();

        $data = $this->_request->getParams();
        $filter = $this->_getParam("filter");
        $this->view->filter = $filter;
        $data = $this->_request->getParams();

        $page = 1;
        if (array_key_exists('page', $data)) {
            // set page if requested
            $page = $data['page'];
        }

        $collectionId = null;
        if (array_key_exists('collectionid', $data)) {
            $collectionId = $data['collectionid'];
        }

        $seriesId = null;
        if (array_key_exists('seriesid', $data)) {
            $seriesId = $data['seriesid'];
        }

        $sortReverse = $this->getSortingDirection($data);
        $this->view->sort_reverse = $sortReverse;
        $this->view->sortDirection = ($sortReverse) ? 'descending' : 'ascending';

        $state = $this->getStateOption($data);
        $this->view->state = $state;

        $sortOrder = $this->getSortingOption($data);
        $this->view->sort_order = $sortOrder;

        if (!empty($collectionId)) {
            $collection = new Opus_Collection($collectionId);
            $result = $collection->getDocumentIds();
            $this->view->collection = $collection;
            if ($collection->isRoot()) {
                $collectionRoleName = 'default_collection_role_' . $collection->getRole()->getDisplayName();
                $this->view->collectionName = $this->view->translate($collectionRoleName);
                if ($this->view->collectionName == $collectionRoleName) {
                    $this->view->collectionName = $collection->getRole()->getDisplayName();
                }
            }
            else {
                $this->view->collectionName = $collection->getNumberAndName();
            }
        }
        else if (!empty($seriesId)) {
            $series = new Opus_Series($seriesId);
            $this->view->series = $series;
            $result = $series->getDocumentIdsSortedBySortKey();
        }
        else {
            $result = $this->_helper->documents($sortOrder, $sortReverse, $state);
        }

        $paginator = Zend_Paginator::factory($result);
        $page = 1;
        if (array_key_exists('page', $data)) {
            // paginator
            $page = $data['page'];
        }
        $this->view->maxHitsPerPage = $this->getItemCountPerPage($data);
        $paginator->setItemCountPerPage($this->view->maxHitsPerPage);
        $paginator->setCurrentPageNumber($page);
        $this->view->paginator = $paginator;
        $this->prepareItemCountLinks();
    }

    /**
     * Liefert die Zahl der Dokumente, die auf einer Seite angezeigt werden soll.
     *
     * Der Wert wird aus verschiedenen Quellen ermittelt
     *
     * - Request Parameter
     * - Session
     * - Konfiguration?
     * - Default
     */
    protected function getItemCountPerPage($params) {
        $value = $this->getOption(self::PARAM_HITSPERPAGE, $params);

        if ($value === 'all' || $value < 0) {
            $value = 0;
        }

        if (!is_numeric($value)) {
            $value = $this->_maxDocsDefault;
        }

        $this->setOption(self::PARAM_HITSPERPAGE, $value);

        return $value;
    }

    /**
     * Ermittelt in welchem Status die angezeigten Dokumente sein sollen.
     * @param $params Request parameter
     * @return string
     */
    protected function getStateOption($params) {
        $value = $this->getOption(self::PARAM_STATE, $params);

        if (!in_array($value, $this->_docOptions)) {
            $value = $this->_stateOptionDefault;
        }

        $this->setOption(self::PARAM_STATE, $value);

        return $value;
    }

    /**
     * Ermittelt wonach die Dokumente sortiert werden sollen.
     * @param $params Request Parameter
     * @return string
     */
    protected function getSortingOption($params) {
        $value = $this->getOption(self::PARAM_SORT_BY, $params);

        if (!in_array($value, $this->_sortingOptions)) {
            $value = $this->_sortingOptionDefault;
        }

        $this->setOption(self::PARAM_SORT_BY, $value);

        return $value;
    }

    /**
     * Ermittelt die Sortierrrichtung (aufwaerts/abwaerts).
     * @param $params Request Parameter
     * @return bool
     */
    protected function getSortingDirection($params) {
        $value = $this->getOption(self::PARAM_SORT_DIRECTION, $params);

        if (!is_bool($value) && !is_numeric($value)) {
            $value = false;
        }
        else {
            $value = ($value) ? true : false;
        }

        $this->setOption(self::PARAM_SORT_DIRECTION, $value);

        return $value;
    }

    /**
     * Holt eine Option vom Request oder der Session.
     * @param $name Name der Option
     * @param $params Request Parameter
     * @return mixed|null
     */
    protected function getOption($name, $params) {
        $namespace = $this->getSession();

        if (array_key_exists($name, $params)) {
            $value = $params[$name];
        }
        else {
            $value = (isset($namespace->$name)) ? $namespace->$name : null;
        }

        return $value;
    }

    /**
     * Setzt Option in der Session.
     *
     * @param $name Name der Option
     * @param $value Optionswert
     */
    protected function setOption($name, $value) {
        $namespace = $this->getSession();
        $namespace->$name = $value;
    }

    /**
     * Liefert die Session für diesen Controller.
     * @return Zend_Session_Namespace
     */
    protected function getSession() {
        if (is_null($this->_namespace)) {
            $this->_namespace = new Zend_Session_Namespace('Admin');
        }

        return $this->_namespace;
    }

    /**
     * Bereitet die Links für die Auswahl der Anzahl der Dokumente pro Seite vor.
     */
    protected function prepareItemCountLinks() {
        $config = $this->getConfig();

        if (isset($config->admin->documents->maxDocsOptions)) {
            $options = $config->admin->documents->maxDocsOptions;
        }
        else {
            $options ="10,50,100,all";
        }

        $itemCountOptions = explode(',', $options);

        $itemCountLinks = array();

        foreach ($itemCountOptions as $option) {
            $link = array();

            $link['label'] = $option;
            $link['url'] = $this->view->url(array(self::PARAM_HITSPERPAGE => $option), null, false);

            $itemCountLinks[$option] = $link;
        }

        $this->view->itemCountLinks = $itemCountLinks;
    }

    /**
     * Bereitet die Links für Status Optionen vor.
     */
    protected function prepareDocStateLinks() {
        $registers = array();

        foreach ($this->_docOptions as $name) {
            $params = array('module' => 'admin', 'controller'=>'documents', 'action'=>'index');
            if ($name !== 'all') {
                $params['state'] = $name;
            }
            $url = $this->view->url($params, null, true);
            $registers[$name] = $url;
        }

        $this->view->registers = $registers;
    }

    /**
     * Bereitet die Links für die Sortier Optionen vor.
     */
    protected function prepareSortingLinks() {
        $sortingLinks = array();

        foreach ($this->_sortingOptions as $name) {
            $params = array(
                'module' => 'admin',
                'controller' => 'documents',
                'action' => 'index',
                'sort_order' => $name
            );
            $sortUrl = $this->view->url($params, 'default', false);
            $sortingLinks[$name] = $sortUrl;
        }

        $this->view->sortingLinks = $sortingLinks;

        $directionLinks = array();

        $directionLinks['ascending'] = $this->view->url(array('sort_reverse' => '0'), 'default', false);
        $directionLinks['descending'] = $this->view->url(array('sort_reverse' => '1'), 'default', false);

        $this->view->directionLinks = $directionLinks;
    }

}
