<?php 
echo '

<html>
<body>
<p style="font-size:12pt;font-family:Open Sans;color:#4B4E42;">
Gentile '.$order["name"].',<br/>
con riferimento al tuo recente ordine #'.$order["order_no"].', ti inviamo i link 
per scaricare i contenuti multimediali acquistati.</p>

<ul style="font-size:12pt;font-family:Open Sans;color:#4B4E42;">
'.$links.'
</ul>

<p style="font-size:12pt;font-family:Open Sans;color:#4B4E42;">Cordiali saluti.<br />
</p>
</body>
</html>

'
?>