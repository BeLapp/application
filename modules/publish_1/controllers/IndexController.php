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
 * @package     Module_Publish
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: IndexController.php 4541 2010-07-15 11:12:28Z tklein $
 */

/**
 * Main entry point for this module.
 *
 * @category    Application
 * @package     Module_Publish
 */
class Publish_1_IndexController extends Controller_Action {
    /**
     * @todo: extends Zend_Controller_Action ausreichend?
     */
     
    /**
     * Renders a list of available document types.
     *
     * @return void
     *
     */
    public function indexAction() {
        // STEP 1
        $this->view->title = $this->view->translate('publish_controller_index');        
        
        //following 3 lines are for xml generating forms
        //$this->view->subtitle = $this->view->translate('publish_controller_step1');
        //$form = new PublishingFirst();
        //$action_url = $this->view->url(array('controller' => 'index', 'action' => 'step2'));

        $form = new Publishing();
        $action_url = $this->view->url(array('controller' => 'index', 'action' => 'check'));
        $form->setAction($action_url)
                ->setMethod('post')
                ->setDecorators(array('FormElements', array('Description', array('placement' => 'prepend','tag' => 'h2')), 'Form'));
        $this->view->form = $form;
    }

    /**
     * XML GENERATING FORMS
     * used to store the upload and doc type
     * build the form that depends on the doc type
     */
    public function step2Action() {
        $this->view->title = $this->view->translate('publish_controller_index');
        $this->view->subtitle = $this->view->translate('publish_controller_step2');        
        
        //create the step 1 form
        $step1Form = new PublishingFirst();
        
        //get the post data
        if ($this->getRequest()->isPost() === true) {
            $data = $this->getRequest()->getPost();
            $documentInSession = new Zend_Session_Namespace('document');
            if (!$step1Form->isValid($this->getRequest()->getPost())) {
                $this->view->form = $step1Form;
                //show errors, go back to index
                return $this->render('index');
            }
            
//            Auskommentiert für den Testlauf! FileUpload funzt nicht.
//            else {
//                // This works only from Zend 1.7 on
//                // 1) STORE THE UPLOAD!!!
//                try {
//                    $upload = new Zend_File_Transfer_Adapter_Http();
//                    $files = $upload->getFileInfo();
//                    $document = $documentInSession->document;
//
//                    $this->view->message = $this->view->translate('publish_controller_upload_successful');
//
//                    // one form has only one file
//                    $file = $files['fileupload'];
//                    $hash = null;
//                    $docfile = $document->addFile();
//                    $docfile->setDocumentId($document->getId());
//                    $docfile->setPathName($file['name']);
//                    $docfile->setMimeType($file['type']);
//                    $docfile->setTempFile($file['tmp_name']);
//                    $docfile->setFromPost($file);
//                    $document->store();
//                }
//                catch (Zend_File_Transfer_Exception $zfte) {
//                    $this->view->message = $zfte->getMessage();
//                }

                // 2) BUILD THE FORM THAT DEPENDS ON THE DOC TYPE
                $doctype = $data['type'];
                echo $doctype;
                $step2Form = new PublishingSecond($doctype, null);
                $action_url = $this->view->url(array('controller' => 'index', 'action' => 'check'));
                $step2Form->setAction($action_url);
                $step2Form->setMethod('post');
                $this->view->form = $step2Form;
            }                                                        
        }
    //}
       
