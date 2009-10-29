<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Settings for OpenID
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Settings
 * @package   StatusNet
 * @author   Craig Andrews <candrews@integralblue.com>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/action.php';
require_once INSTALLDIR.'/plugins/OpenID/openid.php';

/**
 * Settings for OpenID
 *
 * Lets users add, edit and delete OpenIDs from their account
 *
 * @category Settings
 * @package  StatusNet
 * @author   Craig Andrews <candrews@integralblue.com>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class OpenidserverAction extends Action
{

    function handle($args)
    {
        parent::handle($args);
        $oserver = oid_server();
        $request = $oserver->decodeRequest();
        if (in_array($request->mode, array('checkid_immediate',
            'checkid_setup'))) {
            $cur = common_current_user();
            error_log("Request identity: " . $request->identity);
            if(!$cur){
                /* Go log in, and then come back. */
                common_set_returnto($_SERVER['REQUEST_URI']);
                common_redirect(common_local_url('login'));
                return;
            }else if(common_profile_url($cur->nickname) == $request->identity || $request->idSelect()){
                $response = &$request->answer(true, null, common_profile_url($cur->nickname));
            } else if ($request->immediate) {
                $response = &$request->answer(false);
            } else {
                //invalid
                $this->clientError(sprintf(_('You are not authorized to use the identity %s'),$request->identity),$code=403);
            }
        } else {
            $response = &$oserver->handleRequest($request);
        }

        if($response){
            $webresponse = $oserver->encodeResponse($response);

            if ($webresponse->code != AUTH_OPENID_HTTP_OK) {
                header(sprintf("HTTP/1.1 %d ", $webresponse->code),
                       true, $webresponse->code);
            }

            if($webresponse->headers){
                foreach ($webresponse->headers as $k => $v) {
                    header("$k: $v");
                }
            }
            $this->raw($webresponse->body);
        }else{
            $this->clientError(_('Just an OpenID provider. Nothing to see here, move along...'),$code=500);
        }
    }
}
