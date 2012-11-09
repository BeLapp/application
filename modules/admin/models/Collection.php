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
 * @package     Module_Admin
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Admin_Model_Collection {

    private $collection = null;

    public function  __construct($id = null) {
        if ($id === '') {
            throw new Admin_Model_Exception('missing parameter id');
        }
        if (is_null($id)) {
            $this->initNewCollection();
            return;
        }
        try {
            $this->collection = new Opus_Collection($id);
        }
        catch (Opus_Model_NotFoundException $e) {
            throw new Admin_Model_Exception('id parameter value unknown');
        }
    }

    private function initNewCollection() {
        $this->collection = new Opus_Collection();
        $this->collection->setVisible('1');
    }

    /**
     *
     * @return Opus_Collection
     */
    public function getObject() {
        return $this->collection;
    }

    public function delete() {
        if (is_null($this->collection)) {
            return;
        }
        $parents = $this->collection->getParents();
        $this->collection->delete();
        return $parents[1]->getId();
    }

    public function setVisiblity($visibility) {
        if (is_null($this->collection)) {
            return;
        }
        $this->collection->setVisible($visibility);
        $this->collection->store();
        $parents = $this->collection->getParents();
        return $parents[1]->getId();
    }

    public function addDocument($documentId) {
        if (is_null($documentId)) {
            throw new Admin_ModelException('missing document id');
        }
        $document = null;
        try {
            $document = new Opus_Document($documentId);
        }
        catch (Opus_Model_Exception $e) {
            throw new Admin_Model_Exception('invalid document id');
        }
        $document->addCollection($this->collection);
        $document->store();
    }

    public function getName() {
        if (count($this->collection->getParents()) === 1) {
            // die Wurzel einer Collection-Hierarchie hat selbst keinen Namen/Number: in diesem Fall wird der Name der Collection Role verwendet
            return $this->collection->getRole()->getDisplayName();
        }
        return $this->collection->getNumberAndName();
    }

    /**
     * Moves the collection within the same hierarchy level. Return the parent's
     * collection id.
     *
     * @param int $newPosition
     * @return int
     */
    public function move($newPosition) {
        if (is_null($newPosition)) {
            throw new Admin_Model_Exception('missing parameter pos');
        }

        $newPosition = (int) $newPosition;
        if ($newPosition < 1) {
            throw new Admin_Model_Exception('cannot move collection to position ' . $newPosition);
        }

        $parents = $this->collection->getParents();
        if (count($parents) < 2) {
            throw new Admin_Model_Exception('cannot move root collection');
        }

        $siblings = $parents[1]->getChildren();
        if ($newPosition > count($siblings)) {
            throw new Admin_Model_Exception('cannot move collection to position ' . $newPosition);
        }

        // assing sortOrder value to all collections at the same hierarchy level
        $oldPosition = 0;
        foreach ($siblings as $position => $sibling) {
            $sibling->setSortOrder($position);
            $sibling->store();
            if ($sibling->getId() === $this->collection->getId()) {
                $oldPosition = $position;
            }
        }

        // counting for newPosition is not zero-based
        $newPosition--;

        // TODO: moving distance needs to be increased
        if (abs($oldPosition - $newPosition) > 1) {
            // restore value for displaying error message
            $newPosition++;
            throw new Admin_Model_Exception('cannot move collection to position ' . $newPosition);
        }

        // perform move operation
        if ($oldPosition !== $newPosition) {
            $siblings[$oldPosition]->setSortOrder($newPosition);
            $siblings[$newPosition]->setSortOrder($oldPosition);

            $siblings[$oldPosition]->store();
            $siblings[$newPosition]->store();
        }

        return $parents[1]->getId();
    }
}