    /**
     * displays and checks the publishing form contents and calls deposit to store the data
     * uses check_array
     * @return <type>
     */
    public function checkAction()
    {
        if ($this->getRequest()->isPost() === true)
        {
            //create form object
            //$form = new PublishingFirst();
            $form = new Publishing();
            //check if variables are valid
            if (!$form->isValid($this->getRequest()->getPost()))
            {
                $this->view->form = $form;
                //show errors
                return $this->render('index');
            }

            //summery the variables
            $this->view->title = $this->view->translate('publish_controller_check');

            //send form values to check view
            $formValues = $form->getValues();
            $this->view->formValues = $formValues;

            $type = $formValues['Type'];
            $document = new Opus_Document(null, $type, 'repository');
            //$docfile = $document->addFile();
//            $docfile->setDocumentId($document->getId());
//            $docfile->setPathName($file['name']);
//            $docfile->setMimeType($file['type']);
//            $docfile->setTempFile($file['tmp_name']);
//            $docfile->setFromPost($file);
            $docId = $document->store();

            //finally: deposit the data!
            $depositForm = new Publishing();
            $action_url = $this->view->url(array('controller' => 'index', 'action' => 'deposit'));
            $depositForm->setAction($action_url);
            $depositForm->setMethod('post');

            foreach ($formValues as $key => $value) {
                if ($key != 'send') {
                    $hidden = $depositForm->createElement('hidden', $key);
                    $hidden->setValue($value);
                    $depositForm->addElement($hidden);
                }
                else {
                    //do not send the field "send" with the form
                    $depositForm->removeElement('send');
                }
            }
            //send the created document id as hidden field
            $hiddenDocId = $depositForm->createElement('hidden', 'DocumentId');
            $hiddenDocId->setValue($docId);
            $deposit = $depositForm->createElement('submit', 'deposit');
            $depositForm->addElement($deposit)
                    ->addElement($hiddenDocId);

            //send form to view
            $this->view->form = $depositForm;
        }
    }
       
    /**
     * stores a delivered form as document in the database
     * uses check_array
     */
    public function depositAction()
    {
        $this->view->title = $this->view->translate('publish_controller_index');
        $this->view->subtitle = $this->view->translate('publish_controller_deposit_successful');
        
        if ($this->getRequest()->isPost() === true)
        {
            $formValues = $this->getRequest()->getPost();
            $documentId = $formValues['DocumentId'];
            //tape already stored
            //$Type = $formValues['Type'];
            $TitleMain = $formValues['TitleMain'];
            $FirstName = $formValues['AuthorFirstName'];
            $LastName = $formValues['AuthorLastName'];
            $PublishedYear = $formValues['PublishedYear'];
            $TitleAbstract = $formValues['TitleAbstract'];
            
            $document = new Opus_Document($documentId);

            $TitleMainAdd = $document->addTitleMain();
            $TitleMainAdd->setValue($TitleMain);

            $TitleAbstracAdd = $document->addTitleAbstract();
            $TitleAbstracAdd->setValue($TitleAbstract);

            $document->setPublishedYear($PublishedYear);

            $person = new Opus_Person();
            $person->setFirstName($FirstName);
            $person->setLastName($LastName);
            $document->addPersonAuthor($person);

            $document->store();
        }
    }

    
    /**
     * Show form for key upload and do the key upload
     *
     * @return void
     *
     */
    public function keyuploadAction() {
        $this->view->title = $this->view->translate('publish_controller_keyupload');
        $form = new KeyUpload();
        $action_url = $this->view->url(array('controller' => 'index', 'action' => 'create'));
        $form->setAction($action_url);
        $this->view->form = $form;
    }

    /**
     * Returns a filtered representation of the document.
     *
     * @param  Opus_Document  $document The document to be filtered.
     * @return Opus_Model_Filter The filtered document.
     */
    private function __createFilter(Opus_Document $document, $page = null) {
        $filter = new Opus_Model_Filter();
        $filter->setModel($document);
        $type = new Opus_Document_Type($document->getType());
        $pages = $type->getPages();
        $alwayshidden = array('IdentifierOpus3', 'Type', 'ServerState', 'ServerDateModified', 'ServerDatePublished', 'File');
        $blacklist = array_merge($alwayshidden, $type->getPublishFormBlackList());
        if (false === is_null($page) and true === array_key_exists($page, $pages)) {
            $filter->setWhitelist(array_diff($pages[$page]['fields'], $blacklist));
        } else {
            $filter->setBlacklist($blacklist);
        }
        $filter->setSortOrder($type->getPublishFormSortOrder());
        return $filter;
    }

