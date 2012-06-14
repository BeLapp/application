<?php
/*
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
 * @category    Application Unit Test
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Base class for controller tests.
 */
class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase {

    /**
     * Method to initialize Zend_Application for each test.
     */
    public function setUp($applicationEnv = APPLICATION_ENV) {
        // Resetting singletons or other kinds of persistent objects.
        Opus_Db_TableGateway::clearInstances();

        // FIXME Does it help with the mystery bug?
        Zend_Registry::_unsetInstance();

        // Clean-up possible artifacts in $_SERVER of previous test.
        unset($_SERVER['REMOTE_ADDR']);

        $this->bootstrap = new Zend_Application(
            $applicationEnv,
            array(
                "config" => array(
                    APPLICATION_PATH . '/application/configs/application.ini',
                    APPLICATION_PATH . '/tests/tests.ini',
                    APPLICATION_PATH . '/tests/config.ini'
                )
            )
        );

        // added to ensure that application log messages are written to opus.log when running unit tests
        // if not set messages are written to opus-console.log
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';

        parent::setUp();
    }

    /**
     * Clean up database instances.
     */
    protected function tearDown() {
        $this->logoutUser();

        parent::tearDown();
    }

    /**
     * Method to check response for "bad" strings.
     */
    protected function checkForCustomBadStringsInHtml($body, array $badStrings) {
        $bodyLowerCase = strtolower($body);
        foreach ($badStrings AS $badString)
            $this->assertNotContains(
                strtolower($badString),
                $bodyLowerCase,
                "Response must not contain '$badString'");
    }

    /**
     * Method to check response for "bad" strings.
     */
    protected function checkForBadStringsInHtml($body) {
        $badStrings = array("Exception", "Error", "Fehler", "Stacktrace", "badVerb");
        $this->checkForCustomBadStringsInHtml($body, $badStrings);
    }

    /**
     * Login user.
     * 
     * @param string $login
     * @param string $password
     */
    public function loginUser($login, $password) {
        $adapter = new Opus_Security_AuthAdapter();
        $adapter->setCredentials($login, $password);
        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($adapter);
        $this->assertTrue($auth->hasIdentity());
    }

    public function logoutUser() {
        $instance = Zend_Auth::getInstance();
        if (!is_null($instance)) {
            $instance->clearIdentity();
        }
    }

    /**
     * Check if Solr-Config is given, otherwise skip the tests.
     */
    protected function requireSolrConfig() {
        $config = Zend_Registry::get('Zend_Config');
        if (!isset($config->searchengine->index->host) ||
            !isset($config->searchengine->index->port) ||
            !isset($config->searchengine->index->app)) {
            $this->markTestSkipped('No solr-config given.  Skipping test.');
        }
    }

    /**
     *
     * @param Zend_Controller_Response_Abstract $response
     * @param string $location
     */
    protected function assertResponseLocationHeader($response, $location) {
        $locationActual = null;
        foreach ($response->getHeaders() as $header) {
            if ($header['name'] === 'Location') {
                $locationActual = $header['value'];
            }
        }
        $this->assertNotNull($locationActual);
        $this->assertEquals($location, $locationActual);
    }
}