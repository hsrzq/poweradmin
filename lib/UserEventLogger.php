<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2009  Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2022  Poweradmin Development Team
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Poweradmin;

class UserEventLogger
{
    public static function log_successful_auth() {
        Logger::log_notice(sprintf('Successful authentication attempt from [%s] for user \'%s\'', $_SERVER['REMOTE_ADDR'], $_SESSION['userlogin']));
    }

    public static function log_failed_auth() {
        Logger::log_warn(sprintf('Failed authentication attempt from [%s] for user \'%s\'', $_SERVER['REMOTE_ADDR'], $_SESSION["userlogin"]));
    }
}