    /**
     * Returns whether a given page is defined for a document.
     *
     * @param  Opus_Document  $document The document to check.
     * @param  mixed          $page     The page to check for.
     * @return boolean  Whether the page is defined.
     */
    private function __pageExists(Opus_Document $document, $page) {
        $type = new Opus_Document_Type($document->getType());
        $pages = $type->getPages();
        return array_key_exists($page, $pages);
    }

    /**
     * Create, recreate and validate a document form. If it is valid store it.
     *
     * @return void
     */
    public function createAction() {
        $this->view->title = $this->view->translate('publish_controller_create');

        if ($this->_request->isPost() === true) {
            $requested_page = $this->_request->getParam('page');
            $backlink = $this->_request->getParam('back');
            if (false === is_null($backlink)) {
                $requested_page--;
            }
            $data = $this->_request->getPost();
            $form_builder = new Form_Builder();
            $documentInSession = new Zend_Session_Namespace('document');
            if (array_key_exists('gpg_key_upload', $data) === true) {
            	$alternateForm = new KeyUpload();
            	// upload key
                if ($alternateForm->isValid($data) === true) {
                    $gpg = new Opus_GPG();

                    $upload = new Zend_File_Transfer_Adapter_Http();
                    $files = $upload->getFileInfo();

                    // save the file
                    foreach ($files as $file) {
                        $gpg->importKeyFile($file['tmp_name']);
                    }
                }
                
                // build first page of publishing form
                $selectedDoctype = $documentInSession->doctype;
                $document = $documentInSession->document;
                
                $caption = 'publish_index_create_' . $selectedDoctype;
                $action_url = $this->view->url(array('controller' => 'index', 'action' => 'create', 'page' => 1));

                $createForm = $form_builder->build($this->__createFilter($document, 0));
                $createForm->setAction($action_url);
                $createForm->setDecorators(array('FormElements', array('Description', array('placement' => 'prepend','tag' => 'h2')), 'Form'));
                $this->view->form = $createForm;
                return;
            }
            if (array_key_exists('selecttype', $data) === true) {
                // validate document type
                $form = new Overview();
                if ($form->isValid($data) === true) {
                    $possibleDoctypes = Opus_Document_Type::getAvailableTypeNames();
                    $selectedDoctype = $form->getValue('selecttype');
                    if ($selectedDoctype !== $documentInSession->doctype && isset($selectedDoctype) === true) {
                        $documentInSession->doctype = $selectedDoctype;
                    }
                    else {
                        $selectedDoctype = $documentInSession->doctype;
                    }
                    if (in_array($selectedDoctype, $possibleDoctypes) === false) {
                        // TODO: error message
                        // document type does not exists, back to select form
                        $this->_redirectTo($this->view->translate('choose_valid_doctype'),
                            'deposit', 'index', 'publish');
                    }

                    // Store document in session
                    $document = new Opus_Document(null, $selectedDoctype);
                    // this only works if authentication method is ldap
                    $config = new Zend_Config_Ini('../config/config.ini', 'production');
        
                    if ($config->authenticationModule === 'Ldap') {
                        $userdata = Opus_Security_AuthAdapter_Ldap::getUserdata();
                        if (isset($userdata['lastName']) === true) {
                    	    $loggedinPerson = new Opus_Person();
                    	    $loggedinPerson->setFirstName($userdata['firstName']);
                    	    $loggedinPerson->setLastName($userdata['lastName']);
                    	    $loggedinPerson->setEmail($userdata['email']);
                    	    $identifier = new Opus_Person_ExternalKey();
                    	    $identifier->setValue($userdata['personId']);
                    	    $loggedinPerson->setIdentifierLocal($identifier);
                    	    try {
                    	        $document->addPersonSubmitter($loggedinPerson);
                    	    }
                    	    catch (Exception $e) {
                    		    if ($e->getCode === 404) {
                    			    //do nothing, submitter field is not allowed
                    		    }
                    	    }
                        }
                    }
                    $documentInSession->document = $document;

                    // If author selected that he has a GPG-Key, show Uploadform
                    if (array_key_exists('gpgkey', $data) === true) {
                        $gpgkey = $data['gpgkey'];
                        if ($gpgkey === '1') {
                            $documentInSession->keyupload = true;
                            $alternateForm = new KeyUpload();
                            $this->view->form = $alternateForm;
                            return;
                        }
                    }

                    $caption = 'publish_index_create_' . $selectedDoctype;
                    $action_url = $this->view->url(array('controller' => 'index', 'action' => 'create', 'page' => 1));

                    $createForm = $form_builder->build($this->__createFilter($document, 0));
                    $createForm->setAction($action_url);
                    $createForm->setDecorators(array('FormElements', array('Description', array('placement' => 'prepend','tag' => 'h2')), 'Form'));
                    $this->view->form = $createForm;
                } else {
                    // submitted form data is not valid, back to select form
                    $this->view->form = $form;
                }
            } else if (false === array_key_exists('submit', $data)) {
                if (true === array_key_exists('Opus_Model_Filter', $data)) {
                    $form_builder->buildModelFromPostData($documentInSession->document, $data['Opus_Model_Filter']);
                }
                $form = $form_builder->build($this->__createFilter($documentInSession->document, $requested_page - 1));
                $action_url = $this->view->url(array('controller' => 'index', 'action' => 'create', 'page' => $requested_page));
                $form->setAction($action_url);
                if (0 < $requested_page - 1) {
                    $backButton = new Zend_Form_Element_Submit('back');
                    $form->addElement($backButton);
                }
                $this->view->form = $form;
            } else if (false === is_null($requested_page) and true === $this->__pageExists($documentInSession->document, $requested_page)) {
                // if Opus_Model_filter does not exist, an empty form has been submitted
                // its not necessary to store new data to the model
                if (true === array_key_exists('Opus_Model_Filter', $data)) {
                    $form_builder->buildModelFromPostData($documentInSession->document, $data['Opus_Model_Filter']);
                }
                $form = $form_builder->build($this->__createFilter($documentInSession->document, $requested_page - 1));
                if (true === $form->isValid($data)) {
                    $deliver_page = $requested_page + 1;
                    $action_url = $this->view->url(array('controller' => 'index', 'action' => 'create', 'page' => $deliver_page));
                    $form = $form_builder->build($this->__createFilter($documentInSession->document, $requested_page));
                } else {
                    $deliver_page = $requested_page;
                    $action_url = $this->view->url(array('controller' => 'index', 'action' => 'create', 'page' => $deliver_page));
                }
                $form->setAction($action_url);
                if ($deliver_page > 1) {
                    $backButton = new Zend_Form_Element_Submit('back');
                    $form->addElement($backButton);
                }
                $this->view->form = $form;
            } else {
                // if Opus_Model_filter does not exist, an empty form has been submitted
                // its not necessary to store new data to the model
                if (true === array_key_exists('Opus_Model_Filter', $data)) {
                    $form_builder->buildModelFromPostData($documentInSession->document, $data['Opus_Model_Filter']);
                }
                $form = $form_builder->build($this->__createFilter($documentInSession->document, $requested_page - 1));
                if ($form->isValid($data) === true) {
                    // go ahead to summary
                    $this->view->document_data = $documentInSession->document->toArray();
                    $this->view->title = $this->view->translate('publish_controller_summary');
                    $summaryForm = new Summary();
                    $action_url = $this->view->url(array('controller' => 'index', 'action' => 'summary'));
                    $summaryForm->setAction($action_url);
                    $this->view->form = $summaryForm;
                    $backLinkForm = new Zend_Form;
                    $backLinkUrl = $this->view->url(array('controller' => 'index', 'action' => 'create', 'page' => $requested_page + 1));
                    $backLinkForm->setAction($backLinkUrl);
                    $backButton = new Zend_Form_Element_Submit('back');
                    $backLinkForm->addElement($backButton);
                    $this->view->backLinkForm = $backLinkForm;
                } else {
                    $this->view->form = $form;
                }
            }
        } else {
            // action used directly go back to main index
            $this->_redirectTo('index');
        }
    }

