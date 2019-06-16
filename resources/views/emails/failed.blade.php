<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>

</head>

<body style="border:2px solid #494949;  margin:7px;">
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center" valign="middle">@include('emails.header')</td>
  </tr>
  <tr>
    <td height="2" align="center" valign="top" bgcolor="#494949"><div></div></td>
  </tr>
  <tr>
    <td align="center" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td align="center" valign="top" style="padding:0 20px;">
    <h3>Sorry {{$candidate->name}}</h3>
    <?php 
	$message = "Unfortunately you were unable to become an Islamic Practices instructor at Nour Academy since you did not pass the ";
		       	 if($candidate->user_phase == "testing" || $candidate->user_phase == "testEvaluation"){
		       	 	$message .= "Islamic knowledge screening phase" ;
		       	 }
		       	 if($candidate->user_phase == "phoneInterview"){
		       	 	$message .= "phone interview screening phase" ;
		       	 }
		       	 if($candidate->user_phase == "pilotTest"){
		       	 	$message .= "pilot testing phase" ;
		       	 }
				 ?>
    <p><?php echo $message;?></p>
    
    </td>
  </tr>
  <tr>
    <td align="center" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td height="2" align="center" valign="top" bgcolor="#494949"><div></div></td>
  </tr>
  <tr>
    <td align="center">@include('emails.footer')</td>
  </tr>
</table>
</body>
</html>