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
 * @package     Module_Account
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Controller for editing account of logged in user.
 */
class Account_IndexController extends Controller_Action {

    /**
     * Custom access check to be called by parent class.  Returns the value of
     * config key "account.editOwnAccount" if set; false otherwise.
     *
     * @return boolean
     */
    protected function customAccessCheck() {
        $parentValue =  parent::customAccessCheck();

        $config = Zend_Registry::get('Zend_Config');
        if (!isset($config) or !isset($config->account->editOwnAccount)) {
            return false;
        }

        return $parentValue and $config->account->editOwnAccount;
    }

    /**
     * Show account form for logged in user.
     */
    public function indexAction() {
        $login = Zend_Auth::getInstance()->getIdentity();

        if (!empty($login)) {
            $accountForm = new Account_Form_Account($login);

            $actionUrl = $this->view->url(array('action' => 'save'));

            $accountForm->setAction($actionUrl);

            $this->view->accountForm = $accountForm;
        }
        else {
            $params = $this->_helper->returnParams->getReturnParameters();
            $this->_helper->redirector->gotoSimple('index', 'auth', 'default', $params);
        }
    }

    /**
     * Save account information.
     * @return <type>
     */
    public function saveAction() {
        $login = Zend_Auth::getInstance()->getIdentity();
        $config = Zend_Registry::get('Zend_Config');
        $logger = $this->getLogger();

        if (!empty($login) && $this->getRequest()->isPost()) {
            $accountForm = new Account_Form_Account($login);

            $postData = $this->getRequest()->getPost();

            $isPasswordChanged = true;

            if (empty($postData['password'])) {
                // modify to pass default validation
                // TODO think about better solution
                $postData['password'] = 'notchanged';
                $postData['confirmPassword'] = 'notchanged';
                $isPasswordChanged = false;
            }

            // check if username was provided and if it may be changed
            if (!isset($postData['username'])
                    || (isset($config->account->editPasswordOnly) && $config->account->editPasswordOnly)
                    || (isset($config->account->changeLogin) && !$config->account->changeLogin)) {
                $postData['username'] = $login;
            }

            $postData['oldLogin'] = $login;

            if ($accountForm->isValid($postData)) {
                $account = new Opus_Account(null, null, $login);

                $newLogin = $postData['username'];
                $password = $postData['password'];
                $firstname = $postData['firstname'];
                $lastname = $postData['lastname'];
                $email = $postData['email'];

                $isLoginChanged = false;

                if (isset($config->account->editPasswordOnly) && !$config->account->editPasswordOnly) {
                    $account->setFirstName($firstname);
                    $account->setLastName($lastname);
                    $account->setEmail($email);

                    $logger->debug('login = ' . $login);
                    $logger->debug('new login = ' . $newLogin);

                    $isLoginChanged = ($login == $newLogin) ? false : true;

                    if ($isLoginChanged && ($login !== 'admin')) {
                        $logger->debug('login changed');
                        $account->setLogin($newLogin);
                    }
                }

                if ($isPasswordChanged) {
                    $logger->debug('Password changed');
                    $account->setPassword($password);
                }

                $account->store();

                if ($isLoginChanged || $isPasswordChanged) {
                    Zend_Auth::getInstance()->clearIdentity();
                }
            }
            else {
                $actionUrl = $this->view->url(array('action' => 'save'));
                $accountForm->setAction($actionUrl);
                $this->view->accountForm = $accountForm;
                return $this->renderScript('index/index.phtml');
            }
        }

        $this->_helper->redirector('index');
    }

}

