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
 * @category    Unit Tests
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Unit Tests fuer die Ausgabe des Hauptmenues.
 */
class MainMenuTest extends ControllerTestCase {
    
    function testAdminIndex() {
        $this->dispatch("/admin");
        $this->assertQuery('li.active[@id="primary-nav-administration"]', "Admin Eintrag sollte aktiviert sein.");
    }
    
    function testAdminDocuments() {
        $this->dispatch("/admin/documents");
        $this->assertQuery('li.active[@id="primary-nav-administration"]', "Admin Eintrag sollte aktiviert sein.");
    }
    
    function testAdminDocument() {
        $this->dispatch("/admin/document/index/id/146");
        $this->assertQuery('li.active[@id="primary-nav-administration"]', "Admin Eintrag sollte aktiviert sein.");
    }
    
    function testAdminFileManager() {
        $this->dispatch("/admin/filemanager/index/docId/146");
        $this->assertQuery('li.active[@id="primary-nav-administration"]', "Admin Eintrag sollte aktiviert sein.");
    }
    
    function testAdminReview() {
        $this->dispatch("/review");
        $this->assertQuery('li.active[@id="primary-nav-administration"]', "Admin Eintrag sollte aktiviert sein.");
    }
    
    function testAdminControllerIndexPages() {
        $pages = array(
            "/admin/account",
            "/admin/licence",
            "/admin/collectionroles",
            "/admin/series",
            "/admin/security",
            "/admin/language",
            "/admin/statistic",
            "/admin/oailink",
            "/admin/dnbinstitute",
            "/admin/enrichmentkey",
            "/admin/info"
        );
        
        foreach ($pages as $page) {
            $this->dispatch($page);
            $this->assertQuery('li.active[@id="primary-nav-administration"]', "Admin Eintrag sollte aktiviert sein.");
        }
    } 
    
    function testHome() {
        $this->dispatch("/home");
        $this->assertQuery('li.active[@id="primary-nav-home"]', "Home Eintrag sollte aktiviert sein.");
    }
    
    function testSearch() {
        $this->dispatch("/solrsearch");
        $this->assertQuery('li.active[@id="primary-nav-search"]', "Search Eintrag sollte aktiviert sein.");
    }
    
    function testBrowse() {
        $this->dispatch("/solrsearch/browse");
        $this->assertQuery('li.active[@id="primary-nav-browsing"]', "Browse Eintrag sollte aktiviert sein.");
    }
    
    function testPublish() {
        $this->dispatch("/publish");
        $this->assertQuery('li.active[@id="primary-nav-publish"]', "Publish Eintrag sollte aktiviert sein.");
    }
    
    function testFAQ() {
        $this->dispatch("/home/index/help");
        $this->assertQuery('li.active[@id="primary-nav-help"]', "FAQ Eintrag sollte aktiviert sein.");
    }
    
}