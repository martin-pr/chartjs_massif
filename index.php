<!DOCTYPE html>
<html>
<head>
	<title>Massif visualiser</title>
	<style type="text/css">
		body {
			font-family: sans-serif;
		}
	</style>
</head>
<body>
	<h1>Massif visualiser, the web version</h1>

	<p>This is a web version of massif visualiser. Just upload the massif output file and enjoy! :)</p>

	<form action="graph.php" method="post" enctype="multipart/form-data">
		Upload a massif log:
		<input type="file" name="massif_log" id="massif_log">
    	<input type="submit" value="Upload" name="submit">
	</form>
</body>
</html>
