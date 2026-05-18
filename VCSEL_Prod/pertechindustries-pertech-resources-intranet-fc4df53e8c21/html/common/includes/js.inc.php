<?php
//Developer:    Charles Palmer
//Created:      2014.05.27
//Revision:     2020.04.15

/*
*   2020.04.15  CP  Added full_table_desc option
*/
?>
    <script type="text/javascript">
	$(document).ready(function(){
	    //Sort tables
	    //---------------------------------------------------------------------------//
	    $(".sortable").dataTable({
		"bDestroy": true,
		"paging": false
	    });
	    $(".paging").dataTable({
		"bDestroy": true,
		"ordering": false,
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "All"]]
	    });
	    $(".searchable").dataTable({
		"bDestroy": true,
		"paging": false,
		"ordering": false
	    });
	    $(".full_table").dataTable({
		"bDestroy": true,
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "All"]]
      });
      $(".full_table_desc").dataTable({
    "order": [[0, 'desc']],
		"bDestroy": true,
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "All"]]
	    });
	    //---------------------------------------------------------------------------//
	    
	    //Alerts on anchor tags
	    //---------------------------------------------------------------------------//
	    $(".include_alert").click(function(){
		return confirm('Are you sure you want to ' + $(this).attr('title') + '?'); 
	    });
	    //---------------------------------------------------------------------------//
	    
	    //Date picker
	    //---------------------------------------------------------------------------//
	    $("dd.date input").datepicker();
	    //---------------------------------------------------------------------------//
	});
    </script>