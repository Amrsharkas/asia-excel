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
    <h3>Welcome {{$candidate->name}} to our volunteering program</h3>
    
    	<p>Thank you for applying to volunteer as an Islamic Practices instructor at Nour Academy to teach non-Arabic speaking Muslims worldwide. May Allah bless you and reward you for your intentions.</p>
        <p>Please visit the following link to start your enrollment in the Islamic practices instructor volunteer program. </p>
    	<p><a style="color:#494949; font-weight:bold;" href="http://volunteers.nouracademy.com">Click here</a></p>
        <p>and login with the following credintials</p>
        <p><strong>Email</strong>: {{$candidate->email}}</p>
        <p><strong>Password</strong>: {{$candidate->dec_password}}</p>
        
    
    
    
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