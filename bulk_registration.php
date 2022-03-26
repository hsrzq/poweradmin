<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2010  Rejo Zenger <rejo@zenger.nl>
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

/**
 * Script that handles bulk zone registration
 *
 * @package     Poweradmin
 * @copyright   2007-2010 Rejo Zenger <rejo@zenger.nl>
 * @copyright   2010-2022  Poweradmin Development Team
 * @license     https://opensource.org/licenses/GPL-3.0 GPL
 */

use Poweradmin\AppFactory;
use Poweradmin\Dns;
use Poweradmin\DnsRecord;
use Poweradmin\Validation;
use Poweradmin\ZoneTemplate;
use Poweradmin\ZoneType;

require_once 'inc/toolkit.inc.php';
require_once 'inc/message.inc.php';

include_once 'inc/header.inc.php';

$app = AppFactory::create();
$iface_zone_type_default = $app->config('iface_zone_type_default');

$owner = "-1";
if ((isset($_POST['owner'])) && (Validation::is_number($_POST['owner']))) {
    $owner = $_POST['owner'];
}

$dom_type = "NATIVE";
if (isset($_POST["dom_type"]) && (in_array($_POST['dom_type'], ZoneType::getTypes()))) {
    $dom_type = $_POST["dom_type"];
}

if (isset($_POST['domains'])) {
    $domains = explode("\r\n", $_POST['domains']);
    foreach ($domains as $key => $domain) {
        $domain = trim($domain);
        if ($domain == '') {
            unset($domains[$key]);
        } else {
            $domains[$key] = $domain;
        }
    }
} else {
    $domains = array();
}

$zone_template = $_POST['zone_template'] ?? "none";

$zone_master_add = do_hook('verify_permission', 'zone_master_add');
$perm_view_others = do_hook('verify_permission', 'user_view_others');

if (isset($_POST['submit']) && $zone_master_add) {
    $error = false;
    foreach ($domains as $domain) {
        if (!Dns::is_valid_hostname_fqdn($domain, 0)) {
            error($domain . ' failed - ' . ERR_DNS_HOSTNAME);
        } elseif (DnsRecord::domain_exists($domain)) {
            error($domain . " failed - " . ERR_DOMAIN_EXISTS);
            $error = true;
        } elseif (DnsRecord::add_domain($domain, $owner, $dom_type, '', $zone_template)) {
            success("<a href=\"edit.php?id=" . DnsRecord::get_zone_id_from_name($domain) . "\">" . $domain . " - " . SUC_ZONE_ADD . '</a>');
        }
    }

    if (false === $error) {
        unset($domains, $owner, $dom_type, $zone_template);
    }
}

if (!$zone_master_add) {
    error(ERR_PERM_ADD_ZONE_MASTER);
    include_once('inc/footer.inc.php');
    exit;
}
echo "     <h2>" . _('Bulk registration') . "</h2>\n";

$available_zone_types = array("MASTER", "NATIVE");
$users = do_hook('show_users');
$zone_templates = ZoneTemplate::get_list_zone_templ($_SESSION['userid']);

echo "     <form method=\"post\" action=\"bulk_registration.php\">\n";
echo "      <table>\n";
echo "       <tr>\n";
echo "        <td class=\"n\" width=\"100\">" . _('Owner') . ":</td>\n";
echo "        <td class=\"n\">\n";
echo "         <select name=\"owner\">\n";
/*
  Display list of users to assign zone to if creating
  user has the proper permission to do so.
 */
foreach ($users as $user) {
    if ($user['id'] === $_SESSION['userid']) {
        echo "          <option value=\"" . $user['id'] . "\" selected>" . $user['fullname'] . "</option>\n";
    } elseif ($perm_view_others) {
        echo "          <option value=\"" . $user['id'] . "\">" . $user['fullname'] . "</option>\n";
    }
}
echo "         </select>\n";
echo "        </td>\n";
echo "       </tr>\n";
echo "       <tr>\n";
echo "        <td class=\"n\">" . _('Type') . ":</td>\n";
echo "        <td class=\"n\">\n";
echo "         <select name=\"dom_type\">\n";
foreach ($available_zone_types as $type) {
    $type == $iface_zone_type_default ? $selected = ' selected' : $selected = '';
    echo "          <option value=\"" . $type . "\" $selected>" . strtolower($type) . "</option>\n";
}
echo "         </select>\n";
echo "        </td>\n";
echo "       </tr>\n";
echo "       <tr>\n";
echo "        <td class=\"n\">" . _('Template') . ":</td>\n";
echo "        <td class=\"n\">\n";
echo "         <select name=\"zone_template\">\n";
echo "          <option value=\"none\">none</option>\n";
foreach ($zone_templates as $zone_template) {
    echo "          <option value=\"" . $zone_template['id'] . "\">" . $zone_template['name'] . "</option>\n";
}
echo "         </select>\n";
echo "        </td>\n";
echo "       </tr>\n";

echo "       <tr>\n";
echo "        <td class=\"n\">" . _('Zones') . ":</td>\n";
echo "        <td class=\"n\">\n";
echo "         <ul id=\"domain_names\" style=\"list-style-type:none; padding:0 \">\n";
echo "		<li>" . _('Type one domain per line') . ":</li>\n";
echo "          <li><textarea class=\"input\" name=\"domains\" rows=\"10\" cols=\"30\" style=\"width: 500px;\">";
if (isset($error) && isset($_POST['domains'])) {
    echo $_POST['domains'];
}
echo "</textarea></li>\n";
echo "         </ol>\n";
echo "        </td>\n";
echo "       </tr>\n";

echo "       <tr>\n";
echo "        <td class=\"n\">&nbsp;</td>\n";
echo "        <td class=\"n\">\n";
echo "         <input type=\"submit\" class=\"button\" name=\"submit\" value=\"" . _('Add zones') . "\">\n";
echo "        </td>\n";
echo "       </tr>\n";
echo "      </table>\n";
echo "     </form>\n";

include_once('inc/footer.inc.php');
