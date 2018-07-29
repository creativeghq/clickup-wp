<?php
/*
Plugin Name: Clickup task
Plugin URI:
Description: Click up tasks
Author:
Author URI:
Version: 1.0
License: GPLv2 or later
 */

define('CLICKUP_CLIENT_ID', 'G54JUAKVNQBZCP7IVLR9WRRILFSWVYCT');
define('CLICKUP_SECRET_KEY', 'FA4V5G819U0J4SNNM6ZV1GHY6U207O81PYHT13ILVDNSSTHUTGZJKMGQO2TVRBGM');
define('CLICKUP_REDIRECT_URL', 'https://creativeg.gr/your-membership/#tab6');

class clickuptask
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'clickuptask_scripts'),100);
        add_shortcode('clickup-task-list', array($this, 'clickup_task_list'));
        add_action('init', array($this, 'add_rewrite_endpoints'));

        add_action('wp_ajax_clickup_get_user', array($this, 'clickup_get_user'));
        add_action('wp_ajax_nopriv_clickup_get_user', array($this, 'clickup_get_user'));


        add_action('wp_ajax_clickup_get_teams_spaces', array($this, 'clickup_get_teams_spaces'));
        add_action('wp_ajax_nopriv_clickup_get_teams_spaces', array($this, 'clickup_get_teams_spaces'));


        add_action('wp_ajax_clickup_get_projects', array($this, 'clickup_get_projects'));
        add_action('wp_ajax_nopriv_clickup_get_projects', array($this, 'clickup_get_projects'));


        add_action('wp_ajax_clickup_get_tasks', array($this, 'clickup_get_tasks'));
        add_action('wp_ajax_nopriv_clickup_get_tasks', array($this, 'clickup_get_tasks'));

        add_action('template_redirect', array($this, 'template_redirect'));
    }

    public function clickup_get_user()
    {
    	$access_token = $_POST['access_token'];
        $response = $this->curl_get('https://api.clickup.com/api/v1/user',$access_token);
        echo $response;die;
    }

    public function clickup_get_teams_spaces()
    {
    	$access_token = $_POST['access_token'];
    	$return_space = [];
    	$response = $this->curl_get('https://api.clickup.com/api/v1/team',$access_token);
    	if($response) {
            $response = json_decode($response,true);
          	$return_space = [];
    		foreach($response['teams'] as $team) {
    			$spaces = $this->clickup_get_space($team['id'], $access_token);
    			foreach($spaces['spaces'] as $key=>$space) {
                    $return_space[$key] = $space;
                    $return_space[$key]['team_id'] = $team['id'];
    			}
    		}
    	}
    	echo json_encode($return_space);die;
     }



    public function clickup_get_space($team_id, $access_token)
    {
    	$response = $this->curl_get('https://api.clickup.com/api/v1/team/'.$team_id.'/space',$access_token);
    	return json_decode($response, true);
    	
    }

    public function clickup_get_projects()
    {
        $access_token = $_POST['access_token'];
    	$space_id = $_POST['space_id'];
    	$response = $this->curl_get('https://api.clickup.com/api/v1/space/'.$space_id.'/project',$access_token);
    	echo $response;die;
    }

    public function clickup_get_tasks()
    {
    	$access_token = $_POST['access_token'];
    	$team_id = $_POST['team_id'];
    	$space_id = $_POST['space_id'];
    	$project_id = $_POST['project_id'];
    	$list_id = $_POST['list_id'];
    	$page = $_POST['page'];
    	if(!$page) {
    		$page = 1;
    	}
    	$response = $this->curl_get('https://api.clickup.com/api/v1/team/'.$team_id.'/task?space_ids[]='.$space_id.'&project_ids[]='.$project_id.'&list_ids[]='.$list_id,$access_token);
    	echo $response;die;
    }


    public function clickup_task_list()
    {
		 if(!is_admin()) {
            return $this->render('clickup_list_task');
        }
    }

    public function add_rewrite_endpoints()
    {
        if(!is_admin()) {
            add_rewrite_endpoint('clickupredirect', EP_PERMALINK | EP_ROOT);
            flush_rewrite_rules();    
        }
        
    }

    public function clickuptask_scripts()
    {
        wp_enqueue_script(
            'jquery'
        );

        if (!wp_script_is('vuejs', 'enqueued')) {
            wp_enqueue_script(
                'vuejs',
                'https://cdnjs.cloudflare.com/ajax/libs/vue/2.3.4/vue.min.js',
                array()
            );
        }
    }

    public function template_redirect()
    {
        global $wp_query;
        if(isset($_GET['code']) && $_GET['code']){

            $access_token = '';
            $url          = 'https://api.clickup.com/api/v1/oauth/token';
            $fields       = array(
                'code'          => urlencode($_GET['code']),
                'client_id'     => urlencode(CLICKUP_CLIENT_ID),
                'client_secret' => urlencode(CLICKUP_SECRET_KEY),
            );
            $fields_string = '';
            foreach ($fields as $key => $value) {$fields_string .= $key . '=' . $value . '&';}
            rtrim($fields_string, '&');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($fields));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($result, true);
            if (isset($res['access_token'])) {
                $access_token = $res['access_token'];
                echo '<script>localStorage.setItem("clickup_access_token","' . $access_token . '");window.location.reload();</script>';
                echo '<script>window.location.href="'.CLICKUP_REDIRECT_URL.'";</script>';	
            } else {
                echo '<script>window.location.href="'.CLICKUP_REDIRECT_URL.'";</script>';	

            }

        }

    }

    public function curl_get($url, $access_token)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: " . $access_token]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
        // curl_setopt($ch, CURLOPT_TIMEOUT, 3000);
        $return = curl_exec($ch);
        curl_close($ch);

        // $projects = json_decode($return, true);
        return $return;
    }

    public function render($view, $data = null)
    {
        // Handle data
        ($data) ? extract($data) : null;

        ob_start();
        include plugin_dir_path(__FILE__) . 'inc/' . $view . '.php';
        $view = ob_get_contents();
        ob_end_clean();

        return $view;
    }

}

new clickuptask;