    public function summaryAction() {
        //$documentInSession = new Zend_Session_Namespace('document');
        $this->view->title = $this->view->translate('publish_controller_summary');
        $backUrl = $this->view->url(array('module' => 'publish', 'controller' => 'index', 'action' => 'index'), null, false);
        $this->view->backlink = "<a href='$backUrl'>" . $this->view->translate('upload_another_publication') . "</a>";
        if ($this->_request->isPost() === true) {
            $summaryForm = new Summary();
            $postdata = $this->_request->getPost();
            if ($summaryForm->isValid($postdata) === true) {
                $form_builder = new Form_Builder();
                $document = $documentInSession->document;
                if (array_key_exists('submit', $postdata) === true) {
                    // type is stored in serialized model as a string only
                    // to validate document it must be a Document_Type
                    $type = new Opus_Document_Type($document->getType());
                    $document->setType($type);
                    $id = $document->store();
                    $this->view->title = $this->view->translate('publish_controller_upload');
                    $uploadForm = new FileUpload();
                    if (false === is_null($document->getField('File'))) {
                        $action_url = $this->view->url(array('controller' => 'index', 'action' => 'upload'));
                        $uploadForm->setAction($action_url);
                        // TODO: Security save id to session not to form
                        // Actually it is possible to add Files to every document for everybody!
                        $uploadForm->DocumentId->setValue($id);
                        $this->view->form = $uploadForm;
                    }
                } else {
                    // invalid form return to index
                    $this->_redirectTo('index');
                }
            } else {
                // invalid form return to index
                $this->_redirectTo('index');
            }
        } else {
            // on non post request redirect to index action
            $this->_redirectTo('index');
        }
    }

