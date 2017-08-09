<?php

//connect to the DB
$resDB = mysqli_connect("localhost", "***", "***");
mysqli_select_db($resDB, "***");

$error_msg="";

if(strlen($_GET['key'])==32 and preg_match("/[0-9a-f]{32}/",$_GET['key'])){
	//check the DB for the key
	$resCheck = mysqli_query($resDB, "SELECT * FROM downloads WHERE downloadkey = '".mysqli_real_escape_string($resDB,$_GET['key'])."' LIMIT 1");
	$arrCheck = mysqli_fetch_assoc($resCheck);
	
	if($arrCheck['expires']<time()){
		$error_msg = "il link non &egrave; pi&ugrave valido.";
	}
	
	if(empty($arrCheck['filename'])){
		$error_msg = "il file richiesto non &egrave; disponibile.";
	}
	
	if(empty($arrCheck)){
		$error_msg = "la chiave fornita non &egrave valida.";
	}

	if($arrCheck['downloads']){
		$error_msg = "il file &egrave; gi&agrave stato prelevato.";
	}
				
}
else {
	$error_msg = "la chiave fornita &egrave; stata manomessa.";
}

?>


<html>
 <script>
 function start(btn) {
   var property = document.getElementById(btn);
   property.style.backgroundColor = "#ABAEA2";
   property.style.border = "1px solid #ABAEA2";
   property.disabled = true;
   window.location = "dl-start.php?key=<?php echo $_GET['key']; ?>>";
  }
  </script>
 
 <body>
  <p><br /><h1 style='font-size:18pt;font-family:"Open Sans";color:#4B4E42;'>Download contenuti multimediali</h1></p>
  
  <p style='font-size:12pt;font-family:"Open Sans";color:#4B4E42;'> <br />
  Da questa pagina è possibile prelevare i contenuti multimediali acquistati.<br /><br />
	  
  &Egrave consentito un singolo download per ogni contenuto, pertanto &egrave necessario assicurarsi
  di poter scaricare l'intero contenuto prima di avviare il download.<br /><br />
	  
  Il contenuto multimediale richiesto viene fornito sotto forma di un unico file compresso (.zip)
  contenente una raccolta di file audio (.mp3).<br /><br />

  <?php

if ($error_msg) {
	echo "
  Cliccando sul pulsante sotto verr&agrave avviato il download del contenuto richiesto. <br /><br /></p>
  
  <button type='submit' id='button' onclick=start('button') style='height:50px;
  font-size:18px; min-width:230px; background:#1979c3; border:1px solid #1979c3;
  color:#fff; cursor:pointer; display:inline-block; font-family:Open Sans;
  font-weight:600; padding:7px 15px; font-size:1.4rem; box-sizing:border-box;
  vertical-align:middle; border-radius:3px;'> Download </button>
";
}
else {

	echo "</p><p style='font-size:12pt;font-family:Open Sans;color:#FF0000;'>
	<b>Attenzione! Si &egrave; verificato un errore: ".$error_msg."</b>";
	
}
	?>
  
 </body>
</html> 