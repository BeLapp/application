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
 * @package     Tests
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Oai_Model_ContainerTest extends ControllerTestCase {

    private $workspacePath;

    public function  setUp() {
        parent::setUp();
        $config = Zend_Registry::get('Zend_Config');
        if (!isset($config->workspacePath)) {
            throw new Exception("config key 'workspacePath' not defined in config file");
        }
        $this->workspacePath = $config->workspacePath;        
    }

    public function testConstructorWithNullArgument() {
        $model = null;
        try {
            $model = new Oai_Model_Container(null);
        }
        catch (Oai_Model_Exception $e) {
            $this->assertEquals('missing parameter docId', $e->getMessage());
        }
        $this->assertTrue(is_null($model));
    }

    public function testConstructorWithInvalidArgument() {
        $model = null;
        try {
            $model = new Oai_Model_Container('foo');
        }
        catch (Oai_Model_Exception $e) {
            $this->assertEquals('invalid value for parameter docId', $e->getMessage());
        }
        $this->assertTrue(is_null($model));
    }

    public function testConstructorWithUnknownDocId() {
        $model = null;
        try {
            $model = new Oai_Model_Container('123456789');
        }
        catch (Oai_Model_Exception $e) {
            $this->assertEquals('requested docId does not exist', $e->getMessage());
        }
        $this->assertTrue(is_null($model));
    }

    public function testConstructorWithUnublishedDocument() {
        $r = Opus_UserRole::fetchByName('guest');

        $modules = $r->listAccessModules();
        $addOaiModuleAccess = !in_array('oai', $modules);
        if ($addOaiModuleAccess) {
            $r->appendAccessModule('oai');
            $r->store();
        }

        // enable security
        $config = Zend_Registry::get('Zend_Config');
        $security = $config->security;
        $config->security = '1';
        Zend_Registry::set('Zend_Config', $config);
        
        $doc = $this->createTestDocument();
        $doc->setServerState('unpublished');
        $doc->store();

        $model = new Oai_Model_Container($doc->getId());
        $tarball = null;
        try {
            $tarball = $model->getFileHandle();
        }
        catch (Oai_Model_Exception $e) {
            $this->assertEquals('access to requested document is forbidden', $e->getMessage());
        }
        $this->assertTrue(is_null($tarball));

        // cleanup
        $doc->deletePermanent();
        
        if ($addOaiModuleAccess) {
            $r->removeAccessModule('oai');
            $r->store();
        }

        // restore security settings
        $config->security = $security;
        Zend_Registry::set('Zend_Config', $config);
    }

    public function testConstructorWithPublishedDocumentWithoutAnyFiles() {
        $doc = $this->createTestDocument();
        $doc->setServerState('published');
        $doc->store();

        $model = new Oai_Model_Container($doc->getId());
        $tarball = null;
        try {
            $tarball = $model->getFileHandle();
        }
        catch (Oai_Model_Exception $e) {
            $this->assertEquals('requested document does not have any associated readable files', $e->getMessage());
        }
        $this->assertTrue(is_null($tarball));

        // cleanup
        $doc->deletePermanent();
    }

    public function testFunctionGetName() {
        $doc = $this->createTestDocument();
        $doc->setServerState('published');
        $file = new Opus_File();
        $file->setPathName('foo.pdf');
        $file->setVisibleInOai(false);
        $doc->addFile($file);
        $doc->store();

        $container = new Oai_Model_Container($doc->getId());
        $this->assertEquals($doc->getId(), $container->getName());

        // cleanup
        $doc->deletePermanent();
    }

    public function testDocumentWithRestrictedFile() {
        $filepath = $this->createTestFile('foo.pdf');

        $doc = $this->createTestDocument();
        $doc->setServerState('published');
        $file = new Opus_File();
        $file->setPathName(basename($filepath));
        $file->setTempFile($filepath);
        $file->setVisibleInOai(false);
        $doc->addFile($file);
        $doc->store();

        $this->assertTrue(is_readable($this->workspacePath . '/files/' . $doc->getId() . '/' . basename($filepath)));

        $model = new Oai_Model_Container($doc->getId());
        $tarball = null;
        try {
            $tarball = $model->getFileHandle();
        }
        catch (Oai_Model_Exception $e) {
            $this->assertEquals('access denied on all files that are associated to the requested document', $e->getMessage());
        }
        $this->assertTrue(is_null($tarball));

        // cleanup
        $doc->deletePermanent();
        Opus_Util_File::deleteDirectory(dirname($filepath));
    }

    public function testDocumentWithNonExistentFile() {       
        $doc = $this->createTestDocument();
        $doc->setServerState('published');
        $file = new Opus_File();
        $file->setPathName('test.pdf');
        $file->setVisibleInOai(true);
        $doc->addFile($file);
        $doc->store();

        $model = new Oai_Model_Container($doc->getId());
        $tarball = null;
        try {
            $tarball = $model->getFileHandle();
        }
        catch (Oai_Model_Exception $e) {
            $this->assertEquals('requested document does not have any associated readable files', $e->getMessage());
        }
        $this->assertTrue(is_null($tarball));

        //cleanup
        $doc->deletePermanent();
    }

    public function testDocumentWithSingleUnrestrictedFile() {
        $filepath = $this->createTestFile('test.txt');

        $doc = $this->createTestDocument();
        $doc->setServerState('published');
        $file = new Opus_File();
        $file->setPathName(basename($filepath));
        $file->setTempFile($filepath);
        $file->setVisibleInOai(true);
        $doc->addFile($file);
        $doc->store();

        $this->assertTrue(is_readable($this->workspacePath . '/files/' . $doc->getId() . '/' . basename($filepath)));

        $model = new Oai_Model_Container($doc->getId());
        $file = $model->getFileHandle();
        $this->assertTrue(is_readable($file->getPath()));
        $this->assertEquals('.txt', $file->getExtension());
        // TODO OPUSVIER-2503
        $this->assertTrue($file->getMimeType() == 'application/x-empty' || $file->getMimeType() == 'inode/x-empty');

        // cleanup
        $doc->deletePermanent();
        Opus_Util_File::deleteDirectory(dirname($filepath));
        unlink($file->getPath());
    }

    public function testDocumentWithTwoUnrestrictedFiles() {
        $filepath1 = $this->createTestFile('foo.pdf');
        $filepath2 = $this->createTestFile('bar.pdf');        

        $doc = $this->createTestDocument();
        $doc->setServerState('published');
        $file = new Opus_File();
        $file->setPathName(basename($filepath1));
        $file->setTempFile($filepath1);
        $file->setVisibleInOai(true);
        $doc->addFile($file);
        $file = new Opus_File();
        $file->setPathName(basename($filepath2));
        $file->setTempFile($filepath2);
        $file->setVisibleInOai(true);
        $doc->addFile($file);
        $doc->store();

        $this->assertTrue(is_readable($this->workspacePath . '/files/' . $doc->getId() . '/' . basename($filepath1)));
        $this->assertTrue(is_readable($this->workspacePath . '/files/' . $doc->getId() . '/' . basename($filepath2)));

        $model = new Oai_Model_Container($doc->getId());
        $file = $model->getFileHandle();
        $this->assertTrue(is_readable($file->getPath()));
        $this->assertEquals('.tar', $file->getExtension());
        $this->assertEquals('application/x-tar', $file->getMimeType());

        // cleanup
        $doc->deletePermanent();
        Opus_Util_File::deleteDirectory(dirname($filepath1));
        Opus_Util_File::deleteDirectory(dirname($filepath2));
        unlink($file->getPath());
        
    }

    public function testDeleteContainerTarFile() {
        $filepath1 = $this->createTestFile('test.pdf');
        $filepath2 = $this->createTestFile('foo.html');

        $doc = $this->createTestDocument();
        $doc->setServerState('published');
        $file = new Opus_File();
        $file->setPathName(basename($filepath1));
        $file->setTempFile($filepath1);
        $file->setVisibleInOai(true);
        $doc->addFile($file);
        $file = new Opus_File();
        $file->setPathName(basename($filepath2));
        $file->setTempFile($filepath2);
        $file->setVisibleInOai(true);
        $doc->addFile($file);
        $doc->store();

        $this->assertTrue(is_readable($this->workspacePath . '/files/' . $doc->getId() . '/' . basename($filepath1)));
        $this->assertTrue(is_readable($this->workspacePath . '/files/' . $doc->getId() . '/' . basename($filepath2)));

        $model = new Oai_Model_Container($doc->getId());
        $tarball = $model->getFileHandle();
        $this->assertTrue(is_readable($tarball->getPath()));

        $tarball->delete();
        $this->assertFalse(file_exists($tarball->getPath()));

        // cleanup
        $doc->deletePermanent();
        Opus_Util_File::deleteDirectory(dirname($filepath1));
        Opus_Util_File::deleteDirectory(dirname($filepath2));
    }

    public function testDeleteContainerSingleFile() {
        $filepath1 = $this->createTestFile('test.pdf');

        $doc = $this->createTestDocument();
        $doc->setServerState('published');
        $file = new Opus_File();
        $file->setPathName(basename($filepath1));
        $file->setTempFile($filepath1);
        $file->setVisibleInOai(true);
        $doc->addFile($file);
        $doc->store();

        $this->assertTrue(is_readable($this->workspacePath . '/files/' . $doc->getId() . '/' . basename($filepath1)));

        $model = new Oai_Model_Container($doc->getId());
        $tarball = $model->getFileHandle();
        $this->assertTrue(is_readable($tarball->getPath()));

        $tarball->delete();
        $this->assertFalse(file_exists($tarball->getPath()));

        // cleanup
        $doc->deletePermanent();
        Opus_Util_File::deleteDirectory(dirname($filepath1));
    }

    private function createTestFile($filename) {        
        $path = $this->workspacePath . DIRECTORY_SEPARATOR . uniqid();
        mkdir($path, 0777, true);
        $filepath = $path . DIRECTORY_SEPARATOR . $filename;
        touch($filepath);
        $this->assertTrue(is_readable($filepath));
        return $filepath;
    }

}