    /**
     * Create form and handling file uploading
     *
     * @return void
     */
    public function uploadAction() {
        $this->view->title = $this->view->translate('publish_controller_upload');
        $backUrl = $this->view->url(array('module' => 'publish', 'controller' => 'index', 'action' => 'create'), null, false);
        $this->view->backlink = "<a href='$backUrl'>" . $this->view->translate('upload_another_publication') . "</a>";
        $uploadForm = new FileUpload();
        $action_url = $this->view->url(array('controller' => 'index', 'action' => 'upload'));
        $uploadForm->setAction($action_url);
        $documentInSession = new Zend_Session_Namespace('document');
        // store uploaded data in application temp dir
        if ($this->_request->isPost() === true) {
            $data = $this->_request->getPost();
            if ($uploadForm->isValid($data) === true) {
                // This works only from Zend 1.7 on
                // $upload = $uploadForm->getTransferAdapter();
                try {
                    $upload = new Zend_File_Transfer_Adapter_Http();
                    $files = $upload->getFileInfo();
                    $document = $documentInSession->document;

                    $this->view->message = $this->view->translate('publish_controller_upload_successful');

                    // one form has only one file
                    $file = $files['fileupload'];
                    $hash = null;
                    if (array_key_exists('sigupload', $files) === true) {
                        $sigfile = $files['sigupload'];
                    }

                    /*
                     * if (!$upload->isValid($file)) {
                     *   $this->view->message = 'Upload failed: Not a valid file or no file submitted!';
                     *   break;
                     * }
                     */

                    $docfile = $document->addFile();
                    $docfile->setDocumentId($document->getId());
                    $docfile->setLabel($uploadForm->getValue('comment'));
                    $docfile->setLanguage($uploadForm->getValue('language'));
                    $docfile->setPathName($file['name']);
                    $docfile->setMimeType($file['type']);
                    $docfile->setTempFile($file['tmp_name']);
                    $docfile->setFromPost($file);
                    if (array_key_exists('sigupload', $files) === true) {
                        $signature = implode("", file($sigfile['tmp_name']));
                        $hash = $docfile->addHashValue();
                        $hash->setType('gpg-0');
                        $hash->setValue($signature);
                    }
                    $document->store();
                }
                catch (Zend_File_Transfer_Exception $zfte) {
                    $this->view->message = $zfte->getMessage();
                }

                // reset input values fo re-displaying
                $uploadForm->reset();
                // re-insert document id
                $uploadForm->DocumentId->setValue($document->getId());
                $this->view->form = $uploadForm;
            } else {
                // invalid form, populate with transmitted data
                $uploadForm->populate($data);
                $this->view->form = $uploadForm;
            }
        } else {
            // on non post request redirect to index action
            if (false === is_null($documentInSession->document)) {
                if (false === is_null($documentInSession->document->getField('File'))) {
                    $this->view->form = $uploadForm;
                }
            } else {
                $this->_redirectTo('index');
            }
        }
    }

