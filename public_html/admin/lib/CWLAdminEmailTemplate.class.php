<?PHP
/**
 *  This file is an admin page used to manage email templates to users
 *  Templated variables are in the form {UPPER_CASE}
 * 
 *  Coders/Contractors Work Log - A time tracking/invoicing app 
 *  Copyright (C) 2014 Jim A Kinsman (cworklog.com) relipse@gmail.com github.com/relipse 
 *
 *  LICENSES - GPL 3. (If you need a different commercial license please contact Jim) 
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License.
 * 
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *  
 *   You should have received a copy of the GNU General Public License
 *   along with this program (gpl.txt).  If not, see <http://www.gnu.org/licenses/>.
 */
require_once(__DIR__.'/../../lib/misc.inc.php');
require_once(__DIR__.'/../../lib/db.inc.php');
require_once(__DIR__.'/../../lib/Site.class.php');

  class CWLAdminEmailTemplate {
      private $variables = null;
    
      private $content = null;

      public function __construct($content){
         $this->content = $content;

         $this->variables = array(
          'USERNAME'=> function(&$user){ 
               return $user['username'];
          },
          'VALIDATE_ACCOUNT_URL'=> function(&$user){
             if (strlen($user['verify_code']) < 10){
                $code = random_string(25);
                $prep = pdo()->prepare('UPDATE user SET verify_code = :verify_code, verify_command = :verify_command WHERE user.id = :user_id');
                $prep->execute(array('verify_code'=>$code, 'verify_command'=>'initial_email_check',
                                     'user_id'=>$user['id']));
                $user['verify_code'] = $code;
                $user['verify_param'] = 'initial_email_check';
             }
             
             $url = Site::cfg('base_url').'/verify.php?code='.urlencode($user['verify_code']).'&email='.urlencode($user['email']);
             return $url;
          },
          'DELETE_ACCOUNT_URL'=>function(&$user){
             if (strlen($user['verify_code']) < 10){
                $code = random_string(25);
                $prep = pdo()->prepare('UPDATE user SET verify_code = :verify_code, verify_command = :verify_command WHERE user.id = :user_id');
                $prep->execute(array('verify_code'=>$code, 'verify_command'=>'initial_email_check',
                                     'user_id'=>$user['id']));
                $user['verify_code'] = $code;
                $user['verify_param'] = 'initial_email_check';
             }
             
             $url = Site::cfg('base_url').'/delete.php?remove_my_account=1&code='.urlencode($user['verify_code']).'&email='.urlencode($user['email']);
             return $url;
          }
        );
      }

      public function isVar($var){
         return isset($this->variables[$var]);
      }

      public function replaceAll($user){
          $content = $this->content;
          preg_match_all('/{([A-Z0-9_]+)}/imx', $content, $result, PREG_PATTERN_ORDER);
          for ($i = 0; $i < count($result[0]); $i++) {
            # Matched text = $result[0][$i];
              if ($this->isVar($result[1][$i])){
                  $replace_with = call_user_func_array($this->variables[$result[1][$i]], array(&$user));
                  $content = str_replace($result[0][$i], $replace_with, $content);
              }
          }
          return $content;
      }
  }
