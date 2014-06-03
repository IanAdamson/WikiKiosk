
(function($){
		
    // Populate the page when it initially loads.
	getIndex();
    getMaintenanceOverview()

    $('body').delegate('.listitem', 'click', function() {
        getProjectOverview($(this).data('id'));
    });
    
    $('body').delegate('.button_link', 'click', function() {
        switch($(this).data('action') ) {
            case 'back':
                $('body').scrollTop(0);
                goTo($(this).data('to'));  
                break;
            case 'article':
                getProjectFull($(this).data('id'));
                break;
            case 'maintenance':
                getMaintenanceFull($(this).data('title'));
                break;
        };
       
    });
    
    /**
     * Go to a specific page.
     */ 
    function goTo(target) {
        switch(target) {
            case 'maintenance_overview':
                showLoadRight();
                getMaintenanceOverview();
                break;
            case 'split_panel':
                $("#full_panel").hide();
                break;
        };
    };
               
                
    /**
     * Get the project index and use it to populate the left panel.
     */
	function getIndex() {
        var saveData = $.ajax({
			type: 'POST',
			url: "src/server.php",
			data: { action: 'index' },
			dataType: "text",
			success: function(resultData) {
				$('.panel_left_contents').html(resultData);
			}
		});
		
		saveData.error(function() { alert("Something went wrong"); });

		return false;
	}
    
    /**
     * Get the maintenance overview and use it to populate the right panel.
     */
    function getMaintenanceOverview() {
            // Scroll to the top of the page when funct is called
		var saveData = $.ajax({
			type: 'POST',
			url: "src/server.php",
			data: { action: 'maintenance' },
			dataType: "text",
			success: function(resultData) { 
				$('.panel_right_contents').html(resultData);
                $('.panel_right_contents').scrollTop(0);
			}
		});
		
		saveData.error(function() { alert("Something went wrong"); });

		return false;
    }
    
    /**
     * Get the overview of a specific project and show it in the right panel.
     * @param {string} a unique identifier for the project
     */
    function getProjectOverview(articleID) {
        showLoadRight();
		var saveData = $.ajax({
			type: 'POST',
			url: "src/server.php",
			data: { action: 'briefoverview', id: articleID },
			dataType: "text",
			success: function(resultData) { 
				$('.panel_right_contents').html(resultData);
                $('.panel_right_contents').scrollTop(0);
			}
		});
		saveData.error(function() { alert("Something went wrong"); });
		return false;
    }
    
    /**
     * Get the full contents of an article for a specific project and show it in the right panel.
     * @param {string} a unique identifier for the project
     */
    function getProjectFull(articleID) {
        showLoadFull();
        $("#full_panel").show();
		var saveData = $.ajax({
			type: 'POST',
			url: "src/server.php",
			data: { action: 'fullarticle', id: articleID },
			dataType: "text",
			success: function(resultData) { 
				$('.full_panel_contents').html(resultData);
                $('.full_panel_contents').scrollTop(0);
			}
		});
		
		saveData.error(function() { alert("Something went wrong"); });

		return false;    
    }
    
    /**
     * Get the full maintenace article for a specific project and show it in the right panel.
     * @param {string} a unique identifier for the project
     */
    function getMaintenanceFull(articleID) {
        showLoadFull();
        $("#full_panel").show();
		var saveData = $.ajax({
			type: 'POST',
			url: "src/server.php",
			data: { action: 'fullmaintenence', id: articleID },
			dataType: "text", 
			success: function(resultData) { 
				$('.full_panel_contents').html(resultData);
                $('.full.panel_contents').scrollTop(0);

             }
		});
		saveData.error(function() { alert("Something went wrong"); });
		return false;    }


    /**
     * Go back one page.
     * @todo Look into how to best represent the state of the system; state machine maybe?
     */
    function backButton() {
        // Get the current state of the system.
        // Go back one step.
    }
    
    /**
     * Replaces the contents of the right panel with a loading spinner image.
     */
    function showLoadRight(){
        $('.panel_right_contents').html('<div class="loading"></div>');
    }    

    function showLoadFull(){
        $('.full_panel_contents').html('<div class="loading"></div>');
    }
    
     
    /**
     * Idle timer - reload page if idle for 30 minutes.
     */
    var timeout = 30*60*1000; // 30 minutes
    $(document).bind("idle.idleTimer", function () {
        location.reload(true);
    });
    $.idleTimer(timeout);
})(jQuery);