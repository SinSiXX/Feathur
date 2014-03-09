{%if isset|ServerList == false}
	<br><br>
	<div align="center">
		Unfortunately there are no servers added to Feathur, so you can not create a VPS.
		<br><br>
		Add a server then try again.
	</div>
{%/if}
{%if isset|ServerList == true}
	<script type="text/javascript">
		$(document).ready(function() {
			$("#ServerSelection").change(function() {
				$(".openvz, .kvm").hide();
				var target_element = "." + $("#ServerSelection option:selected").data("type");
				$(target_element).show();
			});
		});
	</script>
	<br><br>
	<div align="center">
		<div class="simplebox grid740" style="text-align:left;">
			<div class="titleh">
				<h3>Create VPS</h3>
			</div>
			<div class="body">
				<form name="create" method="post" id="create_form">
					<div class="st-form-line">	
						<span class="st-labeltext">Select Client:</span>	
						<select id="user" name="user" style="width:520px;">
							<option value="z">--- Choose One ---</option>
							{%foreach user in UserList}
								<option value="{%?user[id]}">{%?user[email]}</option>
							{%/foreach}
						</select>
						<div class="clear"></div>
					</div>
					<div class="st-form-line">	
						<span class="st-labeltext">Select Server:</span>	
						<select name="server" id="ServerSelection" style="width:520px;">
							<option value="z">--- Choose One ---</option>
							{%foreach server in ServerList}
								<option value="{%?server[id]}" class="TemplateList" data-type="{%?server[type]}" id="Server{%?server[id]}">{%?server[name]} ({%?server[type]})</option>
							{%/foreach}
						</select>
						<div class="clear"></div>
					</div>
					<div id="CreateForm">
						<!--- start -->
						<div class="st-form-line openvz">	
							<span class="st-labeltext">Select Template:</span>
							<select id="template" name="openvz_template" style="width:520px;">
								<option value="z">--- Choose One ---</option>
								{%if isempty|OpenvzTemplateList == false}
									{%foreach template in OpenvzTemplateList}
										<option value="{%?template[id]}">{%?template[name]}</option>
									{%/foreach}
								{%else}
									<option>No templates for this server, please add one. (Settings => Template Manager)</option>
								{%/if}
							</select>
							<div class="clear"></div>
						</div>
						<div class="st-form-line kvm">
							<span class="st-labeltext">Select Template:</span>	
							<select id="template" name="kvm_template" style="width:520px;">
								<option value="z">--- Choose One ---</option>
								<option value="">None</option>
								{%if isempty|KvmTemplateList == false}
									{%foreach template in KvmTemplateList}
										<option value="{%?template[id]}">{%?template[name]}</option>
									{%/foreach}
								{%else}
									<option>No templates for this server, please add one. (Settings => Template Manager)</option>
								{%/if}
							</select>
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz kvm">
							<span class="st-labeltext">RAM (MB):</span>	
							<input id="ram" type="text" name="ram" value="256" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz">
							<span class="st-labeltext">SWAP (MB):</span>	
							<input id="swap" type="text" name="swap" value="256" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz kvm">
							<span class="st-labeltext">Disk (GB):</span>	
							<input id="disk" type="text" name="disk" value="10" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz">
							<span class="st-labeltext">CPU Units (1000 Default):</span>	
							<input id="cpuunits" type="text" name="cpuunits" value="1000" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz">
							<span class="st-labeltext">CPU Limit (100 per Core):</span>	
							<input id="cpulimit" type="text" name="openvz_cpulimit" value="100" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line kvm">
							<span class="st-labeltext">CPU Limit (1 per Core):</span>	
							<input id="cpulimit" type="text" name="kvm_cpulimit" value="1" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz kvm">
							<span class="st-labeltext">Bandwidth Limit (GB):</span>	
							<input id="bandwidthlimit" type="text" name="bandwidthlimit" value="1024" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz">
							<span class="st-labeltext">Inodes:</span>	
							<input id="inodes" type="text" name="inodes" value="200000" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz">
							<span class="st-labeltext">Max Processes:</span>	
							<input id="numproc" type="text" name="numproc" value="128" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz">
							<span class="st-labeltext">Max Connections:</span>	
							<input id="numiptent" type="text" name="numiptent" value="80" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz kvm">
							<span class="st-labeltext">IP Addresses:</span>	
							<select name="ipaddresses" id="ipaddresses" style="width:520px;">
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5</option>
								<option value="6">6</option>
								<option value="7">7</option>
								<option value="8">8</option>
								<option value="9">9</option>
								<option value="10">10</option>
							</select>
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz kvm">	
							<span class="st-labeltext">Hostname (optional):</span>	
							<input id="hostname" type="text" name="hostname" value="server.example.com" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz kvm">	
							<span class="st-labeltext">Nameserver (optional):</span>	
							<input id="nameserver" type="text" name="nameserver" value="8.8.8.8" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line openvz">
							<span class="st-labeltext">Root Password (optional):</span>	
							<input id="password" type="password" name="root_password" value="" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line kvm">	
							<span class="st-labeltext">VNC Password (optional):</span>	
							<input id="password" type="password" name="vnc_password" value="" style="width:500px;">
							<div class="clear"></div>
						</div>
						<div class="st-form-line" align="center">
							<button type="submit" class="small blue create-button">Create</button>
							<br><br>
						</div>
						<script type="text/javascript">
							$(document).ready(function() {
								$("#create_form").submit(function(){
									$(".create-button").css({visibility: "hidden"});
									 $.ajax({
										type: "POST",
										url: "/admin/vps/create",
										data: $(this).serialize(),
										success: function(data){
											var result = $.parseJSON(data);
											$('#update').html('<div style="z-index: 670;width:60%;" class="albox small-' + result.type + '"><div id="Status" style="padding:4px;padding-left:5px;width:95%;">' + result.result + '</div><div style="float:right;"><a href="#" onClick="return false;" style="margin:-3px;padding:0px;" class="small-close CloseToggle">x</a></div></div>');
											if(result.reload == 1){
												window.location = "/admin/vps/" + result.vps;
											} else {
												$(".create-button").css({visibility: "visible"});
											}
										}
									});
									return false; /* Prevent normal submission */
								});
							});
						</script>
						<div id="update"></div>
						<!--- end -->
					</div>
				</form>
			</div>
		</div>
	</div>
{%/if}
