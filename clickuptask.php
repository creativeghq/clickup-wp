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
        add_action('wp_enqueue_scripts', array($this, 'clickuptask_scripts'));

        // add_filter( 'script_loader_tag', array($this, 'set_scripts_type_attribute'), 10, 3 );

        add_shortcode('clickup-task-list', array($this, 'clickup_task_list'));
        add_action('init', array($this, 'add_rewrite_endpoints'));

        add_action('wp_ajax_clickup_get_user', array($this, 'clickup_get_user'));
        add_action('wp_ajax_nopriv_clickup_get_user', array($this, 'clickup_get_user'));


        add_action('wp_ajax_clickup_task_comments', array($this, 'clickup_task_comments'));
        add_action('wp_ajax_nopriv_clickup_task_comments', array($this, 'clickup_task_comments'));


        add_action('wp_ajax_clickup_post_reply', array($this, 'clickup_post_reply'));
        add_action('wp_ajax_nopriv_clickup_post_reply', array($this, 'clickup_post_reply'));


        add_action('wp_ajax_clickup_get_teams_spaces', array($this, 'clickup_get_teams_spaces'));
        add_action('wp_ajax_nopriv_clickup_get_teams_spaces', array($this, 'clickup_get_teams_spaces'));


        add_action('wp_ajax_clickup_post_comment', array($this, 'clickup_post_comment'));
        add_action('wp_ajax_nopriv_clickup_post_comment', array($this, 'clickup_post_comment'));


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
    	$response = $this->curl_get('https://api.clickup.com/api/v2/team/'.$team_id.'/space?archived=false',$access_token);
    	return json_decode($response, true);
    	
    }

    public function clickup_task_comments()
    {
        $access_token = $_POST['access_token'];
        $task_id = $_POST['task_id'];

        $response = $this->curl_get('https://api.clickup.com/api/v2/task/'.$task_id.'/comment',$access_token);
        print_r($response);die;
        return json_decode($response, true);
    }

    public function clickup_get_projects()
    {
        $access_token = $_POST['access_token'];
    	$space_id = $_POST['space_id'];
    	$response = $this->curl_get('https://api.clickup.com/api/v2/space/'.$space_id.'/folder',$access_token);
    	echo $response;die;
    }

    public function clickup_post_comment()
    {
        $access_token = $_POST['access_token'];
        $task_id = $_POST['task_id'];
        $comment = $_POST['comment'];
        $response = $this->curl_post('https://api.clickup.com/api/v2/task/'.$task_id.'/comment',$access_token, $comment);
        
        echo $response;die;
    }

    public function clickup_post_reply()
    {
        $access_token = $_POST['access_token'];
        $view_id = $_POST['view_id'];   
        $comment = $_POST['comment'];
        $response = $this->curl_post('https://api.clickup.com/api/v2/view/'.$view_id.'/comment',$access_token, $comment);
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
    		$page = 0;
    	}

        $response = $this->curl_get('https://api.clickup.com/api/v2/list/'.$list_id.'/task?space_ids[]='.$space_id.'&project_ids[]='.$project_id.'&team_id='.$team_id,$access_token);

    	echo $response;die;
    }


    public function clickup_task_list()
    {
        // echo is_admin();die;

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
        wp_enqueue_style( 'clickupcss', plugin_dir_url(__FILE__).'assets/style.css', null, 1.0);
        wp_enqueue_style('modalcss', plugin_dir_url(__FILE__) . 'assets/vue-modal.css');
        wp_enqueue_script('dayjs', 'https://unpkg.com/dayjs@1.8.21/dayjs.min.js');

        wp_enqueue_script(
            'jquery'
        );

        if (!wp_script_is('vuejs', 'enqueued')) {
            wp_enqueue_script(
                'vuejs',
                'https://cdn.jsdelivr.net/npm/vue@2',
                array()
            );
        }
        wp_enqueue_script('modal', plugin_dir_url(__FILE__) . 'assets/vue-modal.umd.min.js', array('vuejs'));
        


    }

    // public function set_scripts_type_attribute( $tag, $handle, $src ) {
    //     if ( 'module_handle' === $handle ) {
    //         $tag = '<script type="module" src="'. $src .'"></script>';
    //     }
    //     return $tag;
    // }
    

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

    public function curl_post($url, $access_token, $comment)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            "comment_text=".$comment."&notify_all=true");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: " . $access_token]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $return = curl_exec($ch);
        curl_close($ch);
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
