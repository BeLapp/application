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
 * @package     Module_Publish Unit Test
 * @author      Susanne Gottwald <gottwald@zib.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Publish_Model_ValidationTest extends ControllerTestCase{

    public function testValidationWithInvalidDatatype() {
        $val = new Publish_Model_Validation('Irgendwas');
        $val->validate();

        $this->assertType('array', $val->validator);
    }

    public function testValidationWithCollectionWithoutCollectionRole() {
        $val = new Publish_Model_Validation('Collection');
        $val->validate();
        $validator = $val->validator[0];

        $this->assertNull($validator);

    }

    public function testValidationWithDateDatatype() {
        $val = new Publish_Model_Validation('Date');
        $val->validate();
        $validator = $val->validator[0];

        $this->assertType('Zend_Validate_Date', $validator);

    }

    public function testValidationWithEmailDatatype() {
        $val = new Publish_Model_Validation('Email');
        $val->validate();
        $validator = $val->validator[0];

        $this->assertType('Zend_Validate_EmailAddress', $validator);

    }

    public function testValidationWithEnrichmentDatatype() {
        $val = new Publish_Model_Validation('Enrichment');
        $val->validate();
        $validator = $val->validator[0];

        $this->assertNull($val->validator);

    }

    public function testValidationWithIntegerDatatype() {
        $val = new Publish_Model_Validation('Integer');
        $val->validate();
        $validator = $val->validator[0];

        $this->assertType('Zend_Validate_Int', $validator);

    }

    public function testValidationWithLanguageDatatype() {
        $val = new Publish_Model_Validation('Language');
        $val->validate();
        $validator = $val->validator[0];

        $this->assertType('Zend_Validate_InArray', $validator);
    }

    public function testValidationWithLicenceDatatype() {
        $val = new Publish_Model_Validation('Licence');
        $val->validate();
        $validator = $val->validator[0];

        $this->assertType('Zend_Validate_InArray', $validator);
    }

    public function testValidationWithListDatatype() {
        $options = array();
        $options['eins'] = 'eins';
        $options['zwei'] = 'zwei';

        $val = new Publish_Model_Validation('List', '', $options);
        $val->validate();
        $validator = $val->validator[0];

        $this->assertType('Zend_Validate_InArray', $validator);
    }

    public function testValidationWithTextDatatype() {
        $val = new Publish_Model_Validation('Text');
        $val->validate();

        $this->assertNull($val->validator);
    }

    public function testValidationWithThesisGrantorDatatype() {
        $val = new Publish_Model_Validation('ThesisGrantor');
        $val->validate();
        $validator = $val->validator[0];

        $this->assertType('Zend_Validate_InArray', $validator);
    }

    public function testValidationWithThesisPublisherDatatype() {
        $val = new Publish_Model_Validation('ThesisPublisher');
        $val->validate();
        $validator = $val->validator[0];

        $this->assertType('Zend_Validate_InArray', $validator);
    }

    public function testValidationWithTitleDatatype() {
        $val = new Publish_Model_Validation('Title');
        $val->validate();

        $this->assertNull($val->validator);
    }

    public function testValidationWithYearDatatype() {
        $val = new Publish_Model_Validation('Year');
        $val->validate();
        $validator = $val->validator[0];

        $this->assertType('Zend_Validate_GreaterThan', $validator);
    }

    public function testSelectOptionsForInvalidDatatype() {
        $val = new Publish_Model_Validation('Irgendwas');
        $children = $val->selectOptions();

        $this->assertType('array', $val->validator);

    }

    public function testSelectOptionsForCollection() {
        $val = new Publish_Model_Validation('Collection', 'jel');
        $children = $val->selectOptions('Collection');

        $this->assertArrayHasKey('6720', $children);

    }

    public function testSelectOptionsForLanguage() {
        $val = new Publish_Model_Validation('Language');
        $children = $val->selectOptions();

        $this->assertArrayHasKey('deu', $children);

    }

    public function testSelectOptionsForLicence() {
        $val = new Publish_Model_Validation('Licence');
        $children = $val->selectOptions();

        $this->assertArrayHasKey('4', $children);
    }

    /**
     * Tests that the sort order of the licences in the publish form matches
     * the sort order provided from the database.
     */
    public function testSortOrderOfSelectOptionForLicence() {
        $licences = Opus_Licence::getAll();

        $activeLicences = array();

        foreach($licences as $licence) {
            if ($licence->getActive() == '1') {
                $activeLicences[] = $licence->getDisplayName();
            }
        }

        $val = new Publish_Model_Validation('Licence');
        $values = $val->selectOptions();

        $this->assertEquals( count($values), count($activeLicences));

        $pos = 0;

        foreach ($values as $name) {
            $this->assertEquals($name, $activeLicences[$pos]);
            $pos++;
        }
    }

    public function testSelectOptionsForList() {
        $options = array();
        $options['eins'] = 'eins';
        $options['zwei'] = 'zwei';

        $val = new Publish_Model_Validation('List', '', $options);
        $children = $val->selectOptions();

        $this->assertArrayHasKey('eins', $children);
    }

     public function testSelectOptionsForThesisGrantor() {
        $val = new Publish_Model_Validation('ThesisGrantor');
        $children = $val->selectOptions();

        $this->assertArrayHasKey('1', $children);

    }

    public function testSelectOptionsForThesisPublisher() {
        $val = new Publish_Model_Validation('ThesisPublisher');
        $children = $val->selectOptions();

        $this->assertArrayHasKey('2', $children);

    }
    
    public function testInvisibleCollectionRoleDDC() {
        $val = new Publish_Model_Validation('Collection', 'ddc');
        
        $collectionRole = Opus_CollectionRole::fetchByName($val->collectionRole);
        $visibleFlag = $collectionRole->getVisible();
        $collectionRole->setVisible(0);
        $collectionRole->store();
        
        $children = $val->selectOptions('Collection');        
        $this->assertNull($children);
        
        $collectionRole->setVisible($visibleFlag);
        $collectionRole->store();
        
    }
    
    public function testVisibleCollectionRoleDDC() {
        $val = new Publish_Model_Validation('Collection', 'ddc');
        
        $collectionRole = Opus_CollectionRole::fetchByName($val->collectionRole);
        $visibleFlag = $collectionRole->getVisible();
        $collectionRole->setVisible(1);
        $collectionRole->store();
        
        $children = $val->selectOptions('Collection');        
        $this->assertType('array', $children);                       
        $this->assertArrayHasKey('3', $children);
        
        $collectionRole->setVisible($visibleFlag);
        $collectionRole->store();
    }
    
    /**
     * Regression Test for Ticket https://wiki.kobv.de/jira/browse/OPUSVIER-2209           
     */     
    public function testNonExistingCollectionRole() {
        $collRole = 'irgendwas';
        $val = new Publish_Model_Validation('Collection', $collRole);
        
        $this->assertNull($val->selectOptions());
    }
    
    public function testVisibleSeries() {
        $val = new Publish_Model_Validation('Series');
        
        $children = $val->selectOptions('Series');        
        $this->assertType('array', $children);                       
        $this->assertArrayHasKey('4', $children);
        //series with title: Visible Series                
    }
    
     public function testInvisibleSeries() {
        $val = new Publish_Model_Validation('Series');
        
        $children = $val->selectOptions('Series');        
        $this->assertType('array', $children);                       
        $this->assertArrayNotHasKey('3', $children);
        //series with title: Invisible Series                
    }
    
    public function testSortOrderOfSeries() {
        $val = new Publish_Model_Validation('Series');
        $values = $val->selectOptions();
                
        $series = Opus_Series::getAllSortedBySortKey();

        $visibleSeries = array();

        foreach($series as $serie) {
            if ($serie->getVisible() == '1') {
                $visibleSeries[] = $serie->getTitle();
            }
        }

        $this->assertEquals( count($values), count($visibleSeries));

        $index = 0;
        foreach ($values as $name) {
            $this->assertEquals($name, $visibleSeries[$index]);
            $index++;
        }
                    
    }
    
}

