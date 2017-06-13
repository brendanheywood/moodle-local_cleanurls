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

use local_cleanurls\local\uncleaner\root_uncleaner;
use local_cleanurls\local\uncleaner\user_forum_uncleaner;
use local_cleanurls\local\uncleaner\user_uncleaner;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../cleanurls_testcase.php');

/**
 * Tests for user forum paths.
 *
 * @package     local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cleanurls_user_forum_uncleaner_test extends local_cleanurls_testcase {
    public function test_it_can_be_created_in_user_uncleaner() {
        $root = new root_uncleaner('/user/username/forum');

        $user = $root->get_child();
        self::assertInstanceOf(user_uncleaner::class, $user);

        self::assertTrue(user_forum_uncleaner::can_create($user));
    }

    public function test_it_cannot_have_unexpected_parent() {
        $parent = new local_cleanurls_unittest_uncleaner();
        self::assertFalse(user_forum_uncleaner::can_create($parent));
    }

    public function test_it_recognizes_the_path() {
        $root = new root_uncleaner('/user/username/mypath');
        $userforum = $root->get_child()->get_child();
        self::assertInstanceOf(user_forum_uncleaner::class, $userforum);
        self::assertSame('mypath', $userforum->get_mypath());
    }

    public function test_it_cleans_username() {
        $user = $this->getDataGenerator()->create_user(
            ['email' => 'someone@example.com', 'username' => 'theusername']);

        static::assert_clean_unclean('http://www.example.com/moodle/mod/forum/user.php?mode=discussions&id=' . $user->id,
                                     'http://www.example.com/moodle/user/theusername/discussions');
    }

    public function test_it_cleans_username_in_forum_discussion() {
        $user = $this->getDataGenerator()->create_user(
            ['email' => 'someone@example.com', 'username' => 'theusername']);

        static::assert_clean_unclean('http://www.example.com/moodle/mod/forum/user.php?mode=discussions&id=' . $user->id,
                                     'http://www.example.com/moodle/user/theusername/discussions');
    }
}