    /**
     * Assign a document to a collection
     *
     * @return void
     */
    public function assignAction() {
        $documentInSession = new Zend_Session_Namespace('document');
        $document = $documentInSession->document;
        $documentId = $document->getId();
        $role = $this->getRequest()->getParam('role');
        $path = $this->getRequest()->getParam('path');
        if ($this->_request->isPost() === true) {
            $collection = new Opus_CollectionRole($role);
            $roleName = $collection->getDisplayName();
            if (true === isset($path)) {
                $trail = explode('-', $path);
                foreach($trail as $i => $step) {
                    if ($i < sizeof($trail)) {
                        $collections = $collection->getSubCollection();
                        $collection = $collections[$step];
                    }
                }
            }
            // collections contains only one collection, but this is an array
            $collection->addDocuments($document);
            $collection->store();
            $this->_redirectTo('Document successfully assigned to collection "' . $collection->getDisplayName() . '".'
                    , 'upload', 'index', 'publish');
        } else if (false === isset($role)) {
            $collections = array();
            foreach (Opus_CollectionRole::fetchAll() as $collection) {
                $collections[$collection->getId()] = $collection->getDisplayName();
            }
            $this->view->subcollections = $collections;
            $this->view->breadcrumb = array();
            $this->view->assign = $documentId;
            $this->view->role_id = null;
        } else {
            $collection = new Opus_CollectionRole($role);
            $roleName = $collection->getDisplayName();
            $subcollections = array();
            $breadcrumb = array();
            if (true === isset($path)) {
                $trail = explode('-', $path);
                foreach($trail as $step) {
                    if (false === isset($position)) {
                        $position = $step;
                    } else {
                        $position .= '-' . $step;
                    }
                    $collections = $collection->getSubCollection();
                    $collection = $collections[$step];
                    $breadcrumb[$position] = $collection->getDisplayName();
                }
            }
            if ($collection instanceof Opus_CollectionRole) {
                foreach($collection->getSubCollection() as $i => $subcollection) {
                    $subcollections[$i] = $subcollection->getDisplayName();
                }
            } else {
                foreach($collection->getSubCollection() as $i => $subcollection) {
                    $subcollections[$path . '-' . $i] = $subcollection->getDisplayName();
                }
            }
            $this->view->subcollections = $subcollections;
            $this->view->role_id = $role;
            $this->view->role_name = $roleName;
            $this->view->path = $path;
            $this->view->assign = $documentId;
            $this->view->breadcrumb = $breadcrumb;
        }
    }
}
