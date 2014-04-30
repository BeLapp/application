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
 * @category    TODO
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Admin_StatisticControllerTest extends ControllerTestCase {

    public function testIndexAction() {
        $this->dispatch('/admin/statistic');
        $this->assertResponseCode(200);
        $this->assertModule('admin');
        $this->assertController('statistic');
        $this->assertAction('index');
    }

    public function testShowAction() {
        $this->request
                ->setMethod('POST')
                ->setPost(array('selectedYear' => '2010'));
        $this->dispatch('/admin/statistic/show');
        $this->assertResponseCode(200);
        $this->assertModule('admin');
        $this->assertController('statistic');
        $this->assertAction('show');
    }

    /*
     * Fragt ab, ob bei einem falschen Jahr die Indexseite angezeigt wird
     */
    public function testIndexActionWithWrongYear() {
        $this->request
            ->setMethod('POST')
            ->setPost(array('selectedYear' => '1337'));
        $this->dispatch('/admin/statistic/index');
        $this->assertResponseCode(200);
        $this->assertModule('admin');
        $this->assertController('statistic');
        $this->assertAction('index');
        $this->assertQueryContentContains('//dt', 'Please select year:');
        $this->assertNotQueryContentContains('//h2', 'Month overview');
    }

    public function testDisplayCurrentYear() {
        $this->useGerman();
        $this->request
                ->setMethod('POST')
                ->setPost(array('selectedYear' => '2010'));
        $this->dispatch('/admin/statistic/show');
        $this->assertQueryContentContains('//div[@class="breadcrumbsContainer"]', '2010', 'breadcrumbsContainer does ' .
            'not contain the current year');
        $this->assertQueryContentContains('//h1', 'Veröffentlichungstatistik 2010');
    }
}

