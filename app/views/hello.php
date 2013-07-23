<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test</title>
	<script src="../../assets/script/jquery-2.0.3.js" type="text/javascript"></script>
</head>
<body>
    <div id="testA">
	</div>
	<div id="testB">
		
	</div>
	<div id="js_test">
	</div>
	<div id="div_test">
	</div>
	</body>
</html>



<script>
	jQuery(document).ready(function() {
		$.post('../Temp/'+ <?php echo $tid?>, function(data){
			document.getElementById('div_test').innerHTML=data;
		});
		
		<?php 
		$str = "";
		$str2 = "";
		$count = 0;
		foreach($mids as $mid){
			$str .= "$.post('".$mid->path."/0', function(data){";
			$str.="	\nvar script_".$count." = document.createElement('script');";
			$str.="	\nscript_".$count.".type = 'text/javascript';";
			$str.="	\nscript_".$count.".text = data;";
			$str.="	\n$('body').append(script_".$count.");";
			$str.="\n});";
			
			$str2 .= "\n$.post('".$mid->path."/1', function(data){";
			$str2 .= "\ndocument.getElementById('div_".$count."').innerHTML=data;";
			$str2 .= "\n});";

		}
		echo $str;
		echo $str2;
		?>
	});
</script>

