<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body style="border:2px solid #494949;  margin:7px;">
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center" valign="middle">
    @include('emails.header')</td>
  </tr>
  <tr>
    <td height="2" align="center" valign="top" bgcolor="#494949"><div></div></td>
  </tr>
  <tr>
    <td align="center" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td align="center" valign="top" style="padding:0 20px;">
    <h3>Name: {{$candidate->name}}</h3>
    @if($candidate->user_phase == "testEvaluation")
    	<p>This candidate has finished the test and is waiting for your evaluation</p>
    	
    @endif
    @if($candidate->user_phase == "phoneInterview")
    	<p>This candidate has passed the test and is currently waiting for you to contact them for the interview phase</p>
    @endif
    @if($candidate->user_phase == "pilotTest" && $candidate->pilot_ready == 1)
   	  <p>This candidate is ready to be contacted for pilot testing </p>
    	
    @endif
    
    
    </td>
  </tr>
  <tr>
    <td align="center" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td height="2" align="center" valign="top" bgcolor="#494949"><div></div></td>
  </tr>
  <tr>
    <td align="center" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td align="center">
      @include('emails.footer')</td>
  </tr>
</table>
</body>
</html>