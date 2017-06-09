<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cleanurls\local\uncleaner\category_uncleaner;
use local_cleanurls\local\uncleaner\root_uncleaner;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../cleanurls_testcase.php');

/**
 * Tests for category paths.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_uncleaner_test extends local_cleanurls_testcase {
    public function test_it_can_be_a_category_in_root() {
        $root = new root_uncleaner('/category/abc-123');
        self::assertTrue(category_uncleaner::can_create($root));
    }

    public function test_it_can_be_a_subcategory() {
        $root = new root_uncleaner('/category/abc-123/def-456');
        $category = new category_uncleaner($root);
        self::assertTrue(category_uncleaner::can_create($category));
    }

    public function test_it_requires_a_parent() {
        self::assertFalse(category_uncleaner::can_create(null));
    }

    public function test_it_cleans_category_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);

        static::assert_clean_unclean('http://www.example.com/moodle/course/index.php?categoryid=' . $category->id,
                                     'http://www.example.com/moodle/category/category-' . $category->id);
    }

    public function test_it_cleans_subcategory_urls() {
        $category = $this->getDataGenerator()->create_category(['name' => 'category']);
        $subcategory = $this->getDataGenerator()->create_category([
                                                                      'name'   => 'subcategory',
                                                                      'parent' => $category->id,
                                                                  ]);

        $url = 'http://www.example.com/moodle/course/index.php?categoryid=' . $subcategory->id;
        $expected = 'http://www.example.com/moodle/category/category-' . $category->id . '/subcategory-' . $subcategory->id;
        static::assert_clean_unclean($url, $expected);
    }
}
