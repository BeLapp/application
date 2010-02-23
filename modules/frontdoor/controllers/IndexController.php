<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
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
 * @package     Module_Frontdoor
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 *
 */

class Frontdoor_IndexController extends Controller_Action {

    /**
     * Display the metadata of a document.
     *
     * @return void
     */
    public function indexAction() {
        // $logger = Zend_Registry::get('Zend_Log');        
        // Load document
        $docId = $this->getRequest()->getParam('docId');
        $document = new Opus_Document($docId);

        if (Zend_Registry::get('Zend_Config')->security !== '0') {
            // $logger->info("Check security");
            if (true !== Opus_Security_Realm::getInstance()->check('readMetadata', $document->getServerState())) {
                // $logger->info("Security false.");
                $identity = Zend_Auth::getInstance()->getIdentity();
                if (empty($identity) === true) {
                    $message = $this->translate('frontdoor_no_identity_error');
                } else {
                    $message = $this->translate('frontdoor_wrong_identity_error');
                }
                $this->_redirectTo(
                    $message,
                    'index', 
                    'auth', 
                    'default', 
                    array(
                        'rmodule' => 'frontdoor',
                        'rcontroller' => 'index',
                        'raction' => 'index',
                        'docId' => $docId,
                    )
                );
            }
            // $logger->info("Security true.");
        }

        // Set up filter and get XML-Representation of filtered document.
        $type = new Opus_Document_Type($document->getType(), $document->getWorkflow());
        $filter = new Opus_Model_Filter;
        $filter->setModel($document);
        $xml = $filter->toXml();

        // Set up XSLT-Stylesheet
        $xslt = new DomDocument;
        if (true === file_exists($this->view->getScriptPath('index') . '/' . $type->getName() . '.xslt')) {
            $template = $type->getName() . '.xslt';
        } else {
            $template = 'index.xslt';
        }
        $xslt->load($this->view->getScriptPath('index') . '/' . $template);

        // Set up XSLT-Processor
        $proc = new XSLTProcessor;
        $proc->registerPHPFunctions('Frontdoor_IndexController::translate');
        $proc->importStyleSheet($xslt);

        // Set Base-Url
        $baseUrl = $this->getRequest()->getBaseUrl();
        $this->view->baseUrl = $baseUrl;
        // Set Doctype
        $this->view->doctype('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"  "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">');

        // Set path for layout
        $registry = Zend_Registry::getInstance();
        $config = $registry->get('Zend_Config');
        $theme = 'default';
        if (true === isset($config->theme)) {
            $theme = $config->theme;
        }
        $layoutPath = $baseUrl .'/layouts/'. $theme;

        $proc->setParameter('', 'baseUrl', $baseUrl);
        $proc->setParameter('', 'layoutPath', $layoutPath);

        // Transform to HTML
        $this->view->frontdoor = $proc->transformToXML($xml);
    }

    /**
     * Gateway function to Zend's translation facilities.
     *
     * @param  string  $key The key of the string to translate.
     * @return string  The translated string.
     */
    static public function translate($key) {
        $registry = Zend_Registry::getInstance();
        $translate = $registry->get('Zend_Translate');
        return $translate->_($key);
    }

}
