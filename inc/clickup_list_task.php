<div id="cg_app_clickup">

	<div v-if="!access_token">
	<div>
		<a href="https://app.clickup.com/api?client_id=<?php echo CLICKUP_CLIENT_ID; ?>&redirect_uri=<?php echo CLICKUP_REDIRECT_URL; ?>">Login</a>
	</div>
		
	</div>
	<div v-if="access_token">
	<div>
	<span v-if="user.username">Welcome, {{user.username}}</span><br><a href="javascript:void(0);" v-on:click="logout()"> Logout</a>
	</div>
	</div>
		
		<div id="output" v-if="spaces">
				<div v-for="i in spaces">
				<h2><a href="javascript:void(0);" v-on:click="getProject(i.space_id)">{{i.name}}</a></h2>	
				<div v-if="j.space_id == i.space_id" v-for="j in projects"  class="projects">
					<h3><a href="javascript:void(0);">{{j.name}}</a></h3>	
					<ul class="tasks">
						<li v-for="k in j.list"><a href="javascript:void(0)" v-on:click="getTasks(i.team_id,i.space_id,j.project_id,k.id)">{{k.name}}</a>
						<ul class="tasks">
							<li v-for="l in tasks" v-if="k.id == l.list_id" >
								<label><b>Task:</b></label> {{l.name}} <a :href="'https://app.clickup.com/'+i.team_id+'/'+i.space_id+'/t/'+l.id" target="_blank">View task</a>
								<br>
								<div style="margin-left:10px;"><label><b>Description:</b></label> {{l.content}}</div>
							</li>
						</ul>
					  </li>
					</ul>
				</div>
			</div>

			<ul>
				
			</ul>
		</div>
		
	</div>

	<script type="text/javascript">
		 var app = new Vue({
		  el: '#cg_app_clickup',
		  data(){
		  	return {
				projects:[],
				sections:[],
				tasks:[],
				spaces:[],
				user:[],
				access_token:localStorage.getItem('clickup_access_token')	  
		  	}
		  },
		  created:function(){
		  	if(localStorage.getItem('clickup_access_token') && localStorage.getItem('clickup_access_token')!='') {
		  		this.getUser();
		  		this.getTeamSpaces();
		  	}

		   },
		  methods:{
		 	logout:function(){
		 		localStorage.removeItem('clickup_access_token');
		 		this.access_token = false;
		 		this.projects = [];
		 		this.spaces = [];
		 		this.sections = [];
		 		this.tasks = [];
		 		this.user = [];
		 		window.location.reload();
		  	},
		  	getUser: function() {
		  		$access_token = localStorage.getItem('clickup_access_token');

		  		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' );?>';
		  		jQuery.post(ajaxurl,{'action':'clickup_get_user','access_token':$access_token},function(data){

		  			this.user = data.user;
		  				

		  		}.bind(this),'json')
		  	},
		  	getTeamSpaces:function(){
		  		$access_token = localStorage.getItem('clickup_access_token');

		  		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' );?>';
		  		jQuery.post(ajaxurl,{'action':'clickup_get_teams_spaces','access_token':$access_token},function(data){

		  			jQuery.each(data, function(id, value){
		  				this.spaces.push({'space_id':value.id,'name':value.name,'status':value.statuses[0].status, 'team_id': value.team_id});
		  			}.bind(this))

		  		}.bind(this),'json')
		  	},
		  	getProject: function(spaceid){
		  		$access_token = localStorage.getItem('clickup_access_token');
		  		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' );?>';
		  		jQuery.post(ajaxurl,{'action':'clickup_get_projects','access_token':$access_token, 'space_id':spaceid},function(data){
		  			jQuery.each(data.projects, function(id, value){
		  				this.projects.push({'project_id':value.id,'name':value.name, 'list':value.lists, 'space_id':spaceid});
		  			}.bind(this))
		  		}.bind(this),'json')
		  	},
		  	getTasks:function(team_id,space_id,project_id, list_id){
		  		$access_token = localStorage.getItem('clickup_access_token');
		  		$team_id = team_id;
		  		$page = 1;
		  		$space_id = space_id;
		  		$project_id = project_id;
		  		$list_id = list_id;
		  			var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' );?>';
			  		jQuery.post(ajaxurl,{'action':'clickup_get_tasks','team_id':$team_id,'access_token':$access_token, 'space_id':$space_id, 'project_id':$project_id, 'list_id': $list_id},function(tasks){
			  			jQuery.each(tasks.tasks,function(id,task){
			  				this.tasks.push({'id':task.id,'name':task.name,'content':task.text_content, 'list_id':list_id});
			  			}.bind(this))
			  		}.bind(this),'json')
		  	}
		  }
		})


	</script>
