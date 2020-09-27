<!DOCTYPE html>
<html>
<head>
	<title></title>
	 <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="utf-8" http-equiv="encoding">

    <script type="text/javascript" src="assets/themes/FlatSIS/jquery.min.js"></script>
<script type="text/javascript" src="assets/themes/FlatSIS/bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js"></script>
</head>
<body>

	<script type="text/javascript" src="lib/jquery.min.js"></script>
<script type="text/javascript" src="lib/bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js"></script>

<script type="text/javascript">
    $( document ).ready(function() {
       jQuery.datetimepicker.setLocale('en');

       jQuery('#datetimepicker').datetimepicker({
             i18n:{
              en:{
               months:[
                'January','February','March','April',
                'May','June','July','August',
                'September','October','November','December',
               ],
               dayOfWeek:[
                "Su.", "Mo", "Tu", "We", 
                "Th", "Fr", "Sa.",
               ]
              }
             },
             timepicker:false,
             format:'d.m.Y'
            });
    });
</script>

</body>
</html>